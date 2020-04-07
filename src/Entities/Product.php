<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\Traits\NotableEntity;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\ProductRepository")
 * @ORM\Table(
 *     name="ecommerce_products",
 *     indexes={
 *         @ORM\Index(name="ecommerce_products_brand_index", columns={"brand"}),
 *         @ORM\Index(name="ecommerce_products_name_index", columns={"name"}),
 *         @ORM\Index(name="ecommerce_products_sku_index", columns={"sku"}),
 *         @ORM\Index(name="ecommerce_products_type_index", columns={"type"}),
 *         @ORM\Index(name="ecommerce_products_active_index", columns={"active"}),
 *         @ORM\Index(name="ecommerce_products_subscription_interval_type_index", columns={"subscription_interval_type"}),
 *         @ORM\Index(name="ecommerce_products_subscription_interval_count_index", columns={"subscription_interval_count"}),
 *         @ORM\Index(name="ecommerce_products_stock_index", columns={"stock"}),
 *         @ORM\Index(name="ecommerce_products_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_products_updated_on_index", columns={"updated_at"}),
 *         @ORM\Index(name="ecommerce_products_category_index", columns={"category"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class Product
{
    use TimestampableEntity, SoftDeleteableEntity, NotableEntity;

    const TYPE_DIGITAL_SUBSCRIPTION = 'digital subscription';
    const TYPE_DIGITAL_ONE_TIME = 'digital one time';
    const TYPE_PHYSICAL_ONE_TIME = 'physical one time';

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
    protected $brand;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", unique=true)
     *
     * @var string
     */
    protected $sku;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     *
     * @var float
     */
    protected $price;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $active;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $category;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(type="text", name="thumbnail_url", nullable=true)
     *
     * @var string
     */
    protected $thumbnailUrl;

    /**
     * @ORM\Column(type="boolean", name="is_physical")
     *
     * @var bool
     */
    protected $isPhysical;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     *
     * @var float
     */
    protected $weight;

    /**
     * @ORM\Column(type="string", name="subscription_interval_type", nullable=true)
     *
     * @var string
     */
    protected $subscriptionIntervalType;

    /**
     * @ORM\Column(type="integer", name="subscription_interval_count", nullable=true)
     *
     * @var int
     */
    protected $subscriptionIntervalCount;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var int
     */
    protected $stock;

    /**
     * @ORM\Column(type="boolean", name="auto_decrement_stock")
     *
     * @var bool
     */
    protected $autoDecrementStock = false;

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
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     */
    public function setBrand(string $brand)
    {
        $this->brand = $brand;
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
    public function getSku(): ?string
    {
        return $this->sku;
    }

    /**
     * @param string $sku
     */
    public function setSku(string $sku)
    {
        $this->sku = $sku;
    }

    /**
     * @return float|null
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price)
    {
        $this->price = $price;
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
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory(?string $category)
    {
        $this->category = $category;
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
    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    /**
     * @param string $thumbnailUrl
     */
    public function setThumbnailUrl(?string $thumbnailUrl)
    {
        $this->thumbnailUrl = $thumbnailUrl;
    }

    /**
     * @return bool|null
     */
    public function getIsPhysical(): ?bool
    {
        return $this->isPhysical;
    }

    /**
     * @param bool $isPhysical
     */
    public function setIsPhysical(bool $isPhysical)
    {
        $this->isPhysical = $isPhysical;
    }

    /**
     * @return float|null
     */
    public function getWeight(): ?float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight(?float $weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return string|null
     */
    public function getSubscriptionIntervalType(): ?string
    {
        return $this->subscriptionIntervalType;
    }

    /**
     * @param string $subscriptionIntervalType
     */
    public function setSubscriptionIntervalType(
        ?string $subscriptionIntervalType
    ) {
        $this->subscriptionIntervalType = $subscriptionIntervalType;
    }

    /**
     * @return int|null
     */
    public function getSubscriptionIntervalCount(): ?int
    {
        return $this->subscriptionIntervalCount;
    }

    /**
     * @param int $subscriptionIntervalCount
     */
    public function setSubscriptionIntervalCount(
        ?int $subscriptionIntervalCount
    ) {
        $this->subscriptionIntervalCount = $subscriptionIntervalCount;
    }

    /**
     * @return int|null
     */
    public function getStock(): ?int
    {
        return $this->stock;
    }

    /**
     * @param int $stock
     */
    public function setStock(?int $stock)
    {
        $this->stock = $stock;
    }

    /**
     * @return bool
     */
    public function getAutoDecrementStock(): bool
    {
        return $this->autoDecrementStock;
    }

    /**
     * @param bool $autoDecrementStock
     */
    public function setAutoDecrementStock(bool $autoDecrementStock)
    {
        $this->autoDecrementStock = $autoDecrementStock;
    }
}
