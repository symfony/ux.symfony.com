<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Entity\Chat;
use App\Repository\ChatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\Persistence\persist;

class TurboChatTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    /** @var list<Update> */
    private array $published = [];

    public function testTurboPageDoesNotNeedTheChatTable(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $chatMetadata = [$entityManager->getClassMetadata(Chat::class)];

        $schemaTool->dropSchema($chatMetadata);

        try {
            $this->browser()
                ->visit('/turbo')
                ->assertSuccessful()
                ->assertElementCount('turbo-frame#turbo-chat[src="/turbo/chat"]', 1)
                ->assertElementCount('turbo-frame#turbo-chat-count[src="/turbo/chat"]', 1)
                ->assertElementCount('a[href="#turbo-streams"][aria-label]', 1);
        } finally {
            $schemaTool->createSchema($chatMetadata);
        }
    }

    public function testChatFrameRendersMessagesAndCount(): void
    {
        persist(Chat::class, ['username' => 'user_123', 'message' => 'Hola!']);

        $this->browser()
            ->visit('/turbo/chat')
            ->assertSuccessful()
            ->assertSeeIn('turbo-frame#turbo-chat #chat-box', 'user_123:')
            ->assertSeeIn('turbo-frame#turbo-chat #chat-box', 'Hola!')
            ->assertSeeIn('turbo-frame#turbo-chat-count #chat-message-count', '1');
    }

    public function testSenderStreamUpdatesTheChat(): void
    {
        $browser = $this->browser();
        $this->mockHub();

        $browser
            ->post('/turbo/chat', [
                'body' => ['chat_message' => '3'],
                'headers' => ['Accept' => 'text/vnd.turbo-stream.html'],
            ])
            ->assertSuccessful()
            ->assertContains('<turbo-stream action="update" target="chat-form">')
            ->assertContains('<turbo-stream action="update" target="chat-box">')
            ->assertContains('I love pizza!')
            ->assertContains('<turbo-stream action="replace" target="chat-message-count">');

        $this->assertSame(1, self::getContainer()->get(ChatRepository::class)->count([]));
        $this->assertCount(1, $this->published);
        $this->assertSame(['chat'], $this->published[0]->getTopics());
    }

    public function testUnknownMessageIsIgnored(): void
    {
        $browser = $this->browser();
        $this->mockHub();

        $browser
            ->post('/turbo/chat', ['body' => ['chat_message' => '']])
            ->assertStatus(204);

        $this->assertSame(0, self::getContainer()->get(ChatRepository::class)->count([]));
        $this->assertCount(0, $this->published);
    }

    /**
     * Replaces the Mercure hub (HubInterface aliases the traceable one), so tests never
     * publish to the hub configured in .env. Call it after browser(), which boots its own kernel.
     */
    private function mockHub(): void
    {
        $hub = new MockHub(
            'https://mercure.test/.well-known/mercure',
            new StaticTokenProvider('token'),
            function (Update $update): string {
                $this->published[] = $update;

                return 'id';
            },
        );

        self::getContainer()->set('mercure.hub.default.traceable', $hub);
    }
}
