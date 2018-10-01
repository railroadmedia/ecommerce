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
     * @param $userId
     * @param $productId
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
     * @param $userId
     * @param $productId
     * @param $quantity
     * @param $expirationDate
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
     * @param $userProductId
     * @param $quantity
     * @param $expirationDate
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

    public function deleteUserProduct($userProductId)
    {
        return $this->userProductRepository->query()
            ->where('id', $userProductId)
            ->delete();
    }

}