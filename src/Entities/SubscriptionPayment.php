<?php

namespace Railroad\Ecommerce\Entities;

use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ecommerce_subscription_payments",
 *     indexes={
 *         @ORM\Index(name="ecommerce_subscription_payments_subscription_id_index", columns={"subscription_id"}),
 *         @ORM\Index(name="ecommerce_subscription_payments_payment_id_index", columns={"payment_id"}),
 *         @ORM\Index(name="ecommerce_subscription_payments_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_subscription_payments_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class SubscriptionPayment
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
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Subscription")
     * @ORM\JoinColumn(name="subscription_id", referencedColumnName="id")
     */
    protected $subscription;

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
     * @return Subscription|null
     */
    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    /**
     * @param Subscription $subscription
     */
    public function setSubscription(?Subscription $subscription)
    {
        $this->subscription = $subscription;
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
     */
    public function setPayment(?Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Returns createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return !empty($this->createdAt) ? $this->createdAt : Carbon::now();
    }
}
