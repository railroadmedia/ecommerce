<?php

namespace Railroad\Ecommerce\Contracts;

interface Address
{
    /**
     * @return string|null
     */
    public function getRegion(): ?string;

    /**
     * @param string $state
     *
     * @return void
     */
    public function setRegion(?string $state);

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
