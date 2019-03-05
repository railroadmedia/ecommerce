<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Contracts\UserInterface;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository")
 * @ORM\Table(
 *     name="ecommerce_user_payment_methods",
 *     indexes={
 *         @ORM\Index(name="ecommerce_user_payment_methods_user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="ecommerce_user_payment_methods_payment_method_id_index", columns={"payment_method_id"}),
 *         @ORM\Index(name="ecommerce_user_payment_methods_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_user_payment_methods_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class UserPaymentMethods
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
     * @var int
     *
     * @ORM\Column(type="user_id", name="user_id", nullable=true)
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\PaymentMethod")
     * @ORM\JoinColumn(name="payment_method_id", referencedColumnName="id")
     */
    protected $paymentMethod;

    /**
     * @ORM\Column(type="boolean", name="is_primary")
     *
     * @var bool
     */
    protected $isPrimary;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * @param UserInterface $user
     *
     * @return UserPaymentMethods
     */
    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return PaymentMethod|null
     */
    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * @param PaymentMethod $paymentMethod
     *
     * @return UserPaymentMethods
     */
    public function setPaymentMethod(?PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsPrimary(): ?bool
    {
        return $this->isPrimary;
    }

    /**
     * @param bool $isPrimary
     *
     * @return UserPaymentMethods
     */
    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }
}