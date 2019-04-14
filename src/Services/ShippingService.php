<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Repositories\AddressRepository;
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
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * ShippingService constructor.
     *
     * @param ShippingOptionRepository $shippingOptionRepository
     * @param ProductRepository $productRepository
     * @param DiscountService $discountService
     * @param AddressRepository $addressRepository
     */
    public function __construct(
        ShippingOptionRepository $shippingOptionRepository,
        ProductRepository $productRepository,
        DiscountService $discountService,
        AddressRepository $addressRepository
    )
    {
        $this->shippingOptionRepository = $shippingOptionRepository;
        $this->productRepository = $productRepository;
        $this->discountService = $discountService;
        $this->addressRepository = $addressRepository;
    }

    /**
     * Shipping costs always ignore the payment plan number since all shipping must be paid on the first payment.
     *
     * @param Cart $cart
     * @param $totalDueInItems
     * @return float
     */
    public function getShippingDueForCart(Cart $cart, float $totalDueInItems): float
    {
        $weight = $this->getCartWeight($cart);
        $country = null;

        if (!empty($cart->getShippingAddressId())) {
            $address = $this->addressRepository->byId($cart->getShippingAddressId());

            if (!empty($address)) {
                $country = $address->getCountry();
            }
        }
        elseif (!empty($cart->getShippingAddress())) {
            $country =
                $cart->getShippingAddress()
                    ->getCountry();
        }

        if (empty($country)) {
            return 0;
        }

        $costBeforeDiscounts = $this->shippingOptionRepository->getShippingCosts($country, $weight);

        return (float)($costBeforeDiscounts -
            $this->discountService->getTotalShippingDiscounted($cart, $totalDueInItems, $costBeforeDiscounts));
    }

    /**
     * @param Cart $cart
     *
     * @return float
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

    // todo: test

    /**
     * @param Cart $cart
     *
     * @return bool
     */
    public function doesCartHaveAnyPhysicalItems(Cart $cart): bool
    {
        $products = $this->productRepository->byCart($cart);

        foreach ($products as $product) {
            if ($product->getIsPhysical()) {
                return true;
            }
        }

        return false;
    }

    // todo: test

    /**
     * @param Cart $cart
     *
     * @return bool
     */
    public function doesCartHaveAnyDigitalItems(Cart $cart): bool
    {
        $products = $this->productRepository->byCart($cart);

        foreach ($products as $product) {
            if (!$product->getIsPhysical()) {
                return true;
            }
        }

        return false;
    }
}