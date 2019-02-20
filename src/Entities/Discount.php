<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="ecommerce_discount",
 *     indexes={
 *         @ORM\Index(name="ecommerce_discount_name_index", columns={"name"}),
 *         @ORM\Index(name="ecommerce_discount_type_index", columns={"type"}),
 *         @ORM\Index(name="ecommerce_discount_active_index", columns={"active"}),
 *         @ORM\Index(name="ecommerce_discount_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_discount_updated_on_index", columns={"updated_at"}),
 *         @ORM\Index(name="ecommerce_discount_product_id_index", columns={"product_id"}),
 *         @ORM\Index(name="ecommerce_discount_visible_index", columns={"visible"}),
 *         @ORM\Index(name="ecommerce_discount_product_category_index", columns={"product_category"})
 *     }
 * )
 */
class Discount
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
     *
     * @return Discount
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
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
     *
     * @return Discount
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
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
     *
     * @return Discount
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
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
     *
     * @return Discount
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
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
     *
     * @return Discount
     */
    public function setProductCategory(?string $productCategory): self
    {
        $this->productCategory = $productCategory;

        return $this;
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
     *
     * @return Discount
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
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
     *
     * @return Discount
     */
    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

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
     * @return Discount
     */
    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
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
     *
     * @return Discount
     */
    public function addDiscountCriteria(
        DiscountCriteria $discountCriteria
    ): self {

        if (!$this->discountCriterias->contains($discountCriteria)) {
            $this->discountCriterias[] = $discountCriteria;
            $discountCriteria->setDiscount($this);
        }

        return $this;
    }

    /**
     * @param DiscountCriteria $shippingCostsWeightRange
     *
     * @return Discount
     */
    public function removeDiscountCriteria(
        DiscountCriteria $discountCriteria
    ): self {

        if ($this->discountCriterias->contains($discountCriteria)) {

            $this->discountCriterias->removeElement($discountCriteria);

            // set the owning side to null (unless already changed)
            if ($discountCriteria->getDiscount() === $this) {
                $discountCriteria->setDiscount(null);
            }
        }

        return $this;
    }
}
