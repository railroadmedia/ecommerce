<?php

namespace Railroad\Ecommerce\Repositories\QueryBuilders;


use Railroad\Ecommerce\Repositories\AddressRepository;

class AddressQueryBuilder extends QueryBuilder
{
    /**
     * @return $this
     */
    public function restrictCustomerIdAccess()
    {
        if (!AddressRepository::$pullAllAddresses) {
            $this
                ->where('customer_id', AddressRepository::$availableCustomerId);
        }
        return $this;
    }

    public function restrictUserIdAccess()
    {
        if (!AddressRepository::$pullAllAddresses) {
            $this
                ->where('user_id', AddressRepository::$availableUserId);
        }
        return $this;
    }
}