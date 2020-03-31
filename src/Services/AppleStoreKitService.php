<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
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
use Railroad\Ecommerce\Events\MobileOrderEvent;
use Railroad\Ecommerce\Events\Subscriptions\MobileSubscriptionCanceled;
use Railroad\Ecommerce\Events\Subscriptions\MobileSubscriptionRenewed;
use Railroad\Ecommerce\Exceptions\ReceiptValidationException;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\AppleReceiptRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use ReceiptValidator\iTunes\PurchaseItem;
use ReceiptValidator\iTunes\ResponseInterface;
use Throwable;

class AppleStoreKitService
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var AppleReceiptRepository
     */
    private $appleReceiptRepository;

    /**
     * @var AppleStoreKitGateway
     */
    private $appleStoreKitGateway;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var UserProductService
     */
    private $userProductService;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var SubscriptionPaymentRepository
     */
    private $subscriptionPaymentRepository;

    const RENEWAL_EXPIRATION_REASON = [
        1 => 'Apple in-app: Customer canceled their subscription.',
        2 => 'Apple in-app: Billing error.',
        3 => 'Apple in-app: Customer did not agree to a recent price modification.',
        4 => 'Apple in-app: Product was not available for purchase at the time of renewal.',
        5 => 'Apple in-app: Unknown error.',
    ];

    /**
     * AppleStoreKitService constructor.
     *
     * @param AppleReceiptRepository $appleReceiptRepository
     * @param AppleStoreKitGateway $appleStoreKitGateway
     * @param EcommerceEntityManager $entityManager
     * @param ProductRepository $productRepository
     * @param UserProductService $userProductService
     * @param UserProviderInterface $userProvider
     * @param SubscriptionRepository $subscriptionRepository
     * @param PaymentRepository $paymentRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     */
    public function __construct(
        AppleReceiptRepository $appleReceiptRepository,
        AppleStoreKitGateway $appleStoreKitGateway,
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository,
        UserProductService $userProductService,
        UserProviderInterface $userProvider,
        SubscriptionRepository $subscriptionRepository,
        PaymentRepository $paymentRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository
    ) {
        $this->appleReceiptRepository = $appleReceiptRepository;
        $this->appleStoreKitGateway = $appleStoreKitGateway;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->userProductService = $userProductService;
        $this->userProvider = $userProvider;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
    }

    /**
     * @param AppleReceipt $receipt
     *
     * @return User
     *
     * @throws Exception
     * @throws GuzzleException
     * @throws Throwable
     */
    public function processReceipt(AppleReceipt $receipt)
    : User {
        $this->entityManager->persist($receipt);

        try {
            $appleResponse = $this->appleStoreKitGateway->getResponse($receipt->getReceipt());

            if($receipt->getPurchaseType() == AppleReceipt::APPLE_SUBSCRIPTION_PURCHASE) {
                $currentPurchasedItem = $this->getLatestPurchasedItem($appleResponse);
                $receipt->setValid($currentPurchasedItem->getExpiresDate() > Carbon::now());
            } else {
                $currentPurchasedItem = $appleResponse->getPurchases()[0];
                $receipt->setValid(true);
            }

            $transactionId = $currentPurchasedItem->getTransactionId();

            $receipt->setTransactionId($transactionId);
            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

        } catch (ReceiptValidationException $exception) {

            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());
            $receipt->setRawReceiptResponse(base64_encode(serialize($exception->getAppleResponse())));

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();

            throw $exception;
        }

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        $user = $this->userProvider->getUserByEmail($receipt->getEmail());

        if (!$user) {
            $user = $this->userProvider->createUser($receipt->getEmail(), $receipt->getPassword());

            auth()->loginUsingId($user->getId());
        }

        $this->syncSubscription($appleResponse, $receipt, $user);

        $this->entityManager->flush();

        return $user;
    }

    /**
     * @param AppleReceipt $receipt
     * @param Subscription $subscription
     *
     * @throws Exception
     * @throws GuzzleException
     * @throws Throwable
     */
    public function processNotification(AppleReceipt $receipt)
    {
        $this->entityManager->persist($receipt);

        try {
            $appleResponse = $this->appleStoreKitGateway->getResponse($receipt->getReceipt());

            $currentPurchasedItem = $this->getLatestPurchasedItem($appleResponse);

            $transactionId = $currentPurchasedItem->getTransactionId();

            $receipt->setTransactionId($transactionId);
            $receipt->setValid($currentPurchasedItem->getExpiresDate() > Carbon::now());
            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

        } catch (ReceiptValidationException $exception) {

            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());
            $receipt->setRawReceiptResponse(base64_encode(serialize($exception->getAppleResponse())));

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();

            throw $exception;
        }

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        $subscription = $this->syncSubscription($appleResponse, $receipt);

        if ($receipt->getNotificationType() == AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE) {

            event(
                new MobileSubscriptionRenewed(
                    $subscription, $subscription->getLatestPayment(), MobileSubscriptionRenewed::ACTOR_SYSTEM
                )
            );

        } elseif (!empty($subscription->getCanceledOn())) {

            event(new MobileSubscriptionCanceled($subscription, MobileSubscriptionRenewed::ACTOR_SYSTEM));
        }

        $this->userProductService->updateSubscriptionProductsApp($subscription);

        $this->entityManager->flush();
    }

    /**
     * @param Subscription $subscription
     *
     * @throws GuzzleException
     * @throws Throwable
     */
    public function processSubscriptionRenewal(Subscription $subscription)
    {
        $receipt = $subscription->getAppleReceipt();

        try {
            $appleResponse = $this->appleStoreKitGateway->getResponse($receipt->getReceipt());

            $purchasedItem = $this->getLatestPurchasedItem($appleResponse);

            $receipt->setValid($purchasedItem->getExpiresDate() > Carbon::now());
            $receipt->setValidationError(null);
            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

        } catch (ReceiptValidationException $exception) {

            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());
            $receipt->setRawReceiptResponse(base64_encode(serialize($exception->getAppleResponse())));

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();
        }

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        $subscription = $this->syncSubscription($appleResponse, $receipt);

        if (!empty($receipt)) {
            $this->userProductService->updateSubscriptionProductsApp($subscription);
        } else {
            error_log('Error updating access for an Apple IOS subscription. Could not find or sync subscription for receipt (DB Receipt): ' . var_export($receipt, true));
            error_log('Error updating access for Apple IOS subscription. Could not find or sync subscription for receipt (AppleResponse): ' . var_export($appleResponse, true));
        }
    }

    /**
     * @param ResponseInterface $validationResponse
     *
     * @return PurchaseItem|null
     */
    public function getFirstPurchasedItem(ResponseInterface $validationResponse)
    : ?PurchaseItem {
        if (is_array($validationResponse->getLatestReceiptInfo()) &&
            !empty($validationResponse->getLatestReceiptInfo())) {

            $array = $validationResponse->getLatestReceiptInfo();

            return end($array);
        }

        return null;
    }

    /**
     * @param ResponseInterface $validationResponse
     *
     * @return PurchaseItem|null
     */
    public function getLatestPurchasedItem(ResponseInterface $validationResponse)
    : ?PurchaseItem {
        if (is_array($validationResponse->getLatestReceiptInfo()) &&
            !empty($validationResponse->getLatestReceiptInfo())) {
            return $validationResponse->getLatestReceiptInfo()[0] ?? null;
        }

        return null;
    }

    /**
     * @param ResponseInterface $appleResponse
     * @param AppleReceipt $receipt
     * @param User|null $user
     *
     * @return Subscription|null
     */
    public function syncSubscription(ResponseInterface $appleResponse, AppleReceipt $receipt, ?User $user = null)
    {
        $firstPurchasedItem = $this->getFirstPurchasedItem($appleResponse);
        $latestPurchaseItem = $this->getLatestPurchasedItem($appleResponse);

        if (empty($firstPurchasedItem) || empty($latestPurchaseItem)) {
            return null;
        }

        if ($receipt->getPurchaseType() == AppleReceipt::APPLE_PRODUCT_PURCHASE) {
            $firstPurchasedItem = $appleResponse->getPurchases()[0];
        }

        $products = $this->getProductsByAppleStoreId($firstPurchasedItem->getProductId());

        if (empty($products)) {
            return null;
        }

        $membershipIncludeFreePack = count($products) > 1;

        foreach ($products as $product) {
            if ($product->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION) {
                // if a subscription with this external id already exists, just update it
                // the subscription external ID should always be set to the first purchase item web order line item ID
                $subscription = $this->subscriptionRepository->getByExternalAppStoreId(
                    $firstPurchasedItem->getWebOrderLineItemId()
                );

                if (empty($subscription)) {
                    $subscription = new Subscription();
                    $subscription->setCreatedAt(Carbon::now());
                    $subscription->setTotalCyclesPaid(1);
                    $subscription->setUser($user);
                    $subscription->setStopped(false);
                    $subscription->setRenewalAttempt(0);
                }

                $subscription->setBrand(config('ecommerce.brand'));
                $subscription->setType(Subscription::TYPE_APPLE_SUBSCRIPTION);
                $subscription->setProduct($product);

                if (!empty($appleResponse->getPendingRenewalInfo()[0])) {
                    $subscription->setIsActive($appleResponse->getPendingRenewalInfo()[0]->getAutoRenewStatus());
                    $subscription->setCanceledOn(
                        !empty($latestPurchaseItem->getCancellationDate()) ?
                            $latestPurchaseItem->getCancellationDate()
                                ->copy() : null
                    );

                    if (!empty($subscription->getCanceledOn())) {
                        $subscription->setCancellationReason(
                            $latestPurchaseItem->getRawResponse()['cancellation_reason']
                        );
                    } else {
                        $subscription->setCancellationReason(
                            self::RENEWAL_EXPIRATION_REASON[$appleResponse->getPendingRenewalInfo(
                            )[0]->getExpirationIntent()] ?? ''
                        );
                    }
                } else {
                    $subscription->setCanceledOn(null);
                    $subscription->setIsActive($latestPurchaseItem->getExpiresDate() > Carbon::now());
                }

                $subscription->setStartDate($firstPurchasedItem->getPurchaseDate());
                $subscription->setPaidUntil(
                    $latestPurchaseItem->getExpiresDate()
                        ->copy()
                );
                $subscription->setAppleExpirationDate(
                    $latestPurchaseItem->getExpiresDate()
                        ->copy()
                );

                $subscription->setTotalPrice($product->getPrice());
                $subscription->setTax(0);
                $subscription->setCurrency('USD');

                $subscription->setIntervalType($product->getSubscriptionIntervalType());
                $subscription->setIntervalCount($product->getSubscriptionIntervalCount());

                $subscription->setTotalCyclesPaid(0);
                $subscription->setTotalCyclesDue(null);

                // external app store id should always be the first purchase item web order item id
                $subscription->setExternalAppStoreId($firstPurchasedItem->getWebOrderLineItemId());
                $subscription->setAppleReceipt($receipt);

                $subscription->setCreatedAt(Carbon::now());

                $receipt->setSubscription($subscription);

                // sync payments
                // 1 payment for every purchase item
                foreach (array_reverse($appleResponse->getLatestReceiptInfo()) as $purchaseItem) {

                    // we dont want to add zero dollar trial payments
                    if ($purchaseItem->isTrialPeriod()) {
                        continue;
                    }

                    $subscription->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1);

                    $existingPayment = $this->paymentRepository->getByExternalIdAndProvider(
                        $purchaseItem->getTransactionId(),
                        Payment::EXTERNAL_PROVIDER_APPLE
                    );

                    if (empty($existingPayment)) {
                        $existingPayment = new Payment();
                        $existingPayment->setCreatedAt(Carbon::now());
                    } else {
                        $existingPayment->setUpdatedAt(Carbon::now());
                    }

                    $existingPayment->setTotalDue($product->getPrice());
                    $existingPayment->setTotalPaid($product->getPrice());

                    // if it has a cancellation date it means the transaction was refunded
                    if (!empty($purchaseItem->getCancellationDate())) {
                        $existingPayment->setTotalRefunded($product->getPrice());
                    } else {
                        $existingPayment->setTotalRefunded(0);
                    }

                    $existingPayment->setConversionRate(1);

                    $existingPayment->setType(Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL);
                    $existingPayment->setExternalId($purchaseItem->getTransactionId());
                    $existingPayment->setExternalProvider(Payment::EXTERNAL_PROVIDER_APPLE);

                    $existingPayment->setGatewayName(config('ecommerce.brand'));
                    $existingPayment->setStatus(Payment::STATUS_PAID);
                    $existingPayment->setCurrency('USD');
                    $existingPayment->setCreatedAt(
                        !empty($purchaseItem->getPurchaseDate()) ? $purchaseItem->getPurchaseDate() : Carbon::now()
                    );

                    $this->entityManager->persist($subscription);
                    $this->entityManager->persist($existingPayment);
                    $this->entityManager->flush();

                    // save the payment to the subscription
                    $subscriptionPayment =
                        $this->subscriptionPaymentRepository->getByPayment($existingPayment)[0] ?? null;

                    if (empty($subscriptionPayment)) {
                        $subscriptionPayment = new SubscriptionPayment();
                    }

                    $subscriptionPayment->setSubscription($subscription);
                    $subscriptionPayment->setPayment($existingPayment);

                    $this->entityManager->persist($subscriptionPayment);
                    $this->entityManager->flush();

                    $subscription->setLatestPayment($existingPayment);
                }

                $this->entityManager->persist($receipt);
                $this->entityManager->persist($subscription);

                $this->entityManager->flush();

                event(new MobileOrderEvent(null, null, $subscription));

            } else {
                if ($product->getType() == Product::TYPE_DIGITAL_ONE_TIME) {

                    //pack puchase
                    $order = new Order();
                    $order->setUser($user);
                    $order->setTotalPaid($membershipIncludeFreePack ? 0 : $product->getPrice());
                    $order->setTotalDue($membershipIncludeFreePack ? 0 : $product->getPrice());

                    $order->setTaxesDue(0);
                    $order->setShippingDue(0);
                    $order->setBrand(config('ecommerce.brand'));

                    $this->entityManager->persist($order);

                    $orderItem = new OrderItem();
                    $orderItem->setOrder($order);
                    $orderItem->setProduct($product);
                    $orderItem->setQuantity($firstPurchasedItem->getQuantity());
                    $orderItem->setInitialPrice($product->getPrice());
                    $orderItem->setTotalDiscounted(0);
                    $orderItem->setFinalPrice($product->getPrice());

                    $this->entityManager->persist($orderItem);

                    $order->addOrderItem($orderItem);
                    $this->entityManager->persist($order);

                    if (!$membershipIncludeFreePack) {
                        $payment = new Payment();
                        $payment->setCreatedAt(Carbon::now());

                        $payment->setTotalDue($product->getPrice());
                        $payment->setTotalPaid($product->getPrice());

                        $payment->setConversionRate(1);

                        $payment->setType(Payment::TYPE_INITIAL_ORDER);
                        $payment->setExternalId($firstPurchasedItem->getTransactionId());
                        $payment->setExternalProvider(Payment::EXTERNAL_PROVIDER_APPLE);

                        $payment->setGatewayName(config('ecommerce.brand'));
                        $payment->setStatus(Payment::STATUS_PAID);
                        $payment->setCurrency('USD');

                        $this->entityManager->persist($payment);

                        $orderPayment = new OrderPayment();

                        $orderPayment->setOrder($order);
                        $orderPayment->setPayment($payment);
                        $orderPayment->setCreatedAt(Carbon::now());

                        $this->entityManager->persist($orderPayment);
                    }

                    $this->entityManager->flush();

                    event(new MobileOrderEvent($order, null, null));
                }
            }
        }
    }

    /**
     * @param string $appleStoreId
     * @return array|null
     * @throws \Doctrine\ORM\ORMException
     */
    public function getProductsByAppleStoreId(string $appleStoreId)
    : ?array {
        $productsMap = config('ecommerce.apple_store_products_map');

        if (isset($productsMap[$appleStoreId])) {
            return $this->productRepository->bySkus((array)$productsMap[$appleStoreId]);
        }

        return [];
    }
}
