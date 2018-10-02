<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\UserProductRepository;

class UserProductService
{
    /**
     * @var UserProductRepository
     */
    private $userProductRepository;

    /**
     * UserProductService constructor.
     *
     * @param UserProductRepository $userProductRepository
     */
    public function __construct(UserProductRepository $userProductRepository)
    {
        $this->userProductRepository = $userProductRepository;
    }

    /**
     * Get user product based on user id and product id.
     *
     * @param int $userId
     * @param int $productId
     * @return \Illuminate\Database\Eloquent\Model|null|object|UserProductRepository|\Railroad\Resora\Queries\BaseQuery
     */
    public function getUserProductData($userId, $productId)
    {
        return $this->userProductRepository->query()
            ->where(
                [
                    'user_id' => $userId,
                    'product_id' => $productId,
                ]
            )
            ->first();
    }

    /**
     * Save new record in user_product table.
     *
     * @param int $userId
     * @param int $productId
     * @param int $quantity
     * @param string $expirationDate
     * @return null|\Railroad\Resora\Entities\Entity
     */
    public function saveUserProduct($userId, $productId, $quantity, $expirationDate)
    {
        return $this->userProductRepository->query()
            ->create(
                [
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'expiration_date' => $expirationDate,
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );
    }

    /**
     * Update user product: quantity, expiration date
     *
     * @param int $userProductId
     * @param int $quantity
     * @param string $expirationDate
     * @return int|null|\Railroad\Resora\Entities\Entity
     */
    public function updateUserProduct($userProductId, $quantity, $expirationDate)
    {
        return $this->userProductRepository->update(
            $userProductId,
            [
                'quantity' => $quantity,
                'expiration_date' => $expirationDate,
                'updated_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    /**
     * Delete user product based on row id
     *
     * @param int $userProductId
     * @return int
     */
    public function deleteUserProduct($userProductId)
    {
        return $this->userProductRepository->query()
            ->where('id', $userProductId)
            ->delete();
    }
}