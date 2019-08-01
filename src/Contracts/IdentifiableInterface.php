<?php

namespace Railroad\Ecommerce\Contracts;

interface IdentifiableInterface
{
    /**
     * @return int
     */
    public function getId(): ?int;

    /**
     * @return string
     */
    public function getEmail(): ?string;
}
