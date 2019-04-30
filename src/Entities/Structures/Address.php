<?php

namespace Railroad\Ecommerce\Entities\Structures;

use Doctrine\Common\Inflector\Inflector;
use Railroad\Ecommerce\Contracts\Address as AddressInterface;
use Railroad\Ecommerce\Entities\Address as AddressEntity;
use Serializable;

class Address implements AddressInterface, Serializable
{
    /**
     * @var string
     */
    protected $country;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $firstName;

    /**
     * @var string
     */
    protected $lastName;

    /**
     * @var string
     */
    protected $streetLine1;

    /**
     * @var string
     */
    protected $streetLine2;

    /**
     * @var string
     */
    protected $zip;

    /**
     * @var string
     */
    protected $city;

    const PROPS_MAP
        = [
            'country'       => true, 'state' => true, 'firstName' => true,
            'lastName'      => true, 'streetLine1' => true,
            'streetLine2' => true, 'zip' => true, 'city' => true,
        ];

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
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param  string  $country
     *
     * @return Address
     */
    public function setCountry(?string $country): AddressInterface
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param  string  $state
     *
     * @return Address
     */
    public function setState(?string $state): AddressInterface
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param  string  $firstName
     *
     * @return Address
     */
    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param  string  $lastName
     *
     * @return Address
     */
    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreetLine1(): ?string
    {
        return $this->streetLine1;
    }

    /**
     * @param  string  $streetLine1
     *
     * @return Address
     */
    public function setStreetLine1(?string $streetLine1): self
    {
        $this->streetLine1 = $streetLine1;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreetLine2(): ?string
    {
        return $this->streetLine2;
    }

    /**
     * @param  string  $streetLineTwo
     *
     * @return Address
     */
    public function setStreetLine2(?string $streetLine2): self
    {
        $this->streetLine2 = $streetLine2;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * @param  string  $zipOrPostalCode
     *
     * @return Address
     */
    public function setZip(?string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param  string  $city
     *
     * @return Address
     */
    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Merges data from $address into current address
     *
     * @param  Address  $address
     *
     * @return Address
     */
    public function merge(Address $address): self
    {
        foreach (self::PROPS_MAP as $key => $nil) {
            $setterName = Inflector::camelize('set'.ucwords($key));
            $getterName = Inflector::camelize('get'.ucwords($key));

            $currentValue = call_user_func([$this, $getterName]);
            $newValue = call_user_func([$address, $getterName]);

            if (!$currentValue && $newValue) {
                call_user_func([$this, $setterName], $newValue);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'zip_or_postal_code' => $this->zip,
            'street_line_two'    => $this->streetLine2,
            'street_line_one'    => $this->streetLine1,
            'last_name'          => $this->lastName,
            'first_name'         => $this->firstName, 'state' => $this->state,
            'country'            => $this->country, 'city' => $this->city,
        ];
    }

    /**
     * @param $array
     *
     * @return Address
     */
    public static function createFromArray($array)
    {
        $address = new static;

        foreach (self::PROPS_MAP as $key => $nil) {
            if (isset($array[$key])) {
                $setterName = Inflector::camelize('set'.ucwords($key));

                call_user_func([$address, $setterName], $array[$key]);
            }
        }

        return $address;
    }

    /**
     * @return AddressEntity
     */
    public function toEntity()
    {
        $address = new AddressEntity();

        $address->setCountry($this->getCountry());
        $address->setState($this->getState());
        $address->setCity($this->getCity());
        $address->setLastName($this->getLastName());
        $address->setFirstName($this->getFirstName());
        $address->setCity($this->getCity());
        $address->setStreetLine1($this->getStreetLine1());
        $address->setStreetLine2($this->getStreetLine2());
        $address->setZip($this->getZip());

        return $address;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * @param  array  $data
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        $this->setZip($data['zip_or_postal_code']);
        $this->setStreetLine2($data['street_line_two']);
        $this->setStreetLine1($data['street_line_one']);
        $this->setLastName($data['last_name']);
        $this->setFirstName($data['first_name']);
        $this->setState($data['state']);
        $this->setCountry($data['country']);
        $this->setCity($data['city']);
    }
}
