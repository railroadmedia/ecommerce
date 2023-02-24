<?php

namespace Railroad\Ecommerce\Entities;

use Carbon\Carbon;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Ecommerce\Entities\Traits\NotableEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Railroad\Ecommerce\Repositories\AccessCodeRepository")
 * @ORM\Table(
 *     name="ecommerce_access_codes",
 *     indexes={
 *         @ORM\Index(name="ecommerce_access_codes_code_index", columns={"code"}),
 *         @ORM\Index(name="ecommerce_access_codes_product_ids_index", columns={"product_ids"}),
 *         @ORM\Index(name="ecommerce_access_codes_is_claimed_index", columns={"is_claimed"}),
 *         @ORM\Index(name="ecommerce_access_codes_claimer_id_index", columns={"claimer_id"}),
 *         @ORM\Index(name="ecommerce_access_codes_claimed_on_index", columns={"claimed_on"}),
 *         @ORM\Index(name="ecommerce_access_codes_brand_index", columns={"brand"}),
 *         @ORM\Index(name="ecommerce_access_codes_source_index", columns={"source"}),
 *         @ORM\Index(name="ecommerce_access_codes_created_on_index", columns={"created_at"}),
 *         @ORM\Index(name="ecommerce_access_codes_updated_on_index", columns={"updated_at"})
 *     }
 * )
 */
class AccessCode
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
    protected $isClaimed = false;

    /**
     * @var User
     *
     * @ORM\Column(type="user", name="claimer_id", nullable=true)
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
     * @ORM\Column(type="string",  nullable=true)
     *
     * @var string
     */
    protected $source;

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
     */
    public function setCode(string $code)
    {
        $this->code = $code;
    }

    public function generateCode()
    {
        $this->setCode(bin2hex(openssl_random_pseudo_bytes(24 / 2)));
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
     */
    public function setProductIds(array $productIds)
    {
        $this->productIds = $productIds;
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
     */
    public function setIsClaimed(bool $isClaimed)
    {
        $this->isClaimed = $isClaimed;
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
     */
    public function setClaimedOn(?Carbon $claimedOn)
    {
        $this->claimedOn = $claimedOn;
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
     * @return User|null
     */
    public function getClaimer(): ?User
    {
        return $this->claimer;
    }

    /**
     * @param User|null $claimer
     */
    public function setClaimer(?User $claimer)
    {
        $this->claimer = $claimer;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source)
    {
        $this->source = $source;
    }

}
