<?php

namespace Railroad\Ecommerce\Repositories\Queries;

use Carbon\Carbon;
use Railroad\Ecommerce\Events\PaymentMethodEvent;
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Queries\CachedQuery;

class PaymentMethodQuery extends CachedQuery
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

        event(new PaymentMethodEvent($id, 'created'));

        return $id;
    }

    public function update(array $values)
    {
        $queryClone = $this->cloneWithout([]);

        $idsToBeUpdated = $queryClone->get()->pluck('id');

        $return = parent::update($values);

        foreach ($idsToBeUpdated as $idToBeUpdated) {
            event(new PaymentMethodEvent($idToBeUpdated, 'updated'));
        }

        return $return;
    }

    public function delete($id = null)
    {
        if (! is_null($id)) {
            $this->where($this->from.'.id', '=', $id);
        }

        $this->update(['deleted_on' => Carbon::now()->toDateTimeString()]);
    }
}