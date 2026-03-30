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
    input: 'let x = 42',
    output: ['42'],
)]
#[PatternTest(
    input: 'let pi = 3.14',
    output: ['3.14'],
)]
class SwiftNumberPattern implements Pattern
{
    use IsPattern;

    public function getPattern(): string
    {
        return '/(?P<match>\b\d+\.?\d*)\b/';
    }

    public function getTokenType(): TokenTypeEnum
    {
        return TokenTypeEnum::NUMBER;
    }
}
