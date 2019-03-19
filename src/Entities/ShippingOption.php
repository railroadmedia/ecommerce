<?php

namespace Railroad\Ecommerce\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(
 *     repositoryClass="Railroad\Ecommerce\Repositories\ShippingOptionRepository"
 * )
 * @ORM\Table(
 *     name="ecommerce_shipping_options",
 *     indexes={
 *         @ORM\Index(name="ecommerce_shipping_options_country_index", columns={"country"}),
 *         @ORM\Index(name="ecommerce_shipping_options_active_index", columns={"active"}),
 *         @ORM\Index(name="ecommerce_shipping_options_priority_index", columns={"priority"}),
 *         @ORM\Index(name="ecommerce_shipping_options_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_shipping_options_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class ShippingOption
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
    protected $country;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $active;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $priority;

    /**
     * @ORM\OneToMany(
     *     targetEntity="ShippingCostsWeightRange",
     *     mappedBy="shippingOption",
     *     fetch="EAGER"
     * )
     */
    protected $shippingCostsWeightRanges;

    public function __construct()
    {
        $this->shippingCostsWeightRanges = new ArrayCollection();
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
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return ShippingOption
     */
    public function setCountry(string $country): self
    {
        $this->country = $country;

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
     * @return ShippingOption
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     *
     * @return ShippingOption
     */
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return Collection|ShippingCostsWeightRange[]
     */
    public function getShippingCostsWeightRanges(): Collection
    {
        return $this->shippingCostsWeightRanges;
    }

    /**
     * @param ShippingCostsWeightRange $shippingCostsWeightRange
     *
     * @return ShippingOption
     */
    public function addShippingCostsWeightRange(
        ShippingCostsWeightRange $shippingCostsWeightRange
    ): self {

        if (
            !$this->shippingCostsWeightRanges->contains(
                $shippingCostsWeightRange
            )
        ) {
            $this->shippingCostsWeightRanges[] = $shippingCostsWeightRange;
            $shippingCostsWeightRange->setShippingOption($this);
        }

        return $this;
    }

    /**
     * @param ShippingCostsWeightRange $shippingCostsWeightRange
     *
     * @return ShippingOption
     */
    public function removeShippingCostsWeightRange(
        ShippingCostsWeightRange $shippingCostsWeightRange
    ): self {

        if (
            $this->shippingCostsWeightRanges->contains(
                $shippingCostsWeightRange
            )
        ) {
            $this->shippingCostsWeightRanges
                ->removeElement($shippingCostsWeightRange);

            // set the owning side to null (unless already changed)
            if ($shippingCostsWeightRange->getShippingOption() === $this) {
                $shippingCostsWeightRange->setShippingOption(null);
            }
        }

        return $this;
    }
}
