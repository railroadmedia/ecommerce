<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\DiscountCriteriaRepository")
 * @ORM\Table(
 *     name="ecommerce_discount_criteria",
 *     indexes={
 *         @ORM\Index(name="ecommerce_discount_criteria_name_index", columns={"name"}),
 *         @ORM\Index(name="ecommerce_discount_criteria_type_index", columns={"type"}),
 *         @ORM\Index(name="ecommerce_discount_criteria_product_id_index", columns={"product_id"}),
 *         @ORM\Index(name="ecommerce_discount_criteria_discount_id_index", columns={"discount_id"}),
 *         @ORM\Index(name="ecommerce_discount_criteria_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_discount_criteria_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class DiscountCriteria
{
    use TimestampableEntity;

    const PRODUCTS_RELATION_TYPE_ANY = 'any of products';
    const PRODUCTS_RELATION_TYPE_ALL = 'all of products';

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
    protected $type;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $productsRelationType;

    /**
     * @ORM\ManyToMany(targetEntity="Railroad\Ecommerce\Entities\Product")
     * @ORM\JoinTable(name="ecommerce_discount_criterias_products",
     *      joinColumns={@ORM\JoinColumn(name="discount_criteria_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")}
     * )
     */
    protected $products;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $min;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $max;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Discount")
     * @ORM\JoinColumn(name="discount_id", referencedColumnName="id")
     */
    protected $discount;

    public function __construct()
    {
        $this->products = new ArrayCollection();
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
     * @return string|null
     */
    public function getProductsRelationType(): ?string
    {
        return $this->productsRelationType;
    }

    /**
     * @param string $productsRelationType
     */
    public function setProductsRelationType(string $productsRelationType)
    {
        $this->productsRelationType = $productsRelationType;
    }

    /**
     * @return string|null
     */
    public function getMin(): ?string
    {
        return $this->min;
    }

    /**
     * @param string $min
     */
    public function setMin(string $min)
    {
        $this->min = $min;
    }

    /**
     * @return string|null
     */
    public function getMax(): ?string
    {
        return $this->max;
    }

    /**
     * @param string $max
     */
    public function setMax(string $max)
    {
        $this->max = $max;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * @param Product $product
     */
    public function addProduct(?Product $product)
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
        }
    }

    /**
     * @param Product $product
     */
    public function removeProduct(?Product $product)
    {
        if ($this->products->contains($product)) {

            $this->products->removeElement($product);
        }
    }

    /**
     * @return Discount|null
     */
    public function getDiscount(): ?Discount
    {
        return $this->discount;
    }

    /**
     * @param Discount $discount
     */
    public function setDiscount(?Discount $discount)
    {
        $this->discount = $discount;
    }
}
