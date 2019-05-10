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

    /**
     * @return UserProduct
     */
    public function getNewUserProduct(): UserProduct
    {
        return $this->newUserProduct;
    }

    /**
     * @return UserProduct
     */
    public function getOldUserProduct(): UserProduct
    {
        return $this->oldUserProduct;
    }
}