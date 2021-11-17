<?php

namespace Railroad\Ecommerce\Events;

use Railroad\Ecommerce\Entities\Product;

class AddToCartEvent
{
    /**
     * @var Product
     */
    protected $product;

    /**
     * @var int
     */
    protected $quantity;

    /**
     * Create a new event instance.
     *
     * @param Product $product
     * @param int $quantity
     */
    public function __construct(Product $product, $quantity)
    {
        $this->product = $product;
        $this->quantity = $quantity;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

}
