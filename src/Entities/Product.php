<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
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
 */
class Product
{
    use TimestampableEntity, NotableEntity;

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
     *
     * @return Product
     */
    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
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
     * @return Product
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
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
     *
     * @return Product
     */
    public function setSku(string $sku): self
    {
        $this->sku = $sku;

        return $this;
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
     *
     * @return Product
     */
    public function setPrice(float $price): self
    {
        $this->price = $price;

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
     * @return Product
     */
    public function setType(string $type): self
    {
        $this->type = $type;

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
     * @return Product
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
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
     *
     * @return Product
     */
    public function setCategory(?string $category): self
    {
        $this->category = $category;

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
     * @return Product
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
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
     *
     * @return Product
     */
    public function setThumbnailUrl(?string $thumbnailUrl): self
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
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
     *
     * @return Product
     */
    public function setIsPhysical(bool $isPhysical): self
    {
        $this->isPhysical = $isPhysical;

        return $this;
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
     *
     * @return Product
     */
    public function setWeight(?float $weight): self
    {
        $this->weight = $weight;

        return $this;
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
     *
     * @return Product
     */
    public function setSubscriptionIntervalType(
        ?string $subscriptionIntervalType
    ): self
    {
        $this->subscriptionIntervalType = $subscriptionIntervalType;

        return $this;
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
     *
     * @return Product
     */
    public function setSubscriptionIntervalCount(
        ?int $subscriptionIntervalCount
    ): self
    {
        $this->subscriptionIntervalCount = $subscriptionIntervalCount;

        return $this;
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
     *
     * @return Product
     */
    public function setStock(?int $stock): self
    {
        $this->stock = $stock;

        return $this;
    }
}
