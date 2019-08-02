<?php

namespace Railroad\Ecommerce\Events;

use Railroad\Ecommerce\Entities\Refund;
use Railroad\Ecommerce\Entities\User;

class RefundEvent
{
    /**
     * @var Refund
     */
    protected $refund;

    /**
     * @var User
     */
    protected $user;

    /**
     * Create a new event instance.
     *
     * @param Refund $refund
     * @param User $user
     */
    public function __construct(Refund $refund, User $user)
    {
        $this->refund = $refund;
        $this->user = $user;
    }

    /**
     * @return Refund
     */
    public function getRefund(): Refund
    {
        return $this->refund;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
