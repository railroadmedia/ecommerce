<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\ShippingCostsWeightRangeRepository")
 * @ORM\Table(
 *     name="ecommerce_shipping_costs_weight_range",
 *     indexes={
 *         @ORM\Index(
 *             name="ecommerce_shipping_costs_weight_range_shipping_option_id_index",
 *             columns={"shipping_option_id"}
 *         ),
 *         @ORM\Index(
 *             name="ecommerce_shipping_costs_weight_range_created_on_index",
 *             columns={"created_at"}
 *         ),
 *         @ORM\Index(
 *             name="ecommerce_shipping_costs_weight_range_updated_on_index",
 *             columns={"updated_at"}
 *         )
 *     }
 * )
 */
class ShippingCostsWeightRange
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
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\ShippingOption")
     * @ORM\JoinColumn(name="shipping_option_id", referencedColumnName="id")
     */
    protected $shippingOption;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     *
     * @var float
     */
    protected $min;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     *
     * @var float
     */
    protected $max;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     *
     * @var float
     */
    protected $price;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return float|null
     */
    public function getMin(): ?float
    {
        return $this->min;
    }

    /**
     * @param float $min
     *
     * @return ShippingCostsWeightRange
     */
    public function setMin(float $min): self
    {
        $this->min = $min;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getMax(): ?float
    {
        return $this->max;
    }

    /**
     * @param float $max
     *
     * @return ShippingCostsWeightRange
     */
    public function setMax(float $max): self
    {
        $this->max = $max;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * @param float $price
     *
     * @return ShippingCostsWeightRange
     */
    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return ShippingOption|null
     */
    public function getShippingOption(): ?ShippingOption
    {
        return $this->shippingOption;
    }

    /**
     * @param ShippingOption $shippingOption
     *
     * @return ShippingCostsWeightRange
     */
    public function setShippingOption(?ShippingOption $shippingOption): self
    {
        $this->shippingOption = $shippingOption;

        return $this;
    }
}
