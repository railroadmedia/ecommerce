<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\SubscriptionRepository")
 * @ORM\Table(name="ecommerce_subscription")
 */
class Subscription
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
    protected $brand;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $type;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Usora\Entities\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * temp prop mapping
     * when Customer entity is implemented
     * should be refactored to ManyToOne relation
     *
     * @ORM\Column(type="integer", name="customer_id", nullable=true)
     *
     * @var int
     */
    protected $customer;

    /**
     * temp prop mapping
     * when Order entity is implemented
     * should be refactored to ManyToOne relation
     *
     * @ORM\Column(type="integer", name="order_id", nullable=true)
     *
     * @var int
     */
    protected $order;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    protected $product;

    /**
     * @ORM\Column(type="boolean", name="is_active")
     *
     * @var bool
     */
    protected $isActive;

    /**
     * @ORM\Column(type="datetime", name="start_date")
     *
     * @var \DateTime
     */
    protected $startDate;

    /**
     * @ORM\Column(type="datetime", name="paid_until")
     *
     * @var \DateTime
     */
    protected $paidUntil;

    /**
     * @ORM\Column(type="datetime", name="canceled_on", nullable=true)
     *
     * @var \DateTime
     */
    protected $canceledOn;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    protected $note;

    /**
     * @ORM\Column(
     *     type="decimal",
     *     name="total_price_per_payment",
     *     precision=8,
     *     scale=2
     * )
     *
     * @var string
     */
    protected $totalPricePerPayment;

    /**
     * @ORM\Column(
     *     type="decimal",
     *     name="tax_per_payment",
     *     precision=8,
     *     scale=2,
     *     nullable=true
     * )
     *
     * @var string
     */
    protected $taxPerPayment;

    /**
     * @ORM\Column(
     *     type="decimal",
     *     name="shipping_per_payment",
     *     precision=8,
     *     scale=2,
     *     nullable=true
     * )
     *
     * @var string
     */
    protected $shippingPerPayment;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $currency;

    /**
     * @ORM\Column(type="string", name="interval_type")
     *
     * @var string
     */
    protected $intervalType;

    /**
     * @ORM\Column(type="integer", name="interval_count")
     *
     * @var int
     */
    protected $intervalCount;

    /**
     * @ORM\Column(type="integer", name="total_cycles_due", nullable=true)
     *
     * @var int
     */
    protected $totalCyclesDue;

    /**
     * @ORM\Column(type="integer", name="total_cycles_paid")
     *
     * @var int
     */
    protected $totalCyclesPaid;

    /**
     * temp prop mapping
     * when PaymentMethod entity is implemented
     * should be refactored to ManyToOne relation
     *
     * @ORM\Column(type="integer", name="payment_method_id", nullable=true)
     *
     * @var int
     */
    protected $paymentMethod;

    /**
     * @ORM\Column(type="datetime", name="deleted_on", nullable=true)
     *
     * @var \DateTime
     */
    protected $deletedOn;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCustomer(): ?int
    {
        return $this->customer;
    }

    public function setCustomer(?int $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(?int $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getPaidUntil(): ?\DateTimeInterface
    {
        return $this->paidUntil;
    }

    public function setPaidUntil(\DateTimeInterface $paidUntil): self
    {
        $this->paidUntil = $paidUntil;

        return $this;
    }

    public function getCanceledOn(): ?\DateTimeInterface
    {
        return $this->canceledOn;
    }

    public function setCanceledOn(?\DateTimeInterface $canceledOn): self
    {
        $this->canceledOn = $canceledOn;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getTotalPricePerPayment()
    {
        return $this->totalPricePerPayment;
    }

    public function setTotalPricePerPayment($totalPricePerPayment): self
    {
        $this->totalPricePerPayment = $totalPricePerPayment;

        return $this;
    }

    public function getTaxPerPayment()
    {
        return $this->taxPerPayment;
    }

    public function setTaxPerPayment($taxPerPayment): self
    {
        $this->taxPerPayment = $taxPerPayment;

        return $this;
    }

    public function getShippingPerPayment()
    {
        return $this->shippingPerPayment;
    }

    public function setShippingPerPayment($shippingPerPayment): self
    {
        $this->shippingPerPayment = $shippingPerPayment;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getIntervalType(): ?string
    {
        return $this->intervalType;
    }

    public function setIntervalType(string $intervalType): self
    {
        $this->intervalType = $intervalType;

        return $this;
    }

    public function getIntervalCount(): ?int
    {
        return $this->intervalCount;
    }

    public function setIntervalCount(int $intervalCount): self
    {
        $this->intervalCount = $intervalCount;

        return $this;
    }

    public function getTotalCyclesDue(): ?int
    {
        return $this->totalCyclesDue;
    }

    public function setTotalCyclesDue(?int $totalCyclesDue): self
    {
        $this->totalCyclesDue = $totalCyclesDue;

        return $this;
    }

    public function getTotalCyclesPaid(): ?int
    {
        return $this->totalCyclesPaid;
    }

    public function setTotalCyclesPaid(int $totalCyclesPaid): self
    {
        $this->totalCyclesPaid = $totalCyclesPaid;

        return $this;
    }

    public function getPaymentMethod(): ?int
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?int $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getDeletedOn(): ?\DateTimeInterface
    {
        return $this->deletedOn;
    }

    public function setDeletedOn(?\DateTimeInterface $deletedOn): self
    {
        $this->deletedOn = $deletedOn;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }
}
