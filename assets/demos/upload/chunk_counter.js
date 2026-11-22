export class ChunkCounter {
    sent = 0;
    total = 0;

    reset() {
        this.sent = 0;
        this.total = 0;
    }

    record(totalChunks) {
        this.sent++;
        this.total = totalChunks;
    }

    progressLabel() {
        return `${this.sent} of ${this.total} chunks sent from this page`;
    }

    summaryLabel() {
        if (0 === this.total) return null;

        const reused = this.total - this.sent;

        return reused > 0
            ? `Resumed: ${reused} of ${this.total} chunks were already on the server.`
            : `All ${this.total} chunks sent. Reload during the next transfer to see a resume.`;
    }
}
