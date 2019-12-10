<?php

namespace Railroad\Ecommerce\Events;

use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\User;

class AccessCodeClaimed
{
    /**
     * @var AccessCode
     */
    protected $accessCode;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string|null
     */
    protected $context = null;

    /**
     * AccessCodeClaimed constructor.
     * @param AccessCode $accessCode
     * @param User $user
     * @param $context
     */
    public function __construct(AccessCode $accessCode, User $user, $context)
    {
        $this->accessCode = $accessCode;
        $this->user = $user;
        $this->context = $context;
    }

    /**
     * @return AccessCode
     */
    public function getAccessCode(): AccessCode
    {
        return $this->accessCode;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return string|null
     */
    public function getContext(): ?string
    {
        return $this->context;
    }
}
