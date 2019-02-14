<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ecommerce_order_payment",
 *     indexes={
 *         @ORM\Index(name="ecommerce_order_payment_order_id_index", columns={"order_id"}),
 *         @ORM\Index(name="ecommerce_order_payment_payment_id_index", columns={"payment_id"}),
 *         @ORM\Index(name="ecommerce_order_payment_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_order_payment_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class OrderPayment
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
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Payment")
     * @ORM\JoinColumn(name="payment_id", referencedColumnName="id")
     */
    protected $payment;

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
     * @return OrderPayment
     */
    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
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
     *
     * @return OrderPayment
     */
    public function setPayment(?Payment $payment): self
    {
        $this->payment = $payment;

        return $this;
    }
}
