import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';
import { setupAutosave } from '@symfony/ux-editor/live';

/*
 * Bridges the UX Editor's debounced `ux:editor:change` event to a Live Component
 * action, giving the "autosave + dirty tracking" behaviour without bespoke glue.
 *
 * Place it on the same element as the `live` controller so getComponent() resolves
 * the host component and the bubbling editor event reaches setupAutosave().
 */
export default class extends Controller {
    static values = {
        field: { type: String, default: 'bodyDraft' },
        debounce: { type: Number, default: 800 },
    };

    async connect() {
        const component = await getComponent(this.element);
        this.teardown = setupAutosave(this.element, {
            field: this.fieldValue,
            debounceMs: this.debounceValue,
            dispatch: (field, content) => component.action('autosave', { field, content }),
        });
    }

    disconnect() {
        this.teardown?.();
    }
}
