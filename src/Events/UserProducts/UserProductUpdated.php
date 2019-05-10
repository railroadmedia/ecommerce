<?php

namespace Railroad\Ecommerce\Events\UserProducts;

use Railroad\Ecommerce\Entities\UserProduct;

class UserProductUpdated
{
    /**
     * @var UserProduct
     */
    private $newUserProduct;

    /**
     * @var UserProduct
     */
    private $oldUserProduct;

    /**
     * UserProductUpdated constructor.
     * @param UserProduct $newUserProduct
     * @param UserProduct $oldUserProduct
     */
    public function __construct(UserProduct $newUserProduct, UserProduct $oldUserProduct)
    {
        $this->newUserProduct = $newUserProduct;
        $this->oldUserProduct = $oldUserProduct;
    }
}