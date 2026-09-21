import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['useStatements', 'expandCodeButton', 'codeContent'];

    connect() {
        if (this.hasExpandCodeButtonTarget && !this.#isOverflowing(this.codeContentTarget)) {
            this.expandCodeButtonTarget.remove();
        }
    }

    expandUseStatements(event) {
        this.useStatementsTarget.style.display = 'block';
        event.currentTarget.remove();
    }

    expandCode(event) {
        this.codeContentTarget.style.height = 'auto';
        // the height cap can also sit on the .Terminal wrapper, lift it too
        this.element.closest('.Terminal')?.style.setProperty('max-height', 'none');
        if (this.hasExpandCodeButtonTarget) {
            this.expandCodeButtonTarget.remove();
        }
    }

    #isOverflowing(element) {
        return element.scrollHeight > element.clientHeight;
    }
}
