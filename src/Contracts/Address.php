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
     * @return Address
     */
    public function setState(?string $state): Address;

    /**
     * @return string|null
     */
    public function getCountry(): ?string;

    /**
     * @param string $country
     *
     * @return Address
     */
    public function setCountry(?string $country): Address;
}
