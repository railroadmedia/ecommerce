<?php

namespace Railroad\Ecommerce\Tests\Fixtures;

use Doctrine\Inflector\InflectorFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Ecommerce\Transformers\UserTransformer;

class UserProvider implements UserProviderInterface
{
    CONST RESOURCE_TYPE = 'user';

    /**
     * @param int $id
     * @return User|null
     */
    public function getUserById(int $id): ?User
    {
        $user =
            DB::table(EcommerceTestCase::TABLES['users'])
                ->find($id);

        if ($user) {
            return new User($id, $user->email);
        }

        return null;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getUsersByIds(array $ids): array
    {
        $users =
            DB::table(EcommerceTestCase::TABLES['users'])
                ->whereIn('id', $ids)
                ->get();

        $userObjects = [];

        foreach ($users as $user) {
            $userObjects[] = new User($user->id, $user->email);
        }

        return $userObjects;
    }

    /**
     * @param User $user
     * @return int
     */
    public function getUserId(User $user): int
    {
        return $user->getId();
    }

    /**
     * @return User|null
     */
    public function getCurrentUser(): ?User
    {
        if (!Auth::id()) {
            return null;
        }

        return $this->getUserById(Auth::id());
    }

    /**
     * @return int|null
     */
    public function getCurrentUserId(): ?int
    {
        return Auth::id();
    }

    /**
     * @return TransformerAbstract
     */
    public function getUserTransformer(): TransformerAbstract
    {
        return new UserTransformer();
    }

    /**
     * @param $entity
     * @param string $relationName
     * @param array $data
     */
    public function hydrateTransDomain($entity, string $relationName, array $data): void
    {
        $inflector = app('DoctrineInflector');

        $setterName = $inflector->camelize('set' . ucwords($relationName));

        if (isset($data['data']['type']) &&
            $data['data']['type'] === self::RESOURCE_TYPE &&
            isset($data['data']['id']) &&
            is_object($entity) &&
            method_exists($entity, $setterName)) {

            $user = $this->getUserById($data['data']['id']);

            call_user_func([$entity, $setterName], $user);
        }

        // else some exception should be thrown
    }

    /**
     * @param string $resourceType
     * @return bool
     */
    public function isTransient(string $resourceType): bool
    {
        return $resourceType !== self::RESOURCE_TYPE;
    }

    /**
     * @param string $email
     * @param string $rawPassword
     * @return User|null
     */
    public function createUser(string $email, string $rawPassword): ?User
    {
        $userId =
            DB::table(EcommerceTestCase::TABLES['users'])
                ->insertGetId(
                    [
                        'email' => $email,
                        'password' => Hash::make($rawPassword),
                        'display_name' => $email,
                    ]
                );

        return $this->getUserById($userId);
    }

    /**
     * @param User $user
     * @return string
     */
    public function getUserAuthToken(User $user): string
    {
        return md5($user->getEmail());
    }

    /**
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User
    {
        $user =
            DB::table(EcommerceTestCase::TABLES['users'])
                ->where('email', $email)
                ->get()
                ->first();

        if ($user) {
            return new User($user->id, $user->email);
        }

        return null;
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @return bool
     */
    public function checkCredentials(string $email, string $password): bool
    {
        return true;
    }

    /**
     * Returns a list of brands that the user is currently a member of.
     *
     * @param integer $userId
     * @return array
     */
    public function getBrandsUserIsAMemberOf($userId)
    {
        return [config('ecommerce.brand'), 'test-brand-2'];
    }
}
