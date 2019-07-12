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
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Exceptions\AppleStoreKit\ReceiptValidationException;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
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
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var UserProductService
     */
    private $userProductService;

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
     * @param UserProductService $userProductService
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AppleStoreKitGateway $appleStoreKitGateway,
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService,
        UserProviderInterface $userProvider
    )
    {
        $this->appleStoreKitGateway = $appleStoreKitGateway;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
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

        $payment = $this->createOrderPayment($order);

        $subscriptions = $this->createOrderSubscriptions($currentPurchasedItems, $order, $payment);

        $receipt->setPayment($payment);

        $this->entityManager->flush();

        event(new OrderEvent($order, $payment));

        return $user;
    }

    /**
     * @param AppleReceipt $receipt
     * @param string $webOrderLineItemId
     *
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function processNotification(
        AppleReceipt $receipt,
        string $webOrderLineItemId
    )
    {
        $this->entityManager->persist($receipt);

        try {
            $this->appleStoreKitGateway->validate($receipt->getReceipt());
            $receipt->setValid(true);
        } catch (ReceiptValidationException $exception) {

            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());

            $this->entityManager->flush();

            throw $exception;
        }

        $subscription = $this->subscriptionRepository
            ->findOneBy(['webOrderLineItemId' => $webOrderLineItemId]);

        $oldSubscription = clone($subscription);

        $subscriptionEventType = 'renewed';

        if ($receipt->getNotificationType() == AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE) {
            $payment = $this->createSubscriptionRenewalPayment($subscription);

            $this->renewSubscription($subscription);

            $receipt->setPayment($payment);

            $this->entityManager->flush();

            event(new SubscriptionRenewed($subscription, $payment));

        } else {

            $this->cancelSubscription($subscription, $receipt);

            $this->entityManager->flush();

            $subscriptionEventType = 'canceled';
        }

        $this->userProductService->updateSubscriptionProducts($subscription);

        event(new SubscriptionUpdated($oldSubscription, $subscription));
        event(new SubscriptionEvent($subscription->getId(), $subscriptionEventType));
    }

    /**
     * @param Subscription $subscription
     *
     * @return Payment
     */
    public function createSubscriptionRenewalPayment(Subscription $subscription): Payment
    {
        $payment = new Payment();

        $payment->setTotalDue($subscription->getTotalPrice());
        $payment->setTotalPaid($subscription->getTotalPrice());
        $payment->setTotalRefunded(0);
        $payment->setConversionRate(1);
        $payment->setType(Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL);
        $payment->setExternalId('');
        $payment->setExternalProvider(Payment::EXTERNAL_PROVIDER_APPLE);
        $payment->setGatewayName(config('ecommerce.brand'));
        $payment->setStatus(Payment::STATUS_PAID);
        $payment->setCurrency('');
        $payment->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);

        $subscriptionPayment = new SubscriptionPayment();

        $subscriptionPayment->setSubscription($subscription);
        $subscriptionPayment->setPayment($payment);

        $this->entityManager->persist($subscriptionPayment);

        return $payment;
    }

    /**
     * @param Subscription $subscription
     */
    public function renewSubscription(Subscription $subscription)
    {
        $nextBillDate = null;

        switch ($subscription->getIntervalType()) {
            case config('ecommerce.interval_type_monthly'):
                $nextBillDate =
                    Carbon::now()
                        ->addMonths($subscription->getIntervalCount());
                break;

            case config('ecommerce.interval_type_yearly'):
                $nextBillDate =
                    Carbon::now()
                        ->addYears($subscription->getIntervalCount());
                break;

            case config('ecommerce.interval_type_daily'):
                $nextBillDate =
                    Carbon::now()
                        ->addDays($subscription->getIntervalCount());
                break;

            default:
                throw new Exception("Subscription type not configured");
                break;
        }

        $subscription->setIsActive(true);
        $subscription->setCanceledOn(null);
        $subscription->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1);
        $subscription->setPaidUntil(
            $nextBillDate ? $nextBillDate->startOfDay() :
                Carbon::now()
                    ->addMonths(1)
        );
        $subscription->setUpdatedAt(Carbon::now());
    }

    /**
     * @param Subscription $subscription
     * @param AppleReceipt $receipt
     */
    public function cancelSubscription($subscription, $receipt)
    {
        $noteFormat = 'Canceled by apple notification, receipt id: %s';

        $subscription->setCanceledOn(Carbon::now());
        $subscription->setUpdatedAt(Carbon::now());
        $subscription->setNote(sprintf($noteFormat, $receipt->getId()));
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
    public function createOrderPayment(Order $order): Payment
    {
        $totalDue = 0;

        foreach ($order->getOrderItems() as $orderItem) {
            $totalDue += $orderItem->getFinalPrice();
        }

        $payment = new Payment();

        $payment->setTotalDue($totalDue);
        $payment->setTotalPaid($totalDue);
        $payment->setTotalRefunded(0);
        $payment->setConversionRate(1);
        $payment->setType(Payment::TYPE_APPLE_INITIAL_ORDER);
        $payment->setExternalId('');
        $payment->setExternalProvider(Payment::EXTERNAL_PROVIDER_APPLE);
        $payment->setGatewayName(config('ecommerce.brand'));
        $payment->setStatus(Payment::STATUS_PAID);
        $payment->setCurrency('');
        $payment->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);

        $orderPayment = new OrderPayment();

        $orderPayment->setOrder($order);
        $orderPayment->setPayment($payment);
        $orderPayment->setCreatedAt(Carbon::now());

        $this->entityManager->persist($orderPayment);

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
            $totalDue += $orderItem->getFinalPrice();
            $order->addOrderItem($orderItem);
        }

        $order->setTotalDue($totalDue);
        $order->setProductDue($totalDue);
        $order->setFinanceDue(0);
        $order->setTaxesDue(0);
        $order->setTotalPaid($totalDue);
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
    public function createOrderSubscriptions(
        array $purchasedItems,
        Order $order,
        Payment $payment
    ): array
    {
        $subscriptions = [];

        foreach ($purchasedItems as $item) {
            $subscription = new Subscription();

            $product = $this->getProductByAppleStoreId($item->getProductId());

            if (!$product) {
                continue;
            }

            $nextBillDate = Carbon::now();

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
            $subscription->setWebOrderLineItemId($item->getWebOrderLineItemId());
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
