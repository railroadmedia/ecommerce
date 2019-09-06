<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\UserStripeCustomerIdRepository")
 * @ORM\Table(
 *     name="ecommerce_user_stripe_customer_ids",
 *     indexes={
 *         @ORM\Index(name="ecommerce_user_stripe_customer_ids_user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="ecommerce_user_stripe_customer_ids_stripe_customer_id_index", columns={"stripe_customer_id"}),
 *         @ORM\Index(name="ecommerce_user_stripe_customer_ids_created_at_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_user_stripe_customer_ids_updated_at_index", columns={"updated_at"})
 *     }
 * )
 */
class UserStripeCustomerId
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
     * @var User
     *
     * @ORM\Column(type="user", name="user_id")
     */
    protected $user;

    /**
     * @ORM\Column(
     *     type="string",
     *     length=64,
     *     name="stripe_customer_id"
     * )
     *
     * @var string
     */
    protected $stripeCustomerId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="payment_gateway_name", nullable=true)
     */
    protected $paymentGatewayName;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return string|null
     */
    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    /**
     * @param string $stripeCustomerId
     */
    public function setStripeCustomerId(?string $stripeCustomerId)
    {
        $this->stripeCustomerId = $stripeCustomerId;
    }

    /**
     * @return string
     */
    public function getPaymentGatewayName(): ?string
    {
        return $this->paymentGatewayName;
    }

    /**
     * @param string $paymentGatewayName
     */
    public function setPaymentGatewayName(?string $paymentGatewayName): void
    {
        $this->paymentGatewayName = $paymentGatewayName;
    }
}
