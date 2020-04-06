<?php

namespace Railroad\Ecommerce\Entities;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\Traits\NotableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository")
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
     * @var DateTime
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
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
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
     */
    public function setCompany(string $company)
    {
        $this->company = $company;
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
     */
    public function setTrackingNumber(string $trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getFulfilledOn(): ?DateTimeInterface
    {
        return $this->fulfilledOn;
    }

    /**
     * @param DateTimeInterface $fulfilledOn
     */
    public function setFulfilledOn(?DateTimeInterface $fulfilledOn)
    {
        $this->fulfilledOn = $fulfilledOn;
    }
}
