<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\GoogleReceipt;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Exceptions\ReceiptValidationException;
use Railroad\Ecommerce\Gateways\GooglePlayStoreGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Throwable;

class GooglePlayStoreService
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var GooglePlayStoreGateway
     */
    private $googlePlayStoreGateway;

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
     * GooglePlayStoreService constructor.
     *
     * @param GooglePlayStoreGateway $googlePlayStoreGateway,
     * @param EcommerceEntityManager $entityManager,
     * @param ProductRepository $productRepository,
     * @param SubscriptionRepository $subscriptionRepository,
     * @param UserProductService $userProductService,
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        GooglePlayStoreGateway $googlePlayStoreGateway,
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService,
        UserProviderInterface $userProvider
    )
    {
        $this->googlePlayStoreGateway = $googlePlayStoreGateway;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
        $this->userProvider = $userProvider;
    }

    /**
     * @param GoogleReceipt $receipt
     *
     * @return User
     *
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function processReceipt(GoogleReceipt $receipt): User
    {
        $this->entityManager->persist($receipt);

        try {
            $this->googlePlayStoreGateway
                ->validate(
                    $receipt->getPackageName(),
                    $receipt->getProductId(),
                    $receipt->getPurchaseToken()
                );

            $purchasedProduct = $this->getPurchasedItem($receipt);

            if (!$purchasedProduct) {
                throw new ReceiptValidationException('Purchased product not found in config');
            }

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

        $orderItem = $this->createOrderItem($purchasedProduct);

        $order = $this->createOrder($orderItem, $user);

        $payment = $this->createOrderPayment($order);

        $this->createOrderSubscription($purchasedProduct, $order, $payment);

        $receipt->setPayment($payment);

        $this->entityManager->flush();

        event(new OrderEvent($order, $payment));

        return $user;
    }

    /**
     * @param Order $order
     *
     * @return Payment
     *
     * @throws Throwable
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
        $payment->setType(Payment::TYPE_GOOGLE_INITIAL_ORDER);
        $payment->setExternalId('');
        $payment->setExternalProvider(Payment::EXTERNAL_PROVIDER_GOOGLE);
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
     * @param OrderItem $orderItem
     * @param User $user
     *
     * @return Order
     *
     * @throws Throwable
     */
    public function createOrder(
        OrderItem $orderItem,
        User $user
    ): Order
    {
        $order = new Order();

        $totalDue = $orderItem->getFinalPrice();

        $order->addOrderItem($orderItem);
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
     * @param Product $purchasedProduct
     *
     * @return OrderItem
     *
     * @throws Throwable
     */
    public function createOrderItem(
        Product $purchasedProduct
    ): OrderItem
    {
        $orderItem = new OrderItem();

        $orderItem->setProduct($purchasedProduct);
        $orderItem->setQuantity(1);
        $orderItem->setWeight(0);
        $orderItem->setInitialPrice($purchasedProduct->getPrice());
        $orderItem->setTotalDiscounted(0);
        $orderItem->setFinalPrice($purchasedProduct->getPrice());
        $orderItem->setCreatedAt(Carbon::now());

        $this->entityManager->persist($orderItem);

        return $orderItem;
    }

    /**
     * @param Product $purchasedProduct
     * @param Order $order
     * @param Payment $payment
     *
     * @return Subscription
     *
     * @throws Throwable
     */
    public function createOrderSubscription(
        Product $purchasedProduct,
        Order $order,
        Payment $payment
    ): Subscription
    {
        $subscription = new Subscription();

        $nextBillDate = Carbon::now();

        if (!empty($purchasedProduct->getSubscriptionIntervalType())) {
            if ($purchasedProduct->getSubscriptionIntervalType() == config('ecommerce.interval_type_monthly')) {
                $nextBillDate =
                    Carbon::now()
                        ->addMonths($purchasedProduct->getSubscriptionIntervalCount());

            }
            elseif ($purchasedProduct->getSubscriptionIntervalType() == config('ecommerce.interval_type_yearly')) {
                $nextBillDate =
                    Carbon::now()
                        ->addYears($purchasedProduct->getSubscriptionIntervalCount());

            }
            elseif ($purchasedProduct->getSubscriptionIntervalType() == config('ecommerce.interval_type_daily')) {
                $nextBillDate =
                    Carbon::now()
                        ->addDays($purchasedProduct->getSubscriptionIntervalCount());
            }
        }

        $intervalType = $purchasedProduct ? $purchasedProduct->getSubscriptionIntervalType() : config('ecommerce.interval_type_monthly');

        $intervalCount = $purchasedProduct ? $purchasedProduct->getSubscriptionIntervalCount() : 1;

        $subscription->setBrand(config('ecommerce.brand'));
        $subscription->setType(Subscription::TYPE_SUBSCRIPTION);
        $subscription->setUser($order->getUser());
        $subscription->setOrder($order);
        $subscription->setProduct($purchasedProduct);
        $subscription->setIsActive(true);
        $subscription->setStartDate(Carbon::now());
        $subscription->setPaidUntil($nextBillDate);
        $subscription->setTotalPrice($purchasedProduct->getPrice());
        $subscription->setTax(0);
        $subscription->setCurrency($payment->getCurrency());
        $subscription->setIntervalType($intervalType);
        $subscription->setIntervalCount($intervalCount);
        $subscription->setTotalCyclesPaid(1);
        $subscription->setTotalCyclesDue(1);
        // $subscription->setWebOrderLineItemId(); // todo - update
        $subscription->setCreatedAt(Carbon::now());

        $subscriptionPayment = new SubscriptionPayment();

        $subscriptionPayment->setSubscription($subscription);
        $subscriptionPayment->setPayment($payment);

        $this->entityManager->persist($subscription);
        $this->entityManager->persist($subscriptionPayment);

        return $subscription;
    }

    /**
     * @param GoogleReceipt $receipt
     *
     * @return Product|null
     *
     * @throws Throwable
     */
    public function getPurchasedItem(GoogleReceipt $receipt): ?Product
    {
        $productsMap = config('ecommerce.google_store_products_map');

        return $this->productRepository->bySku($productsMap[$receipt->getProductId()]);
    }
}
