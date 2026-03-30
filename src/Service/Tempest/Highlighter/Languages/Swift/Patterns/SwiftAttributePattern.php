<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Tempest\Highlighter\Languages\Swift\Patterns;

use Tempest\Highlight\IsPattern;
use Tempest\Highlight\Pattern;
use Tempest\Highlight\PatternTest;
use Tempest\Highlight\Tokens\TokenTypeEnum;

#[PatternTest(
    input: '@objc func foo() {}',
    output: ['@objc'],
)]
#[PatternTest(
    input: '@available(iOS 15, *)',
    output: ['@available'],
)]
#[PatternTest(
    input: '@main struct MyApp {}',
    output: ['@main'],
)]
class SwiftAttributePattern implements Pattern
{
    use IsPattern;

    public function getPattern(): string
    {
        return '/(?P<match>@[a-zA-Z_]\w*)/';
    }

    public function getTokenType(): TokenTypeEnum
    {
        return TokenTypeEnum::ATTRIBUTE;
    }
}
