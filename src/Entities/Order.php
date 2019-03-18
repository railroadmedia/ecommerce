<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\OrderRepository")
 * @ORM\Table(
 *     name="ecommerce_order",
 *     indexes={
 *         @ORM\Index(name="ecommerce_order_user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="ecommerce_order_customer_id_index", columns={"customer_id"}),
 *         @ORM\Index(name="ecommerce_order_brand_index", columns={"brand"}),
 *         @ORM\Index(name="ecommerce_order_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_order_updated_on_index", columns={"updated_at"}),
 *         @ORM\Index(name="ecommerce_order_deleted_on_index", columns={"deleted_on"}),
 *         @ORM\Index(name="ecommerce_order_product_due_index", columns={"product_due"}),
 *         @ORM\Index(name="ecommerce_order_finance_due_index", columns={"finance_due"})
 *     }
 * )
 */
class Order
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
     * @ORM\Column(type="decimal", precision=8, scale=2, name="total_due")
     *
     * @var float
     */
    protected $totalDue;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="product_due")
     *
     * @var float
     */
    protected $productDue;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="taxes_due")
     *
     * @var float
     */
    protected $taxesDue;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="shipping_due")
     *
     * @var float
     */
    protected $shippingDue;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="finance_due")
     *
     * @var float
     */
    protected $financeDue;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, name="total_paid")
     *
     * @var float
     */
    protected $totalPaid;

    /**
     * @var User
     *
     * @ORM\Column(type="user_id", name="user_id", nullable=true)
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     */
    protected $customer;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $brand;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Address")
     * @ORM\JoinColumn(name="shipping_address_id", referencedColumnName="id")
     */
    protected $shippingAddress;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Address")
     * @ORM\JoinColumn(name="billing_address_id", referencedColumnName="id")
     */
    protected $billingAddress;

    /**
     * @ORM\Column(type="datetime", name="deleted_on", nullable=true)
     *
     * @var \DateTime
     */
    protected $deletedOn;

    /**
     * @ORM\OneToMany(targetEntity="OrderItem", mappedBy="order")
     */
    protected $orderItems;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
    }

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
    public function getTotalDue(): ?float
    {
        return $this->totalDue;
    }

    /**
     * @param float $totalDue
     *
     * @return Order
     */
    public function setTotalDue(float $totalDue): self
    {
        $this->totalDue = $totalDue;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getProductDue(): ?float
    {
        return $this->productDue;
    }

    /**
     * @param float $productDue
     *
     * @return Order
     */
    public function setProductDue(float $productDue): self
    {
        $this->productDue = $productDue;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTaxesDue(): ?float
    {
        return $this->taxesDue;
    }

    /**
     * @param float $taxesDue
     *
     * @return Order
     */
    public function setTaxesDue(float $taxesDue): self
    {
        $this->taxesDue = $taxesDue;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getShippingDue(): ?float
    {
        return $this->shippingDue;
    }

    /**
     * @param float $shippingDue
     *
     * @return Order
     */
    public function setShippingDue(float $shippingDue): self
    {
        $this->shippingDue = $shippingDue;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getFinanceDue(): ?float
    {
        return $this->financeDue;
    }

    /**
     * @param float $financeDue
     *
     * @return Order
     */
    public function setFinanceDue(float $financeDue): self
    {
        $this->financeDue = $financeDue;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTotalPaid(): ?float
    {
        return $this->totalPaid;
    }

    /**
     * @param float $totalPaid
     *
     * @return Order
     */
    public function setTotalPaid(float $totalPaid): self
    {
        $this->totalPaid = $totalPaid;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     *
     * @return Order
     */
    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDeletedOn(): ?\DateTimeInterface
    {
        return $this->deletedOn;
    }

    /**
     * @param \DateTimeInterface $deletedOn
     *
     * @return Order
     */
    public function setDeletedOn(?\DateTimeInterface $deletedOn): self
    {
        $this->deletedOn = $deletedOn;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Order
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     *
     * @return Order
     */
    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Address|null
     */
    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    /**
     * @param Address $shippingAddress
     *
     * @return Order
     */
    public function setShippingAddress(?Address $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;

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
     * @return Order
     */
    public function setBillingAddress(?Address $billingAddress): self
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * @return Collection|OrderItem[]
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    /**
     * @param OrderItem $orderItem
     *
     * @return Order
     */
    public function addOrderItem(OrderItem $orderItem): self
    {

        if (!$this->orderItems->contains($orderItem)) {

            $this->orderItems[] = $orderItem;

            $orderItem->setOrder($this);
        }

        return $this;
    }

    /**
     * @param OrderItem $orderItem
     *
     * @return Order
     */
    public function removeOrderItem(OrderItem $orderItem): self
    {

        if ($this->orderItems->contains($orderItem)) {

            $this->orderItems->removeElement($orderItem);

            // set the owning side to null (unless already changed)
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }

        return $this;
    }
}
