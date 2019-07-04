<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\Traits\NotableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\OrderRepository")
 * @ORM\Table(
 *     name="ecommerce_orders",
 *     indexes={
 *         @ORM\Index(name="ecommerce_orders_user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="ecommerce_orders_customer_id_index", columns={"customer_id"}),
 *         @ORM\Index(name="ecommerce_orders_brand_index", columns={"brand"}),
 *         @ORM\Index(name="ecommerce_orders_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_orders_updated_on_index", columns={"updated_at"}),
 *         @ORM\Index(name="ecommerce_orders_deleted_on_index", columns={"deleted_at"}),
 *         @ORM\Index(name="ecommerce_orders_product_due_index", columns={"product_due"}),
 *         @ORM\Index(name="ecommerce_orders_finance_due_index", columns={"finance_due"})
 *     }
 * )
 */
class Order
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
     * @ORM\Column(type="user", name="user_id", nullable=true)
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     */
    protected $customer;

    /**
     * @var User
     *
     * @ORM\Column(type="user", name="placed_by_user_id", nullable=true)
     */
    protected $placedByUser;

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
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     *
     * @var \DateTime
     */
    protected $deletedOn;

    /**
     * @ORM\OneToMany(targetEntity="OrderItem", mappedBy="order")
     */
    protected $orderItems;

    /**
     * @ORM\ManyToMany(targetEntity="Railroad\Ecommerce\Entities\Payment")
     * @ORM\JoinTable(name="ecommerce_order_payments",
     *      joinColumns={@ORM\JoinColumn(name="order_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="payment_id", referencedColumnName="id", unique=true)}
     *      )
     */
    protected $payments;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->payments = new ArrayCollection();
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
     */
    public function setTotalDue(float $totalDue)
    {
        $this->totalDue = $totalDue;
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
     */
    public function setProductDue(float $productDue)
    {
        $this->productDue = $productDue;
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
     */
    public function setTaxesDue(float $taxesDue)
    {
        $this->taxesDue = $taxesDue;
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
     */
    public function setShippingDue(float $shippingDue)
    {
        $this->shippingDue = $shippingDue;
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
     */
    public function setFinanceDue(?float $financeDue)
    {
        $this->financeDue = $financeDue;
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
     */
    public function setTotalPaid(float $totalPaid)
    {
        $this->totalPaid = $totalPaid;
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
     */
    public function setBrand(string $brand)
    {
        $this->brand = $brand;
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
     */
    public function setDeletedOn(?\DateTimeInterface $deletedOn)
    {
        $this->deletedOn = $deletedOn;
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
     */
    public function setUser(?User $user)
    {
        $this->user = $user;
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
     */
    public function setCustomer(?Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return User|null
     */
    public function getPlacedByUser(): ?User
    {
        return $this->placedByUser;
    }

    /**
     * @param User $placedByUser
     */
    public function setPlacedByUser(?User $placedByUser)
    {
        $this->placedByUser = $placedByUser;
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
     */
    public function setShippingAddress(?Address $shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;
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
     */
    public function setBillingAddress(?Address $billingAddress)
    {
        $this->billingAddress = $billingAddress;
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
     */
    public function addOrderItem(OrderItem $orderItem)
    {
        if (!$this->orderItems->contains($orderItem)) {

            $this->orderItems[] = $orderItem;

            $orderItem->setOrder($this);
        }
    }

    /**
     * @param OrderItem $orderItem
     */
    public function removeOrderItem(OrderItem $orderItem)
    {
        if ($this->orderItems->contains($orderItem)) {

            $this->orderItems->removeElement($orderItem);

            // set the owning side to null (unless already changed)
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }
    }

    /**
     * @return ArrayCollection|Payment[]
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * @param ArrayCollection $payments
     */
    public function setPayments(ArrayCollection $payments): void
    {
        $this->payments = $payments;
    }
}
