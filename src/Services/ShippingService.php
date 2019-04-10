<?php

namespace Railroad\Ecommerce\Services;

use Doctrine\ORM\ORMException;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;

class ShippingService
{
    /**
     * @var ShippingOptionRepository
     */
    private $shippingOptionRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var DiscountService
     */
    private $discountService;

    /**
     * ShippingService constructor.
     *
     * @param ShippingOptionRepository $shippingOptionRepository
     * @param ProductRepository $productRepository
     * @param DiscountService $discountService
     */
    public function __construct(
        ShippingOptionRepository $shippingOptionRepository,
        ProductRepository $productRepository,
        DiscountService $discountService
    )
    {
        $this->shippingOptionRepository = $shippingOptionRepository;
        $this->productRepository = $productRepository;
        $this->discountService = $discountService;
    }

    /**
     * Shipping costs always ignore the payment plan number since all shipping must be paid on the first payment.
     *
     * @param Cart $cart
     * @return float
     * @throws ORMException
     */
    public function getShippingDueForCart(Cart $cart)
    {
        $weight = $this->getCartWeight($cart);

        if (empty($cart->getBillingAddress())) {
            return 0;
        }

        $costBeforeDiscounts = $this->shippingOptionRepository->getShippingCosts(
            $cart->getBillingAddress()
                ->getCountry(),
            $weight
        );

        return $costBeforeDiscounts - $this->discountService->getTotalShippingDiscountedForCart($cart);
    }

    /**
     * @param Cart $cart
     *
     * @return float
     * @throws ORMException
     */
    public function getCartWeight(Cart $cart): float
    {
        $weight = 0;

        $productsInCart = $this->productRepository->byCart($cart);

        foreach ($productsInCart as $product) {
            $cartItem = $cart->getItemBySku($product->getSku());

            if (!empty($cartItem)) {
                $weight += $product->getWeight() * $cartItem->getQuantity();
            }
        }

        return (float)$weight;
    }
}