import { Controller } from '@hotwired/stimulus';
import docsearch from '@docsearch/js';
import '@docsearch/css/dist/style.min.css';

const SECTIONS = ['Packages', 'Kits', 'Demos', 'Resources'];

const sectionRank = (item) => {
    const rank = SECTIONS.indexOf(item.hierarchy.lvl0);

    return rank === -1 ? SECTIONS.length : rank;
};

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.docsearch = docsearch({
            container: this.element,
            appId: 'TOWOPESDZM',
            apiKey: '72f93d5cc3ba277e9f15a288154e13e3',
            indices: ['Documentation'],
            // DocSearch lists sections in the order their first hit appears
            transformItems: (items) => items.toSorted((a, b) => sectionRank(a) - sectionRank(b)),
        });
    }

    disconnect() {
        this.docsearch?.destroy();
    }
}
