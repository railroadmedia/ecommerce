<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use ReceiptValidator\iTunes\PurchaseItem;
use ReceiptValidator\iTunes\ResponseInterface;
use Throwable;

/**
 * Class AppleStoreKitService
 *
 * @package Railroad\Ecommerce\Services
 */
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

    /**
     * @var AppleReceiptRepository
     */
    private $appleReceiptRepository;

    /**
     * @var OrderPaymentRepository
     */
    private $orderPaymentRepository;

    const RENEWAL_EXPIRATION_REASON = [
        1 => 'Apple in-app: Customer canceled their subscription.',
        2 => 'Apple in-app: Billing error.',
        3 => 'Apple in-app: Customer did not agree to a recent price modification.',
        4 => 'Apple in-app: Product was not available for purchase at the time of renewal.',
        5 => 'Apple in-app: Unknown error.',
    ];

    const SHOULD_SIGNUP = -1;
    const SHOULD_RENEW = 0;
    const SHOULD_LOGIN = 1;

    /**
     * AppleStoreKitService constructor.
     *
     * @param AppleStoreKitGateway $appleStoreKitGateway
     * @param EcommerceEntityManager $entityManager
     * @param ProductRepository $productRepository
     * @param UserProductService $userProductService
     * @param UserProviderInterface $userProvider
     * @param SubscriptionRepository $subscriptionRepository
     * @param PaymentRepository $paymentRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param AppleReceiptRepository $appleReceiptRepository
     * @param OrderPaymentRepository $orderPaymentRepository
     */
    public function __construct(
        AppleStoreKitGateway $appleStoreKitGateway,
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository,
        UserProductService $userProductService,
        UserProviderInterface $userProvider,
        SubscriptionRepository $subscriptionRepository,
        PaymentRepository $paymentRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        AppleReceiptRepository $appleReceiptRepository,
        OrderPaymentRepository $orderPaymentRepository
    ) {
        $this->appleStoreKitGateway = $appleStoreKitGateway;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->userProductService = $userProductService;
        $this->userProvider = $userProvider;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->appleReceiptRepository = $appleReceiptRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
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
        $appleResponse = null;

        try {
            $appleResponse = $this->appleStoreKitGateway->getResponse($receipt->getReceipt());

            $currentPurchasedItem = $this->getLatestPurchasedItem($appleResponse);

            if ($receipt->getPurchaseType() == AppleReceipt::APPLE_SUBSCRIPTION_PURCHASE) {
                $receipt->setValid($currentPurchasedItem->getExpiresDate() > Carbon::now());
            } else {
                $receipt->setValid(true);
            }

            $transactionId = $currentPurchasedItem->getOriginalTransactionId();

            $receipt->setTransactionId($transactionId);
            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

        } catch (Throwable $exception) {

            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());
            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

            if ($exception instanceof ReceiptValidationException) {
                $receipt->setRawReceiptResponse(base64_encode(serialize($exception->getAppleResponse())));
            }

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();

            throw $exception;
        }

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        $user = $this->userProvider->getUserByEmail($receipt->getEmail());

        if (!$user) {
            $user = $this->userProvider->createUser($receipt->getEmail(), $receipt->getPassword());
        }

        auth()->loginUsingId($user->getId());

        $this->syncPurchasedItems($appleResponse, $receipt, $user);

        $this->entityManager->flush();

        return $user;
    }

    /**
     * @param AppleReceipt $receipt
     *
     * @throws GuzzleException
     * @throws ReceiptValidationException
     * @throws Throwable
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function processNotification(AppleReceipt $receipt)
    {
        $appleResponse = null;

        try {
            $appleResponse = $this->appleStoreKitGateway->getResponse($receipt->getReceipt());

            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();

            $currentPurchasedItem = $this->getLatestPurchasedItem($appleResponse);

            $transactionId = $currentPurchasedItem->getTransactionId();

            $receipt->setTransactionId($transactionId);
            $receipt->setValid($currentPurchasedItem->getExpiresDate() > Carbon::now());

        } catch (Throwable $exception) {

            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());
            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

            if ($exception instanceof ReceiptValidationException) {
                $receipt->setRawReceiptResponse(base64_encode(serialize($exception->getAppleResponse())));
            }

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();

            throw $exception;
        }

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        $subscription = $this->syncPurchasedItems($appleResponse, $receipt);

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

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        $appleResponse = null;

        try {
            $appleResponse = $this->appleStoreKitGateway->getResponse($receipt->getReceipt());

            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();

            $purchasedItem = $this->getLatestPurchasedItem($appleResponse);

            $receipt->setValid($purchasedItem->getExpiresDate() > Carbon::now());
            $receipt->setValidationError(null);
            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

        } catch (Throwable $exception) {

            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());
            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

            if ($exception instanceof ReceiptValidationException) {
                $receipt->setRawReceiptResponse(base64_encode(serialize($exception->getAppleResponse())));
            }

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();
        }

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        $subscription = $this->syncPurchasedItems($appleResponse, $receipt);

        if (!empty($receipt)) {
            $this->userProductService->updateSubscriptionProductsApp($subscription);
        } else {
            error_log(
                'Error updating access for an Apple IOS subscription. Could not find or sync subscription for receipt (DB Receipt): ' .
                var_export($receipt, true)
            );
            error_log(
                'Error updating access for Apple IOS subscription. Could not find or sync subscription for receipt (AppleResponse): ' .
                var_export($appleResponse, true)
            );
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
     * @param $productId
     * @return PurchaseItem|null
     */
    public function getFirstPurchasedItemForProductId(ResponseInterface $validationResponse, $productId)
    : ?PurchaseItem {
        if (is_array($validationResponse->getLatestReceiptInfo()) &&
            !empty($validationResponse->getLatestReceiptInfo())) {

            $purchasedItems = array_reverse($validationResponse->getLatestReceiptInfo());

            foreach ($purchasedItems as $purchasedItem) {
                if ($purchasedItem->getProductId() == $productId) {
                    return $purchasedItem;
                }
            }
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
     * @param bool $syncAll
     * @return Subscription|null
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function syncPurchasedItems(
        ResponseInterface $appleResponse,
        AppleReceipt $receipt,
        ?User $user = null,
        $syncAll = false
    ) {

        $latestPurchaseItem = $this->getLatestPurchasedItem($appleResponse);
        $subscription = null;
        $allActivePurchasedItems = [];

        if (empty($latestPurchaseItem)) {
            return null;
        }

        if ($syncAll) {
            $allPurchasedItems = $appleResponse->getLatestReceiptInfo();
            foreach ($allPurchasedItems as $item) {
                if ($item->getExpiresDate() > Carbon::now() || is_null($item->getExpiresDate())) {
                    $allActivePurchasedItems[] = $item;
                }
            }
        } else {
            $allActivePurchasedItems = [$latestPurchaseItem];
        }

        foreach ($allActivePurchasedItems as $latestPurchaseItem) {

            $firstPurchaseItem =
                $this->getFirstPurchasedItemForProductId($appleResponse, $latestPurchaseItem->getProductId());

            $products = $this->getProductsByAppleStoreId($latestPurchaseItem->getProductId());

            if (empty($products)) {
                continue;
            }

            $membershipIncludeFreePack = count($products) > 1;

            foreach ($products as $product) {

                if ($product->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION) {

                    $subscription = $this->syncSubscription(
                        $appleResponse,
                        $receipt,
                        $user,
                        $firstPurchaseItem,
                        $product,
                        $latestPurchaseItem
                    );

                } elseif ($product->getType() == Product::TYPE_DIGITAL_ONE_TIME) {

                    $this->syncPack($user, $latestPurchaseItem, $membershipIncludeFreePack, $product);
                }
            }
        }

        return $subscription;
    }

    /**
     * @param string $appleStoreId
     * @return array|null
     * @throws ORMException
     */
    public function getProductsByAppleStoreId(string $appleStoreId)
    : ?array {
        $productsMap = config('ecommerce.apple_store_products_map');

        if (isset($productsMap[$appleStoreId])) {
            return $this->productRepository->bySkus((array)$productsMap[$appleStoreId]);
        }

        return [];
    }

    /**
     * @param $receipt
     * @return array
     * @throws GuzzleException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function restoreAndSyncPurchasedItems($receipt)
    {
        $shouldCreateAccount = false;
        $shouldLogin = false;
        $receiptUser = null;
        $existsPurchases = false;

        $appleResponse = $this->appleStoreKitGateway->getResponse($receipt);

        $latestPurchaseItem = $this->getLatestPurchasedItem($appleResponse);
        $allPurchasedItems = $appleResponse->getLatestReceiptInfo();
        foreach ($allPurchasedItems as $purchaseItem) {
            if (array_key_exists(
                $purchaseItem->getProductId(),
                config('iap.drumeo-app-apple-store.productsMapping')
            )) {
                $latestPurchaseItem = $purchaseItem;
                break;
            }
        }

        error_log(var_export($latestPurchaseItem, true));

        if (empty($latestPurchaseItem)) {
            return null;
        }

        $originalTransactionId = $latestPurchaseItem->getOriginalTransactionId();

        error_log('Restore Apple receipt with original transaction id: ' . $originalTransactionId);

        ///check if receipt exist in db
        $appleReceipt =
            $this->appleReceiptRepository->findOneBy(['transactionId' => $originalTransactionId], ['id' => 'desc']);

        if (!$appleReceipt) {

            foreach ($appleResponse->getLatestReceiptInfo() as $purchaseItem) {

                    $existsPurchases = true;

                    //check if purchases product is membership
                    if (array_key_exists(
                        $purchaseItem->getProductId(),
                        config('iap.drumeo-app-apple-store.productsMapping')
                    )) {
                        $shouldCreateAccount = true;
                    } elseif (auth()->id()) {
                        $user = $this->userProvider->getUserById(auth()->id());

                        $appleReceipt = new AppleReceipt();
                        $appleReceipt->setReceipt($receipt);
                        $appleReceipt->setEmail($user->getEmail());
                        $appleReceipt->setBrand(config('ecommerce.brand'));
                        $appleReceipt->setRequestType(AppleReceipt::MOBILE_APP_REQUEST_TYPE);

                        $receiptUser = $this->processReceipt($appleReceipt);
                    }
            }

            if (!$existsPurchases) {
                return null;
            }

        } else {

            error_log('Exists apple receipts with ID:' . $appleReceipt->getId());

            $receiptUser = $this->userProvider->getUserByEmail($appleReceipt->getEmail());

            //sync
            $this->syncPurchasedItems($appleResponse, $appleReceipt, $receiptUser, true);

            if (!auth()->id() || auth()->id() != $receiptUser->getId()) {

                $shouldLogin = true;

            }
        }

        return [
            'shouldCreateAccount' => $shouldCreateAccount,
            'shouldLogin' => $shouldLogin,
            'receiptUser' => $receiptUser,
        ];
    }

    /**
     * @param ResponseInterface $appleResponse
     * @param AppleReceipt $receipt
     * @param User|null $user
     * @param PurchaseItem|null $firstPurchaseItem
     * @param $product
     * @param PurchaseItem|null $latestPurchaseItem
     * @return Subscription|null
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function syncSubscription(
        ResponseInterface $appleResponse,
        AppleReceipt $receipt,
        ?User $user,
        ?PurchaseItem $firstPurchaseItem,
        $product,
        ?PurchaseItem $latestPurchaseItem
    ) {

        // if a subscription with this external id already exists, just update it
        // the subscription external ID should always be set to the first purchase item web order line item ID
        $subscription = $this->subscriptionRepository->getByExternalAppStoreId(
            $firstPurchaseItem->getWebOrderLineItemId()
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
                    self::RENEWAL_EXPIRATION_REASON[$appleResponse->getPendingRenewalInfo()[0]->getExpirationIntent()]
                    ??
                    ''
                );
            }
        } else {
            $subscription->setCanceledOn(null);
            $subscription->setIsActive($latestPurchaseItem->getExpiresDate() > Carbon::now());
        }

        $subscription->setStartDate($firstPurchaseItem->getPurchaseDate());

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
        $subscription->setExternalAppStoreId($firstPurchaseItem->getWebOrderLineItemId());
        $subscription->setAppleReceipt($receipt);

        $subscription->setCreatedAt(Carbon::now());

        $receipt->setSubscription($subscription);

        // sync payments
        // 1 payment for every purchase item
        foreach (array_reverse($appleResponse->getLatestReceiptInfo()) as $purchaseItem) {
            // only purchase items with the same original transaction is should be take into consideration;
            // same original transaction id means that are renewal for the same subscription
            if ($purchaseItem->getOriginalTransactionId() == $latestPurchaseItem->getOriginalTransactionId() &&
                ($purchaseItem->getProductId() == $latestPurchaseItem->getProductId())) {

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
                    $existingPayment->setAttemptNumber(0);
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
                $subscriptionPayment = $this->subscriptionPaymentRepository->getByPayment($existingPayment)[0] ?? null;

                if (empty($subscriptionPayment)) {
                    $subscriptionPayment = new SubscriptionPayment();
                }

                $subscriptionPayment->setSubscription($subscription);
                $subscriptionPayment->setPayment($existingPayment);

                $this->entityManager->persist($subscriptionPayment);
                $this->entityManager->flush();

                $subscription->setLatestPayment($existingPayment);
            }
        }

        $this->entityManager->persist($receipt);
        $this->entityManager->persist($subscription);

        $this->entityManager->flush();

        event(new MobileOrderEvent(null, null, $subscription));
        return $subscription;
    }

    /**
     * @param User|null $user
     * @param PurchaseItem|null $latestPurchaseItem
     * @param bool $membershipIncludeFreePack
     * @param $product
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function syncPack(?User $user, ?PurchaseItem $latestPurchaseItem, bool $membershipIncludeFreePack, $product)
    : void {
        //pack purchase
        $payment = $this->paymentRepository->getByExternalIdAndProvider(
            $latestPurchaseItem->getTransactionId(),
            Payment::EXTERNAL_PROVIDER_APPLE
        );

        if (!$membershipIncludeFreePack) {
            if (!$payment) {
                $payment = new Payment();
                $payment->setCreatedAt(Carbon::now());

                $payment->setTotalDue($product->getPrice());
                $payment->setTotalPaid($product->getPrice());
                $payment->setAttemptNumber(0);

                $payment->setConversionRate(1);

                $payment->setType(Payment::TYPE_INITIAL_ORDER);
                $payment->setExternalId($latestPurchaseItem->getTransactionId());
                $payment->setExternalProvider(Payment::EXTERNAL_PROVIDER_APPLE);

                $payment->setGatewayName(config('ecommerce.brand'));
                $payment->setStatus(Payment::STATUS_PAID);
                $payment->setCurrency('USD');

                $this->entityManager->persist($payment);
                $this->entityManager->flush();
            }

            $orderPayment = $this->orderPaymentRepository->getByPayment($payment);

            if (!$orderPayment) {
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
                $orderItem->setQuantity($latestPurchaseItem->getQuantity());
                $orderItem->setInitialPrice($product->getPrice());
                $orderItem->setTotalDiscounted(0);
                $orderItem->setFinalPrice($product->getPrice());

                $this->entityManager->persist($orderItem);

                $order->addOrderItem($orderItem);
                $this->entityManager->persist($order);

                $orderPayment = new OrderPayment();

                $orderPayment->setOrder($order);
                $orderPayment->setPayment($payment);
                $orderPayment->setCreatedAt(Carbon::now());

                $this->entityManager->persist($orderPayment);

                $this->entityManager->flush();
            } else {
                $order = $orderPayment[0]->getOrder();
            }

            event(new MobileOrderEvent($order, null, null));
        }
    }

    /**
     * @param $receipt
     * @return int
     * @throws Throwable
     */
    public function checkSignup($receipt)
    {
        if (!$receipt) {
            return self::SHOULD_SIGNUP;
        }

        try {

            $appleResponse = $this->appleStoreKitGateway->getResponse($receipt);

            $allPurchasedItems = $appleResponse->getLatestReceiptInfo();
            $latestPurchaseItem = null;

            foreach ($allPurchasedItems as $purchaseItem) {
                if (array_key_exists(
                    $purchaseItem->getProductId(),
                    config('iap.drumeo-app-apple-store.productsMapping')
                )) {
                    $latestPurchaseItem = $purchaseItem;
                    break;
                }
            }

            if (is_null($latestPurchaseItem)) {
                return self::SHOULD_SIGNUP;
            }

            if (($latestPurchaseItem->getExpiresDate() > Carbon::now()) &&
                (is_null($latestPurchaseItem->getCancellationDate()))) {
                return self::SHOULD_LOGIN;
            } else {
                return self::SHOULD_RENEW;
            }

        } catch (Throwable $exception) {

            throw $exception;
        }
    }

}