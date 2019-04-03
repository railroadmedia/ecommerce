<?php

namespace Railroad\Ecommerce\Cart\Exceptions;

use Railroad\Ecommerce\Entities\Product;

class ProductNotActiveException extends AddToCartException
{
    /**
     * @var Product
     */
    private $product;

    /**
     * ProductOutOfStockException constructor.
     *
     * @param  Product  $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;

        parent::__construct(
            'Product '.$this->product->getName()
            .' is not currently for sale, please check again later.',
            1
        );
    }

}