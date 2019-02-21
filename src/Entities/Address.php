<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Contracts\Address as AddressInterface;
use Railroad\Usora\Entities\User;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ecommerce_address",
 *     indexes={
 *         @ORM\Index(name="ecommerce_address_type_index", columns={"type"}),
 *         @ORM\Index(name="ecommerce_address_brand_index", columns={"brand"}),
 *         @ORM\Index(name="ecommerce_address_user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="ecommerce_address_customer_id_index", columns={"customer_id"}),
 *         @ORM\Index(name="ecommerce_address_first_name_index", columns={"first_name"}),
 *         @ORM\Index(name="ecommerce_address_last_name_index", columns={"last_name"}),
 *         @ORM\Index(name="ecommerce_address_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_address_updated_on_index", columns={"updated_at"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class Address implements AddressInterface
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $brand;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Usora\Entities\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     */
    protected $customer;

    /**
     * @ORM\Column(type="string", name="first_name")
     *
     * @var string
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string", name="last_name")
     *
     * @var string
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string", name="street_line_1", nullable=true)
     *
     * @var string
     */
    protected $streetLine1;

    /**
     * @ORM\Column(type="string", name="street_line_2", nullable=true)
     *
     * @var string
     */
    protected $streetLine2;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $city;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $zip;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $state;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $country;

    /**
     * Address constructor.
     */
    public function __construct()
    {
        $this->brand = ConfigService::$brand;
    }

    /**
     * @return int|null
     */
    public function getId()
    : ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getType()
    : ?string
    {
        return $this->type;
    }

    /**
     * @param string $brand
     *
     * @return Address
     */
    public function setType(string $type)
    : self {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBrand()
    : ?string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     *
     * @return Address
     */
    public function setBrand(string $brand)
    : self {
        $this->brand = $brand;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName()
    : ?string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return Address
     */
    public function setFirstName(string $firstName)
    : self {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName()
    : ?string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return Address
     */
    public function setLastName(string $lastName)
    : self {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreetLine1()
    : ?string
    {
        return $this->streetLine1;
    }

    /**
     * @param string $streelLineOne
     *
     * @return Address
     */
    public function setStreetLine1(?string $streetLine1)
    : self {
        $this->streetLine1 = $streetLine1;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreetLine2()
    : ?string
    {
        return $this->streetLine2;
    }

    /**
     * @param string $streetLineTwo
     *
     * @return Address
     */
    public function setStreetLine2(?string $streetLine2)
    : self {
        $this->streetLine2 = $streetLine2;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity()
    : ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return Address
     */
    public function setCity(?string $city)
    : self {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZip()
    : ?string
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     *
     * @return Address
     */
    public function setZip(?string $zip)
    : self {
        $this->zip = $zip;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getState()
    : ?string
    {
        return $this->state;
    }

    /**
     * @param string $state
     *
     * @return Address
     */
    public function setState(?string $state)
    : AddressInterface {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry()
    : ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return Address
     */
    public function setCountry(?string $country)
    : AddressInterface {
        $this->country = $country;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser()
    : ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Address
     */
    public function setUser(?User $user)
    : self {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Customer|null
     */
    public function getCustomer()
    : ?Customer
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     *
     * @return Address
     */
    public function setCustomer(?Customer $customer)
    : self {
        $this->customer = $customer;

        return $this;
    }
}
