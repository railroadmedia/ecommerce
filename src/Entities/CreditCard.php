<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\CreditCardRepository")
 * @ORM\Table(
 *     name="ecommerce_credit_cards",
 *     indexes={
 *         @ORM\Index(name="ecommerce_credit_cards_company_name_index", columns={"company_name"}),
 *         @ORM\Index(name="ecommerce_credit_cards_external_id_index", columns={"external_id"}),
 *         @ORM\Index(name="ecommerce_credit_cards_external_customer_id_index", columns={"external_customer_id"}),
 *         @ORM\Index(name="ecommerce_credit_cards_payment_gateway_name_index", columns={"payment_gateway_name"}),
 *         @ORM\Index(name="ecommerce_credit_cards_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_credit_cards_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class CreditCard
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
    protected $fingerprint;

    /**
     * @ORM\Column(type="integer", name="last_four_digits")
     *
     * @var int
     */
    protected $lastFourDigits;

    /**
     * @ORM\Column(type="string", name="cardholder_name", nullable=true)
     *
     * @var string
     */
    protected $cardholderName;

    /**
     * @ORM\Column(type="string", name="company_name")
     *
     * @var string
     */
    protected $companyName;

    /**
     * @ORM\Column(type="datetime", name="expiration_date")
     *
     * @var \DateTime
     */
    protected $expirationDate;

    /**
     * @ORM\Column(type="string", length=64, name="external_id")
     *
     * @var string
     */
    protected $externalId;

    /**
     * @ORM\Column(
     *     type="string",
     *     length=64,
     *     name="external_customer_id",
     *     nullable=true
     * )
     *
     * @var string
     */
    protected $externalCustomerId;

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
    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    /**
     * @param string $fingerprint
     */
    public function setFingerprint(string $fingerprint)
    {
        $this->fingerprint = $fingerprint;
    }

    /**
     * @return int|null
     */
    public function getLastFourDigits(): ?int
    {
        return $this->lastFourDigits;
    }

    /**
     * @param int $lastFourDigits
     */
    public function setLastFourDigits(int $lastFourDigits)
    {
        $this->lastFourDigits = $lastFourDigits;
    }

    /**
     * @return string|null
     */
    public function getCardholderName(): ?string
    {
        return $this->cardholderName;
    }

    /**
     * @param string $cardholderName
     */
    public function setCardholderName(?string $cardholderName)
    {
        $this->cardholderName = $cardholderName;
    }

    /**
     * @return string|null
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName(string $companyName)
    {
        $this->companyName = $companyName;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getExpirationDate(): ?\DateTimeInterface
    {
        return $this->expirationDate;
    }

    /**
     * @param \DateTimeInterface $expirationDate
     */
    public function setExpirationDate(\DateTimeInterface $expirationDate)
    {
        $this->expirationDate = $expirationDate;
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
    public function setExternalId(string $externalId)
    {
        $this->externalId = $externalId;
    }

    /**
     * @return string|null
     */
    public function getExternalCustomerId(): ?string
    {
        return $this->externalCustomerId;
    }

    /**
     * @param string $externalCustomerId
     */
    public function setExternalCustomerId(?string $externalCustomerId)
    {
        $this->externalCustomerId = $externalCustomerId;
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
     */
    public function setPaymentGatewayName(string $paymentGatewayName)
    {
        $this->paymentGatewayName = $paymentGatewayName;
    }
}
