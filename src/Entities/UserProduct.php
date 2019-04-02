<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\UserProductRepository")
 * @ORM\Table(
 *     name="ecommerce_user_products",
 *     indexes={
 *         @ORM\Index(name="ecommerce_user_products_user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="ecommerce_user_products_product_id_index", columns={"product_id"}),
 *         @ORM\Index(name="ecommerce_user_products_quantity_index", columns={"quantity"}),
 *         @ORM\Index(name="ecommerce_user_products_expiration_date_index", columns={"expiration_date"}),
 *         @ORM\Index(name="ecommerce_user_products_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_user_products_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class UserProduct
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
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    protected $product;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $quantity;

    /**
     * @ORM\Column(type="datetime", name="expiration_date", nullable=true)
     *
     * @var \DateTime
     */
    protected $expirationDate;

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
     * @return UserProduct
     */
    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getExpirationDate(): ?\DateTimeInterface
    {
        return $this->expirationDate;
    }

    /**
     * @param \DateTimeInterface $expirationDate
     *
     * @return UserProduct
     */
    public function setExpirationDate(
        ?\DateTimeInterface $expirationDate
    ): self {
        $this->expirationDate = $expirationDate;

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
     * @return UserProduct
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;

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
     * @return UserProduct
     */
    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }
}
