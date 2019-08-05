<?php

namespace Railroad\Ecommerce\Services;

use Doctrine\ORM\ORMException;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

class PurchaserService
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var EcommerceEntityManager
     */
    private $ecommerceEntityManager;

    /**
     * PurchaserService constructor.
     *
     * @param UserProviderInterface $userProvider
     * @param EcommerceEntityManager $ecommerceEntityManager
     */
    public function __construct(UserProviderInterface $userProvider, EcommerceEntityManager $ecommerceEntityManager)
    {
        $this->userProvider = $userProvider;
        $this->ecommerceEntityManager = $ecommerceEntityManager;
    }

    /**
     * @param Purchaser $purchaser
     * @param bool $loginUser
     *
     * @throws ORMException
     */
    public function persist(Purchaser &$purchaser, $loginUser = true)
    {
        // create and login the user right away
        if ($purchaser->getType() == Purchaser::USER_TYPE && empty($purchaser->getId())) {
            $user = $this->userProvider->createUser($purchaser->getEmail(), $purchaser->getRawPassword());

            $purchaser->setId($user->getId());
            $purchaser->setEmail($user->getEmail());

            if ($loginUser) {
                auth()->loginUsingId($user->getId());
            }
        }

        // or create the customer
        if ($purchaser->getType() == Purchaser::CUSTOMER_TYPE && empty($purchaser->getId())) {
            $customer = $purchaser->getCustomerEntity();

            $this->ecommerceEntityManager->persist($customer);
            $this->ecommerceEntityManager->flush();

            $purchaser->setCustomerEntity($customer);
        }
    }
}