import {Controller} from '@hotwired/stimulus';

/*
 * Icon grid: clicking a card opens the shared IconModal (a LiveComponent) by
 * dispatching `Icon:Clicked`. The hover name is handled per-card by the
 * <twig:Tooltip> component (see IconGrid.html.twig).
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {

    connect() {
        this.element.addEventListener('click', this.onClick, true);
    }

    disconnect() {
        this.element.removeEventListener('click', this.onClick, true);
    }

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
}
