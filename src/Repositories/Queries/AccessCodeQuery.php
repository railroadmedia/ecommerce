<?php

namespace Railroad\Ecommerce\Repositories\Queries;

use Carbon\Carbon;
use Railroad\Resora\Queries\CachedQuery;

class AccessCodeQuery extends CachedQuery
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
        if (isset($values['product_ids']) && $values['product_ids']) {
            $values['product_ids'] = serialize($values['product_ids']);
        }

        return parent::insertGetId($values, $sequence);
    }
}
