<?php

namespace Railroad\Ecommerce\Entities\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait NotableEntity
 * @package Railroad\Ecommerce\Entities\Traits
 */
trait NotableEntity
{
    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $note;

    /**
     * @return string
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param string $note
     */
    public function setNote(?string $note): void
    {
        $this->note = $note;
    }
}
