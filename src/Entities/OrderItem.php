<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

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
    public function getWeight(): ?float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     *
     * @return OrderItem
     */
    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

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
    public function getTotalDiscounted(): ?float
    {
        return $this->totalDiscounted;
    }

    /**
     * @param float $totalDiscounted
     *
     * @return OrderItem
     */
    public function setTotalDiscounted(float $totalDiscounted): self
    {
        $this->totalDiscounted = $totalDiscounted;

        return $this;
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
     *
     * @return OrderItem
     */
    public function setFinalPrice(float $finalPrice): self
    {
        $this->finalPrice = $finalPrice;

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

    /**
     * @return Collection|OrderDiscount[]
     */
    public function getOrderItemDiscounts(): Collection
    {
        return $this->orderItemDiscounts;
    }

    /**
     * @param OrderDiscount $orderDiscount
     *
     * @return OrderItem
     */
    public function addOrderItemDiscounts(OrderDiscount $orderDiscount): self
    {
        if (!$this->orderItemDiscounts->contains($orderDiscount)) {
            $this->orderItemDiscounts[] = $orderDiscount;
            $orderDiscount->setOrderItem($this);
        }

        return $this;
    }

    /**
     * @param OrderDiscount $orderDiscount
     *
     * @return OrderItem
     */
    public function removeOrderItemDiscounts(OrderDiscount $orderDiscount): self
    {
        if ($this->orderItemDiscounts->contains($orderDiscount)) {

            $this->orderItemDiscounts->removeElement($orderDiscount);

            // set the owning side to null (unless already changed)
            if ($orderDiscount->getOrderItem() === $this) {
                $orderDiscount->setOrderItem(null);
            }
        }

        return $this;
    }
}
