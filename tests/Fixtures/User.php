<?php

namespace Railroad\Ecommerce\Tests\Fixtures;

use Railroad\Ecommerce\Contracts\UserInterface;
use Railroad\Ecommerce\Faker\Factory;

class User implements UserInterface
{
    protected $id;
    protected $email;

    public function __construct(int $id, string $email = null)
    {
        $this->id = $id;

        if (func_num_args() < 2) {
            $faker = Factory::create();

            $this->email = $faker->email;
        } else {
            $this->email = $email;
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail()
    {
        return $this->email;
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
