<?php

namespace Railroad\Ecommerce\Cart\Exceptions;

class ProductNotFoundException extends AddToCartException
{
    /**
     * @var string
     */
    private $productSku;

    /**
     * ProductOutOfStockException constructor.
     *
     * @param  string  $productSku
     */
    public function __construct(string $productSku)
    {
        $this->productSku = $productSku;

        parent::__construct(
            'No product with SKU '.$this->productSku.' was found.',
            2
        );
    }

}