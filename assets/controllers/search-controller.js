import { Controller } from '@hotwired/stimulus';

const INDEX = 'ux_search';

export default class extends Controller {
    static values = { url: String, key: String };
    static targets = ['modal', 'input', 'body', 'packages', 'demos', 'recipes', 'seeAll', 'empty', 'footer'];

    connect() {
        this._debounceTimer = null;
        this._abortController = null;
        this._lastShiftAt = 0;
        this._onKeydown = this._onGlobalKeydown.bind(this);
        document.addEventListener('keydown', this._onKeydown);
    }

    disconnect() {
        clearTimeout(this._debounceTimer);
        this._abortController?.abort();
        document.removeEventListener('keydown', this._onKeydown);
    }

    openModal() {
        this.modalTarget.hidden = false;
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => this.inputTarget.focus());
    }

    closeModal() {
        this.modalTarget.hidden = true;
        document.body.style.overflow = '';
    }

    onInput() {
        clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(() => this._runQuery(this.inputTarget.value), 200);
    }

    async _runQuery(q) {
        q = q.trim();
        if (!q) {
            this._reset();
            return;
        }

        this._abortController?.abort();
        this._abortController = new AbortController();

        try {
            const response = await fetch(`${this.urlValue}/indexes/${INDEX}/search`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.keyValue}`,
                },
                body: JSON.stringify({ q, limit: 20, attributesToHighlight: ['humanName', 'name', 'description'] }),
                signal: this._abortController.signal,
            });

            if (!response.ok) return;
            const { hits } = await response.json();

            const byType = { package: [], demo: [], recipe: [] };
            for (const hit of hits) byType[hit.type]?.push(hit);

            this._render(this.packagesTarget, { 'Packages': byType.package.slice(0, 5) }, h => ({
                label: h._formatted?.humanName ?? h.humanName,
                desc: h._formatted?.description ?? h.description,
                href: h.url,
            }));

            this._render(this.demosTarget, { 'Demos': byType.demo.slice(0, 5) }, h => ({
                label: h._formatted?.name ?? h.name,
                desc: h._formatted?.description ?? h.description,
                href: h.url,
            }));

            this._render(this.recipesTarget, this._groupByKit(byType.recipe.slice(0, 8)), h => ({
                label: h._formatted?.name ?? h.name,
                desc: h._formatted?.description ?? h.description,
                href: h.url,
            }));

            this.bodyTarget.hidden = false;
            this.emptyTarget.hidden = hits.length > 0;
            this.footerTarget.hidden = hits.length === 0;
            this.seeAllTarget.href = `/search?q=${encodeURIComponent(q)}`;
        } catch (e) {
            if (e.name !== 'AbortError') throw e;
        }
    }

    _reset() {
        this.packagesTarget.innerHTML = '';
        this.demosTarget.innerHTML = '';
        this.recipesTarget.innerHTML = '';
        this.emptyTarget.hidden = true;
        this.bodyTarget.hidden = true;
        this.footerTarget.hidden = true;
        this.seeAllTarget.href = '/search';
    }

    _render(target, groups, mapper) {
        const html = [];
        for (const [heading, hits] of Object.entries(groups)) {
            if (!hits.length) continue;
            html.push(`<li class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider opacity-50">${heading}</li>`);
            for (const hit of hits) {
                const { label, desc, href } = mapper(hit);
                html.push(
                    `<li><a href="${href}" class="block px-3 py-2 rounded-md text-body-text no-underline hover:bg-body-text/5 hover:no-underline">`
                    + `<span class="block font-medium text-sm leading-snug [&_em]:not-italic [&_em]:font-bold">${label}</span>`
                    + `<span class="block text-xs opacity-60 leading-snug whitespace-nowrap overflow-hidden text-ellipsis [&_em]:not-italic [&_em]:font-semibold">${desc}</span>`
                    + `</a></li>`,
                );
            }
        }
        target.hidden = html.length === 0;
        target.innerHTML = html.join('');
    }

    _groupByKit(hits) {
        const groups = {};
        for (const hit of hits) {
            const label = `Toolkit — ${hit.kitHuman || hit.kit}`;
            (groups[label] ??= []).push(hit);
        }
        return groups;
    }

    _onGlobalKeydown(e) {
        if (e.key !== 'Shift' || e.repeat || e.metaKey || e.ctrlKey || e.altKey) return;

        const now = Date.now();
        if (now - this._lastShiftAt < 350) {
            this._lastShiftAt = 0;
            if (this.modalTarget.hidden) this.openModal();
        } else {
            this._lastShiftAt = now;
        }
    }
}
