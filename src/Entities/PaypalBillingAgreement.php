<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository")
 * @ORM\Table(
 *     name="ecommerce_paypal_billing_agreements",
 *     indexes={
 *         @ORM\Index(name="ecommerce_paypal_billing_agreements_external_id_index", columns={"external_id"}),
 *         @ORM\Index(
 *             name="ecommerce_paypal_billing_agreements_payment_gateway_name_index",
 *             columns={"payment_gateway_name"}
 *         ),
 *         @ORM\Index(name="ecommerce_paypal_billing_agreements_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_paypal_billing_agreements_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class PaypalBillingAgreement
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
     * @ORM\Column(type="string", length=64, name="external_id")
     *
     * @var string
     */
    protected $externalId;

    /**
     * @ORM\Column(type="string", length=64, name="payment_gateway_name")
     *
     * @var string
     */
    protected $paymentGatewayName;

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
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     *
     * @return PaypalBillingAgreement
     */
    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaymentGatewayName(): ?string
    {
        return $this->paymentGatewayName;
    }

    /**
     * @param string $paymentGatewayName
     *
     * @return PaypalBillingAgreement
     */
    public function setPaymentGatewayName(string $paymentGatewayName): self
    {
        $this->paymentGatewayName = $paymentGatewayName;

        return $this;
    }
}
