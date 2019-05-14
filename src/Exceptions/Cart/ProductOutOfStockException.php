<?php

namespace Railroad\Ecommerce\Exceptions\Cart;

use Railroad\Ecommerce\Entities\Product;

class ProductOutOfStockException extends AddToCartException
{
    /**
     * @var Product
     */
    private $product;

    /**
     * ProductOutOfStockException constructor.
     *
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;

        parent::__construct(
            'Product ' . $this->product->getName() . ' is currently out of stock, please check back later.',
            1
        );
    }

}