<?php

namespace Railroad\Ecommerce\Listeners;


use Illuminate\Support\Facades\Event;

class GiveContentAccessListener
{
    public function handle(Event $event)
    {
        $order = $event->order;
    }
}