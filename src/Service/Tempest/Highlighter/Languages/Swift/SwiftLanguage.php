<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Tempest\Highlighter\Languages\Swift;

use App\Service\Tempest\Highlighter\Languages\Swift\Patterns\SwiftAttributePattern;
use App\Service\Tempest\Highlighter\Languages\Swift\Patterns\SwiftCommentPattern;
use App\Service\Tempest\Highlighter\Languages\Swift\Patterns\SwiftKeywordPattern;
use App\Service\Tempest\Highlighter\Languages\Swift\Patterns\SwiftNumberPattern;
use App\Service\Tempest\Highlighter\Languages\Swift\Patterns\SwiftStringPattern;
use Tempest\Highlight\Languages\Base\BaseLanguage;

class SwiftLanguage extends BaseLanguage
{
    public function getName(): string
    {
        return 'swift';
    }

    /**
     * @return list<string>
     */
    public function getAliases(): array
    {
        return [];
    }

    public function getPatterns(): array
    {
        return [
            ...parent::getPatterns(),

            new SwiftCommentPattern(),
            new SwiftStringPattern(),
            new SwiftKeywordPattern(),
            new SwiftAttributePattern(),
            new SwiftNumberPattern(),
        ];
    }
}
