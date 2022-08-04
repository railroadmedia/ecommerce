<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;

class OrderValidationService
{
    /**
     * @var UserProductService
     */
    private $userProductService;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * OrderValidationService constructor.
     *
     * @param  UserProductService  $userProductService
     */
    public function __construct(UserProductService $userProductService, CartService $cartService)
    {
        $this->userProductService = $userProductService;
        $this->cartService = $cartService;
    }

    /**
     * @param  Cart  $cart
     * @param  Purchaser  $purchaser
     * @return void
     * @throws PaymentFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function validateOrder(Cart $cart, Purchaser $purchaser)
    {
        if (!empty($purchaser->getId())) {
            // check if they have any trial membership user products for brand that were valid within the last 120 days
            $this->userProductService->arrayCache->deleteAll();

            $usersProducts = $this->userProductService->getAllUsersProducts($purchaser->getId());

            $hasTrialCreatedWithinPeriod = false;

            foreach ($usersProducts as $userProduct) {
                if ($userProduct->getProduct()->getBrand() == $purchaser->getBrand() &&
                    strpos(strtolower($userProduct->getProduct()->getSku()), 'trial') !== false &&
                    !empty($userProduct->getExpirationDate()) &&
                    $userProduct->getExpirationDate() > Carbon::now()->subDays(120)) {
                    $hasTrialCreatedWithinPeriod = true;
                }
            }

            if ($hasTrialCreatedWithinPeriod) {
                $this->cartService->setCart($cart);

                // then check if they are trying to order a trial product, if so, reject it
                foreach ($cart->getItems() as $cartItem) {
                    if (strpos(strtolower($cartItem->getSku()), 'trial') !== false &&
                        $this->cartService->getDueForInitialPayment() === 0) {
                        throw new PaymentFailedException(
                            'This account is not eligible for a free trial period. Please choose a regular membership.'
                        );
                    }
                }
            }
        }
    }
}