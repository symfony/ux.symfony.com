<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Browser;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Playwright\Testing\PlaywrightTestCase;

#[Group('e2e')]
#[CoversNothing]
final class UploadDemosTest extends PlaywrightTestCase
{
    private const PHONE_WIDTH = 390;

    private string $baseUrl;

    protected function setUp(): void
    {
        $baseUrl = $_SERVER['PLAYWRIGHT_BASE_URL'] ?? getenv('PLAYWRIGHT_BASE_URL');
        if (!\is_string($baseUrl) || '' === $baseUrl) {
            self::markTestSkipped('Set PLAYWRIGHT_BASE_URL to run browser tests.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        if (isset($this->context)) {
            parent::tearDown();
        }
    }

    public function testExplanationsFitAPhoneScreen(): void
    {
        $this->page->setViewportSize(self::PHONE_WIDTH, 844);
        $this->page->goto($this->baseUrl.'/demos/upload/keep-files');

        // A clipped ancestor hides the overflow from the page width: measure the blocks themselves
        $rightEdge = $this->page->evaluate(<<<'JS'
            () => Math.max(...[...document.querySelectorAll('.code-description, .code-description :is(h2, p), .Terminal')]
                .map((element) => Math.round(element.getBoundingClientRect().right)))
            JS);

        self::assertLessThanOrEqual(self::PHONE_WIDTH, $rightEdge, 'An explanation or its code runs past the screen.');
    }

    public function testRemovingTheAvatarFromTheFieldClearsTheComponent(): void
    {
        // The sample button is a lazy Stimulus controller: it answers once the network is quiet
        $this->page->goto($this->baseUrl.'/demos/upload/live-avatar', ['waitUntil' => 'networkidle']);
        $component = $this->page->locator('.UploadAvatar');
        $received = $component->locator('.UploadAvatar_status');

        $empty = $component->locator('.UploadAvatar_picture');
        $this->expect($empty)->toBeVisible();

        $this->page->locator('.LiveAvatar [data-controller="upload-samples"]')->click();
        $this->expect($received)->toContainText('Received by the component');
        // The avatar is the preview the field drew from the selected file: a blob address, not a file from the server
        $preview = $component->locator('[data-ux-upload-preview]');
        $this->expect($preview)->toBeVisible();
        self::assertStringStartsWith('blob:', (string) $preview->getAttribute('src'));
        self::assertGreaterThanOrEqual(120, $preview->evaluate('(element) => element.getBoundingClientRect().width'), 'The preview is the avatar, not a thumbnail.');
        $this->expect($empty)->toBeHidden();

        // One button, in the field: the component has none of its own to fall out of step with
        $remove = $component->getByRole('button', ['name' => 'Remove']);
        $this->expect($remove)->toHaveCount(1);
        $remove->click();

        $this->expect($received)->toContainText('No avatar yet');
        $this->expect($empty)->toBeVisible();
        $this->expect($component->locator('[data-ux-upload-item]'))->toHaveCount(0);
    }
}
