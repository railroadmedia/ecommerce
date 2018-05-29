<?php

namespace Railroad\Ecommerce\Queries;

use Carbon\Carbon;
use Railroad\Resora\Queries\BaseQuery;

class PaymentMethodQuery extends BaseQuery
{
    public function delete($id = null)
    {
        if (! is_null($id)) {
            $this->where($this->from.'.id', '=', $id);
        }

        $this->update(['deleted_on' => Carbon::now()->toDateTimeString()]);
    }

}