<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity\Editor;

use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Editor\Content\PageContent;

#[ORM\Entity]
#[ORM\Table(name: 'demo_landing')]
class DemoLanding
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 200)]
    public string $title = '';

    #[ORM\Column(type: 'editor_page', nullable: true)]
    public ?PageContent $homepage = null;

    public function getId(): ?int { return $this->id; }
}
