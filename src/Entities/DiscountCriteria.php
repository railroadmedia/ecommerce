<?php

namespace Railroad\Ecommerce\Entities;

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
     * @ORM\ManyToOne(targetEntity="Railroad\Ecommerce\Entities\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    protected $product;

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
