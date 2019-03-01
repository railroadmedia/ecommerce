<?php

namespace Railroad\Ecommerce\Contracts;

interface UserInterface
{
    public function getId(): int;

    public function __toString();
}
