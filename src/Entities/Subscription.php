<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Usora\Entities\User;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\SubscriptionRepository")
 * @ORM\Table(
 *     name="ecommerce_subscription",
 *     indexes={
 *         @ORM\Index(name="ecommerce_subscription_brand_index", columns={"brand"}),
 *         @ORM\Index(name="ecommerce_subscription_type_index", columns={"type"}),
 *         @ORM\Index(name="ecommerce_subscription_user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="ecommerce_subscription_customer_id_index", columns={"customer_id"}),
 *         @ORM\Index(name="ecommerce_subscription_order_id_index", columns={"order_id"}),
 *         @ORM\Index(name="ecommerce_subscription_product_id_index", columns={"product_id"}),
 *         @ORM\Index(name="ecommerce_subscription_is_active_index", columns={"is_active"}),
 *         @ORM\Index(name="ecommerce_subscription_start_date_index", columns={"start_date"}),
 *         @ORM\Index(name="ecommerce_subscription_paid_until_index", columns={"paid_until"}),
 *         @ORM\Index(name="ecommerce_subscription_currency_index", columns={"currency"}),
 *         @ORM\Index(name="ecommerce_subscription_interval_type_index", columns={"interval_type"}),
 *         @ORM\Index(name="ecommerce_subscription_payment_method_id_index", columns={"payment_method_id"}),
 *         @ORM\Index(name="ecommerce_subscription_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_subscription_updated_on_index", columns={"updated_at"}),
 *         @ORM\Index(name="ecommerce_subscription_deleted_on_index", columns={"deleted_on"})
 *     }
 * )
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
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     */
    protected $customer;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
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
     * @var float
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
     * @var float
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
     * @var float
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
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\PaymentMethod")
     * @ORM\JoinColumn(name="payment_method_id", referencedColumnName="id")
     */
    protected $paymentMethod;

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
     * @return string|null
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     *
     * @return Subscription
     */
    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

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
     * @return Subscription
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Order|null
     */
    public function getOrder(): ?Order
    {
        return $this->order;
    }

    /**
     * @param Order $order
     *
     * @return Subscription
     */
    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     *
     * @return Subscription
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    /**
     * @param \DateTimeInterface $startDate
     *
     * @return Subscription
     */
    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPaidUntil(): ?\DateTimeInterface
    {
        return $this->paidUntil;
    }

    /**
     * @param \DateTimeInterface $paidUntil
     *
     * @return Subscription
     */
    public function setPaidUntil(\DateTimeInterface $paidUntil): self
    {
        $this->paidUntil = $paidUntil;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getCanceledOn(): ?\DateTimeInterface
    {
        return $this->canceledOn;
    }

    /**
     * @param \DateTimeInterface $canceledOn
     *
     * @return Subscription
     */
    public function setCanceledOn(?\DateTimeInterface $canceledOn): self
    {
        $this->canceledOn = $canceledOn;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param string $note
     *
     * @return Subscription
     */
    public function setNote(string $note): self
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTotalPricePerPayment(): ?float
    {
        return $this->totalPricePerPayment;
    }

    /**
     * @param float $totalPricePerPayment
     *
     * @return Subscription
     */
    public function setTotalPricePerPayment(float $totalPricePerPayment): self
    {
        $this->totalPricePerPayment = $totalPricePerPayment;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTaxPerPayment(): ?float
    {
        return $this->taxPerPayment;
    }

    /**
     * @param float $taxPerPayment
     *
     * @return Subscription
     */
    public function setTaxPerPayment(?float $taxPerPayment): self
    {
        $this->taxPerPayment = $taxPerPayment;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getShippingPerPayment(): ?float
    {
        return $this->shippingPerPayment;
    }

    /**
     * @param float $shippingPerPayment
     *
     * @return Subscription
     */
    public function setShippingPerPayment(?float $shippingPerPayment): self
    {
        $this->shippingPerPayment = $shippingPerPayment;

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
     * @return Subscription
     */
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIntervalType(): ?string
    {
        return $this->intervalType;
    }

    /**
     * @param string $intervalType
     *
     * @return Subscription
     */
    public function setIntervalType(string $intervalType): self
    {
        $this->intervalType = $intervalType;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getIntervalCount(): ?int
    {
        return $this->intervalCount;
    }

    /**
     * @param int $intervalCount
     *
     * @return Subscription
     */
    public function setIntervalCount(int $intervalCount): self
    {
        $this->intervalCount = $intervalCount;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTotalCyclesDue(): ?int
    {
        return $this->totalCyclesDue;
    }

    /**
     * @param int $totalCyclesDue
     *
     * @return Subscription
     */
    public function setTotalCyclesDue(?int $totalCyclesDue): self
    {
        $this->totalCyclesDue = $totalCyclesDue;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTotalCyclesPaid(): ?int
    {
        return $this->totalCyclesPaid;
    }

    /**
     * @param int $totalCyclesPaid
     *
     * @return Subscription
     */
    public function setTotalCyclesPaid(int $totalCyclesPaid): self
    {
        $this->totalCyclesPaid = $totalCyclesPaid;

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
     * @return Subscription
     */
    public function setPaymentMethod(?PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

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
     * @return Subscription
     */
    public function setDeletedOn(?\DateTimeInterface $deletedOn): self
    {
        $this->deletedOn = $deletedOn;

        return $this;
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
     *
     * @return Subscription
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
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
     *
     * @return Subscription
     */
    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

     /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return Subscription
     */
    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }
}
