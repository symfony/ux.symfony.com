import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['delivered'];

    connect() {
        this.image = this.element.querySelector('img');
        this.measure = this.measure.bind(this);
        this.image.addEventListener('load', this.measure);

        if (this.image.complete && this.image.naturalWidth) {
            this.measure();
        }
    }

    disconnect() {
        this.image.removeEventListener('load', this.measure);
    }

    async measure() {
        const url = this.image.currentSrc;
        const entry = performance.getEntriesByName(url).at(-1);
        let type = entry?.contentType;
        let bytes = entry?.encodedBodySize;

        if (!type || !bytes) {
            const response = await fetch(url, {
                method: 'HEAD',
                headers: { Accept: 'image/avif,image/webp,image/*,*/*;q=0.8' },
            }).catch(() => null);
            type ||= response?.headers.get('content-type');
            bytes ||= Number(response?.headers.get('content-length'));
        }

        if (!type || !bytes) {
            return;
        }

        const weight = bytes >= 1000000 ? `${(bytes / 1000000).toFixed(1)} MB` : `${Math.round(bytes / 1000)} KB`;
        this.deliveredTarget.textContent = `${weight} ${type.replace('image/', '').toUpperCase()}`;
    }
}
