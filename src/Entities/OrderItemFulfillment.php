<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ecommerce_order_item_fulfillment",
 *     indexes={
 *         @ORM\Index(name="ecommerce_order_item_fulfillment_order_id_index", columns={"order_id"}),
 *         @ORM\Index(name="ecommerce_order_item_fulfillment_order_item_id_index", columns={"order_item_id"}),
 *         @ORM\Index(name="ecommerce_order_item_fulfillment_status_index", columns={"status"}),
 *         @ORM\Index(name="ecommerce_order_item_fulfillment_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_order_item_fulfillment_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class OrderItemFulfillment
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
     * @ORM\OneToOne(targetEntity="Railroad\Ecommerce\Entities\OrderItem")
     * @ORM\JoinColumn(name="order_item_id", referencedColumnName="id")
     */
    protected $orderItem;

    /**
     * @ORM\Column(type="string", length=64)
     *
     * @var string
     */
    protected $status;

    /**
     * @ORM\Column(type="string", name="company")
     *
     * @var string
     */
    protected $company;

    /**
     * @ORM\Column(type="string", name="tracking_number")
     *
     * @var string
     */
    protected $trackingNumber;

    /**
     * @ORM\Column(type="datetime", name="fulfilled_on", nullable=true)
     *
     * @var \DateTime
     */
    protected $fulfilledOn;

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
     *
     * @return OrderItemFulfillment
     */
    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
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
     *
     * @return OrderItemFulfillment
     */
    public function setOrderItem(?OrderItem $orderItem): self
    {
        $this->orderItem = $orderItem;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return OrderItemFulfillment
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @param string $company
     *
     * @return OrderItemFulfillment
     */
    public function setCompany(string $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    /**
     * @param string $trackingNumber
     *
     * @return OrderItemFulfillment
     */
    public function setTrackingNumber(string $trackingNumber): self
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getFulfilledOn(): ?\DateTimeInterface
    {
        return $this->fulfilledOn;
    }

    /**
     * @param \DateTimeInterface $fulfilledOn
     *
     * @return OrderItemFulfillment
     */
    public function setFulfilledOn(?\DateTimeInterface $fulfilledOn): self
    {
        $this->fulfilledOn = $fulfilledOn;

        return $this;
    }
}
