<?php

namespace Railroad\Ecommerce\Contracts;

use Railroad\Ecommerce\Contracts\UserInterface;
use Railroad\Doctrine\Contracts\UserEntityInterface;
use League\Fractal\TransformerAbstract;

interface UserProviderInterface
{
    public function getUserById(int $id): ?UserEntityInterface;

    public function getUserId(UserEntityInterface $user): int;

    public function getCurrentUser(): ?UserInterface;

    public function getCurrentUserId(): ?int;

    public function getUserTransformer(): TransformerAbstract;

    public function createUser(string $email, string $password): ?UserInterface;
}
