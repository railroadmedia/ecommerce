<?php

namespace Railroad\Ecommerce\Events;

class UserDefaultPaymentMethodEvent
{
    /**
     * @var int
     */
    protected $userId;

    /**
     * @var int
     */
    protected $defaultPaymentMethodId;

    /**
     * Create a new event instance.
     *
     * @param int $userId
     * @param int $defaultPaymentMethodId
     */
    public function __construct($userId, $defaultPaymentMethodId)
    {
        $this->userId = $userId;
        $this->defaultPaymentMethodId = $defaultPaymentMethodId;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return int
     */
    public function getDefaultPaymentMethodId()
    {
        return $this->defaultPaymentMethodId;
    }
}
