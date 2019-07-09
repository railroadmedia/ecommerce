<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Exceptions\AppleStoreKit\ReceiptValidationException;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;

class AppleStoreKitService
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var AppleStoreKitGateway
     */
    private $appleStoreKitGateway;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * AppleStoreKitService constructor.
     *
     * @param AppleStoreKit $entityManager
     * @param EcommerceEntityManager $entityManager
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AppleStoreKitGateway $appleStoreKitGateway,
        EcommerceEntityManager $entityManager,
        UserProviderInterface $userProvider
    )
    {
        $this->entityManager = $entityManager;
        $this->appleStoreKitGateway = $appleStoreKitGateway;
        $this->userProvider = $userProvider;
    }

    /**
     *
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function processReceipt(AppleReceipt $receipt): User
    {
        $this->entityManager->persist($receipt);

        try {
            $validationResponse = $this->appleStoreKitGateway->validate($receipt->getReceiptKey());
        } catch (ReceiptValidationException $exception) {

            $receipt->setValidationError($exception->getMessage());

            $this->entityManager->flush();

            throw $exception;
        }

        $user = $this->userProvider->getUserByEmail($receipt->getEmail());

        if (!$user) {
            $user = $this->userProvider->createUser($receipt->getEmail(), $receipt->getPassword());
        }

        auth()->loginUsingId($user->getId());

        // give access level to user

        // store payment data

        return $user;
    }
}
