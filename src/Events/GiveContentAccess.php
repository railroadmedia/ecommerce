<?php

namespace Railroad\Ecommerce\Events;

use Illuminate\Support\Facades\Event;
use Railroad\Ecommerce\Entities\Order;

class GiveContentAccess extends Event
{
    /**
     * @var Order
     */
    public $order;

    /**
     * GiveContentAccess constructor.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
