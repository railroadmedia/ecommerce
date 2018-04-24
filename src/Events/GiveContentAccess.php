<?php

namespace Railroad\Ecommerce\Events;


use Illuminate\Support\Facades\Event;

class GiveContentAccess extends Event
{
    public $order;


    /**
     * GiveContentAccess constructor.
     * @param array $order
     */
    public function __construct($order)
    {
        $this->order = $order;
    }


}