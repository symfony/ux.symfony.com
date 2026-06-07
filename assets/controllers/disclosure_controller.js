import { Controller } from '@hotwired/stimulus';

const HIDDEN_CLASS = 'hidden';
const OPEN_CLASS = 'is-open';
const BODY_LOCK_CLASS = 'locked';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['panel'];
    static values = {
        open: { type: Boolean, default: false },
        lockBody: { type: Boolean, default: false },
        portal: { type: Boolean, default: false },
        transitionDuration: { type: Number, default: 200 },
    };

    connect() {
        this.boundEscape = this.handleEscape.bind(this);
        this.boundClickOutside = this.handleClickOutside.bind(this);

        if (this.hasPanelTarget) {
            this.panel = this.panelTarget;

            if (this.portalValue) {
                this.portalAnchor = document.createComment('disclosure-portal-anchor');
                this.panel.parentNode.insertBefore(this.portalAnchor, this.panel);
                document.body.appendChild(this.panel);

                // Stimulus actions inside the panel no longer resolve to this controller
                // (the panel is no longer a descendant of this.element). Delegate clicks
                // for elements marked with `data-action="...disclosure#close"`.
                this.portalClickHandler = (event) => {
                    if (event.target.closest('[data-action*="disclosure#close"]')) {
                        this.setOpen(false);
                    }
                };
                this.panel.addEventListener('click', this.portalClickHandler);
            }
        }
    }

    disconnect() {
        this.removeListeners();
        if (this.hideTimer) {
            clearTimeout(this.hideTimer);
        }
        if (this.lockBodyValue && this.openValue) {
            document.body.classList.remove(BODY_LOCK_CLASS);
        }
        if (this.panel && this.portalClickHandler) {
            this.panel.removeEventListener('click', this.portalClickHandler);
        }
        if (this.panel && this.portalAnchor && this.portalAnchor.parentNode) {
            this.portalAnchor.parentNode.insertBefore(this.panel, this.portalAnchor);
            this.portalAnchor.remove();
        }
    }

    toggle() {
        this.setOpen(!this.openValue);
    }

    open() {
        this.setOpen(true);
    }

    close() {
        this.setOpen(false);
    }

    setOpen(value) {
        this.openValue = value;

        if (this.panel) {
            if (this.hideTimer) {
                clearTimeout(this.hideTimer);
                this.hideTimer = null;
            }

            if (value) {
                this.panel.classList.remove(HIDDEN_CLASS);
                // Force a reflow so the transition runs from the closed state to the open one
                void this.panel.offsetWidth;
                this.panel.classList.add(OPEN_CLASS);
            } else {
                this.panel.classList.remove(OPEN_CLASS);
                this.hideTimer = setTimeout(() => {
                    if (!this.openValue && this.panel) {
                        this.panel.classList.add(HIDDEN_CLASS);
                    }
                }, this.transitionDurationValue);
            }
        }

        this.element.querySelectorAll('[aria-controls]').forEach((trigger) => {
            trigger.setAttribute('aria-expanded', value ? 'true' : 'false');
        });

        if (this.lockBodyValue) {
            document.body.classList.toggle(BODY_LOCK_CLASS, value);
        }

        if (value) {
            document.addEventListener('keydown', this.boundEscape);
            setTimeout(() => document.addEventListener('click', this.boundClickOutside), 0);
        } else {
            this.removeListeners();
        }
    }

    removeListeners() {
        document.removeEventListener('keydown', this.boundEscape);
        document.removeEventListener('click', this.boundClickOutside);
    }

    handleEscape(event) {
        if (event.key === 'Escape') {
            this.setOpen(false);
        }
    }

    handleClickOutside(event) {
        const inElement = this.element.contains(event.target);
        const inPanel = this.panel && this.panel.contains(event.target);
        if (!inElement && !inPanel) {
            this.setOpen(false);
        }
    }
}
