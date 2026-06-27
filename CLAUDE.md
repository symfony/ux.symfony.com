# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`ux.symfony.com` — the official Symfony UX showcase site. It hosts demos, docs links, and the changelog for every official `symfony/ux-*` package, plus a CSS kit gallery (Toolkit), a Cookbook, and a LiveComponent memory game.

## Common commands

Local dev (Symfony CLI):

- `symfony serve` — start web server on port `9044`, plus workers from `.symfony.local.yaml` (Sass watch, Tailwind watch for `app-tailwind.css`, `toolkit-shadcn.css`)
- `bin/console tailwind:build assets/styles/toolkit-flowbite-4.css` — third Tailwind entrypoint is built by composer hooks, not the watcher
- `docker compose up -d` — Mercure hub for Turbo Streams (required by some demos)

Quality gates (mirror the GitHub workflows):

- PHPStan: `symfony php vendor/bin/phpstan analyse` (level 6, runs against `bin/`, `config/`, `public/`, `src/`, `tests/`)
- PHPUnit: `symfony php vendor/bin/simple-phpunit` — bridge wrapper required by `symfony/phpunit-bridge`
    - CI uses raw `vendor/bin/phpunit` after `bin/console sass:build`; locally prefer `simple-phpunit` so the bridge resolves the PHP 8.5 / PHPUnit 9.6 combination
- Single test: `symfony php vendor/bin/simple-phpunit --filter <TestClass>::<testMethod>`
- PHPStan locally needs a warm cache: `APP_ENV=test bin/console cache:warmup` before first run
- PHP-CS-Fixer (Symfony ruleset + Symfony copyright header): `vendor/bin/php-cs-fixer fix`
- Twig-CS-Fixer: `vendor/bin/twig-cs-fixer lint templates`

PHP / asset notes:

- PHP `~8.5.0` is mandatory (see `composer.json`); CI pins `8.5`
- Symfony components are pinned to `8.0.*`; UX packages run on `3.x-dev` (i.e. UX 3.x against Symfony 8.x)
- After `composer install/update`, the `post-*-cmd` scripts auto-run `tailwind:build` for all three CSS entrypoints — don't skip them or the demos break
- Frontend uses AssetMapper + Stimulus (no Node bundler). Stimulus controllers in `assets/controllers/` are auto-registered; UX controllers come from `assets/controllers.json`

## Architecture

### Package showcase loop (the core domain)

The "list of UX packages" is **not in the database**. It's hardcoded in `src/Service/UxPackageRepository.php` as an array of `App\Model\UxPackage` value objects, each with a slug, route name, gradient, tagline, and SVG file. To add or edit a package on the site, edit that array. The slug is the link between three places:

1. `src/Service/UxPackageRepository.php` — registers the package, owns metadata
2. `src/Controller/UxPackage/<Name>Controller.php` — renders the demo page (one controller per package), route name matches `app_<slug>` in the repo
3. `templates/ux_packages/<slug>.html.twig` — the page body, embedded in `package.html.twig`

`UxPackagesController` provides the index/search; individual demos live in their dedicated controllers so each can wire in its own Twig Components and Live Components.

### Other content domains (filesystem-driven, not Doctrine)

- **Cookbook** — markdown files under `cookbook/`, parsed by `src/Service/CookbookFactory.php` → `CookbookRepository`, rendered via League CommonMark + Tempest Highlight
- **Live Demos** (`LiveDemoRepository`) — list of standalone live-component demos under `src/Controller/Demo/`
- **Changelog** — fetched/aggregated by `src/Service/Changelog/ChangelogProvider.php` (GitHub release data for each UX package)
- **Toolkit kits** — shadcn and Flowbite-4 component recipes, loaded by `src/Service/Toolkit/ToolkitService.php` from the `symfony/ux-toolkit` package and rendered through `templates/toolkit/`

Doctrine ORM is wired up (DBAL + migrations exist) but most user-facing content stays in-code, on disk, or in session — Doctrine is mainly there for the demos that need persistence.

### LiveMemory game

Self-contained module under `src/LiveMemory/` — a card-matching game built to showcase Live Components.

- `Game`, `GameCards`, `GameEngine`, `GameLevels` — domain
- `GameStorageInterface` + `SessionGameStorage` — state lives in the HTTP session
- `GameHydrationExtension` — Live Component hydration for the `Game` value object
- `GameFactory` — entry point used by the `Demo\LiveMemoryController`
- UI components in `src/LiveMemory/Component/` (Twig/Live Components)

This is the single most complex demo on the site; treat it as the reference for non-trivial Live Component patterns.

### Twig / Live Components conventions (enforced by PHPStan)

`phpstan.dist.neon` enables the `kocal/phpstan-symfony-ux` ruleset. Rules that bite during edits:

- Twig Component classes **must be `final`** and **must not end with `Component`** (use e.g. `Alert`, not `AlertComponent`)
- Public properties on Twig Components must be `camelCase`; `class` and `attributes` are forbidden as property names
- Live Action / Live Listener methods must be `public`; `LivePropHydration*` and `LivePropModifier` methods must follow specific signatures
- `PreMount` / `PostMount` signatures are validated

If PHPStan complains about a component, check the rule list in `phpstan.dist.neon` before guessing.

### Tailwind: three independent stylesheets

There are **three** Tailwind builds, each with its own input file and watcher:

- `assets/styles/app-tailwind.css` — main site
- `assets/styles/toolkit-shadcn.css` — the shadcn kit gallery
- `assets/styles/toolkit-flowbite-4.css` — the Flowbite-4 kit gallery

`composer install/update` runs all three via the `tailwind:build` script. `.symfony.local.yaml` watches only the first two — if you touch Flowbite styles locally, run the build manually.

### React / Vue islands

`assets/react/` and `assets/vue/` each carry their own `src/` + `dist/` pair (no bundler, prebuilt artifacts checked in). The `symfony/ux-react` and `symfony/ux-vue` bundles mount these as islands.

## Testing

- Functional tests under `tests/Functional/` use **Zenstruck Browser** (registered as a PHPUnit extension in `phpunit.xml.dist`); `tests/Unit/` covers `Model/` and `Util/`
- Foundry is available (dev/test bundles) but rarely needed since most content isn't ORM-backed
- `tests/baseline-ignore` silences deprecation messages from upstream; check before adding new ignores
- `convertDeprecationsToExceptions="false"` is intentional — UX packages live on `3.x-dev` and surface upstream deprecations

## CI

Three GitHub workflows; matching them locally is the bar before pushing:

- `tests.yaml` — `composer install`, `bin/console sass:build`, `vendor/bin/phpunit`
- `phpstan.yaml` — `cache:warmup` (env=test), then `vendor/bin/phpstan analyse`
- `fabbot.yaml` — Symfony's bot checks license headers on changed PHP files (the `.php-cs-fixer.dist.php` header is what it expects)

## Code style

- PSR-4 under `App\` (`src/`) and `App\Tests\` (`tests/`)
- PHP-CS-Fixer config: `@Symfony` + `@Symfony:risky` + `@PHP8x1Migration` + `@PHPUnit9x1Migration:risky`, with the Symfony copyright header on every PHP file
- Twig + HTML + PHP: 4-space indent, LF line endings, UTF-8 (see `.editorconfig`)
- New PHP files must start with the Symfony license header (PHP-CS-Fixer rewrites missing ones; Fabbot will reject PRs without it)
