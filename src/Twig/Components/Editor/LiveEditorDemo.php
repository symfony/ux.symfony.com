<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Editor;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\UX\Editor\Bridge\CKEditor\Config\CKEditorConfig;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Form\EditorType;
use Symfony\UX\Editor\Live\LiveEditor;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('Editor:LiveEditorDemo')]
final class LiveEditorDemo
{
    use DefaultActionTrait;
    use LiveEditor;

    #[LiveProp(writable: true)]
    public bool $readOnly = false;

    #[LiveProp(writable: true)]
    public string $bodyDraft = '<p>Edit me…</p>';

    private FormFactoryInterface $formFactory;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;

        // In-memory repo for the demo (host's saveDraft trait expects ->upsert).
        $this->draftRepo = new class {
            public array $store = [];

            public function upsert(string $id, string $field, mixed $content): void
            {
                $this->store[$id][$field] = $content;
            }
        };
    }

    public function getEntityId(): string { return 'demo-1'; }

    public function getConfig(): EditorConfigInterface
    {
        return new CKEditorConfig(
            common: new CommonOptions(
                toolbar: ['bold', 'italic', 'link'],
                readOnly: $this->readOnly,
                placeholder: 'Edit me…',
            ),
            licenseKey: 'GPL',
        );
    }

    public function getEditorFormWidget(): FormView
    {
        $form = $this->formFactory->createNamed('demo_live', EditorType::class, new HtmlContent($this->bodyDraft), [
            'config' => $this->getConfig(),
            'sanitize' => false,
            'data_class' => null,
        ]);

        return $form->createView();
    }

    #[LiveAction]
    public function toggleReadOnly(): void
    {
        $this->readOnly = !$this->readOnly;
    }

    /**
     * Autosave entry point invoked from the editor-autosave Stimulus controller,
     * which debounces the editor's `ux:editor:change` events. Persists the draft
     * (via the LiveEditor trait) and keeps the bodyDraft prop in sync so the form
     * re-renders with the saved content.
     */
    #[LiveAction]
    public function autosave(#[LiveArg] string $field, #[LiveArg] string $content): void
    {
        $this->bodyDraft = $content;
        $this->saveDraft($field, $content);
    }
}
