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
            // check if they have any trial membership user products for brand that were valid within the last 120 days
            $this->userProductService->arrayCache->deleteAll();

            $usersProducts = $this->userProductService->getAllUsersProducts($purchaser->getId());

            $hasTrialCreatedWithinPeriod = false;

            foreach ($usersProducts as $userProduct) {

                $currentBrandRelevant = $userProduct->getProduct()->getBrand() == $purchaser->getBrand();
                $productLikelyATrial = strpos(strtolower($userProduct->getProduct()->getSku()), 'trial') !== false;

                $recentExpiry = false;

                if(!empty($userProduct->getExpirationDate())){
                    $userProductExpiryNotGreaterThan90DaysPast = $userProduct->getExpirationDate() > Carbon::now()->subDays(90);
                    // $userProductExpiryPast = $userProduct->getExpirationDate() < Carbon::now();
                    // $userProductExpiryInFuture = $userProduct->getExpirationDate() > Carbon::now();

                    $recentExpiry = $userProductExpiryNotGreaterThan90DaysPast;
                }

                if ($currentBrandRelevant && $productLikelyATrial && $recentExpiry) {
                    $hasTrialCreatedWithinPeriod = true;
                }
            }

            if ($hasTrialCreatedWithinPeriod) {
                $this->cartService->setCart($cart);

                // then check if they are trying to order a trial product, if so, reject it
                foreach ($cart->getItems() as $cartItem) {
                    if (strpos(strtolower($cartItem->getSku()), 'trial') !== false &&
                        $this->cartService->getDueForInitialPayment() === 0) {

                        // OLD PART REPLACED BY NEW STUFF BELOW (delete this comment anytime, ALSO DELETE THE NOW-OBSOLETE COMMENTED-OUT CODE BELOW)
//                        throw new PaymentFailedException(
//                            'This account is not eligible for a free trial period. Please choose a regular membership.'
//                        );

                        // NEW (delete this comment anytime)
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