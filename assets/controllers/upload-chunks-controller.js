import { Controller } from '@hotwired/stimulus';

/*
 * Counts the chunks this page sends, from the upload field events. When a
 * transfer resumes after a reload, fewer chunks than the total go out: the
 * others were already on the server.
 */

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['sent', 'summary'];

    connect() {
        this.sent = 0;
        this.total = 0;
    }

    chunk(event) {
        this.sent++;
        this.total = event.detail.totalChunks;
        this.sentTarget.textContent = `${this.sent} of ${this.total} chunks sent from this page`;
    }

    complete() {
        if (0 === this.total) return;

        const reused = this.total - this.sent;
        this.summaryTarget.hidden = false;
        this.summaryTarget.textContent =
            reused > 0
                ? `Resumed: ${reused} of ${this.total} chunks were already on the server.`
                : `All ${this.total} chunks sent. Reload during the next transfer to see a resume.`;
    }
}
