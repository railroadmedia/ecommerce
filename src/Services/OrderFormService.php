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
     * @var \Railroad\Ecommerce\Services\SubscriptionService
     */
    private $subscriptionService;

    /**
     * OrderFormService constructor.
     *
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
        OrderItemFulfillmentService $orderItemFulfillmentService,
        SubscriptionService $subscriptionService
    ) {
        $this->cartService                 = $cartService;
        $this->cartAddressService          = $cartAddressService;
        $this->taxService                  = $taxService;
        $this->shippingService             = $shippingService;
        $this->customerService             = $customerService;
        $this->addressService              = $addressService;
        $this->orderService                = $orderService;
        $this->orderItemService            = $orderItemService;
        $this->paymentService              = $paymentService;
        $this->paymentMethodService        = $paymentMethodService;
        $this->orderItemFulfillmentService = $orderItemFulfillmentService;
        $this->subscriptionService         = $subscriptionService;
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
     *
     * @return array|null
     */
    public function prepareOrderForm()
    {
        $cartItems = $this->cartService->getAllCartItems();
        if(empty($cartItems))
        {
            return null;
        }

        $billingAddress  = $this->cartAddressService->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);
        $shippingAddress = $this->cartAddressService->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE);

        $shippingCosts = $this->shippingService->getShippingCosts($cartItems, $shippingAddress);

        $taxes = $this->taxService->calculateTaxesForCartItems($cartItems, $billingAddress['country'], $billingAddress['region'], $shippingCosts);

        return array_merge(
            [
                'shippingAddress' => $shippingAddress,
                'billingAddress'  => $billingAddress
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

        if(empty($cartItems))
        {
            return null;
        }

        $userId     = request()->user()->id ?? null;
        $customerId = $this->setCustomer($billingEmail);

        list($billingAddress, $billingAddressDB) = $this->setBillingAddress($billingCountry, $billingZip, $billingRegion, $userId, $customerId);
        list($shippingAddress, $shippingAddressDB) = $this->setShippingAddress($shippingFirstName, $shippingLastName, $shippingAddressLine1, $shippingAddressLine2, $shippingCity, $shippingRegion, $shippingCountry, $shippingZip, $userId, $customerId);

        $shippingCosts = $this->shippingService->getShippingCosts($cartItems, $shippingAddress);

        $cartItemsWithTaxesAndCosts = $this->taxService->calculateTaxesForCartItems($cartItems, $billingAddress['country'], $billingAddress['region'], $shippingCosts);

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

        if(!$paymentMethod['status'])
        {
            return $paymentMethod;
        }

        $payment = $this->paymentService->store(
            $cartItemsWithTaxesAndCosts['totalDue'],
            $cartItemsWithTaxesAndCosts['totalDue'],
            0,
            $paymentMethod['id'],
            $paymentMethod['currency'],
            null,
            []);

        //if successful payment => create order, order items, sent physical items to fulfillment and trigger event that set user access to content
        if($payment['status'])
        {
            $order = $this->saveOrderAndOrderItems(
                $cartItemsWithTaxesAndCosts,
                $userId,
                $customerId,
                $shippingAddressDB,
                $billingAddressDB,
                $payment['id']
            );

            $this->orderItemFulfillmentService->store($order['id']);
            $subscriptions = $this->subscriptionService->createOrderSubscription($order['id'], $paymentMethod['currency']);

            //save the link between payment and subscription
            foreach($subscriptions as $subscription){
                $this->paymentService->createSubscriptionPayment($subscription['id'], $payment['id']);
            }

            event(new GiveContentAccess($order));
        }

        return $payment;
    }

    /** Save the order with the order items and the link between order and payment.
     * @param array $cartItemsWithTaxesAndCosts
     * @param null|integer $userId
     * @param null|integer $customerId
     * @param integer $shippingAddressDB
     * @param integer $billingAddressDB
     * @param integer $paymentId
     */
    private function saveOrderAndOrderItems($cartItemsWithTaxesAndCosts, $userId, $customerId, $shippingAddressDB, $billingAddressDB, $paymentId)
    {
        //save a new order
        $order = $this->orderService->store(
            $cartItemsWithTaxesAndCosts['totalDue'],
            $cartItemsWithTaxesAndCosts['totalTax'],
            $cartItemsWithTaxesAndCosts['shippingCosts'],
            $cartItemsWithTaxesAndCosts['totalDue'],
            $userId,
            $customerId,
            $shippingAddressDB['id'], $billingAddressDB['id']);

        //save order items
        foreach($cartItemsWithTaxesAndCosts['cartItems'] as $item)
        {
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

        //save the link between payment and order
        $this->paymentService->createOrderPayment($order['id'], $paymentId);

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
        if(!empty($billingEmail))
        {
            $customer   = $this->customerService->store('', $billingEmail, ConfigService::$brand);
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
            'region'  => $billingRegion,
            'zip'     => $billingZip
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
            'firstName'       => $shippingFirstName,
            'lastName'        => $shippingLastName,
            'streetLineOne'   => $shippingAddressLine1,
            'streetLineTwo'   => $shippingAddressLine2,
            'zipOrPostalCode' => $shippingZip,
            'city'            => $shippingCity,
            'region'          => $shippingRegion,
            'country'         => $shippingCountry,
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