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
    input: 'let name = "Swift"',
    output: ['let'],
)]
#[PatternTest(
    input: 'func greet() -> String {',
    output: ['func'],
)]
#[PatternTest(
    input: 'import Foundation',
    output: ['import'],
)]
class SwiftKeywordPattern implements Pattern
{
    use IsPattern;

    public function getPattern(): string
    {
        $keywords = [
            'import', 'func', 'var', 'let', 'class', 'struct', 'enum', 'protocol',
            'extension', 'typealias', 'associatedtype',
            'return', 'if', 'else', 'guard', 'switch', 'case', 'default',
            'for', 'while', 'repeat', 'do', 'break', 'continue', 'fallthrough',
            'in', 'where', 'is', 'as',
            'throw', 'throws', 'rethrows', 'try', 'catch',
            'async', 'await',
            'init', 'deinit', 'self', 'super',
            'nil', 'true', 'false',
            'static', 'final', 'lazy', 'mutating', 'nonmutating', 'override',
            'private', 'fileprivate', 'internal', 'public', 'open',
            'weak', 'unowned', 'inout',
            'defer', 'some', 'any',
        ];

        return '/(?<=\b)(?P<match>'.implode('|', $keywords).')(?=\b)/';
    }

    public function getTokenType(): TokenTypeEnum
    {
        return TokenTypeEnum::KEYWORD;
    }
}
