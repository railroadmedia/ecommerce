<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ecommerce_order_item",
 *     indexes={
 *         @ORM\Index(name="ecommerce_order_item_order_id_index", columns={"order_id"}),
 *         @ORM\Index(name="ecommerce_order_item_product_id_index", columns={"product_id"}),
 *         @ORM\Index(name="ecommerce_order_item_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_order_item_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class OrderItem
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
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $quantity;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="initial_price")
     *
     * @var float
     */
    protected $initialPrice;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     *
     * @var float
     */
    protected $discount;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     *
     * @var float
     */
    protected $tax;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="shipping_costs")
     *
     * @var float
     */
    protected $shippingCosts;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="total_price")
     *
     * @var float
     */
    protected $totalPrice;

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
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     *
     * @return OrderItem
     */
    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getInitialPrice(): ?float
    {
        return $this->initialPrice;
    }

    /**
     * @param float $initialPrice
     *
     * @return OrderItem
     */
    public function setInitialPrice(float $initialPrice): self
    {
        $this->initialPrice = $initialPrice;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    /**
     * @param float $discount
     *
     * @return OrderItem
     */
    public function setDiscount(float $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTax(): ?float
    {
        return $this->tax;
    }

    /**
     * @param float $tax
     *
     * @return OrderItem
     */
    public function setTax(float $tax): self
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getShippingCosts(): ?float
    {
        return $this->shippingCosts;
    }

    /**
     * @param float $shippingCosts
     *
     * @return OrderItem
     */
    public function setShippingCosts(float $shippingCosts): self
    {
        $this->shippingCosts = $shippingCosts;

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
     * @return OrderItem
     */
    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

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
     * @return OrderItem
     */
    public function setOrder(?Order $order): self
    {
        $this->order = $order;

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
     * @return OrderItem
     */
    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }
}
