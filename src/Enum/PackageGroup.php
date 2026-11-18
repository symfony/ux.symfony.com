<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Enum;

/**
 * The groups of the package catalogue. The value is the slug of the group page.
 */
enum PackageGroup: string
{
    case Foundation = 'foundation';
    case Media = 'media';
    case Forms = 'forms';
    case Navigation = 'navigation';
    case DesignSystem = 'design-system';
    case Bridges = 'bridges';
    case DevTools = 'dev-tools';

    public function label(): string
    {
        return match ($this) {
            self::Foundation => 'Foundation',
            self::Media => 'Media',
            self::Forms => 'Forms',
            self::Navigation => 'Navigation',
            self::DesignSystem => 'Design System',
            self::Bridges => 'Bridges',
            self::DevTools => 'Dev Tools',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Foundation => 'What every Symfony UX application is built on.',
            self::Media => 'Images, icons, maps and charts for your pages.',
            self::Forms => 'Form fields that go further than the browser ones.',
            self::Navigation => 'Pages, URLs, translations and notifications, from your application to the browser.',
            self::DesignSystem => 'Components, styles and icons for a consistent interface.',
            self::Bridges => 'Another frontend framework inside your Symfony application.',
            self::DevTools => 'See what happens in the page while you build it.',
        };
    }
}
