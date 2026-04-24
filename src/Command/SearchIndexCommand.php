<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Model\LiveDemo;
use App\Model\UxPackage;
use App\Service\LiveDemoRepository;
use App\Service\Search\SearchClient;
use App\Service\Toolkit\ToolkitService;
use App\Service\UxPackageRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Toolkit\Recipe\Recipe;

#[AsCommand(name: 'app:search:index', description: 'Indexes packages, demos and toolkit recipes in Meilisearch')]
final class SearchIndexCommand extends Command
{
    private const SETTINGS = [
        'searchableAttributes' => ['humanName', 'name', 'tagline', 'description', 'kitHuman', 'composer'],
        'filterableAttributes' => ['type'],
        'stopWords' => ['symfony', 'ux'],
        'synonyms' => [
            'lc' => ['live component'],
            'live component' => ['lc'],
            'tc' => ['twig component'],
            'twig component' => ['tc'],
        ],
        'typoTolerance' => ['minWordSizeForTypos' => ['oneTypo' => 4, 'twoTypos' => 8]],
    ];

    public function __construct(
        private readonly SearchClient $searchClient,
        private readonly UxPackageRepository $packageRepository,
        private readonly LiveDemoRepository $demoRepository,
        private readonly ToolkitService $toolkitService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $documents = [
            ...array_map($this->packageToDocument(...), $this->packageRepository->findAll()),
            ...array_map($this->demoToDocument(...), $this->demoRepository->findAll()),
            ...$this->recipeDocuments(),
        ];

        $io->writeln(\sprintf('Indexing <info>%d</info> documents to <info>%s</info>…', \count($documents), SearchClient::INDEX));

        $client = $this->searchClient->admin();

        // Create index if missing (idempotent).
        $task = $client->createIndex(SearchClient::INDEX, ['primaryKey' => 'id']);
        $client->waitForTask($task['taskUid']);

        $index = $client->index(SearchClient::INDEX);

        $index->waitForTask($index->updateSettings(self::SETTINGS)['taskUid']);
        $index->waitForTask($index->deleteAllDocuments()['taskUid']);
        $index->waitForTask($index->addDocuments($documents, 'id')['taskUid']);

        $io->success('Search index updated.');

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function packageToDocument(UxPackage $package): array
    {
        return [
            'id' => 'package_'.$package->getName(),
            'type' => 'package',
            'name' => $package->getName(),
            'humanName' => $package->getHumanName(),
            'tagline' => $this->cleanText($package->getTagLine()),
            'description' => $this->cleanText($package->getDescription()),
            'composer' => $package->getComposerName(),
            'url' => $this->urlGenerator->generate($package->getRoute()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function demoToDocument(LiveDemo $demo): array
    {
        return [
            'id' => 'demo_'.$demo->getIdentifier(),
            'type' => 'demo',
            'name' => $demo->getName(),
            'description' => $this->cleanText($demo->getDescription()),
            'url' => $this->urlGenerator->generate($demo->getRoute()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recipeDocuments(): array
    {
        $documents = [];
        foreach ($this->toolkitService->getKits() as $kitId => $kit) {
            foreach ($kit->getRecipes() as $recipe) {
                $documents[] = $this->recipeToDocument($kitId, $kit->manifest->name, $recipe);
            }
        }

        return $documents;
    }

    /**
     * @return array<string, mixed>
     */
    private function recipeToDocument(string $kitId, string $kitHuman, Recipe $recipe): array
    {
        return [
            'id' => \sprintf('toolkit_%s_%s', $kitId, $recipe->name),
            'type' => 'recipe',
            'kit' => $kitId,
            'kitHuman' => $kitHuman,
            'name' => $recipe->manifest->name,
            'description' => $this->cleanText($recipe->manifest->description),
            'url' => $this->urlGenerator->generate('app_toolkit_component', [
                'kitId' => $kitId,
                'componentName' => $recipe->name,
            ]),
        ];
    }

    /**
     * Strip HTML tags, decode entities, remove markdown code markers and collapse whitespace
     * so Meilisearch `_formatted` output is safe to drop into `innerHTML`.
     */
    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, \ENT_QUOTES | \ENT_HTML5);
        $text = strip_tags($text);
        $text = str_replace('`', '', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
