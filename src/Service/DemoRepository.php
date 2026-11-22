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

use App\Model\Demo;

/**
 * The demos of the website, in the order of the demos page.
 */
class DemoRepository
{
    /**
     * @return list<Demo>
     */
    public function findAll(): array
    {
        return [
            new Demo(
                'inspector',
                'inspector',
                name: 'UX Inspector',
                description: 'Inspect live components, follow their relationships and trace events directly in the browser.',
                author: 'smnandre',
                publishedAt: '2026-09-27',
                tags: ['Stimulus', 'LiveComponent', 'Turbo'],
                longDescription: 'Find a component on the page, inspect its props, follow its relationships and trace its events.',
                route: 'app_inspector',
            ),
            new Demo(
                'pagination',
                'pagination',
                name: 'Live Pagination',
                description: 'Add reactive filters and page changes to ordinary pagination links.',
                author: 'smnandre',
                publishedAt: '2026-08-04',
                tags: ['pagination', 'LiveComponent', 'filters', 'trait'],
                longDescription: 'Combine [`Pagination`](/pagination) with [`LiveComponent`](/live-component)
                    for reactive filters and page changes. Each control remains an ordinary link,
                    and changing a filter returns you to the first page.',
                route: 'app_demo_pagination_live',
                template: 'demos/pagination/live.html.twig',
            ),
            new Demo(
                'pagination',
                'cursor-pagination',
                name: 'Cursor Pagination',
                description: 'Move through ordered values without letting earlier changes shift the current slice.',
                author: 'smnandre',
                publishedAt: '2026-09-21',
                tags: ['pagination', 'cursor', 'no-js'],
                longDescription: 'Move through an ordered feed without a count query. The signed cursor records
                    a stable boundary, so an earlier deletion does not shift the current slice.',
                route: 'app_demo_pagination_cursor',
                template: 'demos/pagination/cursor.html.twig',
            ),
            new Demo(
                'pagination',
                'pagination-themes',
                name: 'Pagination Themes',
                description: 'Render the same pages with a built-in theme or a focused Twig block override.',
                author: 'smnandre',
                publishedAt: '2026-09-21',
                tags: ['pagination', 'Twig', 'themes', 'blocks'],
                longDescription: 'Render the same pagination result with the default bundle CSS,
                    Tailwind utility classes, or a focused application theme override.',
                route: 'app_demo_pagination_themes',
                template: 'demos/pagination/themes.html.twig',
            ),
            new Demo(
                'upload',
                'keep-files',
                name: 'Keep Files on Errors',
                description: 'A validation error empties a file input. Not this one.',
                author: 'smnandre',
                publishedAt: '2026-11-15',
                tags: ['upload', 'form', 'validation'],
                longDescription: 'One form, two file fields: a `FileType` and an [`UploadType`](/upload). Save it with an
                    empty title. The first field comes back empty. The second keeps its file, and the next
                    submit sends a few hundred bytes instead of the whole document.',
            ),
            new Demo(
                'upload',
                'turbo',
                name: 'Upload with Turbo',
                description: 'Save an album from a Turbo Frame. A Turbo Stream adds it to the page.',
                author: 'smnandre',
                publishedAt: '2026-11-15',
                tags: ['upload', 'Turbo', 'gallery'],
                longDescription: 'An [`UploadType`](/upload) form lives in a Turbo Frame. A validation error swaps only the frame, and the
                    photos stay. A valid album comes back as a Turbo Stream: added to the list, form reset,
                    no page reload.',
            ),
            new Demo(
                'upload',
                'live-avatar',
                name: 'Live Avatar',
                description: 'Update a LiveComponent when the upload completes.',
                author: 'smnandre',
                publishedAt: '2026-11-15',
                tags: ['upload', 'LiveComponent', 'avatar'],
                longDescription: 'When an [`UploadType`](/upload) field completes its upload, the signed reference lands on an
                    `#[UploadTarget]` prop and the component renders again with the name and size
                    it received. No form submission, no page reload. The picture is the preview
                    of your browser: this public server keeps no file and sends none back.',
            ),
            new Demo(
                'upload',
                'input',
                name: 'Simple & Enhanced Input',
                description: 'The compact field: a native input, or a list with previews and progress.',
                author: 'smnandre',
                publishedAt: '2026-11-15',
                tags: ['upload', 'compact', 'preview'],
                longDescription: 'The compact layout of [`UploadType`](/upload), twice. On the left, the browser file input,
                    which uploads in the background. On the right, a list of files with thumbnails, progress,
                    and buttons to cancel, retry, or remove each transfer.',
                template: 'demos/upload/field_style.html.twig',
            ),
            new Demo(
                'upload',
                'dropzone',
                name: 'Dropzone & Card Grid',
                description: 'A large drop target, with a file list or a grid of image cards.',
                author: 'smnandre',
                publishedAt: '2026-11-15',
                tags: ['upload', 'dropzone', 'gallery'],
                longDescription: 'The dropzone layout of [`UploadType`](/upload): drop, paste, or click to browse. Without previews, files line up under
                    the target. With `show_preview`, each image becomes a card in a responsive grid.',
                template: 'demos/upload/field_style.html.twig',
            ),
            new Demo(
                'upload',
                'resumable',
                name: 'Resumable Large Files',
                description: 'Pause a transfer, reload the page, and pick up where it stopped.',
                author: 'smnandre',
                publishedAt: '2026-11-15',
                tags: ['upload', 'chunks', 'resume'],
                longDescription: 'With [`UploadType`](/upload), large files travel in chunks, two at a time. Pause the transfer, or reload the
                    page halfway: selecting the same file again sends only the missing chunks.',
            ),
            new Demo(
                'turbo',
                'infinite-scroll-2',
                name: 'Turbo Infinite Scroll - 2/2',
                description: 'Load the next batch automatically with a lazy Turbo Frame.',
                author: 'ker0x',
                publishedAt: '2026-04-20',
                tags: ['grid', 'pagination', 'loading', 'scroll'],
                longDescription: <<<EOF
                    Building on [Part One](/demos/turbo/infinite-scroll), this version loads the next batch when a `lazy Turbo Frame` enters the scrollable catalog; compare it with the [Live Component version](/demos/live-component/infinite-scroll-2), which uses a Live Component to load on scroll.
                    EOF,
            ),
            new Demo(
                'turbo',
                'infinite-scroll',
                name: 'Turbo Infinite Scroll - 1/2',
                description: 'Append a batch of cards with a link and two Turbo Streams.',
                author: 'ker0x',
                publishedAt: '2026-04-20',
                tags: ['grid', 'pagination', 'navigation'],
                longDescription: <<<EOF
                    This demo explores the same product grid as the [Live Component version](/demos/live-component/infinite-scroll), using `Turbo Streams`: click `Load More` to append a batch of cards and replace the link with the next page.
                    EOF,
            ),
            new Demo(
                'live-component',
                'live-remove',
                name: 'Removing a Component',
                description: 'Remove a LiveComponent after its final render, with an optional CSS exit animation.',
                author: 'smnandre',
                publishedAt: '2026-08-26',
                tags: ['remove', 'animation', 'LiveAction', 'LiveResponse'],
                longDescription: <<<EOF
                    Return `LiveResponse::remove()` from a [`LiveAction`](/live-component). LiveComponent renders once more, then removes the component root.
                    Style `[data-live-removing]` to animate the exit.
                    EOF,
            ),
            new Demo(
                'live-component',
                'live-download',
                name: 'Downloading Files',
                description: 'Download from a route or send generated content while preserving state and events.',
                author: 'smnandre',
                publishedAt: '2026-08-04',
                tags: ['file', 'download', 'events', 'LiveAction'],
                longDescription: <<<EOF
                    Return `LiveResponse::downloadUrl()` for a file served by its own route, or `LiveResponse::downloadFile()` for content built by a [`LiveAction`](/live-component).
                    LiveComponent renders state changes and delivers queued events before starting the download.
                    EOF,
            ),
            new Demo(
                'live-component',
                'infinite-scroll-2',
                name: 'Infinite Scroll - 2/2',
                description: 'Automatically load more products on scroll with a Live Component and IntersectionObserver.',
                author: 'smnandre',
                publishedAt: '2024-06-07',
                tags: ['grid', 'pagination', 'loading', 'scroll'],
                longDescription: <<<EOF
                    Building on [Part One](/demos/live-component/infinite-scroll), this version uses a Stimulus `IntersectionObserver` to call a Live Component action when the next item approaches the viewport, loading more products without a button.
                    EOF,
            ),
            new Demo(
                'live-component',
                'infinite-scroll',
                name: 'Infinite Scroll - 1/2',
                description: 'Append product batches on demand with a Live Component while keeping existing cards in place.',
                author: 'smnandre',
                publishedAt: '2024-06-07',
                tags: ['grid', 'pagination', 'navigation'],
                longDescription: <<<EOF
                    Infinite scroll allows users to continuously load content as they scroll down the page.
                    `Part One` of this demo shows how to `append new items` to the page with a [`LiveComponent`](/live-component).
                    EOF,
            ),
            new Demo(
                'live-component',
                'live-memory',
                name: 'Live Memory Card Game',
                description: 'A Memorable Game UX with Live Components!',
                author: 'smnandre',
                publishedAt: '2024-06-07',
                tags: ['game', 'time', 'events', 'LiveAction'],
                longDescription: <<<EOF
                    A Memorable Game UX with Live Components! Discover how to use Live Components to create a game with a vibrant interface,
                     rich interactions and real-time updates. This journey will take you through many features of Live Components, and you'll
                      learn how to use them to create a fun and engaging game.
                    EOF,
            ),
            new Demo(
                'live-component',
                'auto-validating-form',
                name: 'Auto-Validating Form',
                description: 'Create a form that validates each field in-real-time as the user enters data!',
                author: 'weaverryan',
                publishedAt: '2022-06-17',
                tags: ['form', 'validation', 'inline'],
                longDescription: <<<EOF
                    Enter a bad email or leave the password empty, and see how the
                    form validates in real time!

                    This renders a normal `Symfony` form but with extras added on top,
                    all generated from Symfony & Twig.
                    EOF,
            ),
            new Demo(
                'live-component',
                'form-collection-type',
                name: 'Embedded CollectionType Form',
                description: 'Create embedded forms with functional "add" and "remove" buttons all in Twig.',
                author: 'weaverryan',
                publishedAt: '2022-06-17',
                tags: ['form', 'collection'],
                longDescription: <<<EOF
                    Unlock the potential of Symfony's [`CollectionType`](https://symfony.com/doc/current/reference/forms/types/collection.html) while writing zero JavaScript.
                    This demo shows off adding and removing items entirely in PHP & Twig.
                    EOF,
            ),
            new Demo(
                'live-component',
                'dependent-form-fields',
                name: 'Dependent Form Fields',
                description: 'After selecting the first field, automatically reload the options for a second field.',
                author: 'weaverryan',
                publishedAt: '2022-06-17',
                tags: ['form', 'field', 'events'],
                longDescription: <<<EOF
                    Unleash the power of form events, thanks to [`LiveComponent`](/live-component) and [`DynamicForms`](https://github.com/SymfonyCasts/dynamic-forms).
                    EOF,
            ),
            new Demo(
                'live-component',
                'voting',
                name: 'Up & Down Voting',
                description: 'Save up & down votes live in pure Twig & PHP.',
                author: 'weaverryan',
                publishedAt: '2022-06-17',
                tags: ['form', 'LiveAction'],
                longDescription: <<<EOF
                    With each row as its own component, it's easy to add up & down voting + keep track of which items have been voted on.
                    This uses a [LiveAction](https://symfony.com/bundles/ux-live-component/current/index.html#actions) to save everything with Ajax.
                    EOF,
            ),
            new Demo(
                'live-component',
                'inline-edit',
                name: 'Inline Editing',
                description: 'Activate an inline editing form with real-time validation.',
                author: 'weaverryan',
                publishedAt: '2023-02-21',
                tags: ['form', 'inline', 'LiveAction'],
                longDescription: <<<EOF
                    Inline editing? Simple. Use LiveComponents to track if you're in "edit" mode, let
                    the user update any fields on your entity, and save through a `LiveAction`.
                    EOF,
            ),
            new Demo(
                'live-component',
                'chartjs',
                name: 'Auto-Updating Chart',
                description: 'Render & Update a Chart.js chart in real-time.',
                author: 'weaverryan',
                publishedAt: '2023-03-16',
                tags: ['chart', 'data', 'LiveAction', 'stimulus'],
                longDescription: <<<EOF
                    What do you get with Live Components + UX Chart.js + UX Autocomplete?

                    An auto-updating chart that you will ❤️.
                    EOF,
            ),
            new Demo(
                'live-component',
                'invoice',
                name: 'Invoice Creator',
                description: 'Create an invoice + line items that updates as you type.',
                author: 'weaverryan',
                publishedAt: '2023-04-20',
                tags: ['form', 'entity', 'events', 'LiveAction'],
                longDescription: <<<EOF
                    Create or edit an `Invoice` entity along with child components for each related `InvoiceItem` entity.

                    Children components emit events to communicate to the parent and everything is saved in a `saveInvoice` LiveAction method.
                    EOF,
            ),
            new Demo(
                'live-component',
                'product-form',
                name: 'Product Form + Category Modal',
                description: 'Create a Category on the fly - from inside a product form - via a modal.',
                author: 'weaverryan',
                publishedAt: '2023-04-20',
                tags: ['form', 'entity', 'events', 'modal'],
                longDescription: 'Open a child modal component to create a new Category.',
            ),
            new Demo(
                'live-component',
                'upload',
                name: 'Uploading files',
                description: 'Upload file from your live component through a LiveAction.',
                author: 'lustmored',
                publishedAt: '2023-06-26',
                tags: ['form', 'file', 'upload', 'LiveAction'],
                longDescription: 'File uploads are tricky. Submit them to a `#[LiveAction]` with the `files` modifier
                on `data-live-action` then process them.',
            ),
        ];
    }

    /**
     * @return list<Demo>
     */
    public function findByPackage(string $package): array
    {
        return array_values(array_filter(
            $this->findAll(),
            static fn (Demo $demo): bool => $demo->getPackage() === $package,
        ));
    }

    /**
     * @throws \InvalidArgumentException if the demo is not found
     */
    public function find(string $package, string $identifier): Demo
    {
        foreach ($this->findByPackage($package) as $demo) {
            if ($demo->getIdentifier() === $identifier) {
                return $demo;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Unknown "%s" demo "%s".', $package, $identifier));
    }

    /**
     * The demo after the given one, among the demos of the same package.
     */
    public function getNext(Demo $current, bool $loop = false): ?Demo
    {
        $demos = $this->findByPackage($current->getPackage());
        while ($demo = current($demos)) {
            if ($demo->getIdentifier() === $current->getIdentifier()) {
                return next($demos) ?: ($loop ? reset($demos) : null);
            }
            next($demos);
        }

        return null;
    }

    /**
     * The demo before the given one, among the demos of the same package.
     */
    public function getPrevious(Demo $current, bool $loop = false): ?Demo
    {
        $demos = $this->findByPackage($current->getPackage());
        while ($demo = current($demos)) {
            if ($demo->getIdentifier() === $current->getIdentifier()) {
                return prev($demos) ?: ($loop ? end($demos) : null);
            }
            next($demos);
        }

        return null;
    }
}
