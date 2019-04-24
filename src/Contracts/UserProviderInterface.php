<?php

namespace Railroad\Ecommerce\Contracts;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\User;

interface UserProviderInterface
{
    /**
     * @param int $id
     * @return User|null
     */
    public function getUserById(int $id): ?User;

    /**
     * @param User $user
     * @return int
     */
    public function getUserId(User $user): int;

    /**
     * @return User|null
     */
    public function getCurrentUser(): ?User;

    /**
     * @return int|null
     */
    public function getCurrentUserId(): ?int;

    /**
     * @return TransformerAbstract
     */
    public function getUserTransformer(): TransformerAbstract;

    /**
     * @param string $email
     * @param string $rawPassword
     * @return User|null
     */
    public function createUser(string $email, string $rawPassword): ?User;
}
