<?php

namespace Railroad\Ecommerce\Events;

class PaymentEvent
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $eventType;

    /**
     * Create a new event instance.
     *
     * @param $id
     * @param $eventType
     */
    public function __construct($id, $eventType)
    {
        $this->id = $id;
        $this->eventType = $eventType;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEventType()
    {
        return $this->eventType;
    }
}
