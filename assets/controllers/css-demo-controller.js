import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['preview', 'rules'];

    connect() {
        this.link = document.querySelector('link[href*="/ux_css/styles"]');
        if (!this.link) {
            return;
        }

        this.render = this.render.bind(this);
        if (this.link.sheet) {
            this.render();
        } else {
            this.link.addEventListener('load', this.render, { once: true });
        }
    }

    disconnect() {
        this.link?.removeEventListener('load', this.render);
    }

    render() {
        const classes = new Set();
        for (const element of this.previewTarget.querySelectorAll('[class]')) {
            element.classList.forEach((name) => classes.add(name));
        }

        const lines = [];
        try {
            this.collect(this.link.sheet.cssRules, classes, lines, (text) => text);
        } catch {
            return;
        }

        this.rulesTarget.innerHTML = lines.map((line) => this.highlight(line)).join('\n');
    }

    // Same hl-* classes as the server-side highlighter, so both blocks share the theme colors
    highlight(rule) {
        const open = rule.indexOf('{');
        const prelude = this.span('keyword', `${rule.slice(0, open)}`);
        const body = rule.slice(open + 1, rule.lastIndexOf('}'));

        if (rule.startsWith('@')) {
            return `${prelude}{ ${this.highlight(body.trim())} }`;
        }

        const declarations = body
            .split(';')
            .map((declaration) => declaration.trim())
            .filter(Boolean)
            .map((declaration) => {
                const colon = declaration.indexOf(':');
                const value = this.escape(declaration.slice(colon + 1)).replace(
                    /(--[\w-]+)|([\w-]+)(?=\()/g,
                    (match, variable) => this.span(variable ? 'property' : 'keyword', match, false)
                );

                return `${this.span('property', declaration.slice(0, colon))}:${value};`;
            });

        return `${prelude}{ ${declarations.join(' ')} }`;
    }

    span(type, text, escape = true) {
        return `<span class="hl-${type}">${escape ? this.escape(text) : text}</span>`;
    }

    escape(text) {
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    collect(rules, classes, lines, wrap) {
        for (const rule of rules) {
            if (rule instanceof CSSStyleRule) {
                if (this.classesOf(rule.selectorText).some((name) => classes.has(name))) {
                    lines.push(wrap(rule.cssText));
                }
            } else if (rule instanceof CSSLayerBlockRule) {
                this.collect(rule.cssRules, classes, lines, wrap);
            } else if (rule instanceof CSSGroupingRule) {
                const prelude = rule.cssText.slice(0, rule.cssText.indexOf('{')).trim();
                this.collect(rule.cssRules, classes, lines, (text) => wrap(`${prelude} { ${text} }`));
            }
        }
    }

    classesOf(selector) {
        return [...selector.matchAll(/\.((?:\\.|[\w-])+)/g)].map(([, name]) => name.replace(/\\(.)/g, '$1'));
    }
}
