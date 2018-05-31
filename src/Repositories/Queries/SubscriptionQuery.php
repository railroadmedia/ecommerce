<?php

namespace Railroad\Ecommerce\Repositories\Queries;

use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Resora\Queries\CachedQuery;

class SubscriptionQuery extends CachedQuery
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

        event(new SubscriptionEvent($id, 'created'));

        return $id;
    }

    public function update(array $values)
    {
        $queryClone = $this->cloneWithout([]);

        $idsToBeUpdated = $queryClone->get()->pluck('id');

        $return = parent::update($values);

        foreach ($idsToBeUpdated as $idToBeUpdated) {
            event(new SubscriptionEvent($idToBeUpdated, 'updated'));
        }

        return $return;
    }
}