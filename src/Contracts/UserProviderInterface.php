<?php

namespace Railroad\Ecommerce\Contracts;

use Railroad\Ecommerce\Contracts\UserInterface;

interface UserProviderInterface
{
    public function getUserById(int $id): UserInterface;

    public function getUserId(UserInterface $user): int;

    public function getCurrentUser(): UserInterface;

    public function getCurrentUserId(): int;
}
