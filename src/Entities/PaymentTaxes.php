<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\PaymentRepository")
 * @ORM\Table(
 *     name="ecommerce_payment_taxes",
 *     indexes={
 *         @ORM\Index(name="ecommerce_payment_taxes_payment_id_index", columns={"payment_id"}),
 *     }
 * )
 */
class PaymentTaxes
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
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\Payment")
     * @ORM\JoinColumn(name="payment_id", referencedColumnName="id")
     */
    protected $payment;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $country;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $region;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="product_rate", nullable=true)
     *
     * @var float
     */
    protected $productRate;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="shipping_rate", nullable=true)
     *
     * @var float
     */
    protected $shippingRate;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="product_taxes_paid", nullable=true)
     *
     * @var float
     */
    protected $productTaxesPaid;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="shipping_taxes_paid", nullable=true)
     *
     * @var float
     */
    protected $shippingTaxesPaid;

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
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry(?string $country)
    {
        $this->country = $country;
    }

    /**
     * @return string|null
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion(?string $region)
    {
        $this->region = $region;
    }

    /**
     * @return float|null
     */
    public function getProductRate(): ?float
    {
        return $this->productRate;
    }

    /**
     * @param float $productRate
     */
    public function setProductRate(float $productRate)
    {
        $this->productRate = $productRate;
    }

    /**
     * @return float|null
     */
    public function getShippingRate(): ?float
    {
        return $this->shippingRate;
    }

    /**
     * @param float $shippingRate
     */
    public function setShippingRate(float $shippingRate)
    {
        $this->shippingRate = $shippingRate;
    }

    /**
     * @return float|null
     */
    public function getProductTaxesPaid(): ?float
    {
        return $this->productTaxesPaid;
    }

    /**
     * @param float $productTaxesPaid
     */
    public function setProductTaxesPaid(float $productTaxesPaid)
    {
        $this->productTaxesPaid = $productTaxesPaid;
    }

    /**
     * @return float|null
     */
    public function getShippingTaxesPaid(): ?float
    {
        return $this->shippingTaxesPaid;
    }

    /**
     * @param float $shippingTaxesPaid
     */
    public function setShippingTaxesPaid(float $shippingTaxesPaid)
    {
        $this->shippingTaxesPaid = $shippingTaxesPaid;
    }
}
