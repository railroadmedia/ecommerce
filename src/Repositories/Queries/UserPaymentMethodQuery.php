<?php

namespace Railroad\Ecommerce\Repositories\Queries;

use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Resora\Queries\CachedQuery;

class UserPaymentMethodQuery extends CachedQuery
{
    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array   $values
     * @param  string|null  $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $id = parent::insertGetId($values, $sequence);

        if (isset($values['is_primary']) && $values['is_primary']) {
            event(
                new UserDefaultPaymentMethodEvent(
                    $values['user_id'],
                    $values['payment_method_id']
                )
            );
        }

        return $id;
    }

    public function update(array $values)
    {
        $queryClone = $this->cloneWithout([]);

        $result = parent::update($values);

        if (isset($values['is_primary']) && $values['is_primary']) {

            $updatedRows = $queryClone->get();

            foreach ($updatedRows as $row) {
                event(
                    new UserDefaultPaymentMethodEvent(
                        $row->user_id,
                        $row->payment_method_id
                    )
                );
            }
        }

        return $result;
    }
}
