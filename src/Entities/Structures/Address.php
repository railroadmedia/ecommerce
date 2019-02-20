<?php

namespace Railroad\Ecommerce\Entities\Structures;

class Address
{
    /**.
     * @var string
     */
    protected $country;

    /**.
     * @var string
     */
    protected $region;

    public function __construct(
        ?string $country = null,
        ?string $region = null
    ) {

        $this->country = $country;
        $this->region = $region;
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
    public function setCountry(string $country) : self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRegion() : ?string
    {
        return $this->region;
    }

    /**
     * @param string $region
     *
     * @return Address
     */
    public function setRegion(string $region) : self
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Merges data from $address into current address
     * current data is not overwritten, if exists
     *
     * @param string $region
     *
     * @return Address
     */
    public function merge(Address $address): self
    {
        if (!$this->country && $address->getCountry()) {
            $this->country = $address->getCountry();
        }

        if (!$this->region && $address->getRegion()) {
            $this->region = $address->getRegion();
        }

        return $this;
    }
}
