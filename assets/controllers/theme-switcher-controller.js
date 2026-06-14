import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    switch() {
        let currentTheme = localStorage.getItem('user-theme');
        if (!currentTheme) {
            currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        }

        const theme = currentTheme === 'dark' ? 'light' : 'dark';

        this.select(theme);
    }

    select (theme) {
        clearTimeout(this.timeout);

        this.element.setAttribute('data-switch', theme);

        // Small delay to allow CSS transitions during theme switch.
        this.timeout = setTimeout(() => {
            localStorage.setItem('user-theme', theme);
            document.documentElement.classList.toggle('dark', theme === 'dark');
        }, 250);
    }
}
