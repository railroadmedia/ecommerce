<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\Traits\ShopifyEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\OrderItemRepository")
 * @ORM\Table(
 *     name="ecommerce_order_items",
 *     indexes={
 *         @ORM\Index(name="ecommerce_order_items_order_id_index", columns={"order_id"}),
 *         @ORM\Index(name="ecommerce_order_items_product_id_index", columns={"product_id"}),
 *         @ORM\Index(name="ecommerce_order_items_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_order_items_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class OrderItem
{
    use TimestampableEntity, ShopifyEntity;

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
     * @ORM\OneToMany(targetEntity="OrderDiscount", mappedBy="orderItem")
     */
    protected $orderItemDiscounts;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $quantity;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     *
     * @var float
     */
    protected $weight;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="initial_price")
     *
     * @var float
     */
    protected $initialPrice;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="total_discounted")
     *
     * @var float
     */
    protected $totalDiscounted;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="final_price")
     *
     * @var float
     */
    protected $finalPrice;

    public function __construct()
    {
        $this->orderItemDiscounts = new ArrayCollection();
    }

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
     */
    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return float|null
     */
    public function getWeight(): ?float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight(float $weight)
    {
        $this->weight = $weight;
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
     */
    public function setInitialPrice(float $initialPrice)
    {
        $this->initialPrice = $initialPrice;
    }

    /**
     * @return float|null
     */
    public function getTotalDiscounted(): ?float
    {
        return $this->totalDiscounted;
    }

    /**
     * @param float $totalDiscounted
     */
    public function setTotalDiscounted(float $totalDiscounted)
    {
        $this->totalDiscounted = $totalDiscounted;
    }

    /**
     * @return float|null
     */
    public function getFinalPrice(): ?float
    {
        return $this->finalPrice;
    }

    /**
     * @param float $finalPrice
     */
    public function setFinalPrice(float $finalPrice)
    {
        $this->finalPrice = $finalPrice;
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
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(?Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return Collection|OrderDiscount[]
     */
    public function getOrderItemDiscounts(): Collection
    {
        return $this->orderItemDiscounts;
    }

    /**
     * @param OrderDiscount $orderDiscount
     */
    public function addOrderItemDiscounts(OrderDiscount $orderDiscount)
    {
        if (!$this->orderItemDiscounts->contains($orderDiscount)) {
            $this->orderItemDiscounts[] = $orderDiscount;
            $orderDiscount->setOrderItem($this);
        }
    }

    /**
     * @param OrderDiscount $orderDiscount
     */
    public function removeOrderItemDiscounts(OrderDiscount $orderDiscount)
    {
        if ($this->orderItemDiscounts->contains($orderDiscount)) {

            $this->orderItemDiscounts->removeElement($orderDiscount);

            // set the owning side to null (unless already changed)
            if ($orderDiscount->getOrderItem() === $this) {
                $orderDiscount->setOrderItem(null);
            }
        }
    }
}
