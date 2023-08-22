<?php

namespace Railroad\Ecommerce\Entities\Traits;

/**
 * Trait ShopifyEntity
 * @package Railroad\Ecommerce\Entities\Traits
 */
trait ShopifyEntity
{
    /**
     * @ORM\Column(type="integer", name="shopify_id", nullable=true)
     *
     * @var int
     */
    protected $shopifyId;

    /**
     * @return string|null
     */
    public function getShopifyId(): ?string
    {
        return $this->shopifyId;
    }

    /**
     * @param string $shopifyId
     */
    public function setShopifyId(string $shopifyId)
    {
        $this->shopifyId = $shopifyId;
    }
}
