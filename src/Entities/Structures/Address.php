<?php

namespace Railroad\Ecommerce\Entities\Structures;

use Doctrine\Inflector\InflectorFactory;
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
    protected $region;

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

    const PROPS_MAP = [
        'country' => true,
        'region' => true,
        'firstName' => true,
        'lastName' => true,
        'streetLine1' => true,
        'streetLine2' => true,
        'zip' => true,
        'city' => true,
    ];

    public function __construct(
        ?string $country = null,
        ?string $region = null
    )
    {
        $this->country = $country;
        $this->region = $region;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry(?string $country)
    {
        $this->country = $country;
    }

    /**
     * @return string|null
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion(?string $region)
    {
        $this->region = $region;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(?string $firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(?string $lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string|null
     */
    public function getStreetLine1(): ?string
    {
        return $this->streetLine1;
    }

    /**
     * @param string $streetLine1
     */
    public function setStreetLine1(?string $streetLine1)
    {
        $this->streetLine1 = $streetLine1;
    }

    /**
     * @return string|null
     */
    public function getStreetLine2(): ?string
    {
        return $this->streetLine2;
    }

    /**
     * @param string|null $streetLine2
     */
    public function setStreetLine2(?string $streetLine2)
    {
        $this->streetLine2 = $streetLine2;
    }

    /**
     * @return string|null
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * @param string|null $zip
     */
    public function setZip(?string $zip)
    {
        $this->zip = $zip;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(?string $city)
    {
        $this->city = $city;
    }

    /**
     * Merges data from $address into current address
     *
     * @param Address $address
     */
    public function merge(Address $address)
    {
        $inflector = app('DoctrineInflector');

        foreach (self::PROPS_MAP as $key => $nil) {
            $setterName = $inflector->camelize('set' . ucwords($key));
            $getterName = $inflector->camelize('get' . ucwords($key));

            $currentValue = call_user_func([$this, $getterName]);
            $newValue = call_user_func([$address, $getterName]);

            if (empty($newValue)) {
                $newValue = null;
            }

            call_user_func([$this, $setterName], $newValue);
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'zip_or_postal_code' => !empty($this->zip) ? $this->zip : null,
            'street_line_two' => !empty($this->streetLine2) ? $this->streetLine2 : null,
            'street_line_one' => !empty($this->streetLine1) ? $this->streetLine1 : null,
            'last_name' => !empty($this->lastName) ? $this->lastName : null,
            'first_name' => !empty($this->firstName) ? $this->firstName : null,
            'region' => !empty($this->region) ? $this->region : null,
            'country' => !empty($this->country) ? $this->country : null,
            'city' => !empty($this->city) ? $this->city : null,
        ];
    }

    /**
     * @param $array
     *
     * @return Address
     */
    public static function createFromArray($array)
    {
        $inflector = app('DoctrineInflector');

        $address = new static;

        foreach (self::PROPS_MAP as $key => $nil) {
            if (isset($array[$key])) {
                $setterName = $inflector->camelize('set' . ucwords($key));

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
        $address->setRegion($this->getRegion());
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
     * @param string $data
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        $this->setZip($data['zip_or_postal_code']);
        $this->setStreetLine2($data['street_line_two']);
        $this->setStreetLine1($data['street_line_one']);
        $this->setLastName($data['last_name']);
        $this->setFirstName($data['first_name']);
        $this->setRegion($data['region']);
        $this->setCountry($data['country']);
        $this->setCity($data['city']);
    }
}
