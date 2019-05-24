<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Contracts\Address as AddressInterface;
use Railroad\Ecommerce\Entities\Structures\Address as AddressStructure;
use Railroad\Ecommerce\Entities\Traits\NotableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\AddressRepository")
 * @ORM\Table(
 *     name="ecommerce_addresses",
 *     indexes={
 *         @ORM\Index(name="ecommerce_addresses_type_index", columns={"type"}),
 *         @ORM\Index(name="ecommerce_addresses_brand_index", columns={"brand"}),
 *         @ORM\Index(name="ecommerce_addresses_user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="ecommerce_addresses_customer_id_index", columns={"customer_id"}),
 *         @ORM\Index(name="ecommerce_addresses_first_name_index", columns={"first_name"}),
 *         @ORM\Index(name="ecommerce_addresses_last_name_index", columns={"last_name"}),
 *         @ORM\Index(name="ecommerce_addresses_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_addresses_updated_on_index", columns={"updated_at"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class Address implements AddressInterface
{
    use TimestampableEntity, SoftDeleteableEntity, NotableEntity;

    CONST BILLING_ADDRESS_TYPE = 'billing';
    CONST SHIPPING_ADDRESS_TYPE = 'shipping';

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
     * @var User
     *
     * @ORM\Column(type="user", name="user_id", nullable=true)
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     */
    protected $customer;

    /**
     * @ORM\Column(type="string", name="first_name", nullable=true)
     *
     * @var string
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string", name="last_name", nullable=true)
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
     * @ORM\Column(type="string", nullable=true, nullable=true)
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
        $this->brand = config('ecommerce.brand');
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     */
    public function setBrand(string $brand)
    {
        $this->brand = $brand;
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
     * @param string $streetLine2
     */
    public function setStreetLine2(?string $streetLine2)
    {
        $this->streetLine2 = $streetLine2;
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
     * @return string|null
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip(?string $zip)
    {
        $this->zip = $zip;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(?string $state)
    {
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
     * @param string $country
     */
    public function setCountry(?string $country)
    {
        $this->country = $country;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(?User $user)
    {
        $this->user = $user;
    }

    /**
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     */
    public function setCustomer(?Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return AddressStructure
     */
    public function toStructure()
    {
        $addressStructure = new AddressStructure();

        $addressStructure->setCountry($this->getCountry());
        $addressStructure->setState($this->getState());
        $addressStructure->setFirstName($this->getFirstName());
        $addressStructure->setLastName($this->getLastName());
        $addressStructure->setStreetLine1($this->getStreetLine1());
        $addressStructure->setStreetLine2($this->getStreetLine2());
        $addressStructure->setZip($this->getZip());
        $addressStructure->setCity($this->getCity());

        return $addressStructure;
    }
}
