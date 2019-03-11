<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ecommerce_payment_method",
 *     indexes={
 *         @ORM\Index(name="ecommerce_payment_method_method_id_index", columns={"method_id"}),
 *         @ORM\Index(name="ecommerce_payment_method_method_type_index", columns={"method_type"}),
 *         @ORM\Index(name="ecommerce_payment_method_currency_index", columns={"currency"}),
 *         @ORM\Index(name="ecommerce_payment_method_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_payment_method_updated_on_index", columns={"updated_at"}),
 *         @ORM\Index(name="ecommerce_payment_method_deleted_on_index", columns={"deleted_on"}),
 *     }
 * )
 */
class PaymentMethod
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
     * @ORM\Column(type="integer", name="method_id")
     *
     * @var int
     */
    protected $methodId;

    /**
     * @ORM\Column(type="string", name="method_type")
     *
     * @var string
     */
    protected $methodType;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $currency;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Address")
     * @ORM\JoinColumn(name="billing_address_id", referencedColumnName="id")
     */
    protected $billingAddress;

    /**
     * @ORM\Column(type="datetime", name="deleted_on", nullable=true)
     *
     * @var \DateTime
     */
    protected $deletedOn;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getMethodId(): ?int
    {
        return $this->methodId;
    }

    /**
     * @param int $methodId
     *
     * @return PaymentMethod
     */
    public function setMethodId(int $methodId): self
    {
        $this->methodId = $methodId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMethodType(): ?string
    {
        return $this->methodType;
    }

    /**
     * @param string $methodType
     *
     * @return PaymentMethod
     */
    public function setMethodType(string $methodType): self
    {
        $this->methodType = $methodType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return PaymentMethod
     */
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return Address|null
     */
    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    /**
     * @param Address $billingAddress
     *
     * @return PaymentMethod
     */
    public function setBillingAddress(?Address $billingAddress): self
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDeletedOn(): ?\DateTimeInterface
    {
        return $this->deletedOn;
    }

    /**
     * @param \DateTimeInterface $deletedOn
     *
     * @return PaymentMethod
     */
    public function setDeletedOn(?\DateTimeInterface $deletedOn): self
    {
        $this->deletedOn = $deletedOn;

        return $this;
    }
}