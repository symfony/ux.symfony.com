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
}
