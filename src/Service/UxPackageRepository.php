<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Model\UxPackage;

class UxPackageRepository
{
    /**
     * @return list<UxPackage>
     */
    public function findAll(?string $query = null, ?bool $removed = null, bool $sortByName = false): array
    {
        $packages = [
            new UxPackage(
                'icons',
                'Icons',
                'app_icons',
                '#7C5CD6',
                'SVG icons made easy',
                'Render SVG icons seamlessly from your Twig templates.',
                'I need to render SVG icons.',
                'icons.svg',
                gradient: 'linear-gradient(to bottom right, cyan, purple)',
                seoTitle: 'UX Icons - SVG icons made easy',
                socialTitle: 'Symfony UX Icons - SVG icons made easy',
            ),

            new UxPackage(
                'map',
                'Map',
                'app_map',
                '#1BA980',
                'Interactive Maps',
                'Render interactive Maps in PHP with Leaflet or Google Maps.',
                'I need to display markers on a Map.',
                'map.svg',
            ),

            new UxPackage(
                'twig-component',
                'Twig Components',
                'app_twig_component',
                '#7FA020',
                'Render Reusable UI Elements',
                'Create PHP classes that can render themselves',
                'I need to create PHP classes that render'
            ),

            new UxPackage(
                'live-component',
                'Live Components',
                'app_live_component',
                '#D98A11',
                'Interactive UI in PHP & Twig',
                'Build dynamic interfaces with zero JavaScript',
                'I need Twig templates that update in real-time!'
            ),

            (new UxPackage(
                'turbo',
                'Turbo',
                'app_turbo',
                '#5920A0',
                'Single-page Symfony app',
                'Integration with Turbo for single-page-app and real-time experience',
                'I need to transform my app into an SPA!'
            ))
                ->setDocsLink('https://turbo.hotwired.dev/handbook/introduction', 'Documentation specifically for the Turbo JavaScript library.')
                ->setScreencastLink('https://symfonycasts.com/screencast/turbo', 'Go deep into all 3 parts of Turbo.'),

            (new UxPackage(
                'stimulus',
                'Stimulus',
                'app_stimulus',
                '#2EB17B',
                'Central Bridge of Symfony UX',
                'Integration with Stimulus for HTML-powered controllers',
                null,
                'stimulus.svg',
                'symfony/stimulus-bundle',
            ))
            ->setOfficialDocsUrl('https://symfony.com/bundles/StimulusBundle')
            ->setScreencastLink('https://symfonycasts.com/screencast/stimulus', 'More than 40 videos to master Stimulus.'),

            new UxPackage(
                'toolkit',
                'Toolkit',
                'app_toolkit',
                '#64748b',
                'Build your Design System.',
                'Collection of components and templates that you can use to build your pages.',
                'I need components to build my design system.',
                null,
                null,
                true,
            ),

            (new UxPackage(
                'native',
                'Native',
                'app_native',
                '#c18bf4',
                'Hotwire Native for Symfony',
                'Build native mobile apps that wrap your Symfony web application',
                'I need to wrap my Symfony app in a native mobile shell',
                'native.svg',
            ))
                ->setDocsLink('https://native.hotwired.dev/', 'Hotwire Native documentation.'),

            new UxPackage(
                'pagination',
                'Pagination',
                'app_pagination',
                '#635AA6',
                'Pagination for all. One page ahead.',
                'Paginate any data with numbered pages, lookahead, or cursors.',
                'I need to paginate my results.',
                imageFileName: 'pagination.png',
            ),

            new UxPackage(
                'autocomplete',
                'Autocomplete',
                'app_autocomplete',
                '#DF275E',
                'Ajax-powered Form Select',
                'Ajax-powered, auto-completable `select` elements',
                'I need an Ajax-autocomplete select field'
            ),

            new UxPackage(
                'translator',
                'Translator',
                'app_translator',
                '#2248D0',
                'Symfony Translations in JavaScript',
                "Use Symfony's translations in JavaScript",
                'I need to translate strings in JavaScript',
                'translator.svg',
            ),

            (new UxPackage(
                'chartjs',
                'Chart.js',
                'app_chartjs',
                '#21A81E',
                'Interactive charts with Chart.js',
                'Easy charts with Chart.js',
                'I need to build a chart'
            ))
                ->setDocsLink('https://www.chartjs.org/', 'Chart.js documentation.'),

            (new UxPackage(
                'react',
                'React',
                'app_react',
                '#10A2CB',
                'Render React components from Twig',
                'Quickly render `<React />` components &amp; pass them props.',
                'I need to render React components from Twig'
            ))
                ->setDocsLink('https://reactjs.org/', 'Go deeper with the React docs.'),

            (new UxPackage(
                'vue',
                'Vue.js',
                'app_vue',
                '#35b67c',
                'Render Vue components from Twig',
                'Quickly render `<Vue />` components &amp; pass them props.',
                null,
            ))
                ->setDocsLink('https://vuejs.org/', 'Go deeper with the Vue.js docs.'),

            (new UxPackage(
                'svelte',
                'Svelte',
                'app_svelte',
                '#FF3E00',
                'Render Svelte components from Twig',
                'Quickly render `<Svelte />` components &amp; pass them props.',
                null,
                'svelte.svg',
                isRemoved: true,
            ))
                ->setDocsLink('https://svelte.dev/', 'Go deeper with the Svelte docs.'),

            (new UxPackage(
                'cropperjs',
                'Image Cropper',
                'app_cropperjs',
                '#1E8FA8',
                'Form Tools for cropping images',
                'Form Type and tools for cropping images',
                null,
            ))
                ->setDocsLink('https://github.com/fengyuanchen/cropperjs', 'Cropper.js documentation.'),

            new UxPackage(
                'lazy-image',
                'Lazy Image',
                'app_lazy_image',
                '#AC2777',
                'Delay Loading with Blurhash',
                'Optimize Image Loading with BlurHash',
                isRemoved: true,
            ),

            new UxPackage(
                'dropzone',
                'Stylized Dropzone',
                'app_dropzone',
                '#AC9F27',
                'Upload Files with Style',
                'Form type for stylized "drop zone" for file uploads',
                'I need an upload field that looks great'
            ),

            (new UxPackage(
                'swup',
                'Swup Integration',
                'app_swup',
                '#D87036',
                'Stylized Page Transitions',
                'Integration with the page transition library Swup',
                isRemoved: true,
            ))
                ->setDocsLink('https://swup.js.org/', 'Swup documentation'),

            new UxPackage(
                'notify',
                'Notify',
                'app_notify',
                '#204CA0',
                'Native Browser Notifications',
                'Trigger native browser notifications from inside PHP',
            ),

            new UxPackage(
                'calendar-link',
                'CalendarLink',
                'app_calendar_link',
                '#7F1D1D',
                'Save the date, anywhere',
                'Let users add your events to their calendar of choice in one click.',
                'I want users to save my events in one click.',
                imageFileName: 'calendar-link.svg',
            ),

            new UxPackage(
                'toggle-password',
                'Toggle Password',
                'app_toggle_password',
                '#BE0404',
                'Password Visibility Switch',
                'Switch the visibility of a password field',
                isRemoved: true,
            ),

            (new UxPackage(
                'typed',
                'Typed',
                'app_typed',
                '#20A091',
                'Animated Typing with Typed.js',
                'Animated typing with Typed.js',
                isRemoved: true
            ))
                ->setDocsLink('https://github.com/mattboldt/typed.js/', 'Typed.js documentation'),
        ];

        if ($query) {
            $packages = array_filter($packages, static fn (UxPackage $package) => str_contains($package->getName(), $query) || str_contains($package->getHumanName(), $query));
        }

        if (null !== $removed) {
            $packages = array_filter($packages, static fn (UxPackage $package) => $package->isRemoved() === $removed);
        }

        if ($sortByName) {
            usort($packages, static fn (UxPackage $a, UxPackage $b) => $a->getHumanName() <=> $b->getHumanName());
        }

        return array_values($packages);
    }

    public function find(string $name): UxPackage
    {
        $packages = $this->findAll();
        foreach ($packages as $package) {
            if ($package->getName() === $name) {
                return $package;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Unknown package "%s".', $name));
    }

    public function count(): int
    {
        return \count($this->findAll());
    }

    public function findByRoute(string $route): UxPackage
    {
        $packages = $this->findAll();
        foreach ($packages as $package) {
            if ($package->getRoute() === $route) {
                return $package;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Could not find a package for the current route "%s".', $route));
    }
}
