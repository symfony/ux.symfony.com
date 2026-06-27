# `symfony/ux-editor` (Plan A — Core + Quill) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the new `symfony/ux-editor` Composer package — core (content VO, bridge contract, FormType, sanitizer pipeline, Doctrine type, render component, abstract Stimulus base) plus a single working bridge (Quill) — to the point where `EditorType::class` with `'bridge' => 'quill'` accepts HTML input from a Quill instance, sanitizes it via `symfony/html-sanitizer`, persists it as JSON `{raw, format}`, and renders it back through `<twig:ux:editor:render>`.

**Architecture:** Single-package multi-bridge design mirroring `symfony/ux-map`. Polymorphic `EditorContent { raw, format }` value object travels end-to-end (form → sanitizer → Doctrine → render component). `EditorBridgeInterface` registry resolves bridge id to PHP bridge + Stimulus controller name + sanitizer default. Abstract TS Stimulus controller owns hidden-input sync; per-bridge controller owns engine lifecycle. Plan A ships Quill as the reference HTML bridge. Plans B/C/D add EditorJS, TipTap, vvvebjs against the same contract.

**Tech Stack:** PHP 8.2+, Symfony 6.4 LTS / 7.x / `dev-main`. `symfony/form`, `symfony/options-resolver`, `symfony/serializer`, `symfony/html-sanitizer`, `symfony/stimulus-bundle`, `symfony/twig-component`, `symfony/ux-live-component`, `doctrine/dbal`. TypeScript + Stimulus 3, Vitest, Playwright, peer-dep `quill@^2`.

**Spec reference:** `docs/superpowers/specs/2026-05-17-ux-editor-base-design.md`.

---

## File structure (Plan A end state)

```
ux-editor/                                # package root
├── composer.json
├── phpunit.dist.xml
├── package.json
├── tsconfig.json
├── vitest.config.mjs
├── playwright.config.ts
├── README.md
├── CHANGELOG.md
├── LICENSE
├── .github/workflows/test.yml
├── src/
│   ├── UXEditorBundle.php
│   ├── DependencyInjection/
│   │   ├── Configuration.php
│   │   └── UXEditorExtension.php
│   ├── Resources/config/services.php
│   ├── Content/
│   │   ├── EditorContent.php
│   │   ├── EditorContentNormalizer.php
│   │   └── DataTransformer.php
│   ├── Bridge/
│   │   ├── EditorBridgeInterface.php
│   │   ├── EditorBridgeRegistry.php
│   │   └── Quill/
│   │       └── QuillBridge.php
│   ├── Form/
│   │   └── EditorType.php
│   ├── Sanitizer/
│   │   ├── SanitizerDispatcher.php
│   │   └── HtmlSanitizerProfileLoader.php
│   ├── Doctrine/
│   │   └── EditorContentType.php
│   ├── Renderer/
│   │   ├── EditorRendererInterface.php
│   │   ├── EditorRendererRegistry.php
│   │   ├── HtmlRenderer.php
│   │   └── MarkdownRenderer.php
│   ├── Twig/
│   │   └── Components/Render.php
│   ├── Live/
│   │   └── EditorContentHydrationExtension.php
│   └── Exception/
│       ├── UXEditorExceptionInterface.php
│       ├── UnknownBridgeException.php
│       ├── SanitizerProfileNotFoundException.php
│       └── EditorRenderException.php
├── templates/
│   └── components/ux/editor/Render.html.twig
├── assets/
│   ├── controllers.json
│   ├── src/
│   │   ├── index.ts
│   │   ├── abstract_editor_controller.ts
│   │   └── quill_controller.ts
│   ├── test/
│   │   ├── unit/
│   │   │   ├── abstract_editor_controller.test.ts
│   │   │   └── quill_controller.test.ts
│   │   └── browser/
│   │       ├── fixtures/quill.html
│   │       └── quill.test.ts
│   └── dist/                              # generated, gitignored
└── tests/
    ├── bootstrap.php
    ├── Functional/
    │   ├── Kernel.php
    │   ├── EditorTypeTest.php
    │   ├── RenderComponentTest.php
    │   ├── LiveHydrationTest.php
    │   └── EndToEndQuillTest.php
    └── Unit/
        ├── Content/
        │   ├── EditorContentTest.php
        │   ├── EditorContentNormalizerTest.php
        │   └── DataTransformerTest.php
        ├── Bridge/
        │   ├── EditorBridgeRegistryTest.php
        │   └── QuillBridgeTest.php
        ├── Sanitizer/
        │   ├── HtmlSanitizerProfileLoaderTest.php
        │   └── SanitizerDispatcherTest.php
        ├── Doctrine/
        │   └── EditorContentTypeTest.php
        ├── Renderer/
        │   ├── EditorRendererRegistryTest.php
        │   ├── HtmlRendererTest.php
        │   └── MarkdownRendererTest.php
        └── DependencyInjection/
            └── UXEditorExtensionTest.php
```

The split: each file owns one responsibility. The Quill bridge directory (`src/Bridge/Quill/`) only contains `QuillBridge.php`; its Stimulus controller lives under `assets/src/`. Future bridges add sibling directories under `src/Bridge/` and sibling controllers under `assets/src/`.

---

## Conventions used in every task

- Run commands from the package root unless stated otherwise.
- PHP tests: `vendor/bin/phpunit --filter <FullyQualified::test>` for a single test, `vendor/bin/phpunit` for all.
- JS unit tests: `pnpm vitest run --reporter=verbose <path>`; browser tests: `pnpm playwright test <path>`.
- TDD order is non-negotiable: failing test → minimal implementation → passing test → commit.
- Commit messages use Conventional Commits prefixes (`feat:`, `test:`, `chore:`, `docs:`).
- Never `git add -A` or `git add .` — list files explicitly per the project's safety convention.
- Do **not** add `Co-Authored-By:` lines.

---

## Task 1: Package skeleton

**Files:**

- Create: `composer.json`
- Create: `src/UXEditorBundle.php`
- Create: `src/DependencyInjection/UXEditorExtension.php`
- Create: `src/Resources/config/services.php`
- Create: `phpunit.dist.xml`
- Create: `tests/bootstrap.php`
- Create: `package.json`
- Create: `tsconfig.json`
- Create: `vitest.config.mjs`
- Create: `playwright.config.ts`
- Create: `.gitignore`
- Create: `README.md` (one-paragraph stub)
- Create: `CHANGELOG.md` (with v1 unreleased header)
- Create: `LICENSE` (MIT, same as other UX packages)

- [ ] **Step 1: Create `composer.json`**

```json
{
    "name": "symfony/ux-editor",
    "type": "symfony-bundle",
    "description": "Symfony UX Editor: a polymorphic Symfony Form + Stimulus base for rich-text and block editors (Quill, EditorJS, TipTap, vvvebjs).",
    "keywords": ["symfony-ux", "stimulus", "editor", "wysiwyg", "quill", "editorjs", "tiptap"],
    "license": "MIT",
    "authors": [{ "name": "Symfony Community", "homepage": "https://symfony.com/contributors" }],
    "require": {
        "php": ">=8.2",
        "symfony/form": "^6.4|^7.0",
        "symfony/options-resolver": "^6.4|^7.0",
        "symfony/property-access": "^6.4|^7.0",
        "symfony/serializer": "^6.4|^7.0",
        "symfony/stimulus-bundle": "^2.18",
        "symfony/twig-component": "^2.18",
        "symfony/ux-live-component": "^2.18",
        "twig/twig": "^3.0"
    },
    "require-dev": {
        "doctrine/dbal": "^3.0|^4.0",
        "phpunit/phpunit": "^10.5|^11.0",
        "symfony/framework-bundle": "^6.4|^7.0",
        "symfony/html-sanitizer": "^6.4|^7.0",
        "symfony/phpunit-bridge": "^6.4|^7.0",
        "symfony/twig-bridge": "^6.4|^7.0",
        "league/commonmark": "^2.4",
        "zenstruck/browser": "^1.10"
    },
    "suggest": {
        "symfony/html-sanitizer": "Required for HTML-format bridges (Quill, TipTap).",
        "doctrine/dbal": "Required to use the `editor_content` Doctrine column type.",
        "league/commonmark": "Required for the Markdown renderer."
    },
    "autoload": { "psr-4": { "Symfony\\UX\\Editor\\": "src/" } },
    "autoload-dev": { "psr-4": { "Symfony\\UX\\Editor\\Tests\\": "tests/" } },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

- [ ] **Step 2: Create `src/UXEditorBundle.php`**

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\UX\Editor\DependencyInjection\UXEditorExtension;

final class UXEditorBundle extends Bundle
{
    public function getContainerExtension(): UXEditorExtension
    {
        return $this->extension ??= new UXEditorExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
```

- [ ] **Step 3: Create `src/DependencyInjection/UXEditorExtension.php` (stub — config tree added in Task 20)**

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class UXEditorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');
    }

    public function getAlias(): string
    {
        return 'ux_editor';
    }
}
```

- [ ] **Step 4: Create `src/Resources/config/services.php` (empty container — populated as services are added)**

```php
<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();
};
```

- [ ] **Step 5: Create `phpunit.dist.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         backupGlobals="false"
         colors="true"
         bootstrap="tests/bootstrap.php"
         cacheDirectory=".phpunit.cache"
         failOnRisky="true"
         failOnWarning="true">
    <php>
        <ini name="error_reporting" value="-1"/>
        <server name="KERNEL_CLASS" value="Symfony\UX\Editor\Tests\Functional\Kernel"/>
        <server name="APP_ENV" value="test" force="true"/>
        <server name="SHELL_VERBOSITY" value="-1"/>
    </php>
    <testsuites>
        <testsuite name="UX Editor">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 6: Create `tests/bootstrap.php`**

```php
<?php

require __DIR__.'/../vendor/autoload.php';
```

- [ ] **Step 7: Create `package.json`**

```json
{
    "name": "@symfony/ux-editor",
    "description": "Symfony UX Editor: polymorphic Stimulus base for rich-text and block editors.",
    "license": "MIT",
    "version": "0.1.0",
    "type": "module",
    "main": "dist/index.js",
    "types": "dist/index.d.ts",
    "files": ["dist/", "src/"],
    "peerDependencies": {
        "@hotwired/stimulus": "^3.0",
        "quill": "^2.0"
    },
    "peerDependenciesMeta": {
        "quill": { "optional": true }
    },
    "devDependencies": {
        "@hotwired/stimulus": "^3.2",
        "@playwright/test": "^1.45",
        "@types/node": "^20.11",
        "happy-dom": "^14.0",
        "quill": "^2.0",
        "typescript": "^5.4",
        "vitest": "^1.5"
    },
    "scripts": {
        "build": "tsc -p tsconfig.json",
        "test": "vitest run",
        "test:browser": "playwright test"
    }
}
```

- [ ] **Step 8: Create `tsconfig.json`**

```json
{
    "compilerOptions": {
        "target": "ES2022",
        "module": "ES2022",
        "moduleResolution": "bundler",
        "declaration": true,
        "declarationMap": true,
        "sourceMap": true,
        "outDir": "dist",
        "rootDir": "assets/src",
        "strict": true,
        "noUncheckedIndexedAccess": true,
        "esModuleInterop": true,
        "skipLibCheck": true
    },
    "include": ["assets/src/**/*.ts"]
}
```

- [ ] **Step 9: Create `vitest.config.mjs`**

```js
import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        include: ['assets/test/unit/**/*.test.ts'],
        environment: 'happy-dom',
    },
});
```

- [ ] **Step 10: Create `playwright.config.ts`**

```ts
import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: 'assets/test/browser',
    use: { headless: true },
});
```

- [ ] **Step 11: Create `.gitignore`**

```
/vendor/
/node_modules/
/dist/
/.phpunit.cache/
/.phpunit.result.cache
composer.lock
pnpm-lock.yaml
package-lock.json
```

- [ ] **Step 12: Create stub `README.md`, `CHANGELOG.md`, `LICENSE`**

`README.md`:

```markdown
# Symfony UX Editor

Polymorphic Symfony Form + Stimulus base for rich-text and block editors (Quill, EditorJS, TipTap, vvvebjs).

> Pre-release. See `docs/superpowers/specs/2026-05-17-ux-editor-base-design.md` for the design.
```

`CHANGELOG.md`:

```markdown
# CHANGELOG

## Unreleased

- Initial release: core (`EditorContent`, `EditorType`, sanitizer pipeline, Doctrine `editor_content`, `<twig:ux:editor:render>`, abstract Stimulus base) + Quill bridge.
```

`LICENSE`: copy the MIT text from any existing `symfony/ux-*` package's LICENSE file verbatim.

- [ ] **Step 13: Install deps**

```bash
composer install
pnpm install   # or npm install / yarn install per local convention
```

Expected: no errors. Composer reports installation of the dev deps; pnpm/npm prints peer-dep warnings for `quill` (acceptable — Quill is a peer dep).

- [ ] **Step 14: Smoke-run an empty PHPUnit suite**

```bash
vendor/bin/phpunit
```

Expected: `No tests executed!` (no failure). Confirms PHPUnit config is wired.

- [ ] **Step 15: Commit**

```bash
git add composer.json phpunit.dist.xml package.json tsconfig.json vitest.config.mjs playwright.config.ts .gitignore README.md CHANGELOG.md LICENSE src/UXEditorBundle.php src/DependencyInjection/UXEditorExtension.php src/Resources/config/services.php tests/bootstrap.php
git commit -m "chore: scaffold symfony/ux-editor package"
```

---

## Task 2: `EditorContent` value object

**Files:**

- Create: `src/Content/EditorContent.php`
- Test: `tests/Unit/Content/EditorContentTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Unit/Content/EditorContentTest.php`:

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\Content;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\EditorContent;

final class EditorContentTest extends TestCase
{
    public function testConstructAndExpose(): void
    {
        $content = new EditorContent('<p>hi</p>', 'html');
        $this->assertSame('<p>hi</p>', $content->raw);
        $this->assertSame('html', $content->format);
    }

    public function testStringableReturnsRaw(): void
    {
        $this->assertSame('hello', (string) new EditorContent('hello', 'markdown'));
    }

    public function testJsonSerializeShape(): void
    {
        $content = new EditorContent('{"blocks":[]}', 'json');
        $this->assertSame(
            ['raw' => '{"blocks":[]}', 'format' => 'json'],
            $content->jsonSerialize(),
        );
    }

    public function testWithRawReturnsNewInstance(): void
    {
        $original = new EditorContent('a', 'html');
        $next = $original->withRaw('b');
        $this->assertNotSame($original, $next);
        $this->assertSame('b', $next->raw);
        $this->assertSame('html', $next->format);
        $this->assertSame('a', $original->raw);
    }

    public function testEqualityChecksBothFields(): void
    {
        $a = new EditorContent('x', 'html');
        $this->assertTrue($a->equals(new EditorContent('x', 'html')));
        $this->assertFalse($a->equals(new EditorContent('x', 'json')));
        $this->assertFalse($a->equals(new EditorContent('y', 'html')));
    }

    public function testRejectsEmptyFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Format must not be empty.');
        new EditorContent('x', '');
    }
}
```

- [ ] **Step 2: Run test, verify it fails**

```bash
vendor/bin/phpunit --filter EditorContentTest
```

Expected: FAIL — `Class "Symfony\UX\Editor\Content\EditorContent" not found`.

- [ ] **Step 3: Write the value object**

`src/Content/EditorContent.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Content;

/**
 * Immutable polymorphic editor value. `raw` is the engine-native serialization,
 * `format` identifies the dialect (e.g. "html", "json", "markdown", "vvveb-html").
 */
final readonly class EditorContent implements \Stringable, \JsonSerializable
{
    public function __construct(
        public string $raw,
        public string $format,
    ) {
        if ('' === $format) {
            throw new \InvalidArgumentException('Format must not be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->raw;
    }

    public function jsonSerialize(): array
    {
        return ['raw' => $this->raw, 'format' => $this->format];
    }

    public function withRaw(string $raw): self
    {
        return new self($raw, $this->format);
    }

    public function equals(self $other): bool
    {
        return $this->raw === $other->raw && $this->format === $other->format;
    }
}
```

- [ ] **Step 4: Run test, verify it passes**

```bash
vendor/bin/phpunit --filter EditorContentTest
```

Expected: PASS (6/6).

- [ ] **Step 5: Commit**

```bash
git add src/Content/EditorContent.php tests/Unit/Content/EditorContentTest.php
git commit -m "feat: add EditorContent polymorphic value object"
```

---

## Task 3: `EditorContentNormalizer`

**Files:**

- Create: `src/Content/EditorContentNormalizer.php`
- Test: `tests/Unit/Content/EditorContentNormalizerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\Content;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Content\EditorContentNormalizer;

final class EditorContentNormalizerTest extends TestCase
{
    public function testNormalizeShape(): void
    {
        $normalizer = new EditorContentNormalizer();
        $this->assertSame(
            ['raw' => '<p>x</p>', 'format' => 'html'],
            $normalizer->normalize(new EditorContent('<p>x</p>', 'html')),
        );
    }

    public function testDenormalizeShape(): void
    {
        $normalizer = new EditorContentNormalizer();
        $content = $normalizer->denormalize(['raw' => 'x', 'format' => 'markdown'], EditorContent::class);
        $this->assertSame('x', $content->raw);
        $this->assertSame('markdown', $content->format);
    }

    public function testSupportsNormalizationOnlyForEditorContent(): void
    {
        $normalizer = new EditorContentNormalizer();
        $this->assertTrue($normalizer->supportsNormalization(new EditorContent('a', 'html')));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    public function testSupportsDenormalizationOnlyForEditorContent(): void
    {
        $normalizer = new EditorContentNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization([], EditorContent::class));
        $this->assertFalse($normalizer->supportsDenormalization([], \stdClass::class));
    }

    public function testDenormalizeRejectsMissingKeys(): void
    {
        $normalizer = new EditorContentNormalizer();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EditorContent payload must contain "raw" and "format" string fields.');
        $normalizer->denormalize(['raw' => 'x'], EditorContent::class);
    }
}
```

- [ ] **Step 2: Run, fail** (`vendor/bin/phpunit --filter EditorContentNormalizerTest` → class missing).

- [ ] **Step 3: Implement**

`src/Content/EditorContentNormalizer.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Content;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class EditorContentNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        \assert($object instanceof EditorContent);

        return $object->jsonSerialize();
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): EditorContent
    {
        if (!\is_array($data) || !\is_string($data['raw'] ?? null) || !\is_string($data['format'] ?? null)) {
            throw new \InvalidArgumentException('EditorContent payload must contain "raw" and "format" string fields.');
        }

        return new EditorContent($data['raw'], $data['format']);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof EditorContent;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return EditorContent::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [EditorContent::class => true];
    }
}
```

- [ ] **Step 4: Run, pass** — PASS (5/5).

- [ ] **Step 5: Commit**

```bash
git add src/Content/EditorContentNormalizer.php tests/Unit/Content/EditorContentNormalizerTest.php
git commit -m "feat: add EditorContentNormalizer (Symfony Serializer integration)"
```

---

## Task 4: `DataTransformer` (form string ↔ `EditorContent`)

**Files:**

- Create: `src/Content/DataTransformer.php`
- Test: `tests/Unit/Content/DataTransformerTest.php`

The transformer is bridge-aware via the bridge's format (constructor-injected). On submit, it decodes the hidden-input string into an `EditorContent`. For `html` and `markdown`, the raw string is taken as-is. For any other format (`json`, `vvveb-html`, custom), the input is treated as opaque text — the _value of raw_ is the input string verbatim. JSON validation happens here for `json` format: a malformed JSON payload throws a `TransformationFailedException`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\Content;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\UX\Editor\Content\DataTransformer;
use Symfony\UX\Editor\Content\EditorContent;

final class DataTransformerTest extends TestCase
{
    public function testTransformEditorContentToString(): void
    {
        $transformer = new DataTransformer('html');
        $this->assertSame('<p>x</p>', $transformer->transform(new EditorContent('<p>x</p>', 'html')));
    }

    public function testTransformNullToEmptyString(): void
    {
        $transformer = new DataTransformer('html');
        $this->assertSame('', $transformer->transform(null));
    }

    public function testReverseTransformHtmlString(): void
    {
        $transformer = new DataTransformer('html');
        $content = $transformer->reverseTransform('<p>x</p>');
        $this->assertInstanceOf(EditorContent::class, $content);
        $this->assertSame('<p>x</p>', $content->raw);
        $this->assertSame('html', $content->format);
    }

    public function testReverseTransformEmptyStringYieldsNull(): void
    {
        $transformer = new DataTransformer('html');
        $this->assertNull($transformer->reverseTransform(''));
    }

    public function testReverseTransformJsonValidates(): void
    {
        $transformer = new DataTransformer('json');
        $content = $transformer->reverseTransform('{"blocks":[]}');
        $this->assertSame('{"blocks":[]}', $content->raw);
        $this->assertSame('json', $content->format);
    }

    public function testReverseTransformJsonRejectsMalformed(): void
    {
        $transformer = new DataTransformer('json');
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('editor.invalid_payload');
        $transformer->reverseTransform('{not json');
    }

    public function testReverseTransformRejectsNonUtf8(): void
    {
        $transformer = new DataTransformer('html');
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('editor.invalid_encoding');
        $transformer->reverseTransform("\xc3\x28"); // invalid UTF-8 sequence
    }
}
```

- [ ] **Step 2: Run, fail** (class missing).

- [ ] **Step 3: Implement**

`src/Content/DataTransformer.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Content;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<EditorContent|null, string>
 */
final class DataTransformer implements DataTransformerInterface
{
    public function __construct(private readonly string $format)
    {
    }

    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof EditorContent) {
            throw new TransformationFailedException('Expected EditorContent or null.');
        }

        return $value->raw;
    }

    public function reverseTransform(mixed $value): ?EditorContent
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected string.');
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            throw new TransformationFailedException('editor.invalid_encoding');
        }

        if ('json' === $this->format) {
            try {
                json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new TransformationFailedException('editor.invalid_payload');
            }
        }

        return new EditorContent($value, $this->format);
    }
}
```

- [ ] **Step 4: Run, pass** — PASS (7/7).

- [ ] **Step 5: Commit**

```bash
git add src/Content/DataTransformer.php tests/Unit/Content/DataTransformerTest.php
git commit -m "feat: add DataTransformer for EditorContent (UTF-8 + JSON guards)"
```

---

## Task 5: Bridge contract — `EditorBridgeInterface` + `EditorBridgeRegistry`

**Files:**

- Create: `src/Bridge/EditorBridgeInterface.php`
- Create: `src/Bridge/EditorBridgeRegistry.php`
- Create: `src/Exception/UXEditorExceptionInterface.php`
- Create: `src/Exception/UnknownBridgeException.php`
- Test: `tests/Unit/Bridge/EditorBridgeRegistryTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\Bridge;

use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Editor\Bridge\EditorBridgeInterface;
use Symfony\UX\Editor\Bridge\EditorBridgeRegistry;
use Symfony\UX\Editor\Exception\UnknownBridgeException;

final class EditorBridgeRegistryTest extends TestCase
{
    public function testGetReturnsRegisteredBridge(): void
    {
        $bridge = $this->makeBridge('quill', 'html');
        $registry = new EditorBridgeRegistry([$bridge]);
        $this->assertSame($bridge, $registry->get('quill'));
    }

    public function testGetThrowsOnUnknown(): void
    {
        $registry = new EditorBridgeRegistry([$this->makeBridge('quill', 'html')]);

        $this->expectException(UnknownBridgeException::class);
        $this->expectExceptionMessage('Unknown editor bridge "tiptap". Registered: quill.');
        $registry->get('tiptap');
    }

    public function testConstructorRejectsDuplicateIds(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Duplicate editor bridge id "quill".');
        new EditorBridgeRegistry([
            $this->makeBridge('quill', 'html'),
            $this->makeBridge('quill', 'markdown'),
        ]);
    }

    public function testIdsReturnsSortedIdList(): void
    {
        $registry = new EditorBridgeRegistry([
            $this->makeBridge('tiptap', 'html'),
            $this->makeBridge('quill', 'html'),
        ]);
        $this->assertSame(['quill', 'tiptap'], $registry->ids());
    }

    private function makeBridge(string $id, string $format): EditorBridgeInterface
    {
        return new class($id, $format) implements EditorBridgeInterface {
            public function __construct(private string $id, private string $format) {}
            public function id(): string { return $this->id; }
            public function format(): string { return $this->format; }
            public function stimulusController(): string { return 'symfony--ux-editor--'.$this->id; }
            public function defaultSanitizer(): ?string { return 'html' === $this->format ? 'default' : null; }
            public function configureOptions(OptionsResolver $resolver): void {}
        };
    }
}
```

- [ ] **Step 2: Run, fail** (registry missing).

- [ ] **Step 3: Implement exception marker + concrete exception**

`src/Exception/UXEditorExceptionInterface.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Exception;

interface UXEditorExceptionInterface extends \Throwable
{
}
```

`src/Exception/UnknownBridgeException.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Exception;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

final class UnknownBridgeException extends InvalidOptionsException implements UXEditorExceptionInterface
{
    /**
     * @param list<string> $registered
     */
    public static function create(string $id, array $registered): self
    {
        return new self(\sprintf('Unknown editor bridge "%s". Registered: %s.', $id, $registered ? implode(', ', $registered) : '(none)'));
    }
}
```

- [ ] **Step 4: Implement the interface**

`src/Bridge/EditorBridgeInterface.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Server-side contract for an editor engine bridge. Pairs with a Stimulus controller
 * exported under `assets/src/<id>_controller.ts` and (optionally) an `EditorRendererInterface`
 * matching `format()`.
 */
interface EditorBridgeInterface
{
    /** Bridge id — must be unique, used as the `bridge` option value on EditorType. */
    public function id(): string;

    /** Content format declared by this bridge (e.g. "html", "json", "markdown", "vvveb-html"). */
    public function format(): string;

    /** Full Stimulus controller id (matches the bundle's StimulusBundle registration). */
    public function stimulusController(): string;

    /** Name of the html-sanitizer profile to apply by default, or null to skip sanitization. */
    public function defaultSanitizer(): ?string;

    /** Defines and validates bridge-specific options. Called by EditorType. */
    public function configureOptions(OptionsResolver $resolver): void;
}
```

- [ ] **Step 5: Implement the registry**

`src/Bridge/EditorBridgeRegistry.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge;

use Symfony\UX\Editor\Exception\UnknownBridgeException;

final class EditorBridgeRegistry
{
    /** @var array<string, EditorBridgeInterface> */
    private array $bridges = [];

    /**
     * @param iterable<EditorBridgeInterface> $bridges
     */
    public function __construct(iterable $bridges)
    {
        foreach ($bridges as $bridge) {
            $id = $bridge->id();
            if (isset($this->bridges[$id])) {
                throw new \LogicException(\sprintf('Duplicate editor bridge id "%s".', $id));
            }
            $this->bridges[$id] = $bridge;
        }
    }

    public function get(string $id): EditorBridgeInterface
    {
        return $this->bridges[$id] ?? throw UnknownBridgeException::create($id, $this->ids());
    }

    /** @return list<string> */
    public function ids(): array
    {
        $ids = array_keys($this->bridges);
        sort($ids);

        return array_values($ids);
    }
}
```

- [ ] **Step 6: Run, pass**

```bash
vendor/bin/phpunit --filter EditorBridgeRegistryTest
```

Expected: PASS (4/4).

- [ ] **Step 7: Commit**

```bash
git add src/Bridge/EditorBridgeInterface.php src/Bridge/EditorBridgeRegistry.php src/Exception/UXEditorExceptionInterface.php src/Exception/UnknownBridgeException.php tests/Unit/Bridge/EditorBridgeRegistryTest.php
git commit -m "feat: add EditorBridgeInterface + registry with duplicate-id guard"
```

---

## Task 6: Sanitizer profile loader

**Files:**

- Create: `src/Sanitizer/HtmlSanitizerProfileLoader.php`
- Create: `src/Exception/SanitizerProfileNotFoundException.php`
- Test: `tests/Unit/Sanitizer/HtmlSanitizerProfileLoaderTest.php`

The loader is a thin map of profile-name → `HtmlSanitizerInterface`. The bundle's DI extension hands it the configured profiles in Task 21; for now the unit test wires it manually.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\Sanitizer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\UX\Editor\Exception\SanitizerProfileNotFoundException;
use Symfony\UX\Editor\Sanitizer\HtmlSanitizerProfileLoader;

final class HtmlSanitizerProfileLoaderTest extends TestCase
{
    public function testGetReturnsRegisteredProfile(): void
    {
        $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig())->allowSafeElements());
        $loader = new HtmlSanitizerProfileLoader(['default' => $sanitizer]);
        $this->assertSame($sanitizer, $loader->get('default'));
    }

    public function testGetThrowsOnUnknown(): void
    {
        $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig())->allowSafeElements());
        $loader = new HtmlSanitizerProfileLoader(['default' => $sanitizer]);

        $this->expectException(SanitizerProfileNotFoundException::class);
        $this->expectExceptionMessage('Unknown html-sanitizer profile "strict". Registered: default.');
        $loader->get('strict');
    }

    public function testHasReportsMembership(): void
    {
        $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig())->allowSafeElements());
        $loader = new HtmlSanitizerProfileLoader(['default' => $sanitizer]);
        $this->assertTrue($loader->has('default'));
        $this->assertFalse($loader->has('strict'));
    }
}
```

- [ ] **Step 2: Run, fail.**

- [ ] **Step 3: Exception**

`src/Exception/SanitizerProfileNotFoundException.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Exception;

final class SanitizerProfileNotFoundException extends \RuntimeException implements UXEditorExceptionInterface
{
    /**
     * @param list<string> $registered
     */
    public static function create(string $profile, array $registered): self
    {
        return new self(\sprintf('Unknown html-sanitizer profile "%s". Registered: %s.', $profile, $registered ? implode(', ', $registered) : '(none)'));
    }
}
```

- [ ] **Step 4: Loader**

`src/Sanitizer/HtmlSanitizerProfileLoader.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Sanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\UX\Editor\Exception\SanitizerProfileNotFoundException;

final class HtmlSanitizerProfileLoader
{
    /**
     * @param array<string, HtmlSanitizerInterface> $profiles
     */
    public function __construct(private readonly array $profiles)
    {
    }

    public function get(string $profile): HtmlSanitizerInterface
    {
        return $this->profiles[$profile] ?? throw SanitizerProfileNotFoundException::create($profile, array_keys($this->profiles));
    }

    public function has(string $profile): bool
    {
        return isset($this->profiles[$profile]);
    }
}
```

- [ ] **Step 5: Run, pass.**

- [ ] **Step 6: Commit**

```bash
git add src/Sanitizer/HtmlSanitizerProfileLoader.php src/Exception/SanitizerProfileNotFoundException.php tests/Unit/Sanitizer/HtmlSanitizerProfileLoaderTest.php
git commit -m "feat: add HtmlSanitizerProfileLoader keyed map"
```

---

## Task 7: `SanitizerDispatcher`

**Files:**

- Create: `src/Sanitizer/SanitizerDispatcher.php`
- Test: `tests/Unit/Sanitizer/SanitizerDispatcherTest.php`

Branch semantics (must match the spec exactly):

| `option` (form)       | bridge default | Effect                              |
| --------------------- | -------------- | ----------------------------------- |
| `false`               | any            | no sanitize                         |
| `string` profile name | any            | sanitize via profile                |
| `null`                | `null`         | no sanitize                         |
| `null`                | `string`       | sanitize via bridge default profile |
| `\Closure`            | any            | invoke closure on `$raw`            |

- [ ] **Step 1: Test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\Sanitizer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Sanitizer\HtmlSanitizerProfileLoader;
use Symfony\UX\Editor\Sanitizer\SanitizerDispatcher;

final class SanitizerDispatcherTest extends TestCase
{
    public function testOptionFalseSkipsSanitization(): void
    {
        $dispatcher = new SanitizerDispatcher($this->loader());
        $input = new EditorContent('<script>x</script>', 'html');
        $this->assertSame($input, $dispatcher->sanitize($input, false, 'default'));
    }

    public function testOptionStringUsesProfile(): void
    {
        $dispatcher = new SanitizerDispatcher($this->loader());
        $result = $dispatcher->sanitize(new EditorContent('<script>x</script><p>ok</p>', 'html'), 'default', null);
        $this->assertSame('<p>ok</p>', $result->raw);
        $this->assertSame('html', $result->format);
    }

    public function testNullOptionWithNullBridgeDefaultSkips(): void
    {
        $dispatcher = new SanitizerDispatcher($this->loader());
        $input = new EditorContent('<script>x</script>', 'vvveb-html');
        $this->assertSame($input, $dispatcher->sanitize($input, null, null));
    }

    public function testNullOptionFallsBackToBridgeDefault(): void
    {
        $dispatcher = new SanitizerDispatcher($this->loader());
        $result = $dispatcher->sanitize(new EditorContent('<script>x</script><p>ok</p>', 'html'), null, 'default');
        $this->assertSame('<p>ok</p>', $result->raw);
    }

    public function testClosureOptionInvokedOnRaw(): void
    {
        $dispatcher = new SanitizerDispatcher($this->loader());
        $result = $dispatcher->sanitize(
            new EditorContent('hello', 'html'),
            static fn (string $raw) => strtoupper($raw),
            null,
        );
        $this->assertSame('HELLO', $result->raw);
    }

    public function testUnknownProfileSurfacesLoaderException(): void
    {
        $dispatcher = new SanitizerDispatcher($this->loader());
        $this->expectException(\Symfony\UX\Editor\Exception\SanitizerProfileNotFoundException::class);
        $dispatcher->sanitize(new EditorContent('x', 'html'), 'strict', null);
    }

    private function loader(): HtmlSanitizerProfileLoader
    {
        return new HtmlSanitizerProfileLoader([
            'default' => new HtmlSanitizer((new HtmlSanitizerConfig())->allowSafeElements()),
        ]);
    }
}
```

- [ ] **Step 2: Run, fail.**

- [ ] **Step 3: Implement**

`src/Sanitizer/SanitizerDispatcher.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Sanitizer;

use Symfony\UX\Editor\Content\EditorContent;

final class SanitizerDispatcher
{
    public function __construct(private readonly HtmlSanitizerProfileLoader $loader)
    {
    }

    public function sanitize(EditorContent $content, false|string|\Closure|null $option, ?string $bridgeDefault): EditorContent
    {
        if (false === $option) {
            return $content;
        }

        if ($option instanceof \Closure) {
            return $content->withRaw((string) $option($content->raw));
        }

        $profile = $option ?? $bridgeDefault;
        if (null === $profile) {
            return $content;
        }

        return $content->withRaw($this->loader->get($profile)->sanitize($content->raw));
    }
}
```

- [ ] **Step 4: Run, pass** (6/6).

- [ ] **Step 5: Commit**

```bash
git add src/Sanitizer/SanitizerDispatcher.php tests/Unit/Sanitizer/SanitizerDispatcherTest.php
git commit -m "feat: add SanitizerDispatcher (5-branch policy resolution)"
```

---

## Task 8: `EditorType` (form integration)

**Files:**

- Create: `src/Form/EditorType.php`
- Create: `tests/Functional/Kernel.php` (minimal kernel for functional tests — reused throughout)
- Test: `tests/Functional/EditorTypeTest.php`

This is the central wiring task. The FormType:

1. Requires a `bridge` option resolving via `EditorBridgeRegistry`.
2. Lets the resolved bridge contribute its own option set via `configureOptions`.
3. Wires the bridge-aware `DataTransformer`.
4. Adds the `sanitizer` option (default `null` = "use bridge default") plus a `POST_SUBMIT` event listener that runs `SanitizerDispatcher` after the transformer succeeds, replacing the bound `EditorContent`.
5. In `buildView`, emits `data-controller`, `data-<controller>-options-value`, `data-<controller>-format-value`, and `data-<controller>-bridge-value` attributes.

- [ ] **Step 1: Create minimal test kernel**

`tests/Functional/Kernel.php`:

```php
<?php

namespace Symfony\UX\Editor\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\UX\Editor\UXEditorBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

final class Kernel extends BaseKernel
{
    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigComponentBundle();
        yield new UXEditorBundle();
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'test' => true,
                'secret' => 'test',
                'http_method_override' => false,
                'form' => true,
                'router' => ['utf8' => true],
                'html_sanitizer' => [
                    'sanitizers' => [
                        'default' => [
                            'allow_safe_elements' => true,
                        ],
                    ],
                ],
            ]);
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/ux-editor/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/ux-editor/log';
    }
}
```

- [ ] **Step 2: Write the failing functional test**

`tests/Functional/EditorTypeTest.php`:

```php
<?php

namespace Symfony\UX\Editor\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Form\EditorType;

final class EditorTypeTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testSubmitProducesSanitizedEditorContent(): void
    {
        self::bootKernel();
        $form = $this->factory()->create(EditorType::class, null, [
            'bridge' => 'quill',
        ]);

        $form->submit('<script>x</script><p>hello</p>');

        $this->assertTrue($form->isSynchronized());
        $data = $form->getData();
        $this->assertInstanceOf(EditorContent::class, $data);
        $this->assertSame('html', $data->format);
        $this->assertStringNotContainsString('<script>', $data->raw);
        $this->assertStringContainsString('<p>hello</p>', $data->raw);
    }

    public function testSanitizerOptionFalseSkipsSanitization(): void
    {
        self::bootKernel();
        $form = $this->factory()->create(EditorType::class, null, [
            'bridge' => 'quill',
            'sanitizer' => false,
        ]);

        $form->submit('<script>x</script>');

        $this->assertTrue($form->isSynchronized());
        $this->assertStringContainsString('<script>x</script>', $form->getData()->raw);
    }

    public function testViewExposesStimulusAttributes(): void
    {
        self::bootKernel();
        $view = $this->factory()->create(EditorType::class, null, ['bridge' => 'quill'])->createView();
        $attrs = $view->vars['attr'];

        $this->assertSame('symfony--ux-editor--quill', $attrs['data-controller']);
        $this->assertSame('quill', $attrs['data-symfony--ux-editor--quill-bridge-value']);
        $this->assertSame('html', $attrs['data-symfony--ux-editor--quill-format-value']);
        $this->assertJson($attrs['data-symfony--ux-editor--quill-options-value']);
    }

    public function testUnknownBridgeIsRejectedAtBuild(): void
    {
        self::bootKernel();
        $this->expectException(\Symfony\UX\Editor\Exception\UnknownBridgeException::class);
        $this->factory()->create(EditorType::class, null, ['bridge' => 'nope']);
    }

    private function factory(): FormFactoryInterface
    {
        return self::getContainer()->get('form.factory');
    }
}
```

The test references `QuillBridge`, which is built in Task 9. Run sequence is therefore: implement Steps 3–5 of Task 8 here (no test execution), then run Task 9, and execute both tests in Task 9 Step 5.

- [ ] **Step 3: Implement `EditorType`**

`src/Form/EditorType.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Editor\Bridge\EditorBridgeRegistry;
use Symfony\UX\Editor\Content\DataTransformer;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Sanitizer\SanitizerDispatcher;

final class EditorType extends AbstractType
{
    public function __construct(
        private readonly EditorBridgeRegistry $registry,
        private readonly SanitizerDispatcher $sanitizer,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['bridge'])
            ->setAllowedTypes('bridge', 'string')
            ->setDefault('sanitizer', null)
            ->setAllowedTypes('sanitizer', ['null', 'string', 'bool', \Closure::class])
            ->setNormalizer('sanitizer', function ($options, $value) {
                if (true === $value) {
                    return 'default';
                }

                return $value;
            })
            ->setDefault('bridge_options', [])
            ->setAllowedTypes('bridge_options', 'array')
            ->setNormalizer('bridge_options', function ($options, $value) {
                $bridge = $this->registry->get($options['bridge']);
                $bridgeResolver = new OptionsResolver();
                $bridge->configureOptions($bridgeResolver);

                return $bridgeResolver->resolve($value);
            });
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $bridge = $this->registry->get($options['bridge']);

        $builder->addModelTransformer(new DataTransformer($bridge->format()));

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) use ($bridge, $options): void {
            $data = $event->getForm()->getData();
            if (!$data instanceof EditorContent) {
                return;
            }
            $sanitized = $this->sanitizer->sanitize($data, $options['sanitizer'], $bridge->defaultSanitizer());
            if ($sanitized !== $data) {
                $event->getForm()->setData($sanitized);
            }
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $bridge = $this->registry->get($options['bridge']);
        $controller = $bridge->stimulusController();

        $view->vars['attr'] = array_merge($view->vars['attr'] ?? [], [
            'data-controller' => $controller,
            \sprintf('data-%s-bridge-value', $controller) => $bridge->id(),
            \sprintf('data-%s-format-value', $controller) => $bridge->format(),
            \sprintf('data-%s-options-value', $controller) => json_encode($options['bridge_options'], \JSON_THROW_ON_ERROR),
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'ux_editor';
    }
}
```

- [ ] **Step 4: Update `src/Resources/config/services.php` to register the form type + dispatcher + registry**

```php
<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\Editor\Bridge\EditorBridgeInterface;
use Symfony\UX\Editor\Bridge\EditorBridgeRegistry;
use Symfony\UX\Editor\Form\EditorType;
use Symfony\UX\Editor\Sanitizer\HtmlSanitizerProfileLoader;
use Symfony\UX\Editor\Sanitizer\SanitizerDispatcher;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()->autowire()->autoconfigure()

        ->instanceof(EditorBridgeInterface::class)->tag('ux_editor.bridge')

        ->set(EditorBridgeRegistry::class)
            ->args([tagged_iterator('ux_editor.bridge')])

        ->set(HtmlSanitizerProfileLoader::class)
            ->args([[]])  // populated by UXEditorExtension in Task 21

        ->set(SanitizerDispatcher::class)

        ->set(EditorType::class)->tag('form.type')
    ;
};
```

- [ ] **Step 5: Commit (test will run in Task 9)**

```bash
git add src/Form/EditorType.php src/Resources/config/services.php tests/Functional/EditorTypeTest.php tests/Functional/Kernel.php
git commit -m "feat: add EditorType form (bridge resolution + sanitizer hook + Stimulus attrs)"
```

---

## Task 9: Quill bridge (PHP side)

**Files:**

- Create: `src/Bridge/Quill/QuillBridge.php`
- Test: `tests/Unit/Bridge/QuillBridgeTest.php`

The Quill bridge declares: `id='quill'`, `format='html'`, `stimulusController='symfony--ux-editor--quill'`, `defaultSanitizer='default'`, plus a small option set (`toolbar`, `placeholder`, `theme`).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\Bridge;

use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Editor\Bridge\Quill\QuillBridge;

final class QuillBridgeTest extends TestCase
{
    public function testIdentity(): void
    {
        $bridge = new QuillBridge();
        $this->assertSame('quill', $bridge->id());
        $this->assertSame('html', $bridge->format());
        $this->assertSame('symfony--ux-editor--quill', $bridge->stimulusController());
        $this->assertSame('default', $bridge->defaultSanitizer());
    }

    public function testConfigureOptionsDefaults(): void
    {
        $resolver = new OptionsResolver();
        (new QuillBridge())->configureOptions($resolver);
        $resolved = $resolver->resolve([]);

        $this->assertSame('snow', $resolved['theme']);
        $this->assertSame('', $resolved['placeholder']);
        $this->assertIsArray($resolved['toolbar']);
    }

    public function testConfigureOptionsRejectsUnknown(): void
    {
        $resolver = new OptionsResolver();
        (new QuillBridge())->configureOptions($resolver);

        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve(['nope' => 'x']);
    }

    public function testThemeMustBeKnown(): void
    {
        $resolver = new OptionsResolver();
        (new QuillBridge())->configureOptions($resolver);

        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve(['theme' => 'bogus']);
    }
}
```

- [ ] **Step 2: Run, fail.**

- [ ] **Step 3: Implement**

`src/Bridge/Quill/QuillBridge.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\Quill;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Editor\Bridge\EditorBridgeInterface;

final class QuillBridge implements EditorBridgeInterface
{
    public function id(): string
    {
        return 'quill';
    }

    public function format(): string
    {
        return 'html';
    }

    public function stimulusController(): string
    {
        return 'symfony--ux-editor--quill';
    }

    public function defaultSanitizer(): ?string
    {
        return 'default';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('theme', 'snow')
            ->setAllowedValues('theme', ['snow', 'bubble'])
            ->setDefault('placeholder', '')
            ->setAllowedTypes('placeholder', 'string')
            ->setDefault('toolbar', [
                ['bold', 'italic', 'underline', 'strike'],
                [['list' => 'ordered'], ['list' => 'bullet']],
                ['link', 'blockquote', 'code-block'],
                ['clean'],
            ])
            ->setAllowedTypes('toolbar', ['array', 'bool']);
    }
}
```

- [ ] **Step 4: Run unit + functional tests**

```bash
vendor/bin/phpunit --filter QuillBridgeTest
vendor/bin/phpunit --filter EditorTypeTest
```

Expected: all green. `EditorTypeTest` now passes because the Quill bridge is auto-tagged (via `instanceof EditorBridgeInterface` in services.php) and the kernel's `html_sanitizer.default` profile satisfies the default sanitization path.

- [ ] **Step 5: Commit**

```bash
git add src/Bridge/Quill/QuillBridge.php tests/Unit/Bridge/QuillBridgeTest.php
git commit -m "feat(bridge): add Quill bridge (HTML, default sanitizer)"
```

---

## Task 10: `EditorContentType` Doctrine column

**Files:**

- Create: `src/Doctrine/EditorContentType.php`
- Test: `tests/Unit/Doctrine/EditorContentTypeTest.php`

- [ ] **Step 1: Test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Doctrine\EditorContentType;

final class EditorContentTypeTest extends TestCase
{
    protected function setUp(): void
    {
        if (!Type::hasType(EditorContentType::NAME)) {
            Type::addType(EditorContentType::NAME, EditorContentType::class);
        }
    }

    public function testConvertToDatabaseValueProducesJson(): void
    {
        $type = Type::getType(EditorContentType::NAME);
        $platform = new SqlitePlatform();
        $value = $type->convertToDatabaseValue(new EditorContent('<p>hi</p>', 'html'), $platform);
        $this->assertSame('{"raw":"<p>hi<\/p>","format":"html"}', $value);
    }

    public function testConvertToPhpValueParsesJson(): void
    {
        $type = Type::getType(EditorContentType::NAME);
        $platform = new SqlitePlatform();
        $content = $type->convertToPHPValue('{"raw":"<p>hi</p>","format":"html"}', $platform);
        $this->assertInstanceOf(EditorContent::class, $content);
        $this->assertSame('<p>hi</p>', $content->raw);
    }

    public function testConvertToPhpValueReturnsNullForNull(): void
    {
        $type = Type::getType(EditorContentType::NAME);
        $platform = new SqlitePlatform();
        $this->assertNull($type->convertToPHPValue(null, $platform));
    }

    public function testConvertToPhpValueRejectsMalformed(): void
    {
        $type = Type::getType(EditorContentType::NAME);
        $platform = new SqlitePlatform();
        $this->expectException(ConversionException::class);
        $type->convertToPHPValue('{not json', $platform);
    }
}
```

- [ ] **Step 2: Run, fail.**

- [ ] **Step 3: Implement**

`src/Doctrine/EditorContentType.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use Symfony\UX\Editor\Content\EditorContent;

final class EditorContentType extends JsonType
{
    public const NAME = 'editor_content';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!$value instanceof EditorContent) {
            throw new \InvalidArgumentException(\sprintf('Expected %s, got %s.', EditorContent::class, get_debug_type($value)));
        }

        return parent::convertToDatabaseValue($value->jsonSerialize(), $platform);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?EditorContent
    {
        if (null === $value) {
            return null;
        }
        $decoded = parent::convertToPHPValue($value, $platform);
        if (!\is_array($decoded) || !\is_string($decoded['raw'] ?? null) || !\is_string($decoded['format'] ?? null)) {
            throw ConversionException::conversionFailedFormat(
                substr((string) $value, 0, 80),
                self::NAME,
                'JSON object with string "raw" and "format" fields',
            );
        }

        return new EditorContent($decoded['raw'], $decoded['format']);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
```

- [ ] **Step 4: Run, pass.**

- [ ] **Step 5: Commit**

```bash
git add src/Doctrine/EditorContentType.php tests/Unit/Doctrine/EditorContentTypeTest.php
git commit -m "feat: add editor_content Doctrine column type"
```

---

## Task 11: `EditorRendererInterface` + registry

**Files:**

- Create: `src/Renderer/EditorRendererInterface.php`
- Create: `src/Renderer/EditorRendererRegistry.php`
- Create: `src/Exception/EditorRenderException.php`
- Modify: `src/Resources/config/services.php`
- Test: `tests/Unit/Renderer/EditorRendererRegistryTest.php`

- [ ] **Step 1: Test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Exception\EditorRenderException;
use Symfony\UX\Editor\Renderer\EditorRendererInterface;
use Symfony\UX\Editor\Renderer\EditorRendererRegistry;

final class EditorRendererRegistryTest extends TestCase
{
    public function testResolveReturnsMatchingRenderer(): void
    {
        $html = $this->renderer('html');
        $markdown = $this->renderer('markdown');
        $registry = new EditorRendererRegistry([$html, $markdown]);
        $this->assertSame($html, $registry->resolve('html'));
        $this->assertSame($markdown, $registry->resolve('markdown'));
    }

    public function testResolveThrowsForUnknownFormat(): void
    {
        $registry = new EditorRendererRegistry([$this->renderer('html')]);
        $this->expectException(EditorRenderException::class);
        $this->expectExceptionMessage('No renderer registered for editor format "json". Registered: html.');
        $registry->resolve('json');
    }

    public function testHasReportsMembership(): void
    {
        $registry = new EditorRendererRegistry([$this->renderer('html')]);
        $this->assertTrue($registry->has('html'));
        $this->assertFalse($registry->has('vvveb-html'));
    }

    private function renderer(string $format): EditorRendererInterface
    {
        return new class($format) implements EditorRendererInterface {
            public function __construct(private string $format) {}
            public function supports(string $format): bool { return $this->format === $format; }
            public function format(): string { return $this->format; }
            public function render(EditorContent $content, array $options = []): string { return $content->raw; }
        };
    }
}
```

- [ ] **Step 2: Run, fail.**

- [ ] **Step 3: Implement renderer interface, exception, registry**

`src/Renderer/EditorRendererInterface.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Renderer;

use Symfony\UX\Editor\Content\EditorContent;

interface EditorRendererInterface
{
    public function supports(string $format): bool;

    public function format(): string;

    /**
     * @param array<string, mixed> $options
     */
    public function render(EditorContent $content, array $options = []): string;
}
```

`src/Exception/EditorRenderException.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Exception;

final class EditorRenderException extends \RuntimeException implements UXEditorExceptionInterface
{
    /**
     * @param list<string> $registered
     */
    public static function unknownFormat(string $format, array $registered): self
    {
        return new self(\sprintf('No renderer registered for editor format "%s". Registered: %s.', $format, $registered ? implode(', ', $registered) : '(none)'));
    }
}
```

`src/Renderer/EditorRendererRegistry.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Renderer;

use Symfony\UX\Editor\Exception\EditorRenderException;

final class EditorRendererRegistry
{
    /** @var array<string, EditorRendererInterface> */
    private array $renderers = [];

    /**
     * @param iterable<EditorRendererInterface> $renderers
     */
    public function __construct(iterable $renderers)
    {
        foreach ($renderers as $renderer) {
            $this->renderers[$renderer->format()] = $renderer;
        }
    }

    public function has(string $format): bool
    {
        return isset($this->renderers[$format]);
    }

    public function resolve(string $format): EditorRendererInterface
    {
        return $this->renderers[$format] ?? throw EditorRenderException::unknownFormat($format, array_keys($this->renderers));
    }
}
```

- [ ] **Step 4: Register renderer auto-tag in services.php** — extend the configurator:

```php
        ->instanceof(\Symfony\UX\Editor\Renderer\EditorRendererInterface::class)->tag('ux_editor.renderer')

        ->set(\Symfony\UX\Editor\Renderer\EditorRendererRegistry::class)
            ->args([tagged_iterator('ux_editor.renderer')])
```

(Append inside the same `return static function (ContainerConfigurator $container)` block that already exists.)

- [ ] **Step 5: Run, pass.**

- [ ] **Step 6: Commit**

```bash
git add src/Renderer/EditorRendererInterface.php src/Renderer/EditorRendererRegistry.php src/Exception/EditorRenderException.php src/Resources/config/services.php tests/Unit/Renderer/EditorRendererRegistryTest.php
git commit -m "feat: add EditorRenderer interface + registry"
```

---

## Task 12: `HtmlRenderer`

**Files:**

- Create: `src/Renderer/HtmlRenderer.php`
- Test: `tests/Unit/Renderer/HtmlRendererTest.php`

The HtmlRenderer is a passthrough — the value was already sanitized at submit time, so display just returns `raw`. (If the consumer skipped sanitization on submit, they explicitly accepted the risk.)

- [ ] **Step 1: Test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Renderer\HtmlRenderer;

final class HtmlRendererTest extends TestCase
{
    public function testFormatIsHtml(): void
    {
        $this->assertSame('html', (new HtmlRenderer())->format());
    }

    public function testSupportsOnlyHtml(): void
    {
        $r = new HtmlRenderer();
        $this->assertTrue($r->supports('html'));
        $this->assertFalse($r->supports('markdown'));
    }

    public function testRenderEchoesRaw(): void
    {
        $this->assertSame('<p>hi</p>', (new HtmlRenderer())->render(new EditorContent('<p>hi</p>', 'html')));
    }
}
```

- [ ] **Step 2: Run, fail.**

- [ ] **Step 3: Implement**

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Renderer;

use Symfony\UX\Editor\Content\EditorContent;

final class HtmlRenderer implements EditorRendererInterface
{
    public function format(): string
    {
        return 'html';
    }

    public function supports(string $format): bool
    {
        return 'html' === $format;
    }

    public function render(EditorContent $content, array $options = []): string
    {
        return $content->raw;
    }
}
```

- [ ] **Step 4: Run, pass.**

- [ ] **Step 5: Commit**

```bash
git add src/Renderer/HtmlRenderer.php tests/Unit/Renderer/HtmlRendererTest.php
git commit -m "feat(renderer): add HtmlRenderer (passthrough — sanitized at submit)"
```

---

## Task 13: `MarkdownRenderer` (CommonMark)

**Files:**

- Create: `src/Renderer/MarkdownRenderer.php`
- Modify: `src/Resources/config/services.php`
- Test: `tests/Unit/Renderer/MarkdownRendererTest.php`

- [ ] **Step 1: Test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\Renderer;

use League\CommonMark\CommonMarkConverter;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Renderer\MarkdownRenderer;

final class MarkdownRendererTest extends TestCase
{
    public function testFormatIsMarkdown(): void
    {
        $this->assertSame('markdown', $this->renderer()->format());
    }

    public function testRenderConvertsToHtml(): void
    {
        $out = $this->renderer()->render(new EditorContent("# Hello\n\nworld", 'markdown'));
        $this->assertStringContainsString('<h1>Hello</h1>', $out);
        $this->assertStringContainsString('<p>world</p>', $out);
    }

    public function testRenderEscapesRawHtmlByDefault(): void
    {
        $out = $this->renderer()->render(new EditorContent('<script>alert(1)</script>', 'markdown'));
        $this->assertStringNotContainsString('<script>', $out);
    }

    private function renderer(): MarkdownRenderer
    {
        return new MarkdownRenderer(new CommonMarkConverter(['html_input' => 'escape', 'allow_unsafe_links' => false]));
    }
}
```

- [ ] **Step 2: Run, fail.**

- [ ] **Step 3: Implement**

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Renderer;

use League\CommonMark\ConverterInterface;
use Symfony\UX\Editor\Content\EditorContent;

final class MarkdownRenderer implements EditorRendererInterface
{
    public function __construct(private readonly ConverterInterface $converter)
    {
    }

    public function format(): string
    {
        return 'markdown';
    }

    public function supports(string $format): bool
    {
        return 'markdown' === $format;
    }

    public function render(EditorContent $content, array $options = []): string
    {
        return (string) $this->converter->convert($content->raw);
    }
}
```

- [ ] **Step 4: Conditionally register in services.php** — if `league/commonmark` is installed, wire a default converter. Add to the top of `services.php`:

```php
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
```

And append inside the return callback:

```php
    if (class_exists(\League\CommonMark\CommonMarkConverter::class)) {
        $container->services()
            ->set('ux_editor.markdown_converter', \League\CommonMark\CommonMarkConverter::class)
                ->args([['html_input' => 'escape', 'allow_unsafe_links' => false]])

            ->set(\Symfony\UX\Editor\Renderer\MarkdownRenderer::class)
                ->args([service('ux_editor.markdown_converter')]);
    }
```

- [ ] **Step 5: Run, pass.**

- [ ] **Step 6: Commit**

```bash
git add src/Renderer/MarkdownRenderer.php src/Resources/config/services.php tests/Unit/Renderer/MarkdownRendererTest.php
git commit -m "feat(renderer): add MarkdownRenderer via league/commonmark"
```

---

## Task 14: `<twig:ux:editor:render>` Twig component

**Files:**

- Create: `src/Twig/Components/Render.php`
- Create: `templates/components/ux/editor/Render.html.twig`
- Modify: `src/DependencyInjection/UXEditorExtension.php`
- Modify: `src/Resources/config/services.php`
- Test: `tests/Functional/RenderComponentTest.php`

The Twig component asks the renderer registry to handle the format. On missing renderer it follows the configured `unknown_format_strategy` (default `escape`): in prod, output HTML-escaped raw text; if set to `throw`, propagate. The strategy is wired through bundle config in Task 20; this task hard-codes `escape` as the constructor default and makes it injectable.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\TwigComponent\ComponentRendererInterface;

final class RenderComponentTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testRendersHtmlContent(): void
    {
        self::bootKernel();
        $renderer = self::getContainer()->get(ComponentRendererInterface::class);
        $output = $renderer->createAndRender('ux:editor:render', [
            'content' => new EditorContent('<p>hi</p>', 'html'),
        ]);
        $this->assertStringContainsString('<p>hi</p>', $output);
    }

    public function testUnknownFormatEscapesByDefault(): void
    {
        self::bootKernel();
        $renderer = self::getContainer()->get(ComponentRendererInterface::class);
        $output = $renderer->createAndRender('ux:editor:render', [
            'content' => new EditorContent('<p>x</p>', 'unknown-format'),
        ]);
        $this->assertStringContainsString('&lt;p&gt;x&lt;/p&gt;', $output);
        $this->assertStringNotContainsString('<p>x</p>', $output);
    }
}
```

- [ ] **Step 2: Run, fail.**

- [ ] **Step 3: Implement the component PHP**

`src/Twig/Components/Render.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Twig\Components;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Exception\EditorRenderException;
use Symfony\UX\Editor\Renderer\EditorRendererRegistry;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'ux:editor:render', template: '@UXEditor/components/ux/editor/Render.html.twig')]
final class Render
{
    /** Strategy when no renderer is registered for the content's format. */
    public const STRATEGY_THROW = 'throw';
    public const STRATEGY_ESCAPE = 'escape';

    public EditorContent $content;

    /** @var array<string, mixed> */
    public array $options = [];

    public function __construct(
        private readonly EditorRendererRegistry $registry,
        private readonly string $unknownFormatStrategy = self::STRATEGY_ESCAPE,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function html(): string
    {
        try {
            $renderer = $this->registry->resolve($this->content->format);
        } catch (EditorRenderException $e) {
            if (self::STRATEGY_THROW === $this->unknownFormatStrategy) {
                throw $e;
            }
            $this->logger->error('No renderer for editor format "{format}".', ['format' => $this->content->format, 'exception' => $e]);

            return htmlspecialchars($this->content->raw, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        }

        try {
            return $renderer->render($this->content, $this->options);
        } catch (\Throwable $e) {
            if (self::STRATEGY_THROW === $this->unknownFormatStrategy) {
                throw $e;
            }
            $this->logger->error('Renderer for format "{format}" threw.', ['format' => $this->content->format, 'exception' => $e]);

            return htmlspecialchars($this->content->raw, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        }
    }
}
```

- [ ] **Step 4: Implement the component template**

`templates/components/ux/editor/Render.html.twig`:

```twig
{{ this.html()|raw }}
```

(`|raw` is intentional and safe: `html()` returns either a renderer's output, which has already been sanitized at submit, or `htmlspecialchars`-escaped fallback text.)

- [ ] **Step 5: Wire the template path in `UXEditorExtension`** — rewrite the file as:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class UXEditorExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        (new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config')))->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('twig', [
            'paths' => [\dirname(__DIR__, 2).'/templates' => 'UXEditor'],
        ]);
    }

    public function getAlias(): string
    {
        return 'ux_editor';
    }
}
```

- [ ] **Step 6: Register the Render service in services.php** — append:

```php
        ->set(\Symfony\UX\Editor\Twig\Components\Render::class)
            ->args([service(\Symfony\UX\Editor\Renderer\EditorRendererRegistry::class), 'escape'])
```

- [ ] **Step 7: Run, pass.**

- [ ] **Step 8: Commit**

```bash
git add src/Twig/Components/Render.php templates/components/ux/editor/Render.html.twig src/DependencyInjection/UXEditorExtension.php src/Resources/config/services.php tests/Functional/RenderComponentTest.php
git commit -m "feat(twig): add <twig:ux:editor:render> component"
```

---

## Task 15: Live Component hydration extension

**Files:**

- Create: `src/Live/EditorContentHydrationExtension.php`
- Test: `tests/Functional/LiveHydrationTest.php`

The hydration extension teaches `ux-live-component` how to dehydrate an `EditorContent` (to `{raw, format}`) and rehydrate it back. Mirrors the `GameHydrationExtension` pattern in this repo's `LiveMemory` module.

- [ ] **Step 1: Test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Live\EditorContentHydrationExtension;

final class LiveHydrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testDehydrateRehydrateRoundTrip(): void
    {
        self::bootKernel();
        $ext = self::getContainer()->get(EditorContentHydrationExtension::class);

        $content = new EditorContent('<p>hi</p>', 'html');
        $dehydrated = $ext->dehydrate($content);
        $this->assertSame(['raw' => '<p>hi</p>', 'format' => 'html'], $dehydrated);

        $rehydrated = $ext->hydrate($dehydrated, EditorContent::class);
        $this->assertTrue($content->equals($rehydrated));
    }

    public function testSupportsEditorContentOnly(): void
    {
        self::bootKernel();
        $ext = self::getContainer()->get(EditorContentHydrationExtension::class);
        $this->assertTrue($ext->supports(EditorContent::class));
        $this->assertFalse($ext->supports(\stdClass::class));
    }
}
```

- [ ] **Step 2: Run, fail.**

- [ ] **Step 3: Implement**

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Live;

use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

final class EditorContentHydrationExtension implements HydrationExtensionInterface
{
    public function supports(string $className): bool
    {
        return EditorContent::class === $className || is_subclass_of($className, EditorContent::class);
    }

    public function hydrate(mixed $data, string $className): EditorContent
    {
        if (!\is_array($data) || !\is_string($data['raw'] ?? null) || !\is_string($data['format'] ?? null)) {
            throw new \InvalidArgumentException('EditorContent live payload must be {raw: string, format: string}.');
        }

        return new EditorContent($data['raw'], $data['format']);
    }

    public function dehydrate(mixed $object): array
    {
        \assert($object instanceof EditorContent);

        return $object->jsonSerialize();
    }
}
```

(Note: the exact `HydrationExtensionInterface` signature comes from `symfony/ux-live-component`. If method names differ at the installed Live Component version, adjust to match — Live Component minimum constraint is `^2.18` per composer.json.)

- [ ] **Step 4: Run, pass.**

- [ ] **Step 5: Commit**

```bash
git add src/Live/EditorContentHydrationExtension.php tests/Functional/LiveHydrationTest.php
git commit -m "feat(live): add EditorContent hydration extension for ux-live-component"
```

---

## Task 16: Abstract Stimulus controller (TS)

**Files:**

- Create: `assets/src/abstract_editor_controller.ts`
- Test: `assets/test/unit/abstract_editor_controller.test.ts`

The base owns connect/disconnect, hidden-input wiring, `input` event dispatch, and try/catch around the engine-specific hooks. Subclasses implement `mountEditor`, `serializeEditor`, `destroyEditor`.

- [ ] **Step 1: Write the failing Vitest unit test**

```ts
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { Application } from '@hotwired/stimulus';
import AbstractEditorController from '../../src/abstract_editor_controller';

class FakeEditor extends AbstractEditorController {
    public mounted = false;
    public destroyed = false;
    public payload = 'initial';

    protected mountEditor(): void {
        this.mounted = true;
    }

    protected serializeEditor(): string {
        return this.payload;
    }

    protected destroyEditor(): void {
        this.destroyed = true;
    }
}

describe('AbstractEditorController', () => {
    let app: Application;
    let element: HTMLElement;
    let hidden: HTMLInputElement;

    beforeEach(() => {
        document.body.innerHTML = `
          <div data-controller="fake" data-fake-bridge-value="fake"
               data-fake-format-value="html"
               data-fake-options-value='{}'>
            <input type="hidden" data-fake-target="hidden" />
            <div data-fake-target="host"></div>
          </div>`;
        element = document.querySelector('[data-controller="fake"]')!;
        hidden = element.querySelector('input[data-fake-target="hidden"]')!;
        app = Application.start();
        app.register('fake', FakeEditor);
    });

    it('mounts on connect', async () => {
        await vi.waitFor(() => {
            const ctrl = app.getControllerForElementAndIdentifier(element, 'fake') as FakeEditor;
            expect(ctrl.mounted).toBe(true);
        });
    });

    it('syncs hidden input and dispatches input event on serialize trigger', async () => {
        const ctrl = await vi.waitFor(() => {
            const c = app.getControllerForElementAndIdentifier(element, 'fake') as FakeEditor | null;
            if (!c) throw new Error('controller not ready');
            return c;
        });
        const listener = vi.fn();
        hidden.addEventListener('input', listener);
        ctrl.payload = 'updated';
        ctrl.notifyChange();
        expect(hidden.value).toBe('updated');
        expect(listener).toHaveBeenCalled();
    });

    it('renders fallback textarea and dispatches mount-failed when mountEditor throws', async () => {
        class FailingEditor extends AbstractEditorController {
            protected mountEditor(): void {
                throw new Error('boom');
            }
            protected serializeEditor(): string {
                return '';
            }
            protected destroyEditor(): void {}
        }
        const failedEvent = vi.fn();
        document.addEventListener('ux:editor:mount-failed', failedEvent);
        document.body.innerHTML = `
          <div data-controller="failing">
            <input type="hidden" data-failing-target="hidden" />
            <div data-failing-target="host"></div>
          </div>`;
        app.register('failing', FailingEditor);
        await vi.waitFor(() => expect(failedEvent).toHaveBeenCalled());
        const fallback = document.querySelector('[data-controller="failing"] textarea');
        expect(fallback).not.toBeNull();
    });
});
```

- [ ] **Step 2: Run, fail**

```bash
pnpm vitest run assets/test/unit/abstract_editor_controller.test.ts
```

Expected: module not found.

- [ ] **Step 3: Implement**

`assets/src/abstract_editor_controller.ts`:

```ts
import { Controller } from '@hotwired/stimulus';

export default abstract class AbstractEditorController extends Controller<HTMLElement> {
    static targets = ['hidden', 'host'];
    static values = {
        bridge: String,
        format: String,
        options: Object,
    };

    declare readonly hiddenTarget: HTMLInputElement;
    declare readonly hasHiddenTarget: boolean;
    declare readonly hostTarget: HTMLElement;
    declare readonly hasHostTarget: boolean;
    declare readonly bridgeValue: string;
    declare readonly formatValue: string;
    declare readonly optionsValue: Record<string, unknown>;

    private mountedOk = false;

    connect(): void {
        if (!this.hasHiddenTarget) {
            throw new Error('ux-editor: missing data-*-target="hidden" element');
        }
        if (!this.hasHostTarget) {
            throw new Error('ux-editor: missing data-*-target="host" element');
        }
        try {
            this.mountEditor(this.hostTarget);
            this.mountedOk = true;
        } catch (error) {
            this.renderFallback();
            this.dispatch('mount-failed', {
                detail: { bridge: this.bridgeValue, error },
                prefix: 'ux:editor',
                bubbles: true,
            });
        }
    }

    disconnect(): void {
        if (!this.mountedOk) {
            return;
        }
        try {
            this.destroyEditor();
        } catch {
            // disconnect must not throw — engines that fail teardown should not break Stimulus
        }
    }

    /** Subclasses MUST call this whenever the editor's value changes. */
    notifyChange(): void {
        try {
            const value = this.serializeEditor();
            if (value !== this.hiddenTarget.value) {
                this.hiddenTarget.value = value;
                this.hiddenTarget.dispatchEvent(new Event('input', { bubbles: true }));
            }
        } catch (error) {
            this.dispatch('serialize-failed', {
                detail: { bridge: this.bridgeValue, error },
                prefix: 'ux:editor',
                bubbles: true,
            });
        }
    }

    protected abstract mountEditor(host: HTMLElement): void;
    protected abstract serializeEditor(): string;
    protected abstract destroyEditor(): void;

    private renderFallback(): void {
        const fallback = document.createElement('textarea');
        fallback.value = this.hiddenTarget.value;
        fallback.addEventListener('input', () => {
            this.hiddenTarget.value = fallback.value;
            this.hiddenTarget.dispatchEvent(new Event('input', { bubbles: true }));
        });
        this.hostTarget.replaceChildren(fallback);
    }
}
```

- [ ] **Step 4: Run, pass**

```bash
pnpm vitest run assets/test/unit/abstract_editor_controller.test.ts
```

Expected: PASS (3/3).

- [ ] **Step 5: Commit**

```bash
git add assets/src/abstract_editor_controller.ts assets/test/unit/abstract_editor_controller.test.ts
git commit -m "feat(client): add abstract Stimulus editor controller"
```

---

## Task 17: Quill Stimulus controller (TS)

**Files:**

- Create: `assets/src/quill_controller.ts`
- Test: `assets/test/unit/quill_controller.test.ts`

- [ ] **Step 1: Test (mock Quill module)**

```ts
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { Application } from '@hotwired/stimulus';

const onMock = vi.fn();
const getSemanticHTMLMock = vi.fn(() => '<p>hi</p>');
const QuillMock = vi.fn().mockImplementation(() => ({
    on: onMock,
    getSemanticHTML: getSemanticHTMLMock,
}));

vi.mock('quill', () => ({ default: QuillMock }));

import QuillController from '../../src/quill_controller';

describe('QuillController', () => {
    let app: Application;

    beforeEach(() => {
        QuillMock.mockClear();
        onMock.mockClear();
        getSemanticHTMLMock.mockClear();
        document.body.innerHTML = `
          <div data-controller="ux-editor-quill"
               data-ux-editor-quill-bridge-value="quill"
               data-ux-editor-quill-format-value="html"
               data-ux-editor-quill-options-value='{"theme":"snow","placeholder":"","toolbar":[["bold"]]}'>
            <input type="hidden" data-ux-editor-quill-target="hidden" value="" />
            <div data-ux-editor-quill-target="host"></div>
          </div>`;
        app = Application.start();
        app.register('ux-editor-quill', QuillController);
    });

    it('instantiates Quill with the passed options', async () => {
        await vi.waitFor(() => expect(QuillMock).toHaveBeenCalledTimes(1));
        const [hostArg, optionsArg] = QuillMock.mock.calls[0]!;
        expect(hostArg).toBeInstanceOf(HTMLElement);
        expect(optionsArg).toMatchObject({ theme: 'snow', placeholder: '', modules: { toolbar: [['bold']] } });
    });

    it('syncs hidden input when Quill fires text-change', async () => {
        await vi.waitFor(() => expect(onMock).toHaveBeenCalled());
        const handler = onMock.mock.calls.find(([event]) => event === 'text-change')?.[1];
        expect(handler).toBeTypeOf('function');
        handler!();
        const hidden = document.querySelector('input[type=hidden]') as HTMLInputElement;
        expect(hidden.value).toBe('<p>hi</p>');
    });
});
```

- [ ] **Step 2: Run, fail.**

- [ ] **Step 3: Implement**

`assets/src/quill_controller.ts`:

```ts
import Quill from 'quill';
import AbstractEditorController from './abstract_editor_controller';

type QuillOptions = {
    theme: 'snow' | 'bubble';
    placeholder: string;
    toolbar: unknown;
};

export default class QuillController extends AbstractEditorController {
    private editor: Quill | null = null;

    protected mountEditor(host: HTMLElement): void {
        const opts = this.optionsValue as Partial<QuillOptions>;
        this.editor = new Quill(host, {
            theme: opts.theme ?? 'snow',
            placeholder: opts.placeholder ?? '',
            modules: { toolbar: opts.toolbar ?? true },
        });

        if (this.hiddenTarget.value) {
            const editorRoot = host.querySelector('.ql-editor');
            if (editorRoot) {
                editorRoot.innerHTML = this.hiddenTarget.value;
            }
        }

        this.editor.on('text-change', () => this.notifyChange());
    }

    protected serializeEditor(): string {
        return this.editor?.getSemanticHTML() ?? '';
    }

    protected destroyEditor(): void {
        this.editor = null;
    }
}
```

- [ ] **Step 4: Run, pass.**

- [ ] **Step 5: Commit**

```bash
git add assets/src/quill_controller.ts assets/test/unit/quill_controller.test.ts
git commit -m "feat(bridge): add Quill Stimulus controller"
```

---

## Task 18: Quill Playwright browser test

**Files:**

- Create: `assets/test/browser/fixtures/quill.html`
- Create: `assets/test/browser/quill.test.ts`

The browser test serves a static page that loads Stimulus + the compiled controller + Quill, mounts the controller, types into the editor, and asserts the hidden input value updates.

- [ ] **Step 1: Run a one-off build so `dist/` exists**

```bash
pnpm run build
```

Expected: `dist/abstract_editor_controller.js`, `dist/quill_controller.js` are produced.

- [ ] **Step 2: Create fixture page**

`assets/test/browser/fixtures/quill.html`:

```html
<!doctype html>
<html>
    <head>
        <meta charset="utf-8" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" />
    </head>
    <body>
        <div
            data-controller="ux-editor-quill"
            data-ux-editor-quill-bridge-value="quill"
            data-ux-editor-quill-format-value="html"
            data-ux-editor-quill-options-value='{"theme":"snow","placeholder":"","toolbar":[["bold","italic"]]}'>
            <input type="hidden" name="body" data-ux-editor-quill-target="hidden" value="" />
            <div data-ux-editor-quill-target="host"></div>
        </div>
        <script type="importmap">
            {
                "imports": {
                    "@hotwired/stimulus": "https://cdn.jsdelivr.net/npm/@hotwired/stimulus@3.2.2/+esm",
                    "quill": "https://cdn.jsdelivr.net/npm/quill@2.0.2/+esm"
                }
            }
        </script>
        <script type="module">
            import { Application } from '@hotwired/stimulus';
            import QuillController from '../../../dist/quill_controller.js';
            const app = Application.start();
            app.register('ux-editor-quill', QuillController);
        </script>
    </body>
</html>
```

- [ ] **Step 3: Test**

`assets/test/browser/quill.test.ts`:

```ts
import { test, expect } from '@playwright/test';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const fixtureUrl = pathToFileURL(path.resolve(__dirname, 'fixtures/quill.html')).toString();

test('Quill bridge: typing updates hidden input and fires input event', async ({ page }) => {
    await page.goto(fixtureUrl);
    await page.waitForSelector('.ql-editor');

    let inputEvents = 0;
    await page.exposeFunction('__bumpInput', () => {
        inputEvents++;
    });
    await page.evaluate(() => {
        const hidden = document.querySelector('input[type=hidden]')!;
        hidden.addEventListener('input', () => (window as unknown as { __bumpInput: () => void }).__bumpInput());
    });

    await page.locator('.ql-editor').click();
    await page.keyboard.type('Hello world');

    await expect
        .poll(
            async () => {
                return await page.locator('input[type=hidden]').inputValue();
            },
            { timeout: 5000 }
        )
        .toContain('Hello world');

    expect(inputEvents).toBeGreaterThan(0);
});
```

- [ ] **Step 4: Run**

```bash
pnpm playwright test assets/test/browser/quill.test.ts
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add assets/test/browser/quill.test.ts assets/test/browser/fixtures/quill.html
git commit -m "test(quill): add Playwright browser test for typing → hidden input sync"
```

---

## Task 19: End-to-end functional test (Quill, top to bottom)

**Files:**

- Create: `tests/Functional/EndToEndQuillTest.php`

Exercises the entire submit → sanitize → Doctrine round-trip → render path **without** the browser layer (Task 18 already covered that). Demonstrates the package works as advertised when a real Doctrine type and form factory are used together.

- [ ] **Step 1: Test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Functional;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\Type;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\Editor\Content\EditorContent;
use Symfony\UX\Editor\Doctrine\EditorContentType;
use Symfony\UX\Editor\Form\EditorType;
use Symfony\UX\TwigComponent\ComponentRendererInterface;

final class EndToEndQuillTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testFormSubmitSanitizesAndPersistsAndRenders(): void
    {
        self::bootKernel();

        if (!Type::hasType(EditorContentType::NAME)) {
            Type::addType(EditorContentType::NAME, EditorContentType::class);
        }
        $doctrineType = Type::getType(EditorContentType::NAME);
        $platform = new SqlitePlatform();

        // 1) submit malicious + good HTML through EditorType
        $factory = self::getContainer()->get(FormFactoryInterface::class);
        $form = $factory->create(EditorType::class, null, ['bridge' => 'quill']);
        $form->submit('<script>alert(1)</script><p>safe <b>bold</b></p>');

        $this->assertTrue($form->isSynchronized());
        /** @var EditorContent $content */
        $content = $form->getData();
        $this->assertInstanceOf(EditorContent::class, $content);
        $this->assertStringNotContainsString('<script>', $content->raw);
        $this->assertStringContainsString('<p>safe <b>bold</b></p>', $content->raw);

        // 2) round-trip through Doctrine type
        $stored = $doctrineType->convertToDatabaseValue($content, $platform);
        $loaded = $doctrineType->convertToPHPValue($stored, $platform);
        $this->assertTrue($content->equals($loaded));

        // 3) render through twig component
        $renderer = self::getContainer()->get(ComponentRendererInterface::class);
        $output = $renderer->createAndRender('ux:editor:render', ['content' => $loaded]);
        $this->assertStringContainsString('<p>safe <b>bold</b></p>', $output);
        $this->assertStringNotContainsString('<script>', $output);
    }
}
```

- [ ] **Step 2: Run**

```bash
vendor/bin/phpunit --filter EndToEndQuillTest
```

Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Functional/EndToEndQuillTest.php
git commit -m "test: add end-to-end Quill submit → sanitize → Doctrine → render"
```

---

## Task 20: Bundle config tree (`render.unknown_format_strategy`)

**Files:**

- Create: `src/DependencyInjection/Configuration.php`
- Modify: `src/DependencyInjection/UXEditorExtension.php`
- Test: `tests/Unit/DependencyInjection/UXEditorExtensionTest.php`

- [ ] **Step 1: Test**

```php
<?php

namespace Symfony\UX\Editor\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Editor\DependencyInjection\UXEditorExtension;
use Symfony\UX\Editor\Twig\Components\Render;

final class UXEditorExtensionTest extends TestCase
{
    public function testDefaultStrategyIsEscape(): void
    {
        $container = $this->build([]);
        $args = $container->getDefinition(Render::class)->getArguments();
        $this->assertSame(Render::STRATEGY_ESCAPE, $args['$unknownFormatStrategy'] ?? $args[1]);
    }

    public function testStrategyOverride(): void
    {
        $container = $this->build([['render' => ['unknown_format_strategy' => 'throw']]]);
        $args = $container->getDefinition(Render::class)->getArguments();
        $this->assertSame(Render::STRATEGY_THROW, $args['$unknownFormatStrategy'] ?? $args[1]);
    }

    public function testInvalidStrategyRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->build([['render' => ['unknown_format_strategy' => 'shrug']]]);
    }

    private function build(array $configs): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new UXEditorExtension())->load($configs, $container);

        return $container;
    }
}
```

- [ ] **Step 2: Run, fail.**

- [ ] **Step 3: Configuration**

`src/DependencyInjection/Configuration.php`:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ux_editor');
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->arrayNode('render')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('unknown_format_strategy')
                            ->values(['throw', 'escape'])
                            ->defaultValue('escape')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
```

- [ ] **Step 4: Extension wiring**

Update `UXEditorExtension::load`:

```php
public function load(array $configs, ContainerBuilder $container): void
{
    $config = $this->processConfiguration(new Configuration(), $configs);
    (new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config')))->load('services.php');

    $container->getDefinition(\Symfony\UX\Editor\Twig\Components\Render::class)
        ->setArgument('$unknownFormatStrategy', $config['render']['unknown_format_strategy']);
}
```

(`processConfiguration` is inherited from `Extension`.)

- [ ] **Step 5: Run, pass.**

- [ ] **Step 6: Commit**

```bash
git add src/DependencyInjection/Configuration.php src/DependencyInjection/UXEditorExtension.php tests/Unit/DependencyInjection/UXEditorExtensionTest.php
git commit -m "feat(bundle): add ux_editor config tree (render.unknown_format_strategy)"
```

---

## Task 21: Wire sanitizer profiles from bundle config

**Files:**

- Modify: `src/DependencyInjection/Configuration.php`
- Modify: `src/DependencyInjection/UXEditorExtension.php`
- Modify: `tests/Functional/EditorTypeTest.php` (regression test for unknown profile)

The goal: the `HtmlSanitizerProfileLoader` constructed in services.php is empty by default. Pull profile names from a new `ux_editor.sanitizer.profiles` config node mapping a profile name to an existing `symfony/html-sanitizer` service id. Default mapping is `default → html_sanitizer.sanitizer.default` (the service FrameworkBundle exposes when `framework.html_sanitizer.sanitizers.default` is configured).

- [ ] **Step 1: Extend Configuration**

In `Configuration::getConfigTreeBuilder()`, before the closing `->end()->end()`, insert:

```php
                ->arrayNode('sanitizer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('profiles')
                            ->normalizeKeys(false)
                            ->scalarPrototype()->end()
                            ->info('Map of profile name → html-sanitizer service id (e.g. {default: "html_sanitizer.sanitizer.default"})')
                            ->defaultValue(['default' => 'html_sanitizer.sanitizer.default'])
                        ->end()
                    ->end()
                ->end()
```

- [ ] **Step 2: Read it in `UXEditorExtension::load`**

Append after the existing `Render` argument override:

```php
$profiles = [];
foreach ($config['sanitizer']['profiles'] as $name => $serviceId) {
    $profiles[$name] = new \Symfony\Component\DependencyInjection\Reference($serviceId);
}
$container->getDefinition(\Symfony\UX\Editor\Sanitizer\HtmlSanitizerProfileLoader::class)
    ->replaceArgument(0, $profiles);
```

- [ ] **Step 3: Add a regression test asserting unknown-profile rejection through the form**

Append to `tests/Functional/EditorTypeTest.php`:

```php
public function testSanitizerOptionUnknownProfileThrows(): void
{
    self::bootKernel();
    $form = $this->factory()->create(EditorType::class, null, [
        'bridge' => 'quill',
        'sanitizer' => 'nonexistent',
    ]);

    $this->expectException(\Symfony\UX\Editor\Exception\SanitizerProfileNotFoundException::class);
    $form->submit('<p>x</p>');
}
```

- [ ] **Step 4: Run all functional tests**

```bash
vendor/bin/phpunit tests/Functional
```

Expected: all green.

- [ ] **Step 5: Commit**

```bash
git add src/DependencyInjection/Configuration.php src/DependencyInjection/UXEditorExtension.php tests/Functional/EditorTypeTest.php
git commit -m "feat(bundle): wire sanitizer.profiles config → HtmlSanitizerProfileLoader"
```

---

## Task 22: Asset registration (`controllers.json`)

**Files:**

- Create: `assets/controllers.json`
- Create: `assets/src/index.ts`
- Modify: `package.json`

- [ ] **Step 1: Create `assets/controllers.json`**

```json
{
    "controllers": {
        "@symfony/ux-editor": {
            "quill": {
                "main": "dist/quill_controller.js",
                "fetch": "lazy",
                "enabled": true
            }
        }
    },
    "entrypoints": []
}
```

StimulusBundle picks this up automatically when the bundle is installed and registers `symfony--ux-editor--quill` (matching the bridge's `stimulusController()` value).

- [ ] **Step 2: Add `assets/src/index.ts` re-exporting the public surface**

```ts
export { default as AbstractEditorController } from './abstract_editor_controller';
export { default as QuillController } from './quill_controller';
```

- [ ] **Step 3: Update `package.json` `files` and `exports`**

Replace `"files": ["dist/", "src/"]` with `"files": ["dist/", "src/", "assets/controllers.json"]` and add an `exports` block alongside:

```json
"exports": {
    ".": "./dist/index.js",
    "./quill_controller": "./dist/quill_controller.js",
    "./abstract_editor_controller": "./dist/abstract_editor_controller.js",
    "./controllers.json": "./assets/controllers.json"
}
```

- [ ] **Step 4: Build + smoke check**

```bash
pnpm run build
ls dist/
```

Expected: `dist/index.js`, `dist/quill_controller.js`, `dist/abstract_editor_controller.js`.

- [ ] **Step 5: Commit**

```bash
git add assets/controllers.json assets/src/index.ts package.json
git commit -m "chore: register controllers.json + npm exports for StimulusBundle pickup"
```

---

## Task 23: README + CHANGELOG

**Files:**

- Modify: `README.md`
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Flesh out README**

````markdown
# Symfony UX Editor

A polymorphic Symfony Form + Stimulus base for rich-text and block editors.

## Status

Pre-release. Plan A (this package) ships the core and the **Quill** bridge.
Plans B/C/D add EditorJS, TipTap and vvvebjs against the same contract.

## Install

```
composer require symfony/ux-editor
npm install --save quill
```

## Quickstart (Quill)

```php
use Symfony\UX\Editor\Form\EditorType;

$builder->add('body', EditorType::class, [
    'bridge' => 'quill',
    // 'sanitizer' => 'default',    // default; pass false to skip, a closure for custom, or another profile name
    // 'bridge_options' => ['theme' => 'snow', 'toolbar' => [...]],
]);
```

Persist with the `editor_content` Doctrine type:

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        types:
            editor_content: Symfony\UX\Editor\Doctrine\EditorContentType
```

```php
use Symfony\UX\Editor\Content\EditorContent;

#[ORM\Column(type: 'editor_content', nullable: true)]
private ?EditorContent $body = null;
```

Render saved content:

```twig
<twig:ux:editor:render :content="post.body" />
```

## Configuration

```yaml
ux_editor:
    render:
        unknown_format_strategy: escape # or "throw"
    sanitizer:
        profiles:
            default: html_sanitizer.sanitizer.default
            # add named profiles per `framework.html_sanitizer.sanitizers.*`
```

## Contributing

See `docs/superpowers/specs/2026-05-17-ux-editor-base-design.md` for the design.
````

- [ ] **Step 2: Update CHANGELOG**

```markdown
# CHANGELOG

## 0.1.0 — Unreleased

- Core: `EditorContent` value object, `EditorBridgeInterface` + registry, `EditorType` form type, `DataTransformer`, `SanitizerDispatcher` (5-branch policy resolution), `HtmlSanitizerProfileLoader`, `EditorContentType` Doctrine column.
- Display: `EditorRendererInterface` + registry, `HtmlRenderer`, `MarkdownRenderer` (via `league/commonmark`), `<twig:ux:editor:render>` Twig component with configurable unknown-format strategy.
- Live: `EditorContentHydrationExtension` for `symfony/ux-live-component`.
- Client: abstract Stimulus controller + Quill bridge controller, `controllers.json` for StimulusBundle pickup.
- Bridges shipped: **Quill** (HTML, default sanitizer).
```

- [ ] **Step 3: Commit**

```bash
git add README.md CHANGELOG.md
git commit -m "docs: README quickstart + CHANGELOG for 0.1.0"
```

---

## Task 24: CI workflow + green-suite checkpoint

**Files:**

- Create: `.github/workflows/test.yml`

- [ ] **Step 1: Create CI**

```yaml
name: Tests

on:
    pull_request:
    push:
        branches: [main]

jobs:
    phpunit:
        runs-on: ubuntu-latest
        strategy:
            fail-fast: false
            matrix:
                php: ['8.2', '8.3', '8.4']
                symfony: ['6.4.*', '7.0.*', '7.1.*']
        steps:
            - uses: actions/checkout@v4
            - uses: shivammathur/setup-php@v2
              with: { php-version: '${{ matrix.php }}' }
            - run: composer require --no-update symfony/flex
            - run: composer config extra.symfony.require ${{ matrix.symfony }}
            - run: composer update --no-progress
            - run: vendor/bin/phpunit

    vitest:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v4
            - uses: actions/setup-node@v4
              with: { node-version: '20' }
            - run: corepack enable
            - run: pnpm install --frozen-lockfile
            - run: pnpm vitest run

    playwright:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v4
            - uses: actions/setup-node@v4
              with: { node-version: '20' }
            - run: corepack enable
            - run: pnpm install --frozen-lockfile
            - run: pnpm playwright install --with-deps chromium
            - run: pnpm run build
            - run: pnpm playwright test
```

- [ ] **Step 2: Run the full local suite once to confirm the checkpoint**

```bash
vendor/bin/phpunit
pnpm vitest run
pnpm run build
pnpm playwright test
```

Expected: all suites green.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/test.yml
git commit -m "chore(ci): add PHPUnit + Vitest + Playwright matrix"
```

---

## End-of-plan checkpoint

After Task 24 commits, the package is in a shippable state for Plan A:

- `composer require symfony/ux-editor` + `npm install quill` is enough to use it.
- A consumer can `->add('body', EditorType::class, ['bridge' => 'quill'])`, get an `EditorContent` value object that's sanitized via `symfony/html-sanitizer` and stored as JSON via the `editor_content` Doctrine type.
- `<twig:ux:editor:render :content="...">` renders saved content safely.
- Live Component hydration works for `EditorContent` props.
- CI runs PHPUnit + Vitest + Playwright across a PHP × Symfony matrix.

## Follow-up plans (Plan B / C / D — to be written separately)

Each follow-up plan adds **one** bridge against the now-stable Plan A contract:

- **Plan B — EditorJS bridge.** Adds `src/Bridge/EditorJs/EditorJsBridge.php` (format `json`, default sanitizer `null`), `src/Renderer/EditorJsRenderer.php` (block walker for paragraph/header/list/image/code/quote), `assets/src/editorjs_controller.ts`, mirrored tests. Estimated 6-8 tasks.
- **Plan C — TipTap bridge.** Adds `src/Bridge/TipTap/TipTapBridge.php` (format `html`, default sanitizer `default`), `assets/src/tiptap_controller.ts`, mirrored tests. Estimated 5-6 tasks.
- **Plan D — vvvebjs bridge.** Adds `src/Bridge/Vvveb/VvvebBridge.php` (format `vvveb-html`, default sanitizer `null`), `src/Renderer/VvvebRenderer.php` (iframe-wrapped page HTML), `assets/src/vvveb_controller.ts`, mirrored tests. Estimated 6-7 tasks.

Each plan ships in isolation: install Plan A, then add Plan B/C/D as a separate composer/npm peer + DI tag. The Plan A contract does not change.

## Self-review notes

- **Spec coverage:** every section of the spec maps to one or more tasks: Decisions D1-D6 → architecture chosen in Task 1 (composer.json) + the data flow tests; `EditorContent` → Task 2; normalizer → Task 3; transformer → Task 4; bridge contract → Task 5; sanitizer pipeline → Tasks 6-7 + Task 21 wiring; FormType → Task 8; Quill bridge (PHP) → Task 9; Doctrine type → Task 10; renderer interface + html/markdown renderers + Twig component → Tasks 11-14 + Task 20 strategy config; Live Component hydration → Task 15; abstract Stimulus → Task 16; Quill Stimulus + browser test → Tasks 17-18; end-to-end → Task 19; bundle config + asset registration + docs + CI → Tasks 20-24.
- **Out-of-scope items in the spec** (in-editor uploads, real-time collab, EditorJS custom blocks, SSR previews, vvveb template editor) are not addressed by any task — that is intentional and matches the spec.
- **Open questions in the spec** (validation constraints, `debug:ux:editor` CLI, Markdown extension hooks) are not addressed — these were explicitly punted to v1.1 and are out of scope for Plan A.
- **Type consistency:** `EditorContent` constructor signature is identical everywhere (`(string $raw, string $format)`); `EditorBridgeInterface` method names match across tasks 5, 8, 9, 21; `notifyChange` on the abstract controller is the single sync entry point used by Quill in task 17; `stimulusController()` returns `symfony--ux-editor--quill` consistently across the bridge, the form view test, and the Stimulus fixture pages.
- **No placeholders:** every step contains the exact code or command an engineer needs to execute. Quill's TypeScript import uses the static `import Quill from 'quill'` form; the Vitest test mocks the `quill` module accordingly.

---

## Monorepo Integration Addendum

The package targets `/Users/hamza/Workspace/ux/src/Editor/` inside the `symfony/ux` monorepo (verified: origin `makraz/ux`, upstream `symfony/ux`; existing packages live at `src/Map/`, `src/Cropperjs/`, etc.). The following deltas apply on top of the standalone plan above. Run every command **from the monorepo root** (`/Users/hamza/Workspace/ux/`) unless otherwise stated; paths inside tasks that say "from package root" mean `src/Editor/`.

### Delta 1 — Task 1 `composer.json`

Use the monorepo profile (matches `src/Map/composer.json`):

```json
{
    "name": "symfony/ux-editor",
    "type": "symfony-bundle",
    "description": "Symfony UX Editor: a polymorphic Symfony Form + Stimulus base for rich-text and block editors (Quill, EditorJS, TipTap, vvvebjs).",
    "keywords": ["symfony-ux", "stimulus", "editor", "wysiwyg", "quill", "editorjs", "tiptap"],
    "homepage": "https://symfony.com",
    "license": "MIT",
    "authors": [{ "name": "Symfony Community", "homepage": "https://symfony.com/contributors" }],
    "autoload": {
        "psr-4": { "Symfony\\UX\\Editor\\": "src/" },
        "exclude-from-classmap": []
    },
    "autoload-dev": {
        "psr-4": { "Symfony\\UX\\Editor\\Tests\\": "tests/" }
    },
    "require": {
        "php": ">=8.4",
        "symfony/form": "^7.4|^8.0",
        "symfony/options-resolver": "^7.4|^8.0",
        "symfony/property-access": "^7.4|^8.0",
        "symfony/serializer": "^7.4|^8.0",
        "symfony/stimulus-bundle": "^2.18.1|^3.0",
        "symfony/ux-twig-component": "^2.18|^3.0",
        "symfony/ux-live-component": "^2.18|^3.0",
        "twig/twig": "^3.0"
    },
    "require-dev": {
        "doctrine/dbal": "^3.0|^4.0",
        "phpunit/phpunit": "^11.1|^12.0",
        "symfony/framework-bundle": "^7.4|^8.0",
        "symfony/html-sanitizer": "^7.4|^8.0",
        "symfony/twig-bridge": "^7.4|^8.0",
        "league/commonmark": "^2.4",
        "zenstruck/browser": "^1.10"
    },
    "suggest": {
        "symfony/html-sanitizer": "Required for HTML-format bridges (Quill, TipTap).",
        "doctrine/dbal": "Required to use the `editor_content` Doctrine column type.",
        "league/commonmark": "Required for the Markdown renderer."
    },
    "conflict": {
        "symfony/ux-twig-component": "<2.21"
    }
}
```

The Symfony license header is **mandatory** on every PHP file in this monorepo (`fabbot.yaml` workflow enforces it); the plan's code blocks already include it.

### Delta 2 — Task 1 `tsconfig.json`

Replace the standalone config with an extension of the monorepo's `tsconfig.package.json`:

```json
{
    "extends": "../../tsconfig.package.json",
    "compilerOptions": {
        "rootDir": "assets/src",
        "outDir": "assets/dist"
    },
    "include": ["assets/src/**/*.ts", "assets/test/**/*.ts"]
}
```

### Delta 3 — Task 1 `package.json`

The monorepo pnpm workspace (`pnpm-workspace.yaml`) already includes `src/*/assets`, so the package's `package.json` lives at `src/Editor/assets/package.json` (matching `src/Map/assets/package.json`). Move the npm config there:

```
src/Editor/
├── composer.json
├── phpunit.dist.xml
├── src/
├── tests/
└── assets/
    ├── package.json           # ← npm config lives here in monorepo packages
    ├── tsconfig.json          # ← from Delta 2
    ├── vitest.config.mjs
    ├── playwright.config.ts
    ├── src/
    ├── test/
    └── dist/                  # gitignored
```

Adjust Task 1 Steps 7-10 paths accordingly: `package.json` → `assets/package.json`, `tsconfig.json` → `assets/tsconfig.json`, etc. The build script `pnpm run build` runs from `src/Editor/assets/`.

### Delta 4 — Task 1 `.gitignore`

The monorepo already has a top-level `.gitignore`. Don't create a package-local one; verify with `git check-ignore src/Editor/assets/dist`. If it isn't ignored, add `src/Editor/assets/dist/` to the **monorepo root** `.gitignore`.

### Delta 5 — Task 1 `LICENSE`, `README.md`, `CHANGELOG.md`

Copy `LICENSE` verbatim from `src/Map/LICENSE`. `README.md` and `CHANGELOG.md` are per-package (matching `src/Map/`).

### Delta 6 — Task 14 + Task 20 template path

The `UXEditorExtension::prepend()` template path uses `\dirname(__DIR__, 2).'/templates'` to point at `src/Editor/templates/` — this still works because `__DIR__` is `src/Editor/src/DependencyInjection/`, two levels up is `src/Editor/`. No change needed.

### Delta 7 — Task 22 `assets/controllers.json`

Same content as in the plan, lives at `src/Editor/assets/controllers.json`. StimulusBundle discovery is unaffected.

### Delta 8 — Task 24 replacement (drop standalone CI)

The monorepo's CI workflows (`.github/workflows/unit-tests.yaml`, `functional-tests.yml`, `browser-tests.yml`, `code-quality.yaml`) discover every package automatically — usually by iterating `src/*/composer.json`. Do **not** create `src/Editor/.github/workflows/test.yml`.

**Replacement task — Splitsh registration:**

- Modify: `/Users/hamza/Workspace/ux/splitsh.json`

Insert into `subtrees`:

```json
"ux-editor": "src/Editor",
```

(Keep alphabetical: between `ux-dropzone` and `ux-icons`.)

- [ ] Step 1: Edit `splitsh.json` to add the entry above.
- [ ] Step 2: Run the existing monorepo's CI commands locally to verify the package is discovered:
    ```bash
    # PHPUnit — from src/Editor/
    ../../vendor/bin/phpunit
    # Code quality — from monorepo root
    vendor/bin/php-cs-fixer fix --dry-run --diff src/Editor/
    ```
    Expected: PHPUnit green; PHP-CS-Fixer either green or auto-fixes header lines.
- [ ] Step 3: Commit
    ```bash
    git add splitsh.json
    git commit -m "chore: register ux-editor in splitsh"
    ```

### Delta 9 — Task 1 dependency install

In the monorepo, `composer install` runs at the root and installs vendor for all sub-packages (each has its own `composer.json` but `vendor/` is shared at root). After scaffolding `src/Editor/composer.json`, run from the monorepo root:

```bash
composer update --working-dir=src/Editor
pnpm install   # from monorepo root; picks up src/Editor/assets via pnpm-workspace
```

### Delta 10 — Test execution paths

All `vendor/bin/phpunit` commands in the plan run from `src/Editor/`. Some monorepo packages use `../../vendor/bin/phpunit` because `vendor/` lives at the monorepo root. Use whichever resolves; both refer to the same binary.

### Delta 11 — PHP-CS-Fixer

The monorepo's root `.php-cs-fixer.dist.php` covers `src/Editor/` automatically. Run `vendor/bin/php-cs-fixer fix src/Editor/` periodically to keep headers and formatting clean. Fabbot CI will reject PRs without the Symfony copyright header on each PHP file.

### Delta 12 — Quill engine resolution

The monorepo's root `package.json` declares `quill` only where used. For the new package the peer dep lives in `src/Editor/assets/package.json`. AssetMapper / Importmap consumers will need to register `quill` themselves (already documented in the plan's README quickstart).

### Summary of effective task count after deltas

Tasks 1-23 from the plan apply with paths rooted at `src/Editor/` (and `assets/package.json` / `assets/tsconfig.json` per Delta 3). **Task 24 is replaced** by Delta 8 (splitsh registration).

Total: 24 tasks (23 unchanged + 1 replaced).
