<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ecommerce_product")
 */
class Product
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
    protected $brand;

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
    protected $sku;

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2)
     *
     * @var string
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
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $category;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(type="text", name="thumbnail_url")
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
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $weight;

    /**
     * @ORM\Column(type="string", name="subscription_interval_type")
     *
     * @var string
     */
    protected $subscriptionIntervalType;

    /**
     * @ORM\Column(type="integer", name="subscription_interval_count")
     *
     * @var int
     */
    protected $subscriptionIntervalCount;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $stock;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(string $thumbnailUrl): self
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }

    public function getIsPhysical(): ?bool
    {
        return $this->isPhysical;
    }

    public function setIsPhysical(bool $isPhysical): self
    {
        $this->isPhysical = $isPhysical;

        return $this;
    }

    public function getWeight(): ?bool
    {
        return $this->weight;
    }

    public function setWeight(bool $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getSubscriptionIntervalType(): ?string
    {
        return $this->subscriptionIntervalType;
    }

    public function setSubscriptionIntervalType(
        string $subscriptionIntervalType
    ): self {

        $this->subscriptionIntervalType = $subscriptionIntervalType;

        return $this;
    }

    public function getSubscriptionIntervalCount(): ?int
    {
        return $this->subscriptionIntervalCount;
    }

    public function setSubscriptionIntervalCount(
        int $subscriptionIntervalCount
    ): self {

        $this->subscriptionIntervalCount = $subscriptionIntervalCount;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;

        return $this;
    }
}
