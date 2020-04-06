<?php

namespace Railroad\Ecommerce\Entities;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\Traits\NotableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\DiscountRepository")
 * @ORM\Table(
 *     name="ecommerce_discounts",
 *     indexes={
 *         @ORM\Index(name="ecommerce_discounts_name_index", columns={"name"}),
 *         @ORM\Index(name="ecommerce_discounts_type_index", columns={"type"}),
 *         @ORM\Index(name="ecommerce_discounts_active_index", columns={"active"}),
 *         @ORM\Index(name="ecommerce_discounts_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_discounts_updated_on_index", columns={"updated_at"}),
 *         @ORM\Index(name="ecommerce_discounts_product_id_index", columns={"product_id"}),
 *         @ORM\Index(name="ecommerce_discounts_visible_index", columns={"visible"}),
 *         @ORM\Index(name="ecommerce_discounts_product_category_index", columns={"product_category"})
 *     }
 * )
 */
class Discount
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
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     *
     * @var float
     */
    protected $amount;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    protected $product;

    /**
     * @ORM\Column(type="string", name="product_category", nullable=true)
     *
     * @var string
     */
    protected $productCategory;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $active;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $visible;

    /**
     * @ORM\Column(type="datetime", name="expiration_date", nullable=true)
     *
     * @var DateTime
     */
    protected $expirationDate;

    /**
     * @ORM\OneToMany(targetEntity="DiscountCriteria", mappedBy="discount")
     */
    protected $discountCriterias;

    public function __construct()
    {
        $this->discountCriterias = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount(float $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string|null
     */
    public function getProductCategory(): ?string
    {
        return $this->productCategory;
    }

    /**
     * @param string $productCategory
     */
    public function setProductCategory(?string $productCategory)
    {
        $this->productCategory = $productCategory;
    }

    /**
     * @return bool|null
     */
    public function getActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active)
    {
        $this->active = $active;
    }

    /**
     * @return bool|null
     */
    public function getVisible(): ?bool
    {
        return $this->visible;
    }

    /**
     * @param bool $visible
     */
    public function setVisible(bool $visible)
    {
        $this->visible = $visible;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getExpirationDate(): ?DateTimeInterface
    {
        return $this->expirationDate;
    }

    /**
     * @param DateTimeInterface $expirationDate
     */
    public function setExpirationDate(?DateTimeInterface $expirationDate)
    {
        $this->expirationDate = $expirationDate;
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
     * @return Collection|DiscountCriteria[]
     */
    public function getDiscountCriterias(): Collection
    {
        return $this->discountCriterias;
    }

    /**
     * @param DiscountCriteria $discountCriteria
     */
    public function addDiscountCriteria(
        DiscountCriteria $discountCriteria
    ) {
        if (!$this->discountCriterias->contains($discountCriteria)) {
            $this->discountCriterias[] = $discountCriteria;
            $discountCriteria->setDiscount($this);
        }
    }

    /**
     * @param DiscountCriteria $discountCriteria
     */
    public function removeDiscountCriteria(
        DiscountCriteria $discountCriteria
    ) {
        if ($this->discountCriterias->contains($discountCriteria)) {

            $this->discountCriterias->removeElement($discountCriteria);

            // set the owning side to null (unless already changed)
            if ($discountCriteria->getDiscount() === $this) {
                $discountCriteria->setDiscount(null);
            }
        }
    }
}
