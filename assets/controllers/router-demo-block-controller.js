import { Controller } from '@hotwired/stimulus';
import { path, url } from '../router.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['parameter', 'output', 'error'];

    static values = {
        function: String,
        route: String,
    };

    connect() {
        this.render();
    }

    render() {
        const parameters = {};

        this.parameterTargets.forEach((input) => {
            const value = input.type === 'number' && input.value !== '' ? Number(input.value) : input.value;
            const [name, key] = input.dataset.parameter.split('.');

            if (key) {
                parameters[name] = { ...parameters[name], [key]: value };
            } else {
                parameters[name] = value;
            }

            const code = this.element.querySelector(`[data-code-parameter="${input.dataset.parameter}"]`);
            if (code) {
                code.textContent = typeof value === 'number' ? String(value) : `'${value}'`;
            }
        });

        try {
            const generated = (this.functionValue === 'url' ? url : path)(this.routeValue, parameters);
            this.outputTarget.textContent = generated;
            this.outputTarget.href = generated;
            this.outputTarget.hidden = false;
            this.errorTarget.hidden = true;
        } catch (error) {
            this.errorTarget.textContent = `${error.name}: ${error.message}`;
            this.errorTarget.hidden = false;
            this.outputTarget.hidden = true;
        }
    }
}
