<?php

namespace Railroad\Ecommerce\Events\UserProducts;

use Railroad\Ecommerce\Entities\UserProduct;

class UserProductCreated
{
    /**
     * @var UserProduct
     */
    public $userProduct;

    /**
     * UserProductCreated constructor.
     * @param UserProduct $userProduct
     */
    public function __construct(UserProduct $userProduct)
    {
        $this->userProduct = $userProduct;
    }
}