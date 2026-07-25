import { Controller } from '@hotwired/stimulus';

const MIN_WIDTH = 280;
const ZOOM_STEP = 0.1;
const ZOOM_MIN = 0.25;
const ZOOM_MAX = 2;

// Turns the preview iframe into a small sandbox: device presets, zoom, grid
// overlay and a drag handle. Width holds the *emulated* viewport width in px
// (0 = fluid, fills the available space); the visual box is that width scaled
// by zoom, so an oversized device just scrolls horizontally.
export default class extends Controller {
    static targets = ['viewport', 'stage', 'frame', 'handle', 'zoomLabel', 'grid', 'gridBtn', 'preset'];
    static values = {
        width: { type: Number, default: 0 },
        zoom: { type: Number, default: 1 },
        grid: Boolean,
        baseHeight: String,
    };

    connect() {
        this.observer = new ResizeObserver(() => {
            if (this.widthValue === 0) this.#render();
        });
        this.observer.observe(this.viewportTarget);
        this.#render();
    }

    disconnect() {
        this.observer?.disconnect();
        this.contentObserver?.disconnect();
        this.dragAbort?.abort();
        cancelAnimationFrame(this._heightFrame);
    }

    setWidth({ params: { width } }) {
        this.widthValue = Number(width);
    }

    zoomIn() {
        this.zoomValue = Math.min(ZOOM_MAX, this.zoomValue + ZOOM_STEP);
    }

    zoomOut() {
        this.zoomValue = Math.max(ZOOM_MIN, this.zoomValue - ZOOM_STEP);
    }

    toggleGrid() {
        this.gridValue = !this.gridValue;
    }

    startResize(event) {
        event.preventDefault();

        // Anchor to the fixed centre of the viewport: width grows/shrinks
        // symmetrically so the stage stays centred while dragging, and the
        // handle (its right edge) tracks the pointer without feedback.
        this._centerX = this.#viewportCenterX();
        this.viewportTarget.classList.add('is-resizing');

        this.handleTarget.setPointerCapture(event.pointerId);
        this.dragAbort = new AbortController();
        const { signal } = this.dragAbort;
        this.handleTarget.addEventListener('pointermove', (e) => this.#resizeMove(e), { signal });
        this.handleTarget.addEventListener('pointerup', (e) => this.#resizeEnd(e), { signal });
    }

    widthValueChanged() { this.#render(); }
    zoomValueChanged() { this.#render(); }
    gridValueChanged() { this.#render(); }

    // Once the (same-origin) preview document is available, keep watching its
    // content size so interactive demos that change height on their own (an
    // accordion opening, a toggle expanding) stay fully visible, then measure.
    frameLoaded() {
        this.contentObserver?.disconnect();
        try {
            const doc = this.frameTarget.contentDocument;
            const body = doc?.body;
            if (body) {
                // Padding is constant, so cache it here rather than reading it on
                // every measure (the drag path runs #contentHeight each frame).
                const style = doc.defaultView.getComputedStyle(body);
                this.bodyPadding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);

                this.contentObserver = new ResizeObserver(() => this.#applyHeight());
                for (const child of body.children) this.contentObserver.observe(child);
            }
        } catch {
            // cross-origin: content height stays fixed to the base height
        }
        this.#render();
    }

    #resizeMove(event) {
        const half = event.clientX - this._centerX;
        this.widthValue = Math.max(MIN_WIDTH, Math.round((half * 2) / this.zoomValue));
    }

    #resizeEnd(event) {
        this.handleTarget.releasePointerCapture(event.pointerId);
        this.dragAbort.abort();
        this.viewportTarget.classList.remove('is-resizing');
    }

    #viewportCenterX() {
        const rect = this.viewportTarget.getBoundingClientRect();
        return rect.left + this.viewportTarget.clientWidth / 2;
    }

    #render() {
        const zoom = this.zoomValue;
        const emWidth = this.widthValue > 0 ? this.widthValue : this.#availableWidth();

        this.frameTarget.style.width = `${emWidth}px`;
        this.frameTarget.style.transform = `scale(${zoom})`;
        this.frameTarget.style.transformOrigin = 'top left';
        this.stageTarget.style.width = `${emWidth * zoom}px`;

        this.zoomLabelTarget.textContent = `${Math.round(zoom * 100)}%`;

        this.gridTarget.hidden = !this.gridValue;
        this.gridBtnTarget.setAttribute('aria-pressed', String(this.gridValue));

        this.presetTargets.forEach((btn) => {
            const active = Number(btn.dataset.previewViewportWidthParam) === this.widthValue;
            btn.setAttribute('aria-pressed', String(active));
        });

        // Height depends on the iframe content reflowing to the new width, which
        // isn't reliably synchronous — measure and apply it on the next frame.
        cancelAnimationFrame(this._heightFrame);
        this._heightFrame = requestAnimationFrame(() => this.#applyHeight());
    }

    #applyHeight() {
        const height = this.#contentHeight();
        this.frameTarget.style.height = `${height}px`;
        this.stageTarget.style.height = `${height * this.zoomValue}px`;
    }

    #availableWidth() {
        return Math.max(MIN_WIDTH, this.viewportTarget.clientWidth);
    }

    // Grow the frame to the preview's natural height when the content reflows
    // taller than the configured base height (e.g. narrowed to mobile), so it
    // never gets clipped. Measures the content element rather than body
    // scrollHeight, which the body's flex-centering under-reports. Falls back to
    // the base height before load or if the document is unreachable (cross-origin).
    #contentHeight() {
        const baseHeight = parseFloat(this.baseHeightValue) || 0;
        try {
            const body = this.frameTarget.contentDocument?.body;
            if (!body || !body.children.length) return baseHeight;

            let content = 0;
            for (const child of body.children) content = Math.max(content, child.offsetHeight);

            // Keep the body's own padding (cached at load) visible around the
            // content — the body is border-box, so its padding eats into the
            // configured height. Never shrink below the configured base height.
            return Math.max(baseHeight, Math.ceil(content + (this.bodyPadding || 0)));
        } catch {
            return baseHeight;
        }
    }
}
