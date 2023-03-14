<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\RedirectNeededException;

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
            // check if they have any trial membership user products for that were valid within the last x days
            $this->userProductService->arrayCache->deleteAll();

            $usersProducts = $this->userProductService->getAllUsersProducts($purchaser->getId());

            $hasRecentTrial = false;

            foreach ($usersProducts as $userProduct) {

                if(empty($userProduct->getExpirationDate())){
                    continue;
                }

                $productAlmostCertainlyTrial =
                    strpos(strtolower($userProduct->getProduct()->getSku()), 'trial') !== false;

                $expiryWithinTimeConstraint = $userProduct->getExpirationDate() > Carbon::now()->subDays(90);

                if ($productAlmostCertainlyTrial && $expiryWithinTimeConstraint) {
                    $hasRecentTrial = true;
                }
            }

            if ($hasRecentTrial) {
                $this->cartService->setCart($cart);

                // check if they are trying to order a trial product, if so, reject it
                foreach ($cart->getItems() as $cartItem) {
                    if (strpos(strtolower($cartItem->getSku()), 'trial') !== false &&

                        $this->cartService->getDueForInitialPayment() === 0) {

                        $urlForEvergreenSalesPage = 'https://www.' . $purchaser->getBrand() . '.com/lp';
                        if(env('APP_ENV') === 'local'){
                            $urlForEvergreenSalesPage = 'https://dev.' . $purchaser->getBrand() . '.com:8443/lp';
                        }

                        $redirectMessageToUser = 'It looks like you’ve started a trial with Musora in the last 90 ' .
                            'days. Unfortunately, that means that your account is not eligible to start another ' .
                            'trial at this time. Click below to check out a special offer and start your membership ' .
                            'today!';

                        $messageTitleText = 'Something went wrong';

                        $buttonText = 'YOUR OFFER';

                        throw new RedirectNeededException(
                            $urlForEvergreenSalesPage,
                            $redirectMessageToUser,
                            $messageTitleText,
                            $buttonText
                        );
                    }
                }
            }
        }
    }
}