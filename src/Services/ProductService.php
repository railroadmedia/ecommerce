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

    /** Get all the active products that meet the conditions
     * @param array $conditions
     * @return mixed
     */
    public function getActiveProductByConditions(array $conditions)
    {
        return $this->productRepository->getActiveProductsByConditions($conditions)[0] ?? null;
    }
}