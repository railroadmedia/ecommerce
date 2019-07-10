<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Exceptions\AppleStoreKit\ReceiptValidationException;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use ReceiptValidator\iTunes\ResponseInterface;
use ReceiptValidator\iTunes\PurchaseItem;

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
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * AppleStoreKitService constructor.
     *
     * @param AppleStoreKit $entityManager
     * @param EcommerceEntityManager $entityManager
     * @param ProductRepository $productRepository
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AppleStoreKitGateway $appleStoreKitGateway,
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository,
        UserProviderInterface $userProvider
    )
    {
        $this->appleStoreKitGateway = $appleStoreKitGateway;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->userProvider = $userProvider;
    }

    /**
     * @param AppleReceipt $receipt
     *
     * @return User
     *
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function processReceipt(AppleReceipt $receipt): User
    {
        $this->entityManager->persist($receipt);

        try {
            $validationResponse = $this->appleStoreKitGateway->validate($receipt->getReceipt());
            $receipt->setValid(true);
        } catch (ReceiptValidationException $exception) {

            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());

            $this->entityManager->flush();

            throw $exception;
        }

        $user = $this->userProvider->getUserByEmail($receipt->getEmail());

        if (!$user) {
            $user = $this->userProvider->createUser($receipt->getEmail(), $receipt->getPassword());

            auth()->loginUsingId($user->getId());
        }

        $currentPurchasedItems = $this->getPurchasedItems($validationResponse);

        $orderItems = $this->createOrderItems($currentPurchasedItems);

        $order = $this->createOrder($orderItems, $user);

        $payment = $this->createPayment($order);

        $subscriptions = $this->createSubscriptions($currentPurchasedItems, $order, $payment);

        // todo - create user products

        $this->entityManager->flush();

        return $user;
    }

    /**
     * @param ResponseInterface $validationResponse
     *
     * @return PurchaseItem[]
     */
    public function getPurchasedItems(ResponseInterface $validationResponse): array
    {
        $items = [];

        foreach ($validationResponse->getPurchases() as $item) {
            if ($item->getExpiresDate() >= $validationResponse->getReceiptCreationDate()) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param Order $order
     *
     * @return Payment
     */
    public function createPayment(Order $order): Payment
    {
        $totalDue = 0;

        foreach ($order->getOrderItems() as $orderItem) {
            $totalDue += $orderItem->setFinalPrice();
        }

        // todo - add values for these fields
        $externalPaymentId = '';
        $externalProvider = '';
        $currency = '';

        $payment = new Payment();

        $payment->setTotalDue($totalDue);
        $payment->setTotalPaid($totalDue);
        $payment->setTotalRefunded(0);
        $payment->setConversionRate(1);
        $payment->setType(Payment::TYPE_INITIAL_APPLE_ORDER);
        $payment->setExternalId($externalPaymentId);
        $payment->setExternalProvider($externalProvider);
        $payment->setGatewayName(config('ecommerce.brand'));
        $payment->setStatus(Payment::STATUS_PAID);
        $payment->setCurrency($currency);
        $payment->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);

        return $payment;
    }

    /**
     * @param OrderItem[] $orderItems
     * @param User $user
     *
     * @return Order
     */
    public function createOrder(
        array $orderItems,
        User $user
    ): Order
    {
        $order = new Order();

        $totalDue = 0;

        foreach ($orderItems as $orderItem) {
            $totalDue += $orderItem->setFinalPrice();
            $order->addOrderItem($orderItem);
        }

        $order->setTotalDue($totalDue);
        $order->setProductDue($totalDue);
        $order->setFinanceDue(0);
        $order->setTaxesDue(0);
        $order->setTotalPaid(0);
        $order->setBrand(config('ecommerce.brand'));
        $order->setUser($user);
        $order->setShippingDue(0);

        $this->entityManager->persist($order);

        return $order;
    }

    /**
     * @param PurchaseItem[] $purchasedItems
     *
     * @return OrderItem[]
     */
    public function createOrderItems(
        array $purchasedItems
    ): array
    {
        $orderItems = [];

        foreach ($purchasedItems as $item) {
            $product = $this->getProductByAppleStoreId($item->getProductId());

            if ($product) {
                $orderItem = new OrderItem();

                $orderItem->setProduct($product);
                $orderItem->setQuantity($item->getQuantity());
                $orderItem->setWeight(0);
                $orderItem->setInitialPrice($product->getPrice());
                $orderItem->setTotalDiscounted(0);
                $orderItem->setFinalPrice($product->getPrice());
                $orderItem->setCreatedAt(Carbon::now());

                $orderItems[] = $orderItem;

                $this->entityManager->persist($orderItem);
            }
        }

        return $orderItems;
    }

    /**
     * @param PurchaseItem[] $purchasedItems
     * @param Order $order
     * @param Payment $payment
     *
     * @return Subscription[]
     */
    public function createSubscriptions(
        array $purchasedItems,
        Order $order,
        Payment $payment
    ): array
    {
        $subscriptions = [];

        foreach ($purchasedItems as $item) {
            $subscription = new Subscription();

            $product = $this->getProductByAppleStoreId($item->getProductId());

            $nextBillDate = null;

            if (!empty($product->getSubscriptionIntervalType())) {
                if ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_monthly')) {
                    $nextBillDate =
                        Carbon::now()
                            ->addMonths($product->getSubscriptionIntervalCount());

                }
                elseif ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_yearly')) {
                    $nextBillDate =
                        Carbon::now()
                            ->addYears($product->getSubscriptionIntervalCount());

                }
                elseif ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_daily')) {
                    $nextBillDate =
                        Carbon::now()
                            ->addDays($product->getSubscriptionIntervalCount());
                }
            }

            $intervalType = $product ? $product->getSubscriptionIntervalType() : config('ecommerce.interval_type_monthly');

            $intervalCount = $product ? $product->getSubscriptionIntervalCount() : 1;

            $subscription->setBrand(config('ecommerce.brand'));
            $subscription->setType(Subscription::TYPE_SUBSCRIPTION);
            $subscription->setUser($order->getUser());
            $subscription->setOrder($order);
            $subscription->setProduct($product);
            $subscription->setIsActive(true);
            $subscription->setStartDate(Carbon::now());
            $subscription->setPaidUntil($nextBillDate);
            $subscription->setTotalPrice($product->getPrice());
            $subscription->setTax(0);
            $subscription->setCurrency($payment->getCurrency());
            $subscription->setIntervalType($intervalType);
            $subscription->setIntervalCount($intervalCount);
            $subscription->setTotalCyclesPaid(1);
            $subscription->setTotalCyclesDue(1);
            $subscription->setCreatedAt(Carbon::now());

            $subscriptionPayment = new SubscriptionPayment();

            $subscriptionPayment->setSubscription($subscription);
            $subscriptionPayment->setPayment($payment);

            $this->entityManager->persist($subscription);
            $this->entityManager->persist($subscriptionPayment);

            $subscriptions[] = $subscription;
        }

        return $subscriptions;
    }

    /**
     * @param string $appleStoreId
     *
     * @return Product|null
     */
    public function getProductByAppleStoreId(string $appleStoreId): ?Product
    {
        $productsMap = config('ecommerce.apple_store_products_map');

        if (isset($productsMap[$appleStoreId])) {
            return $this->productRepository->bySku($productsMap[$appleStoreId]);
        }
    }
}
