<?php

namespace Railroad\Ecommerce\Entities;

use Railroad\Resora\Entities\Entity;

class CartItem extends Entity
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var Product
     */
    public $product;

    /**
     * @var integer
     */
    public $quantity;

    /**
     * CartItem constructor.
     *
     * @param Product $product
     * @param int $quantity
     */
    public function __construct(Product $product, $quantity)
    {
        $this->id = (bin2hex(openssl_random_pseudo_bytes(32)));
        $this->product = $product;
        $this->quantity = $quantity;
    }
}