<?php

namespace Railroad\Ecommerce\Tests\Fixtures;

use Railroad\Ecommerce\Tests\Fixtures\User;
use Railroad\Ecommerce\Contracts\UserInterface;
use Railroad\Ecommerce\Contracts\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function getUserById(int $id): UserInterface
    {
        return new User($id);
    }

    public function getUserId(UserInterface $user): int
    {
        return $user->getId();
    }

    public function getCurrentUser(): UserInterface
    {
        return $this->getUserById(auth()->id());
    }

    public function getCurrentUserId(): int
    {
        return auth()->id();
    }
}
