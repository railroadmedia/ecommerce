<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Proxy\Proxy;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\PaymentMethodRepository")
 * @ORM\Table(
 *     name="ecommerce_payment_methods",
 *     indexes={
 *         @ORM\Index(name="ecommerce_payment_methods_method_id_index", columns={"method_id"}),
 *         @ORM\Index(name="ecommerce_payment_methods_method_type_index", columns={"method_type"}),
 *         @ORM\Index(name="ecommerce_payment_methods_currency_index", columns={"currency"}),
 *         @ORM\Index(name="ecommerce_payment_methods_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_payment_methods_updated_on_index", columns={"updated_at"}),
 *         @ORM\Index(name="ecommerce_payment_methods_deleted_on_index", columns={"deleted_at"}),
 *     }
 * )
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class PaymentMethod
{
    use TimestampableEntity, SoftDeleteableEntity;

    const TYPE_CREDIT_CARD = 'credit_card';
    const TYPE_PAYPAL = 'paypal';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\CreditCard", fetch="EAGER")
     * @ORM\JoinColumn(name="credit_card_id", referencedColumnName="id")
     *
     * @var CreditCard|null
     */
    protected $creditCard;

    /**
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\PaypalBillingAgreement", fetch="EAGER")
     * @ORM\JoinColumn(name="paypal_billing_agreement_id", referencedColumnName="id")
     *
     * @var PaypalBillingAgreement|null
     */
    protected $paypalBillingAgreement;

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
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\UserPaymentMethods", inversedBy="paymentMethod")
     * @ORM\JoinColumn(name="id", referencedColumnName="payment_method_id")
     */
    protected $userPaymentMethod;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return CreditCard|null
     */
    public function getCreditCard(): ?CreditCard
    {
        return $this->creditCard;
    }

    /**
     * @param CreditCard|null $creditCard
     */
    public function setCreditCard(?CreditCard $creditCard): PaymentMethod
    {
        $this->creditCard = $creditCard;

        return $this;
    }

    /**
     * @return PaypalBillingAgreement|null
     */
    public function getPaypalBillingAgreement(): ?PaypalBillingAgreement
    {
        return $this->paypalBillingAgreement;
    }

    /**
     * @param PaypalBillingAgreement|null $paypalBillingAgreement
     */
    public function setPaypalBillingAgreement(?PaypalBillingAgreement $paypalBillingAgreement): PaymentMethod
    {
        $this->paypalBillingAgreement = $paypalBillingAgreement;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethodType(): ?string
    {
        if (!empty($this->creditCard)) {
            return self::TYPE_CREDIT_CARD;
        }
        elseif (!empty($this->paypalBillingAgreement)) {
            return self::TYPE_PAYPAL;
        }
        else {
            return 'unknown';
        }
    }

    /**
     * @return CreditCard|PaypalBillingAgreement|null
     */
    public function getMethod()
    {
        if ($this->getMethodType() == self::TYPE_CREDIT_CARD) {
            return $this->creditCard;
        }

        if ($this->getMethodType() == self::TYPE_PAYPAL) {
            return $this->paypalBillingAgreement;
        }

        return null;
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
     * @return string|null
     */
    public function getExternalProvider()
    {
        if ($this->getMethodType() == self::TYPE_CREDIT_CARD) {
            return Payment::EXTERNAL_PROVIDER_STRIPE;
        }

        if ($this->getMethodType() == self::TYPE_PAYPAL) {
            return Payment::EXTERNAL_PROVIDER_PAYPAL;
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getUserPaymentMethod()
    {
        return $this->userPaymentMethod;
    }
}