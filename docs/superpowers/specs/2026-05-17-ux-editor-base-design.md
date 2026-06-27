# `symfony/ux-editor` — Design

- **Status**: Draft — pending review
- **Date**: 2026-05-17
- **Author**: Hamza Makraz (brainstorm with Claude Code)
- **Target package**: `symfony/ux-editor` (new, to live in the `symfony/ux` monorepo)
- **Companion v1 bridges**: Quill, EditorJS, TipTap, vvvebjs

## Summary

`symfony/ux-editor` is a new Symfony UX package that wraps multiple JS editor engines behind a single PHP and Stimulus contract. Following the precedent set by `symfony/ux-map` (one core package, one bridge per renderer, abstract Stimulus base), the package ships:

- A polymorphic content value object (`EditorContent { raw: string, format: string }`) used end-to-end (form value, Doctrine column, Twig render input).
- A `EditorBridgeInterface` registry plus four reference bridges (Quill, EditorJS, TipTap, vvvebjs) inside the core repo.
- A single `EditorType` Symfony Form Type taking a `bridge` option, integrated with `symfony/html-sanitizer` for HTML-format bridges, with sanitization on by default.
- An abstract Stimulus controller that each bridge extends to wire the engine to a hidden input and to dispatch `input` events for first-class Live Component support.
- A Twig component `<twig:ux:editor:render>` plus per-format renderers for safe display, and a Doctrine type `editor_content` for JSON-backed storage.

## Motivation

Symfony UX already publishes wrappers for individual editor-adjacent libraries (`ux-cropperjs`, `ux-dropzone`, `ux-autocomplete`) and one true multi-bridge package (`ux-map` with Leaflet + Google). It does not publish a rich-text/block editor wrapper. Apps that want Quill, EditorJS, TipTap, or vvvebjs today must build their own Stimulus glue, FormType, sanitizer wiring, and storage decisions, and rebuild that glue for every editor they touch.

A "base editor" package that mirrors `ux-map`'s shape — single contract, multiple bridges, opinionated form integration — turns those decisions into a one-line `EditorType::class` call regardless of which engine the app uses, and makes the choice of engine swappable without rewriting the consumer code.

## Decisions

The following decisions are locked. Each is the answer to a brainstorming question and is what every subsequent section assumes.

| #   | Decision                                                                                                                                                                                   | Rationale                                                                                                                                                                                                                                            |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| D1  | Polymorphic content API: `EditorContent { raw: string, format: string }` with `format` as a free-form string (well-known: `html`, `json`, `markdown`; bridge-defined: `vvveb-html`, etc.). | App code that does not care about the engine stays portable. Free-form `format` is required so whole-page builders (vvvebjs) can declare their own dialect.                                                                                          |
| D2  | Full server-side pipeline: form value + sanitization + Twig render component + Doctrine type all in core.                                                                                  | Safe-by-default storage and display. Each piece is opt-in at the boundary (`'sanitizer' => false`, custom Doctrine column, render component skipped) so the package is not all-or-nothing.                                                           |
| D3  | v1 bridges (in monorepo): Quill (HTML), EditorJS (JSON), TipTap (HTML), vvvebjs (page HTML).                                                                                               | Validates the contract against three distinct content shapes (inline HTML fragment, JSON block tree, full-page HTML) before release. Mirrors `ux-map` shipping Leaflet + Google in `src/Bridge/`.                                                    |
| D4  | Client architecture: abstract Stimulus base + per-bridge concrete controllers.                                                                                                             | Direct mirror of `ux-map`'s `abstract_map_controller.ts`. Each bridge owns its lifecycle; the base owns hidden-input sync and the Live Component `input` event.                                                                                      |
| D5  | Single `EditorType` with required `bridge` option (no per-bridge subclasses in v1).                                                                                                        | One entry point, one mental model. Per-bridge options are validated by the bridge's own `OptionsResolver` configuration, so app code still gets typo protection.                                                                                     |
| D6  | Approach A — safe-by-default + peer-dep engines + Live Component in core.                                                                                                                  | Sanitizer ON by default for HTML bridges; engine npm packages are peer deps so the package does not lock-step upstream releases; Live Component glue lives in core because the showcase site (and most apps) consume editors inside Live Components. |

## Architecture

A single Composer package `symfony/ux-editor` with a `Symfony\UX\Editor\` PSR-4 root, holding interfaces, the registry, the FormType, sanitizer dispatch, Doctrine type, Twig render component, per-format renderers, and four bridge directories (`Bridge/Quill`, `Bridge/EditorJs`, `Bridge/TipTap`, `Bridge/Vvveb`). Client-side assets live under `assets/src/` with the abstract base controller plus one concrete controller per bridge. Engine npm packages (`quill`, `@editorjs/editorjs`, `@tiptap/core`, `vvvebjs`) are peer dependencies of the asset package; each bridge declares the supported range.

Optional thin wrapper packages (`symfony/ux-quill-editor`, `symfony/ux-editorjs-editor`, `symfony/ux-tiptap-editor`, `symfony/ux-vvveb-editor`) can publish later as Composer convenience installers, the same way `symfony/ux-leaflet-map` wraps `ux-map`'s Leaflet bridge. They are out of scope for v1.

### Bridge defaults at a glance

The per-bridge `EditorBridgeInterface` implementation pins these values; `EditorType` and the renderer registry both key off them.

| Bridge   | `id()`     | Stimulus controller name | `format()`   | `defaultSanitizer()`                                       | Engine peer dep                       |
| -------- | ---------- | ------------------------ | ------------ | ---------------------------------------------------------- | ------------------------------------- |
| Quill    | `quill`    | `ux-editor--quill`       | `html`       | `'default'`                                                | `quill`                               |
| EditorJS | `editorjs` | `ux-editor--editorjs`    | `json`       | `null` (renderer escapes per-block)                        | `@editorjs/editorjs` + standard tools |
| TipTap   | `tiptap`   | `ux-editor--tiptap`      | `html`       | `'default'`                                                | `@tiptap/core` + starter kit          |
| vvvebjs  | `vvveb`    | `ux-editor--vvveb`       | `vvveb-html` | `null` (whole-page HTML; generic sanitizer would shred it) | `vvvebjs`                             |

The Stimulus controller name follows the AssetMapper / `stimulus-bundle` convention of `package--controller`; this is the value used as the `data-controller` attribute and as the value-attribute prefix (`data-ux-editor--quill-options-value`).

## Components / Units

```
src/
├── UXEditorBundle.php
├── DependencyInjection/
│   └── UXEditorExtension.php           # bundle config: default bridge, sanitizer profiles, renderer overrides
├── Content/
│   ├── EditorContent.php               # immutable VO: __construct(string $raw, string $format)
│   ├── EditorContentNormalizer.php     # Serializer normalizer ↔ array{raw, format}
│   └── DataTransformer.php             # Form DataTransformer string ↔ EditorContent
├── Bridge/
│   ├── EditorBridgeInterface.php       # id(), format(), defaultSanitizer(), configureOptions(), assetModule()
│   ├── EditorBridgeRegistry.php        # service locator tagged with 'ux_editor.bridge'
│   ├── Quill/QuillBridge.php
│   ├── EditorJs/EditorJsBridge.php
│   ├── TipTap/TipTapBridge.php
│   └── Vvveb/VvvebBridge.php
├── Form/
│   └── EditorType.php                  # extends TextType; required 'bridge' option; renders hidden input + Stimulus attrs
├── Sanitizer/
│   ├── SanitizerDispatcher.php         # resolves: form opt → bridge default → registered profile → noop
│   └── HtmlSanitizerProfileLoader.php  # reads bundle config for named profiles
├── Doctrine/
│   └── EditorContentType.php           # Doctrine Type 'editor_content' → JSON column {raw, format}
├── Twig/
│   ├── Components/Render.php           # <twig:ux:editor:render :content :options>
│   └── EditorRendererRegistry.php      # tag 'ux_editor.renderer' resolves by format
├── Renderer/
│   ├── EditorRendererInterface.php     # supports(format):bool, render(EditorContent, options):string
│   ├── HtmlRenderer.php
│   ├── EditorJsRenderer.php
│   ├── MarkdownRenderer.php
│   └── VvvebRenderer.php               # emits sandboxed iframe for whole-page HTML
├── Live/
│   └── EditorContentHydrationExtension.php  # LiveComponent hydration for EditorContent
└── Exception/
    ├── UXEditorExceptionInterface.php
    ├── UnknownBridgeException.php
    ├── SanitizerProfileNotFoundException.php
    └── EditorRenderException.php

assets/src/
├── abstract_editor_controller.ts       # Stimulus base; lifecycle mount/serialize/destroy
├── quill_controller.ts
├── editorjs_controller.ts
├── tiptap_controller.ts
└── vvveb_controller.ts
```

Unit responsibilities (one line each — what it does / how it is used / what it depends on):

- **`EditorContent`** — immutable `{raw, format}` VO; built by transformer/Doctrine; depends only on PHP stdlib.
- **`EditorBridgeInterface`** — server-side bridge contract; implemented by each bridge; no other deps.
- **`EditorBridgeRegistry`** — service locator of bridges; consumed by `EditorType` and `SanitizerDispatcher`.
- **`EditorType`** — Form type taking `bridge` option; orchestrates bridge lookup, options merge, hidden input + Stimulus attribute rendering; delegates coercion to `DataTransformer`.
- **`SanitizerDispatcher`** — single entry point that runs sanitization on submit; depends on `symfony/html-sanitizer` (soft suggest) and the bridge's declared default profile.
- **`EditorContentType`** — Doctrine JSON column type; consumes `EditorContentNormalizer`.
- **`EditorRendererInterface` + registry** — display side; `<twig:ux:editor:render>` asks the registry for a renderer matching `content.format` and falls back per §Error Handling.
- **`AbstractEditorController` (TS)** — Stimulus base; subclasses implement `mountEditor`, `serializeEditor`, `destroyEditor`; base owns hidden-input sync, the `input` event, and lifecycle.
- **Each `<engine>_controller.ts`** — knows nothing about peers; depends on its peer npm module only.
- **`EditorContentHydrationExtension`** — hydrates `EditorContent` as a Live Component prop, mirroring `GameHydrationExtension` in `ux.symfony.com`'s `LiveMemory` module.

The boundary that matters most: a bridge MUST be implementable without touching any other bridge. Every shared concern (hidden-input wiring, sanitizer dispatch, render dispatch, hydration) lives in the core, not in the bridges.

## Data Flow

### 1. Render flow (server → DOM, form rendered)

```
EditorType::buildView()
  resolves $bridge = $registry->get($options['bridge'])
  $bridge->configureOptions() merged into $options['bridge_options']
  $view->vars['stimulus_controller'] = $bridge->id()           # e.g. "ux-editor--quill"
  $view->vars['stimulus_values']     = {bridge, format, options}
  template renders <input type="hidden"
                          data-controller="ux-editor--quill"
                          data-ux-editor--quill-options-value='{"toolbar":[...]}'
                          value="<raw>">
Stimulus connects in browser:
  AbstractEditorController.connect()
    calls subclass.mountEditor(host, hiddenInput, JSON.parse(options))
    subclass attaches engine, wires "change" listener →
      hiddenInput.value = serializeEditor()
      hiddenInput.dispatchEvent(new Event('input'))
```

### 2. Submit flow (DOM → model)

```
Form submits → hidden input string lands at DataTransformer::reverseTransform($string)
  JSON-decode if format != 'html'/'markdown' else use raw string
  produce EditorContent($raw, $format)
SanitizerDispatcher::sanitize(EditorContent, $options['sanitizer'], $bridge->defaultSanitizer())
  option === false                                 → as-is
  option === string (profile)                      → resolve profile, sanitize raw, return new EditorContent
  option === null && bridge default === null       → as-is (e.g. vvveb)
  option === null && bridge default === 'default'  → use default profile
  option === closure                               → invoke with raw, return new EditorContent
Resulting EditorContent reaches bound model property.
If property is typed EditorContent and column type is 'editor_content':
  Doctrine type writes JSON {raw, format}.
```

### 3. Display flow (storage → page)

```
<twig:ux:editor:render :content="post.body" />
  Render::mount() asks EditorRendererRegistry for a renderer matching content.format
  renderer->render($content, $options):
    HtmlRenderer       → echoes $content->raw verbatim (already sanitized on submit)
    EditorJsRenderer   → walks blocks; emits HTML per block plugin (paragraph/header/list/image/code/quote)
    MarkdownRenderer   → CommonMark → HTML
    VvvebRenderer      → <iframe srcdoc="..." sandbox="allow-same-origin"> wrapping page HTML
    no renderer        → see §Error Handling 4
```

### 4. Live Component overlay on flow 1

When `EditorType` is rendered inside a Live Component, Symfony Form's renderer already places `data-action="input->live#update"` and a `data-model` attribute on the hidden input. The abstract Stimulus controller dispatches the `input` event whenever `serializeEditor()` produces a new value, which triggers Live's standard debounced model sync. No bridge-side glue is required; the contract is: keep the hidden input's `value` fresh and dispatch `input` on change.

## Error Handling

### Configuration errors (caught at boot / form build)

- Unknown bridge id in `EditorType` `'bridge'` option → `UnknownBridgeException extends InvalidOptionsException`, includes registered bridge ids.
- Sanitizer profile not registered → `SanitizerProfileNotFoundException`, includes known profiles. Resolved eagerly at form build, not on submit.
- Bridge declares a format with no renderer → see Display errors.

### Client-side runtime errors

- Engine peer dep missing → AssetMapper fails to resolve the engine module; the base wraps `mountEditor` in try/catch, renders a fallback `<textarea>` into the host element, and dispatches `ux:editor:mount-failed`. The hidden input keeps the prior value so form submission still works.
- `serializeEditor()` throws → base catches, leaves hidden input untouched, dispatches `ux:editor:serialize-failed`. Submission proceeds with the last good value.
- Hidden input target missing at connect → throw (this is a dev-time wiring bug, not a runtime user state).

### Submission / sanitization errors

- HTML bridge raw is not valid UTF-8 → `TransformationFailedException` (translation key `editor.invalid_encoding`).
- JSON-format bridge gets non-JSON payload (tampered submit) → `TransformationFailedException` (`editor.invalid_payload`).
- HTML sanitizer throws (misconfigured profile) → propagate (no swallowing); Symfony Form maps it to a generic transformation error in prod.
- Doctrine `editor_content` decode failure → `ConversionException` containing the first 80 chars of the value.

### Display errors

- Renderer missing for declared format → in dev, `EditorRenderException` listing registered renderers; in prod, escape `$content->raw` to plain text (configurable: `ux_editor.render.unknown_format_strategy: throw|escape`). Never silently output raw HTML.
- Renderer throws mid-render → caught by the Twig component; escape to plain text; log at error level with bridge id and exception.

### Cross-cutting

- Sanitization is never silently downgraded: if sanitization is requested and fails, the value is rejected. The display path relies on "stored = sanitized" so it must hold.
- All exceptions implement `UXEditorExceptionInterface`.

## Testing

### PHP — PHPUnit

Unit (no Symfony kernel):

- `EditorContent` — equality, immutability, JSON round-trip, free-form `format` strings.
- `EditorContentNormalizer` — symmetric normalize/denormalize, missing/extra keys, unicode/binary `raw`.
- `EditorBridgeRegistry` — happy path, unknown id throws with bridge list, duplicate id is a boot-time error.
- `DataTransformer` — string ↔ `EditorContent` for `html`/`json`/`markdown`/custom; `null` and empty string; UTF-8 rejection; malformed JSON rejection.
- `SanitizerDispatcher` — every branch in §Submit flow, using an in-memory `HtmlSanitizer` double.
- `HtmlSanitizerProfileLoader` — config → profile map; unknown profile exception.
- `EditorContentType` (Doctrine) — `convertToDatabaseValue` / `convertToPHPValue`; null handling; decode-error path.
- Each `*Bridge` — `configureOptions()` rejects unknown keys and applies defaults; `defaultSanitizer()` returns the documented value.
- Each `*Renderer` — golden-file: input `EditorContent` → expected HTML output. Covers EditorJS dialect (paragraph, header, list, image, code, quote) and Markdown edge cases.

Functional (Symfony kernel):

- `EditorType` end-to-end per bridge: create form, submit serialized payload, assert sanitization happened, assert bound model holds the correct `EditorContent`.
- `<twig:ux:editor:render>` component against each format; assert HTML matches the matching renderer's golden output.
- Live Component integration: tiny LiveComponent fixture wrapping `EditorType`; assert hydration round-trips an `EditorContent` via `EditorContentHydrationExtension`.

### JS — Vitest + Playwright

Unit (Vitest), mirrors `ux-dropzone/assets/test/unit/controller.test.ts`:

- `AbstractEditorController` — connect/disconnect calls `mountEditor`/`destroyEditor`; `change` listener wires correctly; `serializeEditor` failure does not crash; `mountEditor` failure renders fallback textarea and dispatches `ux:editor:mount-failed`.
- Each bridge controller — mock the engine module; assert the controller calls expected engine APIs and that `serializeEditor` returns the right shape.

Browser (Playwright), mirrors `ux-dropzone/assets/test/browser/`:

- One spec per bridge, against a static fixture page. For each: mount, type/insert content, assert hidden input value updates and `input` event fires, disconnect cleans up.
- vvveb spec also asserts the iframe sandbox is created and the host element does not leak global styles.

### Deliberately not tested

- Third-party engines' own behaviour (e.g. "Quill renders bold" — Quill's repo).
- Sanitizer policy exhaustiveness (covered by `symfony/html-sanitizer`).
- In-editor network/upload pipelines (out of v1 scope).

### CI

`phpunit.dist.xml` per package; GitHub workflow matches the UX monorepo's existing `ux-map` workflow (PHP 8.2+, Symfony LTS + stable + dev-main). JS tests run via `pnpm test` in `assets/` per existing UX conventions.

## Out of Scope (v1)

- In-editor file/image upload pipelines. Bridges may add their own upload hooks later but core does not define an upload contract.
- Real-time collaborative editing (CRDT/OT, Y.js, etc.).
- Custom block plugins for EditorJS beyond the standard set (paragraph, header, list, image, code, quote).
- Server-side rendering of editor previews for SSR/SEO purposes; v1 renders at request time on Twig render.
- A WYSIWYG schema editor for vvvebjs templates; bridge consumes user-supplied templates only.

## Open Questions / Future Work

- Should bridges be able to declare server-side **validation constraints** keyed off `format`? (e.g. max blocks for EditorJS, max raw byte size for HTML.) Punted to v1.1 because constraints are a clean additive extension to `EditorType`.
- Should the package ship a CLI command `bin/console ux:editor:debug` listing registered bridges, renderers, and profiles? Mirrors `debug:autowiring` precedent; small enough to add in v1 if there is time, but not load-bearing.
- Should `MarkdownRenderer` accept extension hooks (League CommonMark extensions) via DI config? Likely yes, deferred to a follow-up to keep v1 scope tight.

## Glossary

- **Bridge** — a server-side `EditorBridgeInterface` implementation plus its matching client-side Stimulus controller and engine npm peer dep. v1 bridges: Quill, EditorJS, TipTap, vvvebjs.
- **Format** — a free-form string identifying the on-disk shape of `EditorContent.raw`. Well-known values: `html`, `json`, `markdown`. Bridge-defined values: `vvveb-html`, etc.
- **Sanitizer profile** — a named `symfony/html-sanitizer` configuration. Each bridge declares its default profile (or `null` to skip sanitization); `EditorType` `'sanitizer'` option overrides per field.
- **Renderer** — an `EditorRendererInterface` service that turns one `EditorContent` format into safe HTML for display. Resolved via `EditorRendererRegistry` keyed on `format`.
