import {Controller} from '@hotwired/stimulus';

/*
 * Icon grid: clicking a card opens the icon modal; hovering a card shows the
 * icon name in a small bubble (only at the "small" grid size — the large grid
 * already prints the name under each icon).
 *
 * A single shared bubble is positioned via event delegation, so the grid can
 * hold hundreds of cards without one tooltip instance each.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {

    #tip = null;
    #currentCard = null;

    connect() {
        this.element.addEventListener('click', this.onClick, true);
        this.element.addEventListener('mouseover', this.onOver);
        this.element.addEventListener('mouseout', this.onOut);
    }

    disconnect() {
        this.element.removeEventListener('click', this.onClick, true);
        this.element.removeEventListener('mouseover', this.onOver);
        this.element.removeEventListener('mouseout', this.onOut);
        this.#tip?.remove();
        this.#tip = null;
        this.#currentCard = null;
    }

    onOver = (event) => {
        if (document.body.dataset.iconSize !== 'small') {
            return;
        }
        const card = event.target.closest('[data-icon-card]');
        if (!card || card === this.#currentCard) {
            return;
        }
        this.#currentCard = card;

        const tip = this.#ensureTip();
        tip.textContent = card.title;
        tip.style.display = 'block';

        const cardRect = card.getBoundingClientRect();
        const tipRect = tip.getBoundingClientRect();
        const left = cardRect.left + cardRect.width / 2 - tipRect.width / 2;
        const top = cardRect.top - tipRect.height - 6;
        tip.style.transform = `translate3d(${Math.round(left)}px, ${Math.round(top)}px, 0)`;
    };

    onOut = (event) => {
        const card = event.target.closest('[data-icon-card]');
        if (!card || card.contains(event.relatedTarget)) {
            return;
        }
        this.#currentCard = null;
        if (this.#tip) {
            this.#tip.style.display = 'none';
        }
    };

    onClick = (event) => {
        const iconCard = event.target.closest('[data-icon-card]');
        if (!iconCard) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();

        const customEvent = new CustomEvent('Icon:Clicked', {detail: {icon: iconCard.title}, bubbles: true});
        window.dispatchEvent(customEvent);
    };

    #ensureTip() {
        if (!this.#tip) {
            const tip = document.createElement('div');
            tip.setAttribute('role', 'tooltip');
            tip.style.cssText = 'position: fixed; left: 0; top: 0; display: none; z-index: 50;'
                + ' pointer-events: none; white-space: nowrap; max-width: 20rem;'
                + ' padding: 0.375rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; line-height: 1rem;'
                + ' background: var(--color-body-text); color: var(--color-body); will-change: transform;';
            document.body.appendChild(tip);
            this.#tip = tip;
        }
        return this.#tip;
    }
}
