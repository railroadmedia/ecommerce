<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\Traits\NotableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\PaymentRepository")
 * @ORM\Table(
 *     name="ecommerce_payments",
 *     indexes={
 *         @ORM\Index(name="ecommerce_payments_type_index", columns={"type"}),
 *         @ORM\Index(name="ecommerce_payments_external_id_index", columns={"external_id"}),
 *         @ORM\Index(name="ecommerce_payments_external_provider_index", columns={"external_provider"}),
 *         @ORM\Index(name="ecommerce_payments_status_index", columns={"status"}),
 *         @ORM\Index(name="ecommerce_payments_payment_method_id_index", columns={"payment_method_id"}),
 *         @ORM\Index(name="ecommerce_payments_currency_index", columns={"currency"}),
 *         @ORM\Index(name="ecommerce_payments_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_payments_updated_on_index", columns={"updated_at"}),
 *         @ORM\Index(name="ecommerce_payments_deleted_on_index", columns={"deleted_at"}),
 *     }
 * )
 */
class Payment
{
    use TimestampableEntity, NotableEntity;

    const STATUS_PAID = 'paid';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_FAILED = 'failed';

    const EXTERNAL_PROVIDER_STRIPE = 'stripe';
    const EXTERNAL_PROVIDER_PAYPAL = 'paypal';
    const EXTERNAL_PROVIDER_APPLE = 'apple';
    const EXTERNAL_PROVIDER_GOOGLE = 'google';

    const TYPE_INITIAL_ORDER = 'initial_order';
    const TYPE_SUBSCRIPTION_RENEWAL = 'subscription_renewal';
    const TYPE_PAYMENT_PLAN = 'payment_plan';
    const TYPE_APPLE_INITIAL_ORDER = 'apple_initial_order';
    const TYPE_APPLE_SUBSCRIPTION_RENEWAL = 'apple_subscription_renewal';
    const TYPE_GOOGLE_INITIAL_ORDER = 'google_initial_order';
    const TYPE_GOOGLE_SUBSCRIPTION_RENEWAL = 'google_subscription_renewal';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\PaymentTaxes", mappedBy="payment")
     */
    protected $paymentTaxes;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="total_due")
     *
     * @var float
     */
    protected $totalDue;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="total_paid", nullable=true)
     *
     * @var float
     */
    protected $totalPaid;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="total_refunded", nullable=true)
     *
     * @var float
     */
    protected $totalRefunded;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="conversion_rate")
     *
     * @var float
     */
    protected $conversionRate;

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
     * @ORM\Column(type="string", length=64, name="gateway_name", nullable=true)
     *
     * @var string
     */
    protected $gatewayName;

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
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\PaymentMethod")
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
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     *
     * @var \DateTime
     */
    protected $deletedOn;

    /**
     * @ORM\OneToOne(targetEntity="OrderPayment", mappedBy="payment")
     */
    protected $orderPayment;

    /**
     * @ORM\OneToOne(targetEntity="SubscriptionPayment", mappedBy="payment")
     */
    protected $subscriptionPayment;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return PaymentTaxes|null
     */
    public function getPaymentTaxes(): ?PaymentTaxes
    {
        return $this->paymentTaxes;
    }

    /**
     * @param PaymentTaxes $paymentTaxes
     */
    public function setPayment(?PaymentTaxes $paymentTaxes)
    {
        $this->paymentTaxes = $paymentTaxes;
    }

    /**
     * @return float|null
     */
    public function getTotalDue(): ?float
    {
        return $this->totalDue;
    }

    /**
     * @param float $totalDue
     */
    public function setTotalDue(float $totalDue)
    {
        $this->totalDue = $totalDue;
    }

    /**
     * @return float|null
     */
    public function getTotalPaid(): ?float
    {
        return $this->totalPaid;
    }

    /**
     * @param float $totalPaid
     */
    public function setTotalPaid(?float $totalPaid)
    {
        $this->totalPaid = $totalPaid;
    }

    /**
     * @return float|null
     */
    public function getTotalRefunded(): ?float
    {
        return $this->totalRefunded;
    }

    /**
     * @param float $totalRefunded
     */
    public function setTotalRefunded(?float $totalRefunded)
    {
        $this->totalRefunded = $totalRefunded;
    }

    /**
     * @return float|null
     */
    public function getConversionRate(): ?float
    {
        return $this->conversionRate;
    }

    /**
     * @param float $conversionRate
     */
    public function setConversionRate(float $conversionRate)
    {
        $this->conversionRate = $conversionRate;
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
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     */
    public function setExternalId(?string $externalId)
    {
        $this->externalId = $externalId;
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
     */
    public function setExternalProvider(?string $externalProvider)
    {
        $this->externalProvider = $externalProvider;
    }

    /**
     * @return string
     */
    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    /**
     * @param string $gatewayName
     */
    public function setGatewayName(string $gatewayName)
    {
        $this->gatewayName = $gatewayName;
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
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
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
     */
    public function setMessage(?string $message)
    {
        $this->message = $message;
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
     * @return \DateTimeInterface|null
     */
    public function getDeletedOn(): ?\DateTimeInterface
    {
        return $this->deletedOn;
    }

    /**
     * @param \DateTimeInterface $deletedOn
     */
    public function setDeletedOn(?\DateTimeInterface $deletedOn)
    {
        $this->deletedOn = $deletedOn;
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
     * @return OrderPayment|null
     */
    public function getOrderPayment(): ?OrderPayment
    {
        return $this->orderPayment;
    }

    /**
     * @param OrderPayment $orderPayment
     */
    public function setOrderPayment(?OrderPayment $orderPayment)
    {
        $this->orderPayment = $orderPayment;
    }

    /**
     * @return SubscriptionPayment|null
     */
    public function getSubscriptionPayment(): ?SubscriptionPayment
    {
        return $this->subscriptionPayment;
    }

    /**
     * @param SubscriptionPayment $subscriptionPayment
     */
    public function setSubscriptionPayment(
        ?SubscriptionPayment $subscriptionPayment
    ) {
        $this->subscriptionPayment = $subscriptionPayment;
    }

    /**
     * @return Order|null
     */
    public function getOrder()
    {
        if (!empty($this->getOrderPayment())) {
            return $this->getOrderPayment()
                ->getOrder();
        }

        return null;
    }

    /**
     * @return Subscription|null
     */
    public function getSubscription()
    {
        if (!empty($this->getSubscriptionPayment())) {
            return $this->getSubscriptionPayment()
                ->getSubscription();
        }

        return null;
    }
}
