import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menuButton'];

    initialize() {
        this.open = false;
    }

    connect() {
        document.body.classList.remove('overflow-y-hidden');
    }

    disconnect() {
        clearTimeout(this.resizeTimeout);
        document.body.classList.remove('overflow-y-hidden');
    }

    menuButtonDisconnected() {
        document.body.classList.remove('overflow-y-hidden');
    }

    toggleMenu() {
        this.open = !this.open;
        this.element.classList.toggle('open', this.open);
        document.body.classList.toggle('overflow-y-hidden', this.open);
    }

    // Crossing a breakpoint would otherwise play the open/close transitions of the mobile menu
    suspendTransitions() {
        this.element.classList.add('resizing');
        clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(() => this.element.classList.remove('resizing'), 100);
    }

    closeMenuOnDesktop() {
        if (this.open && this.menuButtonTarget.offsetParent === null) {
            this.toggleMenu();
        }
    }
}
