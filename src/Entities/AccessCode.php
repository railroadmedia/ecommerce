<?php

namespace Railroad\Ecommerce\Entities;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Contracts\UserInterface;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\AccessCodeRepository")
 * @ORM\Table(
 *     name="ecommerce_access_code",
 *     indexes={
 *         @ORM\Index(name="ecommerce_access_code_code_index", columns={"code"}),
 *         @ORM\Index(name="ecommerce_access_code_product_ids_index", columns={"product_ids"}),
 *         @ORM\Index(name="ecommerce_access_code_is_claimed_index", columns={"is_claimed"}),
 *         @ORM\Index(name="ecommerce_access_code_claimer_id_index", columns={"claimer_id"}),
 *         @ORM\Index(name="ecommerce_access_code_claimed_on_index", columns={"claimed_on"}),
 *         @ORM\Index(name="ecommerce_access_code_brand_index", columns={"brand"}),
 *         @ORM\Index(name="ecommerce_access_code_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_access_code_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class AccessCode
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
    protected $code;

    /**
     * @ORM\Column(type="array", name="product_ids")
     *
     * @var array
     */
    protected $productIds;

    /**
     * @ORM\Column(type="boolean", name="is_claimed")
     *
     * @var bool
     */
    protected $isClaimed;

    /**
     * @var int
     *
     * @ORM\Column(type="user_id", name="claimer_id", nullable=true)
     */
    protected $claimer;

    /**
     * @ORM\Column(type="datetime", name="claimed_on", nullable=true)
     *
     * @var Carbon
     */
    protected $claimedOn;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $brand;

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
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return AccessCode
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getProductIds(): ?array
    {
        return $this->productIds;
    }

    /**
     * @param array $productIds
     *
     * @return AccessCode
     */
    public function setProductIds(array $productIds): self
    {
        $this->productIds = $productIds;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsClaimed(): ?bool
    {
        return $this->isClaimed;
    }

    /**
     * @param bool $isClaimed
     *
     * @return AccessCode
     */
    public function setIsClaimed(bool $isClaimed): self
    {
        $this->isClaimed = $isClaimed;

        return $this;
    }

    /**
     * @return Carbon|null
     */
    public function getClaimedOn(): ?Carbon
    {
        return $this->claimedOn;
    }

    /**
     * @param Carbon|null $claimedOn
     *
     * @return AccessCode
     */
    public function setClaimedOn(?Carbon $claimedOn): self
    {
        $this->claimedOn = $claimedOn;

        return $this;
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
     * @return AccessCode
     */
    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * @return UserInterface|null
     */
    public function getClaimer(): ?UserInterface
    {
        return $this->claimer;
    }

    /**
     * @param UserInterface|null $claimer
     *
     * @return AccessCode
     */
    public function setClaimer(?UserInterface $claimer): self
    {
        $this->claimer = $claimer;

        return $this;
    }
}
