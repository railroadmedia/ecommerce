<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Repositories\ProductRepository;


class ProductService
{

    private $productRepository;

    // all possible product types
    const TYPE_PRODUCT = 'product';
    const TYPE_SUBSCRIPTION = 'subscription';

    /**
     * ProductService constructor.
     * @param $productRepository
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getActiveProductFromSku($productSku)
    {
        return $this->productRepository->getActiveProductFromSku($productSku);
    }
}