<?php

namespace Railroad\Ecommerce\Tests\Fixtures;

use Railroad\Ecommerce\Contracts\UserInterface;

class User implements UserInterface
{
    protected $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function __toString()
    {
        /*
        method needed by UnitOfWork
        https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/cookbook/custom-mapping-types.html
        */

        return (string) $this->id;
    }
}
