<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ecommerce_order_discounts",
 *     indexes={
 *         @ORM\Index(name="ecommerce_order_discounts_order_id_index", columns={"order_id"}),
 *         @ORM\Index(name="ecommerce_order_discounts_order_item_id_index", columns={"order_item_id"}),
 *         @ORM\Index(name="ecommerce_order_discounts_discount_id_index", columns={"discount_id"}),
 *         @ORM\Index(name="ecommerce_order_discounts_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_order_discounts_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class OrderDiscount
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
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\OrderItem")
     * @ORM\JoinColumn(name="order_item_id", referencedColumnName="id")
     */
    protected $orderItem;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Discount")
     * @ORM\JoinColumn(name="discount_id", referencedColumnName="id")
     */
    protected $discount;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return OrderItem|null
     */
    public function getOrderItem(): ?OrderItem
    {
        return $this->orderItem;
    }

    /**
     * @param OrderItem $orderItem
     */
    public function setOrderItem(?OrderItem $orderItem)
    {
        $this->orderItem = $orderItem;
    }

    /**
     * @return Discount|null
     */
    public function getDiscount(): ?Discount
    {
        return $this->discount;
    }

    /**
     * @param Discount $discount
     */
    public function setDiscount(?Discount $discount)
    {
        $this->discount = $discount;
    }
}
