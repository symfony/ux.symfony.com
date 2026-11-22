import { Controller } from '@hotwired/stimulus';
import { ChunkCounter } from '../demos/upload/chunk_counter.js';

/*
 * Counts the chunks this page sends, from the upload field events. When a
 * transfer resumes after a reload, fewer chunks than the total go out: the
 * others were already on the server.
 */

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['sent', 'summary'];

    connect() {
        this.counter = new ChunkCounter();
    }

    start() {
        this.counter.reset();
        this.sentTarget.textContent = 'No chunk sent yet.';
        this.summaryTarget.hidden = true;
    }

    chunk(event) {
        this.counter.record(event.detail.totalChunks);
        this.sentTarget.textContent = this.counter.progressLabel();
    }

    complete() {
        const label = this.counter.summaryLabel();
        if (null === label) return;

        this.summaryTarget.hidden = false;
        this.summaryTarget.textContent = label;
    }
}
