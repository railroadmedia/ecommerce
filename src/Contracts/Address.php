<?php

namespace Railroad\Ecommerce\Contracts;

interface Address
{
    /**
     * @return string|null
     */
    public function getState(): ?string;

    /**
     * @param string $state
     *
     * @return void
     */
    public function setState(?string $state);

    /**
     * @return string|null
     */
    public function getCountry(): ?string;

    /**
     * @param string $country
     *
     * @return void
     */
    public function setCountry(?string $country);
}
