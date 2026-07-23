import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['frame', 'loader'];
    static values = { src: String };

    // Load the preview only once it scrolls into view, so time-based demos
    // (auto-close, delayed countdown) start their timers when the user is
    // actually looking at them rather than while still below the fold.
    connect() {
        this.observer = new IntersectionObserver((entries) => this.#onIntersect(entries));
        this.observer.observe(this.element);
    }

    disconnect() {
        this.observer?.disconnect();
    }

    // Re-run the demo on demand (the frames load once, so this replays
    // time-based examples that have already finished).
    reload() {
        this.#start();
    }

    loaded() {
        this.loaderTarget.style.display = 'none';
        this.frameTarget.removeAttribute('data-loading');
    }

    #onIntersect(entries) {
        if (!entries.some((entry) => entry.isIntersecting)) {
            return;
        }

        this.#start();
        this.observer.disconnect();
    }

    #start() {
        this.loaderTarget.style.display = '';
        this.frameTarget.setAttribute('data-loading', '');
        this.frameTarget.src = this.srcValue;
    }
}
