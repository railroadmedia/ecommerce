<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\Traits\NotableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ecommerce_customers",
 *     indexes={
 *         @ORM\Index(name="ecommerce_customers_brand_index", columns={"brand"}),
 *         @ORM\Index(name="ecommerce_customers_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_customers_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class Customer
{
    use TimestampableEntity, NotableEntity;

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
    protected $brand;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $phone;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $email;

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
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone(?string $phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(?string $email)
    {
        $this->email = $email;
    }
}
