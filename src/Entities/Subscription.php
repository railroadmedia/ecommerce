<?php

namespace Railroad\Ecommerce\Entities;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\SubscriptionRepository")
 * @ORM\Table(
 *     name="ecommerce_subscriptions",
 *     indexes={
 *         @ORM\Index(name="ecommerce_subscriptions_brand_index", columns={"brand"}),
 *         @ORM\Index(name="ecommerce_subscriptions_type_index", columns={"type"}),
 *         @ORM\Index(name="ecommerce_subscriptions_user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="ecommerce_subscriptions_customer_id_index", columns={"customer_id"}),
 *         @ORM\Index(name="ecommerce_subscriptions_order_id_index", columns={"order_id"}),
 *         @ORM\Index(name="ecommerce_subscriptions_product_id_index", columns={"product_id"}),
 *         @ORM\Index(name="ecommerce_subscriptions_is_active_index", columns={"is_active"}),
 *         @ORM\Index(name="ecommerce_subscriptions_start_date_index", columns={"start_date"}),
 *         @ORM\Index(name="ecommerce_subscriptions_paid_until_index", columns={"paid_until"}),
 *         @ORM\Index(name="ecommerce_subscriptions_currency_index", columns={"currency"}),
 *         @ORM\Index(name="ecommerce_subscriptions_interval_type_index", columns={"interval_type"}),
 *         @ORM\Index(name="ecommerce_subscriptions_payment_method_id_index", columns={"payment_method_id"}),
 *         @ORM\Index(name="ecommerce_subscriptions_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_subscriptions_updated_on_index", columns={"updated_at"}),
 *         @ORM\Index(name="ecommerce_subscriptions_deleted_on_index", columns={"deleted_at"})
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
     * @var User
     *
     * @ORM\Column(type="user_id", name="user_id", nullable=true)
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
     *     name="total_price",
     *     precision=8,
     *     scale=2
     * )
     *
     * @var float
     */
    protected $totalPrice;

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
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     *
     * @var \DateTime
     */
    protected $deletedAt;

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
    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    /**
     * @param \DateTimeInterface $startDate
     *
     * @return Subscription
     */
    public function setStartDate(DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPaidUntil(): ?DateTimeInterface
    {
        return $this->paidUntil;
    }

    /**
     * @param \DateTimeInterface $paidUntil
     *
     * @return Subscription
     */
    public function setPaidUntil(DateTimeInterface $paidUntil): self
    {
        $this->paidUntil = $paidUntil;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getCanceledOn(): ?DateTimeInterface
    {
        return $this->canceledOn;
    }

    /**
     * @param \DateTimeInterface $canceledOn
     *
     * @return Subscription
     */
    public function setCanceledOn(?DateTimeInterface $canceledOn): self
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
    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    /**
     * @param float $totalPrice
     *
     * @return Subscription
     */
    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

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
    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTimeInterface $deletedAt
     *
     * @return Subscription
     */
    public function setDeletedAt(?DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

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
