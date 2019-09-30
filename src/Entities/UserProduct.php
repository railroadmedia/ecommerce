<?php

namespace Railroad\Ecommerce\Entities;

use Carbon\Carbon;
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
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Product", fetch="EAGER")
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
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     *
     * @var \DateTime
     */
    protected $deletedAt;

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
     */
    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;
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
     */
    public function setExpirationDate(
        ?\DateTimeInterface $expirationDate
    )
    {
        $this->expirationDate = $expirationDate;
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
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(?Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTimeInterface $deletedAt
     */
    public function setDeletedAt(?\DateTimeInterface $deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if (empty($this->getExpirationDate()) || $this->getExpirationDate() > Carbon::now()) {
            return true;
        }

        return false;
    }
}
