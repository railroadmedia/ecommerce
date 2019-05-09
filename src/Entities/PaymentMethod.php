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
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\CreditCard", fetch="LAZY")
     * @ORM\JoinColumn(name="method_id", referencedColumnName="id")
     */
    protected $creditCard; // sets $this->methodId null if not commented out

    /**
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\PaypalBillingAgreement", fetch="LAZY")
     * @ORM\JoinColumn(name="method_id", referencedColumnName="id")
     */
    protected $payPalBillingAgreement; // sets $this->methodId null if not commented out

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
     * @return CreditCard|PaypalBillingAgreement|null
     */
    public function getMethod()
    {
        if ($this->getMethodType() == self::TYPE_CREDIT_CARD) {
            $this->creditCard->getCreatedAt();

            return $this->creditCard;
        }

        if ($this->getMethodType() == self::TYPE_PAYPAL) {
            $this->payPalBillingAgreement->getCreatedAt();

            return $this->payPalBillingAgreement;
        }

        return null;
    }
}