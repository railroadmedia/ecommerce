<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ecommerce_payment",
 *     indexes={
 *         @ORM\Index(name="ecommerce_payment_type_index", columns={"type"}),
 *         @ORM\Index(name="ecommerce_payment_external_id_index", columns={"external_id"}),
 *         @ORM\Index(name="ecommerce_payment_external_provider_index", columns={"external_provider"}),
 *         @ORM\Index(name="ecommerce_payment_status_index", columns={"status"}),
 *         @ORM\Index(name="ecommerce_payment_payment_method_id_index", columns={"payment_method_id"}),
 *         @ORM\Index(name="ecommerce_payment_currency_index", columns={"currency"}),
 *         @ORM\Index(name="ecommerce_payment_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_payment_updated_on_index", columns={"updated_at"}),
 *         @ORM\Index(name="ecommerce_payment_deleted_on_index", columns={"deleted_on"}),
 *     }
 * )
 */
class Payment
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
     * @ORM\Column(type="decimal", precision=8, scale=2)
     *
     * @var float
     */
    protected $due;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     *
     * @var float
     */
    protected $paid;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     *
     * @var float
     */
    protected $refunded;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(type="string", length=64, name="external_id", nullable=true)
     *
     * @var string
     */
    protected $externalId;

    /**
     * @ORM\Column(type="string", length=64, name="external_provider", nullable=true)
     *
     * @var string
     */
    protected $externalProvider;

    /**
     * @ORM\Column(type="string", length=64)
     *
     * @var string
     */
    protected $status;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string
     */
    protected $message;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\PaymentMethod")
     * @ORM\JoinColumn(name="payment_method_id", referencedColumnName="id")
     */
    protected $paymentMethod;

    /**
     * @ORM\Column(type="text", length=3)
     *
     * @var string
     */
    protected $currency;

    /**
     * @ORM\Column(type="datetime", name="deleted_on", nullable=true)
     *
     * @var \DateTime
     */
    protected $deleted_on;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return float|null
     */
    public function getDue(): ?float
    {
        return $this->due;
    }

    /**
     * @param float $due
     *
     * @return Payment
     */
    public function setDue(float $due): self
    {
        $this->due = $due;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPaid(): ?float
    {
        return $this->paid;
    }

    /**
     * @param float $paid
     *
     * @return Payment
     */
    public function setPaid(float $paid): self
    {
        $this->paid = $paid;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getRefunded(): ?float
    {
        return $this->refunded;
    }

    /**
     * @param float $refunded
     *
     * @return Payment
     */
    public function setRefunded(float $refunded): self
    {
        $this->refunded = $refunded;

        return $this;
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
     *
     * @return Payment
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     *
     * @return Payment
     */
    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExternalProvider(): ?string
    {
        return $this->externalProvider;
    }

    /**
     * @param string $externalProvider
     *
     * @return Payment
     */
    public function setExternalProvider(?string $externalProvider): self
    {
        $this->externalProvider = $externalProvider;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Payment
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return Payment
     */
    public function setMessage(?string $message): self
    {
        $this->message = $message;

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
     * @return Payment
     */
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDeletedOn(): ?\DateTimeInterface
    {
        return $this->deleted_on;
    }

    /**
     * @param \DateTimeInterface $deleted_on
     *
     * @return Payment
     */
    public function setDeletedOn(?\DateTimeInterface $deleted_on): self
    {
        $this->deleted_on = $deleted_on;

        return $this;
    }

    /**
     * @return PaymentMethod|null
     */
    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * @param PaymentMethod $paymentMethod
     *
     * @return Payment
     */
    public function setPaymentMethod(?PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }
}
