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
     * @param array $ids
     * @return User[]
     */
    public function getUsersByIds(array $ids): array;

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

    /**
     * @param User $user
     * @return string
     */
    public function getUserAuthToken(User $user): string;

    /**
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User;

    /**
     * @param string $email
     * @param string $password
     *
     * @return bool
     */
    public function checkCredentials(string $email, string $password): bool;
}
