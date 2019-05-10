<?php

namespace Railroad\Ecommerce\Events\UserProducts;

use Railroad\Ecommerce\Entities\UserProduct;

class UserProductDeleted
{
    /**
     * @var UserProduct
     */
    public $userProduct;

    /**
     * UserProductDeleted constructor.
     * @param UserProduct $userProduct
     */
    public function __construct(UserProduct $userProduct)
    {
        $this->userProduct = $userProduct;
    }
}