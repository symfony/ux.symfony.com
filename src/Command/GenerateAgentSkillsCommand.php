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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:generate-agent-skills', description: 'Generates the Agent Skills Discovery index from GitHub skill repositories')]
class GenerateAgentSkillsCommand
{
    private const SCHEMA_URL = 'https://schemas.agentskills.io/discovery/0.2.0/schema.json';

    /**
     * Each source defines a GitHub repository and the skill paths to pull from it.
     * The skill path is relative to the repository root and must contain a SKILL.md file.
     * The skill name in the index is derived from the last segment of the path.
     *
     * @var list<array{repository: string, skills: list<string>}>
     */
    private const SOURCES = [
        [
            'repository' => 'smnandre/symfony-ux-skills',
            'skills' => [
                'skills/live-component',
                'skills/stimulus',
                'skills/symfony-ux',
                'skills/turbo',
                'skills/twig-component',
                'skills/ux-icons',
                'skills/ux-map',
            ],
        ],
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $fs = new Filesystem();
        $outputDir = $this->projectDir.'/public/.well-known/agent-skills';

        $skills = [];

        foreach (self::SOURCES as $source) {
            $repository = $source['repository'];

            // Resolve latest commit SHA on main
            $io->section($repository);
            $commitSha = $this->httpClient->request('GET', \sprintf('https://api.github.com/repos/%s/commits/main', $repository), [
                'headers' => ['Accept' => 'application/vnd.github.v3+json'],
            ])->toArray()['sha'];
            $io->writeln('  Commit: '.$commitSha);

            $rawBaseUrl = \sprintf('https://raw.githubusercontent.com/%s/%s', $repository, $commitSha);

            foreach ($source['skills'] as $skillPath) {
                $skillName = basename($skillPath);
                $skillUrl = $rawBaseUrl.'/'.$skillPath.'/SKILL.md';
                $io->writeln('  '.$skillName.'...');

                $content = $this->httpClient->request('GET', $skillUrl)->getContent();
                $description = $this->extractDescription($content);

                $skills[] = [
                    'name' => $skillName,
                    'type' => 'skill-md',
                    'description' => $description,
                    'url' => $skillUrl,
                    'digest' => 'sha256:'.hash('sha256', $content),
                ];

                if (\strlen($description) > 120) {
                    $io->writeln('    -> '.mb_substr($description, 0, 120).'...');
                } else {
                    $io->writeln('    -> '.$description);
                }
            }
        }

        // All fetches succeeded — write atomically
        $io->section('Writing index');
        $fs->remove($outputDir);
        $fs->mkdir($outputDir);

        $index = [
            '$schema' => self::SCHEMA_URL,
            'skills' => $skills,
        ];

        $indexPath = $outputDir.'/index.json';
        $fs->dumpFile($indexPath, json_encode($index, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES)."\n");

        $io->success(\sprintf('Generated %d skills from %d sources at %s', \count($skills), \count(self::SOURCES), $indexPath));

        return Command::SUCCESS;
    }

    private function extractDescription(string $content): string
    {
        if (!preg_match('/^---\s*\n(.+?)\n---/s', $content, $matches)) {
            return '';
        }

        $frontmatter = $matches[1];
        if (preg_match('/^description:\s*(.+)$/m', $frontmatter, $descMatch)) {
            return trim($descMatch[1]);
        }

        return '';
    }
}
