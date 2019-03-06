<?php

namespace Railroad\Ecommerce\Tests\Fixtures;

use Illuminate\Support\Facades\DB;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Ecommerce\Tests\Fixtures\User;
use Railroad\Ecommerce\Contracts\UserInterface;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Doctrine\Common\Inflector\Inflector;
use Railroad\Doctrine\Contracts\UserEntityInterface;
use Railroad\Doctrine\Contracts\UserProviderInterface as DoctrineUserProviderInterface;
use Railroad\DoctrineArrayHydrator\Contracts\UserProviderInterface as DoctrineArrayHydratorUserProviderInterface;
use Railroad\Ecommerce\Tests\Fixtures\UserTransformer;
use League\Fractal\TransformerAbstract;

class UserProvider implements
    UserProviderInterface,
    DoctrineUserProviderInterface,
    DoctrineArrayHydratorUserProviderInterface
{
    CONST RESOURCE_TYPE = 'user';

    public function getUserById(int $id): ?UserEntityInterface
    {
        $user = DB::table(EcommerceTestCase::TABLES['users'])->find($id);

        if ($user) {
            return new User($id, $user->email);
        }

        return null;
    }

    public function getUserId(UserEntityInterface $user): int
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

    public function getUserTransformer(): TransformerAbstract
    {
        return new UserTransformer();
    }

    public function isTransient(string $resourceType): bool {

        return $resourceType !== self::RESOURCE_TYPE;
    }

    public function hydrateTransDomain(
        $entity,
        string $relationName,
        array $data
    ): void {

        $setterName = Inflector::camelize('set' . ucwords($relationName));

        if (
            isset($data['data']['type']) &&
            $data['data']['type'] === self::RESOURCE_TYPE &&
            isset($data['data']['id']) &&
            is_object($entity) &&
            method_exists($entity, $setterName)
        ) {

            $user = $this->getUserById($data['data']['id']);

            call_user_func([$entity, $setterName], $user);
        }

        // else some exception should be thrown
    }

    public function createUser(
        string $email,
        string $password
    ): ?UserInterface {

        $userId = DB::table(EcommerceTestCase::TABLES['users'])
            ->insertGetId([
                'email' => $email,
                'password' => $password,
                'display_name' => $email,
            ]);

        return $this->getUserById($userId);
    }
}
