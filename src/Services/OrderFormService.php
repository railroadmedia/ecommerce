<?php

namespace Railroad\Ecommerce\Services;


use Railroad\Ecommerce\Events\GiveContentAccess;

class OrderFormService
{
    /**
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var TaxService
     */
    private $taxService;

    /**
     * @var ShippingService
     */
    private $shippingService;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var CustomerService
     */
    private $customerService;

    /**
     * @var AddressService
     */
    private $addressService;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var OrderItemService
     */
    private $orderItemService;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var PaymentMethodService
     */
    private $paymentMethodService;

    /**
     * @var OrderItemFulfillmentService
     */
    private $orderItemFulfillmentService;


    /**
     * OrderFormService constructor.
     * @param $cartAddressService
     */
    public function __construct(
        CartService $cartService,
        CartAddressService $cartAddressService,
        TaxService $taxService,
        ShippingService $shippingService,
        CustomerService $customerService,
        AddressService $addressService,
        OrderService $orderService,
        OrderItemService $orderItemService,
        PaymentService $paymentService,
        PaymentMethodService $paymentMethodService,
        OrderItemFulfillmentService $orderItemFulfillmentService
    ) {
        $this->cartService = $cartService;
        $this->cartAddressService = $cartAddressService;
        $this->taxService = $taxService;
        $this->shippingService = $shippingService;
        $this->customerService = $customerService;
        $this->addressService = $addressService;
        $this->orderService = $orderService;
        $this->orderItemService = $orderItemService;
        $this->paymentService = $paymentService;
        $this->paymentMethodService = $paymentMethodService;
        $this->orderItemFulfillmentService = $orderItemFulfillmentService;
    }

    /** Get the taxes and shipping costs for all the cart items.
     * Return null if the cart it's empty;
     * otherwise an array with the following structure:
     *      'shippingAddress' => array|null
     *      'billingAddress'  => array|null
     *      'cartItems' => array
     *      'totalDue' => float
     *      'totalTax' => float
     *      'shippingCosts' => float
     * @return array|null
     */
    public function prepareOrderForm()
    {
        $cartItems = $this->cartService->getAllCartItems();
        if (empty($cartItems)) {
            return null;
        }

        $billingAddress = $this->cartAddressService->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);
        $shippingAddress = $this->cartAddressService->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE);

        $shippingCosts = $this->shippingService->getShippingCosts($cartItems, $shippingAddress);

        $taxes = $this->taxService->calculateTaxesForCartItems($cartItems, $billingAddress['country'], $billingAddress['region'], $shippingCosts);

        return array_merge(
            [
                'shippingAddress' => $shippingAddress,
                'billingAddress' => $billingAddress
            ],
            $taxes
        );
    }

    public function submitOrder(
        $paymentType,
        $billingCountry,
        $billingEmail = '',
        $billingZip = '',
        $billingRegion = '',
        $shippingFirstName = '',
        $shippingLastName = '',
        $shippingAddressLine1 = '',
        $shippingAddressLine2 = '',
        $shippingCity = '',
        $shippingRegion = '',
        $shippingCountry = '',
        $shippingZip = '',
        $paymentPlan = '',
        $paypalExpressCheckoutToken = '',
        $stripeCreditCardToken = '',
        $creditCardExpirationMonth = '',
        $creditCardExpirationYear = '',
        $creditCardNumber = '',
        $last4 = '',
        $paymentGatewayId
    ) {
        $cartItems = $this->cartService->getAllCartItems();

        if (empty($cartItems)) {
            return null;
        }

        $userId = request()->user()->id ?? null;
        $customerId = $this->setCustomer($billingEmail);

        list($billingAddress, $billingAddressDB) = $this->setBillingAddress($billingCountry, $billingZip, $billingRegion, $userId, $customerId);
        list($shippingAddress, $shippingAddressDB) = $this->setShippingAddress($shippingFirstName, $shippingLastName, $shippingAddressLine1, $shippingAddressLine2, $shippingCity, $shippingRegion, $shippingCountry, $shippingZip, $userId, $customerId);

        $shippingCosts = $this->shippingService->getShippingCosts($cartItems, $shippingAddress);

        $cartItemsWithTaxesAndCosts = $this->taxService->calculateTaxesForCartItems($cartItems, $billingAddress['country'], $billingAddress['region'], $shippingCosts);

        $order = $this->saveOrderAndOrderItems($cartItemsWithTaxesAndCosts, $userId, $customerId, $shippingAddressDB, $billingAddressDB);

        $paymentMethod = $this->paymentMethodService->store($paymentType,
            $paymentGatewayId,
            $creditCardExpirationYear,
            $creditCardExpirationMonth,
            $creditCardNumber,
            $last4,
            '',
            '',
            $paypalExpressCheckoutToken,
            $billingAddressDB['id'],
            null,
            $userId,
            $customerId);

        $payment = $this->paymentService->store($order['due'],
            $order['paid'],
            0,
            $paymentType,
            '',
            null,
            $status = false,
            $message = '',
            $paymentMethod['id'],
            $currency = null,
            $orderId = $order['id'],
            $subscriptionId = null);

        //sent physical items to fulfilment
        $this->orderItemFulfillmentService->store($order['id']);

        event(new GiveContentAccess($order));

        return $payment;
    }

    /**
     * @param $cartItemsWithTaxesAndCosts
     * @param $userId
     * @param $customerId
     * @param $shippingAddressDB
     * @param $billingAddressDB
     */
    private function saveOrderAndOrderItems($cartItemsWithTaxesAndCosts, $userId, $customerId, $shippingAddressDB, $billingAddressDB)
    {
        //save a new order
        $order = $this->orderService->store(
            $cartItemsWithTaxesAndCosts['totalDue'],
            $cartItemsWithTaxesAndCosts['totalTax'],
            $cartItemsWithTaxesAndCosts['shippingCosts'],
            0,
            $userId,
            $customerId,
            $shippingAddressDB['id'], $billingAddressDB['id']);

        //save order items
        foreach ($cartItemsWithTaxesAndCosts['cartItems'] as $item) {
            $order['items'][] = $this->orderItemService->store(
                $order['id'],
                $item['options']['product-id'],
                $item['quantity'],
                $item['price'] * $item['quantity'],
                0,
                $item['itemTax'],
                $item['itemShippingCosts'],
                $item['totalPrice']);
        }

        return $order;
    }

    /**
     * @param $billingEmail
     * @return null
     */
    private function setCustomer($billingEmail)
    {
        $customerId = null;

        //if the billing email exists on request => we have a new customer
        if (!empty($billingEmail)) {
            $customer = $this->customerService->store('', $billingEmail, ConfigService::$brand);
            $customerId = $customer['id'];
        }
        return $customerId;
    }

    /**
     * @param $billingCountry
     * @param $billingZip
     * @param $billingRegion
     * @param $userId
     * @param $customerId
     * @return array
     */
    private function setBillingAddress($billingCountry, $billingZip, $billingRegion, $userId, $customerId)
    {
//set the billing address on session
        $billingAddress = $this->cartAddressService->setAddress([
            'country' => $billingCountry,
            'region' => $billingRegion,
            'zip' => $billingZip
        ], CartAddressService::BILLING_ADDRESS_TYPE);

        //save billing address in database
        $billingAddressDB = $this->addressService->store(
            CartAddressService::BILLING_ADDRESS_TYPE,
            ConfigService::$brand,
            $userId,
            $customerId,
            '',
            '',
            '',
            '',
            '',
            $billingZip,
            $billingRegion,
            $billingCountry);
        return array($billingAddress, $billingAddressDB);
    }

    /**
     * @param $shippingFirstName
     * @param $shippingLastName
     * @param $shippingAddressLine1
     * @param $shippingAddressLine2
     * @param $shippingCity
     * @param $shippingRegion
     * @param $shippingCountry
     * @param $shippingZip
     * @param $userId
     * @param $customerId
     * @return array
     */
    private function setShippingAddress($shippingFirstName, $shippingLastName, $shippingAddressLine1, $shippingAddressLine2, $shippingCity, $shippingRegion, $shippingCountry, $shippingZip, $userId, $customerId)
    {
//set the shipping address on session
        $shippingAddress = $this->cartAddressService->setAddress([
            'firstName' => $shippingFirstName,
            'lastName' => $shippingLastName,
            'streetLineOne' => $shippingAddressLine1,
            'streetLineTwo' => $shippingAddressLine2,
            'zipOrPostalCode' => $shippingZip,
            'city' => $shippingCity,
            'region' => $shippingRegion,
            'country' => $shippingCountry,
        ], CartAddressService::SHIPPING_ADDRESS_TYPE);

        //save the shipping address
        $shippingAddressDB = $this->addressService->store(
            CartAddressService::SHIPPING_ADDRESS_TYPE,
            ConfigService::$brand,
            $userId,
            $customerId,
            $shippingFirstName,
            $shippingLastName,
            $shippingAddressLine1,
            $shippingAddressLine2,
            $shippingCity,
            $shippingZip,
            $shippingRegion,
            $shippingCountry);
        return array($shippingAddress, $shippingAddressDB);
    }
}