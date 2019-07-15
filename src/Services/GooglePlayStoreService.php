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
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Exceptions\ReceiptValidationException;
use Railroad\Ecommerce\Gateways\GooglePlayStoreGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;

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
            $validationResponse = $this->googlePlayStoreGateway
                ->validate(
                    $receipt->getPackageName(),
                    $receipt->getProductId(),
                    $receipt->getPurchaseToken()
                );
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

    public function getPurchasedItems()
    {
        // todo - update
        return [];
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
     * @param [] $purchasedItems
     *
     * @return OrderItem[]
     */
    public function createOrderItems(
        array $purchasedItems
    ): array
    {
        $orderItems = [];

        foreach ($purchasedItems as $item) {
            $product = $this->getProductByGoogleStoreId($item->getProductId());

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
}
