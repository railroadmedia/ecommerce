<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
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
use Railroad\Ecommerce\ExternalHelpers\CurrencyConversion;
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

    /**
     * @var CurrencyConversion
     */
    private $currencyConvertionHelper;

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
        OrderPaymentRepository $orderPaymentRepository,
        CurrencyConversion $currencyConversion
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
        $this->currencyConvertionHelper = $currencyConversion;
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
    public function processReceipt(AppleReceipt $receipt): User
    {
        $appleResponse = null;

        try {
            $this->entityManager->persist($receipt);
            $this->entityManager->flush();

            $appleResponse = $this->appleStoreKitGateway->getResponse($receipt->getReceipt());

            $currentPurchasedItem = $this->getLatestPurchasedItem($appleResponse);

            if (!$currentPurchasedItem && !empty($appleResponse->getPurchases())) {
                $currentPurchasedItem = $appleResponse->getPurchases()[0];
            }

            if ($receipt->getPurchaseType() == AppleReceipt::APPLE_SUBSCRIPTION_PURCHASE) {
                $receipt->setValid($currentPurchasedItem->getExpiresDate() > Carbon::now());
            } else {
                $receipt->setValid(true);
                $receipt->setValidationError('');
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
        $receiptId = null;
        try {
            $appleResponse = $this->appleStoreKitGateway->getResponse($receipt->getReceipt());

            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();
            $receiptId = $receipt->getId();

            Log::debug("Processing Apple Receipt $receiptId");
            $currentPurchasedItem = $this->getLatestPurchasedItem($appleResponse);

            if ($currentPurchasedItem) {
                $transactionId = $currentPurchasedItem->getTransactionId();
                $originalTransactionId = $currentPurchasedItem->getOriginalTransactionId();

                $receipt->setTransactionId($transactionId);
                $receipt->setValid($currentPurchasedItem->getExpiresDate() > Carbon::now());

                $oldReceipts =
                    $this->appleReceiptRepository->createQueryBuilder('ap')
                        ->where('ap.transactionId  = :transactionId')
                        ->andWhere('ap.email is not null')
                        ->setParameter('transactionId', $originalTransactionId)
                        ->getQuery()
                        ->getResult();

                if (!empty($oldReceipts)) {
                    $receipt->setEmail($oldReceipts[0]->getEmail());
                    if ($oldReceipts[0]->getLocalPrice()) {
                        $receipt->setLocalPrice($oldReceipts[0]->getLocalPrice());
                    }
                    if ($oldReceipts[0]->getLocalCurrency()) {
                        $receipt->setLocalCurrency($oldReceipts[0]->getLocalCurrency());
                    }
                } else {
                    Log::warning("No receipt exists for original Apple transaction $originalTransactionId");
                    Log::debug("Failed Processing Apple Receipt $receiptId");
                    return;
                }
            } else {
                $receipt->setValid(false);
                $receipt->setValidationError('Missing purchased item; latest_receipt_info empty array');
            }
        } catch (Throwable $exception) {
            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());
            $receipt->setRawReceiptResponse(base64_encode(serialize($appleResponse)));

            if ($exception instanceof ReceiptValidationException) {
                $receipt->setRawReceiptResponse(base64_encode(serialize($exception->getAppleResponse())));
            }

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();
            Log::debug("Failed Processing Apple Receipt $receiptId");
            throw $exception;
        }

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        $user = $this->userProvider->getUserByEmail($receipt->getEmail());

        $subscription = $this->syncPurchasedItems($appleResponse, $receipt, $user);
        Log::debug("Apple Receipt Synced $receiptId");

        if (!is_null($subscription)) {
            if ($receipt->getNotificationType() == AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE) {
                Log::debug("Apple Receipt Successfully Renewed $receiptId");
                event(
                    new MobileSubscriptionRenewed(
                        $subscription, $subscription->getLatestPayment(), MobileSubscriptionRenewed::ACTOR_SYSTEM
                    )
                );
            } elseif (!empty($subscription->getCanceledOn())) {
                Log::debug("Apple Receipt Successfully Canceled $receiptId");
                event(new MobileSubscriptionCanceled($subscription, MobileSubscriptionRenewed::ACTOR_SYSTEM));
            }

            $this->userProductService->updateSubscriptionProductsApp($subscription);
        }
        $this->entityManager->flush();
        Log::debug("Apple Receipt Success $receiptId");
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

        if (!$receipt) {
            return;
        }

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        $appleResponse = null;

        try {
            $appleResponse = $this->appleStoreKitGateway->getResponse($receipt->getReceipt());

            if ($appleResponse->getResultCode() == 21004) {
                $app = $receipt->getBrand();
                if (config('ecommerce.payment_gateways.apple_store_kit.' . $app . '.shared_secret')) {
                    config()->set(
                        'ecommerce.payment_gateways.apple_store_kit.shared_secret',
                        config('ecommerce.payment_gateways.apple_store_kit.' . $app . '.shared_secret')
                    );

                    $appleResponse = $this->appleStoreKitGateway->getResponse($receipt->getReceipt());
                }
            }

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

        if ($appleResponse) {
            $subscription = $this->syncPurchasedItems($appleResponse, $receipt, subscription: $subscription);

            if (!empty($subscription)) {
                $this->userProductService->updateSubscriptionProductsApp($subscription);
            } else {
//                error_log(
//                    'Error updating access for an Apple IOS subscription. Could not find or sync subscription for receipt (DB Receipt): ' .
//                    print_r($receipt->getId(), true)
//                );
//                error_log(
//                    'Error updating access for Apple IOS subscription. Could not find or sync subscription for receipt (AppleResponse): ' .
//                    print_r($appleResponse, true)
//                );
            }
        }
    }

    /**
     * @param ResponseInterface $validationResponse
     *
     * @return PurchaseItem|null
     */
    public function getFirstPurchasedItem(ResponseInterface $validationResponse): ?PurchaseItem
    {
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
    public function getFirstPurchasedItemForProductId(ResponseInterface $validationResponse, $productId): ?PurchaseItem
    {
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
    public function getLatestPurchasedItem(ResponseInterface $validationResponse): ?PurchaseItem
    {
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
        $syncAll = false,
        ?Subscription $subscription = null,
    ) {
        $latestPurchaseItem = $this->getLatestPurchasedItem($appleResponse);
        $allPurchasedItems = $appleResponse->getLatestReceiptInfo();
        $allActivePurchasedItems = [];

        if (!$latestPurchaseItem && !empty($appleResponse->getPurchases())) {
            $latestPurchaseItem = $appleResponse->getPurchases()[0];
            $allPurchasedItems = $appleResponse->getPurchases();
        }

        if (empty($latestPurchaseItem)) {
            return null;
        }

        if ($syncAll) {
            foreach ($allPurchasedItems as $item) {
                if ($item->getExpiresDate() >
                    Carbon::now()
                        ->subDays(
                            config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only', 1)
                        ) || is_null($item->getExpiresDate())) {
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
                        $latestPurchaseItem,
                        subscription: $subscription,
                    );
                } elseif ($product->getType() == Product::TYPE_DIGITAL_ONE_TIME) {
                    $this->syncPack($user, $latestPurchaseItem, $membershipIncludeFreePack, $product, $receipt);
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
    public function getProductsByAppleStoreId(string $appleStoreId): ?array
    {
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

        if (!$latestPurchaseItem && !empty($appleResponse->getPurchases())) {
            $latestPurchaseItem = $appleResponse->getPurchases()[0];
            $allPurchasedItems = $appleResponse->getPurchases();
        }

        foreach ($allPurchasedItems as $purchaseItem) {
            if (array_key_exists(
                $purchaseItem->getProductId(),
                config('ecommerce.apple_store_products_map', [])
            )) {
                $latestPurchaseItem = $purchaseItem;
                break;
            }
        }

//        error_log(var_export($latestPurchaseItem, true));

        if (empty($latestPurchaseItem)) {
            error_log(
                'restoreAndSyncPurchasedItemsMissingLatestPurchaseItemOnReceipt  appleResponse ' . var_export(
                    $appleResponse,
                    true
                )
            );
            error_log(
                'restoreAndSyncPurchasedItemsMissingLatestPurchaseItemOnReceipt  allPurchasedItems ' . var_export(
                    $allPurchasedItems,
                    true
                )
            );
            return null;
        }

        $originalTransactionId = $latestPurchaseItem->getOriginalTransactionId();

        error_log('restoreAndSyncApple receiptWithOriginalTransactionId: ' . $originalTransactionId);

        $appleReceipt = null;

        ///check if receipt exist in db
        $appleReceipts =
            $this->appleReceiptRepository->createQueryBuilder('ap')
                ->where('ap.transactionId = :transactionId')
                ->orWhere('ap.receipt = :receipt')
                ->setParameters(
                    [
                        'transactionId' => $originalTransactionId,
                        'receipt' => $receipt,
                    ]
                )
                ->orderBy('ap.id', 'desc')
                ->getQuery()
                ->getResult();

        if (!empty($appleReceipts)) {
            $appleReceipt = \Arr::first($appleReceipts);
        }

        error_log('restoreAndSyncPurchasedItems  appleReceiptFromDB ' . var_export($appleReceipt, true));

        if (!$appleReceipt) {
            foreach ($allPurchasedItems as $purchaseItem) {
                //check if purchases product is membership
                if (array_key_exists(
                    $purchaseItem->getProductId(),
                    config('ecommerce.apple_store_products_map', [])
                )) {
                    $shouldCreateAccount = true;
                    $existsPurchases = true;
                } elseif ($this->userProvider->getCurrentUserId()) {
                    $existsPurchases = true;
                    $user = $this->userProvider->getCurrentUser();

                    $appleReceipt = new AppleReceipt();
                    $appleReceipt->setReceipt($receipt);
                    $appleReceipt->setEmail($user->getEmail());
                    $appleReceipt->setBrand(config('ecommerce.brand'));
                    $appleReceipt->setRequestType(AppleReceipt::MOBILE_APP_REQUEST_TYPE);

                    $receiptUser = $this->processReceipt($appleReceipt);
                } else {
                    error_log(
                        'restoreAndSyncPurchasedItems  notExistsReceiptInDbAndCurrentUserAndProductFromReceiptNotMembership productId ' . var_export(
                            $purchaseItem->getProductId(),
                            true
                        )
                    );
                }
            }

            if (!$existsPurchases) {
                error_log(
                    'restoreAndSyncPurchasedItems  notExistsPurchasesAndNotExistsReceipt allPurchasedItemsFromReceipt ' . var_export(
                        $allPurchasedItems,
                        true
                    )
                );
                return null;
            }
        } else {
            if (!$appleReceipt->getTransactionId()) {
                $appleReceipt->setTransactionId($originalTransactionId);
                $this->entityManager->persist($appleReceipt);
            }
//            error_log(
//                'Exists apple receipts with ID:' .
//                $appleReceipt->getId() .
//                '    transaction id:' .
//                $appleReceipt->getTransactionId()
//            );

            if (!$appleReceipt->getEmail()) {
                $shouldCreateAccount = true;
            } else {
                $receiptUser = $this->userProvider->getUserByEmail($appleReceipt->getEmail());

                //sync
                $this->syncPurchasedItems($appleResponse, $appleReceipt, $receiptUser, true);

                if (!$this->userProvider->getCurrentUserId() || ($this->userProvider->getCurrentUserId(
                        ) != $receiptUser->getId())) {
                    $shouldLogin = true;
                }
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
        ?PurchaseItem $latestPurchaseItem,
        ?Subscription $subscription = null
    ) {
        // if a subscription with this external id already exists, just update it
        // the subscription external ID should always be set to the first purchase item web order line item ID
        if (!$subscription) {
            $subscription = $this->subscriptionRepository->getByExternalAppStoreId(
                $firstPurchaseItem->getWebOrderLineItemId()
            );
        }

        if (empty($subscription)) {
            $subscription = new Subscription();
            $subscription->setCreatedAt(Carbon::now());
            $subscription->setTotalCyclesPaid(1);
            $subscription->setStopped(false);
            $subscription->setRenewalAttempt(0);

            if ($receipt->getLocalCurrency() &&
                $receipt->getLocalCurrency() != config('ecommerce.default_currency') &&
                in_array($receipt->getLocalCurrency(), config('ecommerce.allowable_currencies'))) {
                try {
                    $totalPaidUsd = $this->currencyConvertionHelper->convert(
                        $receipt->getLocalPrice(),
                        $receipt->getLocalCurrency(),
                        config('ecommerce.default_currency')
                    );
                    if ($totalPaidUsd && $totalPaidUsd <= ($product->getPrice() + 40)) {
                        $subscription->setTotalPrice($totalPaidUsd);
                    } else {
                        $subscription->setTotalPrice($product->getPrice());
                        error_log(
                            'Apple purchase(id=' . $receipt->getId() . '): user currency=' .
                            $receipt->getLocalCurrency() .
                            ' user local price=' .
                            $receipt->getLocalPrice() .
                            ' converted price=' .
                            $totalPaidUsd .
                            ' is greater with more the 40 USD that the product price. Store the product price=' .
                            $product->getPrice() .
                            ' in DB.'
                        );
                    }
                } catch (Exception $e) {
                    $subscription->setTotalPrice($product->getPrice());
                }
            } else {
                $subscription->setTotalPrice($product->getPrice());
            }

            $subscription->setBrand(config('ecommerce.brand'));
            $subscription->setType(Subscription::TYPE_APPLE_SUBSCRIPTION);
            $subscription->setProduct($product);

            if ($user) {
                $subscription->setUser($user);
            }
        }

        if (!empty($appleResponse->getPendingRenewalInfo())) {
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

        $subscription->setTax(0);
        $subscription->setCurrency(config('ecommerce.default_currency'));

        $subscription->setIntervalType($product->getSubscriptionIntervalType());
        $subscription->setIntervalCount($product->getSubscriptionIntervalCount());

        $subscription->setTotalCyclesPaid(0);
        $subscription->setTotalCyclesDue(null);

        // external app store id should always be the first purchase item web order item id
        $subscription->setExternalAppStoreId($firstPurchaseItem->getWebOrderLineItemId());
        $subscription->setAppleReceipt($receipt);

        $subscription->setCreatedAt(Carbon::now());

        $receipt->setSubscription($subscription);

        $purchasedItems = $this->getPurchasedItems($appleResponse, $latestPurchaseItem);
        $transactionIds = array_map(function ($item) {
            return $item->getTransactionId();
        }, $purchasedItems);
        $existingPayments = $this->paymentRepository->getByExternalIdsAndProvider(
            $transactionIds,
            Payment::EXTERNAL_PROVIDER_APPLE
        );
        $subscriptionPayments = $this->subscriptionPaymentRepository->getByPayments($existingPayments);

        // sync payments
        // 1 payment for every purchased item
        foreach ($purchasedItems as $purchasedItem) {
            $subscription->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1);
            $existingPayment = $existingPayments[$purchasedItem->getTransactionId()] ?? null;

            if (empty($existingPayment)) {
                $existingPayment = new Payment();
                $existingPayment->setCreatedAt(Carbon::now());
                $existingPayment->setAttemptNumber(0);
            } else {
                $existingPayment->setUpdatedAt(Carbon::now());
            }

            $existingPayment->setTotalDue($subscription->getTotalPrice());
            $existingPayment->setTotalPaid($subscription->getTotalPrice());

            // if it has a cancellation date it means the transaction was refunded
            if (!empty($purchasedItem->getCancellationDate())) {
                $existingPayment->setTotalRefunded($subscription->getTotalPrice());
            } else {
                $existingPayment->setTotalRefunded(0);
            }

            $existingPayment->setConversionRate(1);

            $existingPayment->setType(Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL);
            $existingPayment->setExternalId($purchasedItem->getTransactionId());
            $existingPayment->setExternalProvider(Payment::EXTERNAL_PROVIDER_APPLE);

            $existingPayment->setGatewayName(config('ecommerce.brand'));
            $existingPayment->setStatus(Payment::STATUS_PAID);
            $existingPayment->setCurrency('USD');
            $existingPayment->setCreatedAt(
                !empty($purchasedItem->getPurchaseDate()) ? $purchasedItem->getPurchaseDate() : Carbon::now()
            );

            $this->entityManager->persist($subscription);
            $this->entityManager->persist($existingPayment);
            $this->entityManager->flush();

            // save the payment to the subscription
            $subscriptionPayment = $subscriptionPayments[$existingPayment->getId()] ?? null;

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
        return $subscription;
    }

    /**
     * @param User|null $user
     * @param PurchaseItem|null $latestPurchaseItem
     * @param bool $membershipIncludeFreePack
     * @param $product
     * @param $receipt
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function syncPack(
        ?User $user,
        ?PurchaseItem $latestPurchaseItem,
        bool $membershipIncludeFreePack,
        $product,
        $receipt
    ): void {
        //pack purchase
        $payment = $this->paymentRepository->getByExternalIdAndProvider(
            $latestPurchaseItem->getTransactionId(),
            Payment::EXTERNAL_PROVIDER_APPLE
        );

        if (!$membershipIncludeFreePack) {
            if (!$payment) {
                $payment = new Payment();
                $payment->setCreatedAt(Carbon::now());

                if ($receipt->getLocalCurrency() &&
                    $receipt->getLocalCurrency() != config('ecommerce.default_currency') &&
                    in_array(
                        $receipt->getLocalCurrency(),
                        config('ecommerce.allowable_currencies')
                    )) {
                    $totalPaidUsd = $this->currencyConvertionHelper->convert(
                        $receipt->getLocalPrice(),
                        $receipt->getLocalCurrency(),
                        config('ecommerce.default_currency')
                    );

                    if ($totalPaidUsd && $totalPaidUsd <= ($product->getPrice() + 40)) {
                        $payment->setTotalDue($totalPaidUsd);
                        $payment->setTotalPaid($totalPaidUsd);
                    } else {
                        $payment->setTotalDue($product->getPrice());
                        $payment->setTotalPaid($product->getPrice());

                        error_log(
                            'Apple purchase(id = ' . $receipt->getId() . ') user currency=' .
                            $receipt->getLocalCurrency() .
                            ' user local price=' .
                            $receipt->getLocalPrice() .
                            ' converted price=' .
                            $totalPaidUsd .
                            ' is greater with more the 40 USD that the product price. Store the product price=' .
                            $product->getPrice() .
                            ' in DB.'
                        );
                    }
                } else {
                    $payment->setTotalDue($product->getPrice());
                    $payment->setTotalPaid($product->getPrice());
                }

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
                $order->setTotalPaid($membershipIncludeFreePack ? 0 : $payment->getTotalPaid());
                $order->setTotalDue($membershipIncludeFreePack ? 0 : $payment->getTotalPaid());

                $order->setTaxesDue(0);
                $order->setShippingDue(0);
                $order->setBrand(config('ecommerce.brand'));

                $this->entityManager->persist($order);

                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setProduct($product);
                $orderItem->setQuantity($latestPurchaseItem->getQuantity());
                $orderItem->setInitialPrice($payment->getTotalPaid());
                $orderItem->setTotalDiscounted(0);
                $orderItem->setFinalPrice($payment->getTotalPaid());

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
            error_log('checkSignupMissingReceipt  shouldSignup');
            return self::SHOULD_SIGNUP;
        }

        try {
            $appleResponse = $this->appleStoreKitGateway->getResponse($receipt);

            $allPurchasedItems = $appleResponse->getLatestReceiptInfo();
            $latestPurchaseItem = null;

            foreach ($allPurchasedItems as $purchaseItem) {
                if (array_key_exists(
                    $purchaseItem->getProductId(),
                    config('ecommerce.apple_store_products_map', [])
                )) {
                    $latestPurchaseItem = $purchaseItem;
                    break;
                }
            }

            if (is_null($latestPurchaseItem)) {
                error_log(
                    'checkSignupMissingLatestPurchaseItem  shouldSignup appleResponse ' . var_export(
                        $appleResponse,
                        true
                    )
                );
                error_log(
                    'checkSignupMissingLatestPurchaseItem  shouldSignup allPurchasedItems' . var_export(
                        $allPurchasedItems,
                        true
                    )
                );
                return self::SHOULD_SIGNUP;
            }

            $appleReceipt = null;
            $originalTransactionId = $latestPurchaseItem->getOriginalTransactionId();
            $appleReceipts =
                $this->appleReceiptRepository->createQueryBuilder('ap')
                    ->where('ap.transactionId = :transactionId')
                    ->orWhere('ap.receipt = :receipt')
                    ->setParameters(
                        [
                            'transactionId' => $originalTransactionId,
                            'receipt' => $receipt,
                        ]
                    )
                    ->orderBy('ap.id', 'desc')
                    ->getQuery()
                    ->getResult();

            if (!empty($appleReceipts)) {
                $appleReceipt = \Arr::first($appleReceipts);
            }

            if (($latestPurchaseItem->getExpiresDate() >
                    Carbon::now()
                        ->subDays(
                            config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only', 1)
                        )) && (is_null($latestPurchaseItem->getCancellationDate()))) {
                return ($appleReceipt) ? self::SHOULD_LOGIN : self::SHOULD_SIGNUP;
            } else {
                return ($appleReceipt) ? self::SHOULD_RENEW : self::SHOULD_SIGNUP;
            }
        } catch (Throwable $exception) {
            error_log('checkSignupThrowException ' . var_export($exception->getMessage(), true));
            throw $exception;
        }
    }

    /**
     * @param ResponseInterface $appleResponse
     * @param PurchaseItem $latestPurchaseItem
     * @return array
     */
    private function getPurchasedItems(ResponseInterface $appleResponse, PurchaseItem $latestPurchaseItem): array
    {
        $purchasedItems = [];

        foreach (array_reverse($appleResponse->getLatestReceiptInfo()) as $purchasedItem) {
            // only purchase items with the same original transaction is should be take into consideration;
            // same original transaction id means that are renewal for the same subscription

            if ($purchasedItem->getOriginalTransactionId() == $latestPurchaseItem->getOriginalTransactionId() &&
                ($purchasedItem->getProductId() == $latestPurchaseItem->getProductId())) {
                // we dont want to add zero dollar trial payments
                if ($purchasedItem->isTrialPeriod()) {
                    continue;
                }
                $purchasedItems[] = $purchasedItem;
            }
        }
        return $purchasedItems;
    }

}
