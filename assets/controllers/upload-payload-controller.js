import { Controller } from '@hotwired/stimulus';

/*
 * Shows what a form really sends: the size of each field in the last submission,
 * measured from its FormData.
 *
 * Each size target names a field with data-field (its full name, such as
 * "keep_files_form[upload]"). Sizes survive the page reload of a plain form
 * submission through sessionStorage, keyed by form name.
 */

const format = (bytes) => {
    if (bytes < 1024) return `${bytes} bytes`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['size'];

    connect() {
        this.form = this.element.querySelector('form');

        const stored = this.#read();
        if (null === stored) return;

        this.sizeTargets.forEach((target) => {
            target.textContent = format(stored[target.dataset.field] ?? 0);
            target.dataset.state = 'measured';
        });
    }

    measure(event) {
        if (event.target !== this.form) return;

        const sizes = {};
        for (const [name, value] of new FormData(this.form)) {
            const field = this.sizeTargets.map((target) => target.dataset.field).find((prefix) => name.startsWith(prefix));
            if (!field) continue;
            sizes[field] = (sizes[field] ?? 0) + (value instanceof File ? value.size : new Blob([value]).size);
        }
        this.#write(sizes);
    }

    #key() {
        return `upload-payload:${this.form?.name}`;
    }

    #read() {
        try {
            const value = sessionStorage.getItem(this.#key());
            sessionStorage.removeItem(this.#key());

            return null === value ? null : JSON.parse(value);
        } catch {
            return null;
        }
    }

    #write(sizes) {
        try {
            sessionStorage.setItem(this.#key(), JSON.stringify(sizes));
        } catch {
            // Private mode: the measure is simply not shown.
        }
    }
}
