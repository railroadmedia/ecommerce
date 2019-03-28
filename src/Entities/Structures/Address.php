<?php

namespace Railroad\Ecommerce\Entities\Structures;

use Doctrine\Common\Inflector\Inflector;
use Railroad\Ecommerce\Contracts\Address as AddressInterface;

class Address implements AddressInterface
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
    protected $streetLineOne;

    /**
     * @var string
     */
    protected $streetLineTwo;

    /**
     * @var string
     */
    protected $zipOrPostalCode;

    /**
     * @var string
     */
    protected $city;

    const PROPS_MAP = [
        'country' => true,
        'state' => true,
        'firstName' => true,
        'lastName' => true,
        'streetLineOne' => true,
        'streetLineTwo' => true,
        'zipOrPostalCode' => true,
        'city' => true
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
     * @return string|null
     */
    public function getFirstName() : ?string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return Address
     */
    public function setFirstName(?string $firstName) : self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName() : ?string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return Address
     */
    public function setLastName(?string $lastName) : self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreetLineOne() : ?string
    {
        return $this->streetLineOne;
    }

    /**
     * @param string $streetLineOne
     *
     * @return Address
     */
    public function setStreetLineOne(?string $streetLineOne) : self
    {
        $this->streetLineOne = $streetLineOne;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreetLineTwo() : ?string
    {
        return $this->streetLineTwo;
    }

    /**
     * @param string $streetLineTwo
     *
     * @return Address
     */
    public function setStreetLineTwo(?string $streetLineTwo) : self
    {
        $this->streetLineTwo = $streetLineTwo;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZipOrPostalCode() : ?string
    {
        return $this->zipOrPostalCode;
    }

    /**
     * @param string $zipOrPostalCode
     *
     * @return Address
     */
    public function setZipOrPostalCode(?string $zipOrPostalCode) : self
    {
        $this->zipOrPostalCode = $zipOrPostalCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity() : ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return Address
     */
    public function setCity(?string $city) : self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Merges data from $address into current address
     *
     * @param Address $address
     *
     * @return Address
     */
    public function merge(Address $address): self
    {
        foreach (self::PROPS_MAP as $key => $nil) {
            $setterName = Inflector::camelize('set' . ucwords($key));
            $getterName = Inflector::camelize('get' . ucwords($key));

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
            'zip_or_postal_code' => $this->zipOrPostalCode,
            'street_line_two' => $this->streetLineTwo,
            'street_line_one' => $this->streetLineOne,
            'last_name' => $this->lastName,
            'first_name' => $this->firstName,
            'state' => $this->state,
            'country' => $this->country,
            'city' => $this->city
        ];
    }

    public static function createFromArray($array)
    {
        $address = new static;

        foreach (self::PROPS_MAP as $key => $nil) {
            if (isset($array[$key])) {
                $setterName = Inflector::camelize('set' . ucwords($key));

                call_user_func([$address, $setterName], $array[$key]);
            }
        }

        return $address;
    }
}
