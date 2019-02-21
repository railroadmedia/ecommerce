<?php

namespace Railroad\Ecommerce\Entities\Structures;

use Railroad\Ecommerce\Contracts\Address as AddressInterface;

class Address implements AddressInterface
{
    /**.
     * @var string
     */
    protected $country;

    /**.
     * @var string
     */
    protected $state;

    public function __construct(
        ?string $country = null,
        ?string $state = null
    ) {

        $this->country = $country;
        $this->state = $state;
    }

    /**
     * @return string|null
     */
    public function getCountry() : ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return Address
     */
    public function setCountry(?string $country) : AddressInterface
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getState() : ?string
    {
        return $this->state;
    }

    /**
     * @param string $state
     *
     * @return Address
     */
    public function setState(?string $state) : AddressInterface
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Merges data from $address into current address
     * current data is not overwritten, if exists
     *
     * @param Address $address
     *
     * @return Address
     */
    public function merge(Address $address): self
    {
        if (!$this->country && $address->getCountry()) {
            $this->country = $address->getCountry();
        }

        if (!$this->state && $address->getState()) {
            $this->state = $address->getState();
        }

        return $this;
    }
}
