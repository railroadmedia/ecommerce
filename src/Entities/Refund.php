<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\Traits\NotableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\RefundRepository")
 * @ORM\Table(
 *     name="ecommerce_refunds",
 *     indexes={
 *         @ORM\Index(name="ecommerce_refunds_payment_id_index", columns={"payment_id"}),
 *         @ORM\Index(name="ecommerce_refunds_external_provider_index", columns={"external_provider"}),
 *         @ORM\Index(name="ecommerce_refunds_external_id_index", columns={"external_id"}),
 *         @ORM\Index(name="ecommerce_refunds_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_refunds_updated_on_index", columns={"updated_at"}),
 *     }
 * )
 */
class Refund
{
    use TimestampableEntity, NotableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\Payment")
     * @ORM\JoinColumn(name="payment_id", referencedColumnName="id")
     */
    protected $payment;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="payment_amount")
     *
     * @var float
     */
    protected $paymentAmount;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="refunded_amount")
     *
     * @var float
     */
    protected $refundedAmount;

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
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Payment|null
     */
    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    /**
     * @param Payment $payment
     */
    public function setPayment(?Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return float|null
     */
    public function getPaymentAmount(): ?float
    {
        return $this->paymentAmount;
    }

    /**
     * @param float $paymentAmount
     */
    public function setPaymentAmount(?float $paymentAmount)
    {
        $this->paymentAmount = $paymentAmount;
    }

    /**
     * @return float|null
     */
    public function getRefundedAmount(): ?float
    {
        return $this->refundedAmount;
    }

    /**
     * @param float $refundedAmount
     */
    public function setRefundedAmount(?float $refundedAmount)
    {
        $this->refundedAmount = $refundedAmount;
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
}
