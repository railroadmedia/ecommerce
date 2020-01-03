<?php

namespace Railroad\Ecommerce\Entities;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\Traits\NotableEntity;

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
    use TimestampableEntity, NotableEntity;

    const TYPE_SUBSCRIPTION = 'subscription';
    const TYPE_APPLE_SUBSCRIPTION = 'apple_subscription';
    const TYPE_GOOGLE_SUBSCRIPTION = 'google_subscription';
    const TYPE_PAYMENT_PLAN = 'payment plan';

    // log actions names
    const ACTION_RENEW = 'renew';
    const ACTION_CANCEL = 'cancel';
    const ACTION_DEACTIVATED = 'deactivated'; // canceled after several failed payments

    // states
    const STATE_ACTIVE = 'active';
    const STATE_SUSPENDED = 'suspended';
    const STATE_CANCELED = 'canceled';

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
     * @ORM\Column(type="user", name="user_id", nullable=true)
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
     * @ORM\Column(type="datetime", name="apple_expiration_date", nullable=true)
     *
     * @var \DateTime
     */
    protected $appleExpirationDate;

    /**
     * @ORM\Column(type="datetime", name="canceled_on", nullable=true)
     *
     * @var \DateTime
     */
    protected $canceledOn;

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
     * @ORM\Column(
     *     type="decimal",
     *     name="tax",
     *     precision=8,
     *     scale=2
     * )
     *
     * @var float
     */
    protected $tax;

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
     * @ORM\ManyToMany(targetEntity="Railroad\Ecommerce\Entities\Payment")
     * @ORM\JoinTable(name="ecommerce_subscription_payments",
     *      joinColumns={@ORM\JoinColumn(name="subscription_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="payment_id", referencedColumnName="id", unique=true)}
     *      )
     */
    protected $payments;

    /**
     * Field set for apple/google subscriptions, used for renewal/cancel notifications
     *
     * @ORM\Column(type="string", name="external_app_store_id", nullable=true)
     *
     * @var string
     */
    protected $externalAppStoreId;

    /**
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\AppleReceipt", mappedBy="subscription")
     */
    protected $appleReceipt;

    /**
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\Payment")
     * @ORM\JoinColumn(name="failed_payment_id", referencedColumnName="id", nullable=true)
     */
    protected $failedPayment;

    /**
     * @ORM\Column(type="string", name="cancellation_reason", nullable=true)
     *
     * @var string
     */
    protected $cancellationReason;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->tax = 0;
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
     * @return Order|null
     */
    public function getOrder(): ?Order
    {
        return $this->order;
    }

    /**
     * @param Order $order
     */
    public function setOrder(?Order $order)
    {
        $this->order = $order;
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
     */
    public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;
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
     */
    public function setStartDate(DateTimeInterface $startDate)
    {
        $this->startDate = $startDate;
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
     */
    public function setPaidUntil(DateTimeInterface $paidUntil)
    {
        $this->paidUntil = $paidUntil;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getAppleExpirationDate(): ?DateTimeInterface
    {
        return $this->appleExpirationDate;
    }

    /**
     * @param \DateTimeInterface $appleExpirationDate
     */
    public function setAppleExpirationDate(?DateTimeInterface $appleExpirationDate)
    {
        $this->appleExpirationDate = $appleExpirationDate;
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
     */
    public function setCanceledOn(?DateTimeInterface $canceledOn)
    {
        $this->canceledOn = $canceledOn;
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
     */
    public function setTotalPrice(float $totalPrice)
    {
        $this->totalPrice = $totalPrice;
    }

    /**
     * @return float
     */
    public function getTax(): float
    {
        return $this->tax;
    }

    /**
     * @param float $tax
     */
    public function setTax(float $tax)
    {
        $this->tax = $tax;
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
     */
    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
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
     */
    public function setIntervalType(string $intervalType)
    {
        $this->intervalType = $intervalType;
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
     */
    public function setIntervalCount(int $intervalCount)
    {
        $this->intervalCount = $intervalCount;
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
     */
    public function setTotalCyclesDue(?int $totalCyclesDue)
    {
        $this->totalCyclesDue = $totalCyclesDue;
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
     */
    public function setTotalCyclesPaid(int $totalCyclesPaid)
    {
        $this->totalCyclesPaid = $totalCyclesPaid;
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
     */
    public function setPaymentMethod(?PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
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
     */
    public function setDeletedAt(?DateTimeInterface $deletedAt)
    {
        $this->deletedAt = $deletedAt;
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
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(?Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return ArrayCollection|Payment[]
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * @param ArrayCollection $payments
     */
    public function setPayments(ArrayCollection $payments): void
    {
        $this->payments = $payments;
    }

    /**
     * @return string|null
     */
    public function getExternalAppStoreId(): ?string
    {
        return $this->externalAppStoreId;
    }

    /**
     * @param string $externalAppStoreId
     */
    public function setExternalAppStoreId(string $externalAppStoreId)
    {
        $this->externalAppStoreId = $externalAppStoreId;
    }

    /**
     * @return AppleReceipt|null
     */
    public function getAppleReceipt(): ?AppleReceipt
    {
        return $this->appleReceipt;
    }

    /**
     * @param AppleReceipt $appleReceipt
     */
    public function setAppleReceipt(?AppleReceipt $appleReceipt)
    {
        $this->appleReceipt = $appleReceipt;
    }

    /**
     * States:
     * - active (subscription will auto renew)
     * - canceled (user canceled the subscription)
     * - suspended (subscription failed to renew)
     *
     * @return string
     */
    public function getState()
    {
        if ($this->getIsActive()) {
            return self::STATE_ACTIVE;
        }

        if (!empty($this->getCanceledOn())) {
            return self::STATE_CANCELED;
        }

        return self::STATE_SUSPENDED;
    }

    /**
     * @return Payment|null
     */
    public function getFailedPayment(): ?Payment
    {
        return $this->failedPayment;
    }

    /**
     * @param Payment $payment
     */
    public function setFailedPayment(?Payment $payment)
    {
        $this->failedPayment = $payment;
    }

    /**
     * @return string|null
     */
    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    /**
     * @param string|null $cancellationReason
     */
    public function setCancellationReason($cancellationReason)
    {
        $this->cancellationReason = $cancellationReason;
    }
}
