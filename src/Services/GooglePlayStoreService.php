<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Illuminate\Support\Facades\Log;
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
use Railroad\Ecommerce\Events\MobileOrderEvent;
use Railroad\Ecommerce\Events\PaymentEvent;
use Railroad\Ecommerce\Events\MobilePaymentEvent;
use Railroad\Ecommerce\Events\Subscriptions\MobileSubscriptionCanceled;
use Railroad\Ecommerce\Events\Subscriptions\MobileSubscriptionRenewed;
use Railroad\Ecommerce\Exceptions\ReceiptValidationException;
use Railroad\Ecommerce\ExternalHelpers\CurrencyConversion;
use Railroad\Ecommerce\Gateways\GooglePlayStoreGateway;
use Railroad\Ecommerce\Gateways\RevenueCatGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\GoogleReceiptRepository;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use ReceiptValidator\GooglePlay\PurchaseResponse;
use ReceiptValidator\GooglePlay\SubscriptionResponse;
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
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var SubscriptionPaymentRepository
     */
    private $subscriptionPaymentRepository;

    /**
     * @var GoogleReceiptRepository
     */
    private $googleReceiptRepository;

    /**
     * @var OrderPaymentRepository
     */
    private $orderPaymentRepository;

    /**
     * @var CurrencyConversion
     */
    private $currencyConvertionHelper;

    private RevenueCatGateway $revenueCatGateway;

    /**
     * @var array
     */
    public static $cancellationReasonMap = [
        0 => 'User canceled the subscription',
        1 => 'Subscription was canceled by the system, for example because of a billing problem',
        2 => 'Subscription was replaced with a new subscription',
        3 => 'Subscription was canceled by the developer',
    ];

    const SHOULD_SIGNUP = -1;
    const SHOULD_RENEW = 0;
    const SHOULD_LOGIN = 1;

    /**
     * GooglePlayStoreService constructor.
     *
     * @param GooglePlayStoreGateway $googlePlayStoreGateway
     * @param EcommerceEntityManager $entityManager
     * @param ProductRepository $productRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProductService $userProductService
     * @param UserProviderInterface $userProvider
     * @param PaymentRepository $paymentRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param GoogleReceiptRepository $googleReceiptRepository
     * @param OrderPaymentRepository $orderPaymentRepository
     */
    public function __construct(
        GooglePlayStoreGateway $googlePlayStoreGateway,
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService,
        UserProviderInterface $userProvider,
        PaymentRepository $paymentRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        GoogleReceiptRepository $googleReceiptRepository,
        OrderPaymentRepository $orderPaymentRepository,
        CurrencyConversion $currencyConvertionHelper,
        RevenueCatGateway $revenueCatGateway
    ) {
        $this->googlePlayStoreGateway = $googlePlayStoreGateway;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
        $this->userProvider = $userProvider;
        $this->paymentRepository = $paymentRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->googleReceiptRepository = $googleReceiptRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->currencyConvertionHelper = $currencyConvertionHelper;
        $this->revenueCatGateway = $revenueCatGateway;
    }

    /**
     * @param GoogleReceipt $receipt
     *
     * @return User
     *
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function processReceipt(GoogleReceipt $receipt)
    : User {
        $this->entityManager->persist($receipt);

        // save it to the database
        try {

            if ($receipt->getPurchaseType() == GoogleReceipt::GOOGLE_PRODUCT_PURCHASE) {

                $googleResponse = $this->googlePlayStoreGateway->validatePurchase(
                    $receipt->getPackageName(),
                    $receipt->getProductId(),
                    $receipt->getPurchaseToken()
                );

            } else {

                $googleResponse = $this->googlePlayStoreGateway->getResponse(
                    $receipt->getPackageName(),
                    $receipt->getProductId(),
                    $receipt->getPurchaseToken()
                );

            }

            $receipt->setValid(true);

            $receipt->setOrderId(
                $googleResponse->getRawResponse()
                    ->getOrderId()
            );

            $receipt->setRawReceiptResponse(base64_encode(serialize($googleResponse)));

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();

        } catch (ReceiptValidationException $exception) {

            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());
            $receipt->setRawReceiptResponse(base64_encode(serialize($exception->getGoogleSubscriptionResponse())));

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();

            throw $exception;
        }

        // create the user if necessary, log them in
        $user = $this->userProvider->getUserByEmail($receipt->getEmail());

        if (!$user) {
            $user = $this->userProvider->createUser($receipt->getEmail(), $receipt->getPassword());
        }

        auth()->loginUsingId($user->getId());

        // sync the subscription or product
        $subscription = $this->syncPurchasedItems($receipt, $googleResponse, $user);

        $this->revenueCatGateway->sendRequest($receipt->getPurchaseToken(), $user, $receipt->getProductId(), 'android');

        event(new MobilePaymentEvent(null, null, $subscription));

        return $user;
    }

    /**
     * @param GoogleReceipt $receipt
     * @param Subscription $subscription
     *
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function processNotification(
        GoogleReceipt $receipt,
        Subscription $subscription
    ) {
        $this->entityManager->persist($receipt);

        try {
            $googleResponse = $this->googlePlayStoreGateway->getResponse(
                $receipt->getPackageName(),
                $receipt->getProductId(),
                $receipt->getPurchaseToken()
            );

            $receipt->setValid(true);

            $receipt->setOrderId(
                $googleResponse->getRawResponse()
                    ->getOrderId()
            );

            $receipt->setRawReceiptResponse(base64_encode(serialize($googleResponse)));

        } catch (ReceiptValidationException $exception) {

            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());
            $receipt->setRawReceiptResponse(base64_encode(serialize($exception->getGoogleSubscriptionResponse())));

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();

            throw $exception;
        }

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        $subscription = $this->syncPurchasedItems($receipt, $googleResponse, $subscription->getUser());

        if (!empty($subscription)) {
            if ($receipt->getNotificationType() == GoogleReceipt::GOOGLE_RENEWAL_NOTIFICATION_TYPE) {

                event(
                    new MobileSubscriptionRenewed(
                        $subscription, $subscription->getLatestPayment(), MobileSubscriptionRenewed::ACTOR_SYSTEM
                    )
                );

                event(new MobilePaymentEvent(null, null, $subscription));

            } else {

                event(new MobileSubscriptionCanceled($subscription, MobileSubscriptionRenewed::ACTOR_SYSTEM));
            }

            $this->userProductService->updateSubscriptionProductsApp($subscription);
        }
    }

    /**
     * @param GoogleReceipt $googleReceipt
     * @param SubscriptionResponse|PurchaseResponse $googleSubscriptionResponse
     * @param User $user
     *
     * @throws ORMException
     * @throws ReceiptValidationException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    public function syncPurchasedItems(
        GoogleReceipt $googleReceipt,
        $googleSubscriptionResponse,
        User $user
    ) {
        $userId = $user->getId();
        Log::debug("GooglePlayStoreService:syncPurchasedItems $userId");
        $subscription = null;
        $isTrial =  $googleSubscriptionResponse->getPaymentState() == 2;
        $purchasedProducts = $this->getPurchasedItem($googleReceipt, $isTrial);

        if (empty($purchasedProducts)) {
            throw new ReceiptValidationException('Purchased google in app product not found in config.');
        }

        $membershipIncludeFreePack = count($purchasedProducts) > 1;

        foreach ($purchasedProducts as $purchasedProduct) {
            if ($purchasedProduct->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION) {
                // if a subscription with this external id already exists, just update it
                $subscription =
                    $this->subscriptionRepository->getByExternalAppStoreId($googleReceipt->getPurchaseToken());

                if (empty($subscription)) {
                    $subscription = new Subscription();
                    $subscription->setCreatedAt(Carbon::now());
                    $subscription->setTotalCyclesPaid(1);
                    $subscription->setStopped(false);
                    $subscription->setRenewalAttempt(0);
                }

                $subscription->setBrand(config('ecommerce.brand'));
                $subscription->setType(Subscription::TYPE_GOOGLE_SUBSCRIPTION);
                $subscription->setUser($user);
                $subscription->setProduct($purchasedProduct);

                $subscription->setIsActive($googleSubscriptionResponse->getAutoRenewing());
                $subscription->setStartDate(
                    Carbon::createFromTimestampMs($googleSubscriptionResponse->getStartTimeMillis())
                );
                $subscription->setPaidUntil(
                    Carbon::createFromTimestampMs($googleSubscriptionResponse->getExpiryTimeMillis())
                );

                $subscription->setCanceledOn(null);
                $subscription->setCancellationReason(null);

                if (!empty($googleSubscriptionResponse->getUserCancellationTimeMillis()) ||
                    !empty($googleSubscriptionResponse->getCancelReason())) {

                    $subscription->setCanceledOn(
                        !empty($googleSubscriptionResponse->getUserCancellationTimeMillis()) ?
                            Carbon::createFromTimestampMs(
                                $googleSubscriptionResponse->getUserCancellationTimeMillis()
                            ) : null
                    );
                    $subscription->setCancellationReason(
                        self::$cancellationReasonMap[$googleSubscriptionResponse->getCancelReason()]
                        ??
                        $googleSubscriptionResponse->getCancelReason()
                    );
                }

                if ($googleReceipt->getLocalCurrency() &&
                    $googleReceipt->getLocalCurrency() != config('ecommerce.default_currency') &&
                    in_array($googleReceipt->getLocalCurrency(), config('ecommerce.allowable_currencies'))) {

                    $totalPaidUsd = $this->currencyConvertionHelper->convert(
                        $googleReceipt->getLocalPrice(),
                        $googleReceipt->getLocalCurrency(),
                        config('ecommerce.default_currency')
                    );

                    if ($totalPaidUsd && $totalPaidUsd <= ($purchasedProduct->getPrice() + 40)) {
                        $subscription->setTotalPrice($totalPaidUsd);
                    } else {
                        $subscription->setTotalPrice($purchasedProduct->getPrice());
                        error_log(
                            'Google purchase(id='.$googleReceipt->getId().'): user currency=' .
                            $googleReceipt->getLocalCurrency() .
                            ' user local price=' .
                            $googleReceipt->getLocalPrice() .
                            ' converted price=' .
                            $totalPaidUsd .
                            ' is greater with more the 40 USD that the product price. Store the product price=' .
                            $purchasedProduct->getPrice() .
                            ' in DB.'
                        );
                    }

                } else {
                    $subscription->setTotalPrice($purchasedProduct->getPrice());
                }

                $subscription->setTax(0);
                $subscription->setCurrency(config('ecommerce.default_currency'));

                $subscription->setIntervalType($purchasedProduct->getSubscriptionIntervalType());
                $subscription->setIntervalCount($purchasedProduct->getSubscriptionIntervalCount());
                $subscription->setTotalCyclesDue(null);

                $subscription->setExternalAppStoreId($googleReceipt->getPurchaseToken());
                $subscription->setUpdatedAt(Carbon::now());

                $this->entityManager->persist($subscription);
                $this->entityManager->flush();

                // do the payments
                $responseOrderId =
                    $googleSubscriptionResponse->getRawResponse()
                        ->getOrderId();

                $numberOfPaidOrders = 0;
                $responseOrderIdWithoutIncrement = $responseOrderId;

                if (strpos($responseOrderId, '..') !== false) {
                    $numberOfPaidOrders = substr($responseOrderId, strpos($responseOrderId, '..') + 2) + 1;
                    $responseOrderIdWithoutIncrement = substr($responseOrderId, 0, strpos($responseOrderId, '..'));
                }

                $expirationDate = Carbon::createFromTimestampMs($googleSubscriptionResponse->getExpiryTimeMillis());
                $startDate = Carbon::createFromTimestampMs($googleSubscriptionResponse->getStartTimeMillis());
                $incrementDate = $expirationDate->copy();

                $existingPayment = null;

                for ($i = $numberOfPaidOrders; $i > 0; $i--) {

                    // make payments working back from the expiration date
                    if ($incrementDate > $startDate) {

                        // make payment
                        $existingPayment = $this->paymentRepository->getByExternalIdAndProvider(
                            $responseOrderIdWithoutIncrement . '..' . ($i - 1),
                            Payment::EXTERNAL_PROVIDER_GOOGLE
                        );

                        if (empty($existingPayment)) {
                            $existingPayment = new Payment();
                            $existingPayment->setAttemptNumber(0);
                        } else {
                            $existingPayment->setUpdatedAt(Carbon::now());
                        }

                        $existingPayment->setTotalDue($subscription->getTotalPrice());
                        $existingPayment->setTotalPaid($subscription->getTotalPrice());
                        $existingPayment->setTotalRefunded(0);
                        $existingPayment->setConversionRate(1);
                        $existingPayment->setType(Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL);

                        $existingPayment->setExternalId($responseOrderIdWithoutIncrement . '..' . ($i - 1));
                        $existingPayment->setExternalProvider(Payment::EXTERNAL_PROVIDER_GOOGLE);

                        $existingPayment->setGatewayName(config('ecommerce.brand'));
                        $existingPayment->setStatus(Payment::STATUS_PAID);
                        $existingPayment->setCurrency(config('ecommerce.default_currency'));

                        // increment
                        if ($purchasedProduct->getSubscriptionIntervalType() ==
                            config('ecommerce.interval_type_daily')) {

                            $incrementDate->subDays($purchasedProduct->getSubscriptionIntervalCount());

                        } elseif ($purchasedProduct->getSubscriptionIntervalType() ==
                            config('ecommerce.interval_type_monthly')) {

                            $incrementDate->subMonths($purchasedProduct->getSubscriptionIntervalCount());

                        } elseif ($purchasedProduct->getSubscriptionIntervalType() ==
                            config('ecommerce.interval_type_yearly')) {

                            $incrementDate->subYears($purchasedProduct->getSubscriptionIntervalCount());

                        } else {
                            break;
                        }

                        $existingPayment->setCreatedAt($incrementDate->copy());

                        $this->entityManager->persist($existingPayment);
                        $this->entityManager->flush();

                        // dont duplicate link rows
                        $subscriptionPayment =
                            $this->subscriptionPaymentRepository->getByPayment($existingPayment)[0] ?? null;

                        if (empty($subscriptionPayment)) {
                            $subscriptionPayment = new SubscriptionPayment();
                        }

                        $subscriptionPayment->setSubscription($subscription);
                        $subscriptionPayment->setPayment($existingPayment);

                        $this->entityManager->persist($subscriptionPayment);
                        $this->entityManager->flush();

                        $subscription->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1);

                        if (empty($subscription->getLatestPayment())) {
                            $subscription->setLatestPayment($existingPayment);
                        }
                        event(new PaymentEvent($existingPayment));
                    }
                }

                // If this is the first order and the payment status is paid (not trial), then create a payment for the up
                // front order as well. If the user already used their trial week in the past google charges them
                // the full amount up front.
                if ($numberOfPaidOrders == 0 && $googleSubscriptionResponse->getPaymentState() == 1) {
                    // make payment
                    $existingPayment = $this->paymentRepository->getByExternalIdAndProvider(
                        $responseOrderIdWithoutIncrement,
                        Payment::EXTERNAL_PROVIDER_GOOGLE
                    );

                    if (empty($existingPayment)) {
                        $existingPayment = new Payment();
                        $existingPayment->setAttemptNumber(0);
                    } else {
                        $existingPayment->setUpdatedAt(Carbon::now());
                    }

                    $existingPayment->setTotalDue($subscription->getTotalPrice());
                    $existingPayment->setTotalPaid($subscription->getTotalPrice());
                    $existingPayment->setTotalRefunded(0);
                    $existingPayment->setConversionRate(1);
                    $existingPayment->setType(Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL);

                    $existingPayment->setExternalId($responseOrderIdWithoutIncrement);
                    $existingPayment->setExternalProvider(Payment::EXTERNAL_PROVIDER_GOOGLE);

                    $existingPayment->setGatewayName(config('ecommerce.brand'));
                    $existingPayment->setStatus(Payment::STATUS_PAID);
                    $existingPayment->setCurrency(config('ecommerce.default_currency'));
                    $existingPayment->setCreatedAt($startDate);

                    $this->entityManager->persist($existingPayment);
                    $this->entityManager->flush();

                    // dont duplicate link rows
                    $subscriptionPayment =
                        $this->subscriptionPaymentRepository->getByPayment($existingPayment)[0] ?? null;

                    if (empty($subscriptionPayment)) {
                        $subscriptionPayment = new SubscriptionPayment();
                    }

                    $subscriptionPayment->setSubscription($subscription);
                    $subscriptionPayment->setPayment($existingPayment);

                    $this->entityManager->persist($subscriptionPayment);
                    $this->entityManager->flush();

                    $subscription->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1);

                    if (empty($subscription->getLatestPayment())) {
                        $subscription->setLatestPayment($existingPayment);
                    }
                    event(new PaymentEvent($existingPayment));
                }

                $this->entityManager->persist($subscription);
                $this->entityManager->flush();

                event(new MobileOrderEvent(null, null, $subscription));
            } else {
                if ($purchasedProduct->getType() == Product::TYPE_DIGITAL_ONE_TIME) {

                    //pack purchase
                    $payment = $this->paymentRepository->getByExternalIdAndProvider(
                        $googleSubscriptionResponse->getRawResponse()
                            ->getOrderId(),
                        Payment::EXTERNAL_PROVIDER_GOOGLE
                    );

                    if (!$membershipIncludeFreePack) {
                        if (!$payment) {
                            $payment = new Payment();
                            $payment->setAttemptNumber(0);
                            $payment->setCreatedAt(Carbon::now());
                            if ($googleReceipt->getLocalCurrency() &&
                                $googleReceipt->getLocalCurrency() != config('ecommerce.default_currency') &&
                                in_array(
                                    $googleReceipt->getLocalCurrency(),
                                    config('ecommerce.allowable_currencies')
                                )) {

                                $totalPaidUsd = $this->currencyConvertionHelper->convert(
                                    $googleReceipt->getLocalPrice(),
                                    $googleReceipt->getLocalCurrency(),
                                    config('ecommerce.default_currency')
                                );

                                if ($totalPaidUsd && $totalPaidUsd <= ($purchasedProduct->getPrice() + 40)) {
                                    $payment->setTotalDue($totalPaidUsd);
                                    $payment->setTotalPaid($totalPaidUsd);
                                } else {
                                    $payment->setTotalDue($purchasedProduct->getPrice());
                                    $payment->setTotalPaid($purchasedProduct->getPrice());
                                    error_log(
                                        'Google purchase(id='.$googleReceipt->getId().'): user currency=' .
                                        $googleReceipt->getLocalCurrency() .
                                        ' user local price=' .
                                        $googleReceipt->getLocalPrice() .
                                        ' converted price=' .
                                        $totalPaidUsd .
                                        ' is greater with more the 40 USD that the product price. Store the product price=' .
                                        $purchasedProduct->getPrice() .
                                        ' in DB.'
                                    );
                                }
                            } else {
                                $payment->setTotalDue($purchasedProduct->getPrice());
                                $payment->setTotalPaid($purchasedProduct->getPrice());
                            }

                            $payment->setConversionRate(1);

                            $payment->setType(Payment::TYPE_INITIAL_ORDER);
                            $payment->setExternalId(
                                $googleSubscriptionResponse->getRawResponse()
                                    ->getOrderId()
                            );
                            $payment->setExternalProvider(Payment::EXTERNAL_PROVIDER_GOOGLE);

                            $payment->setGatewayName(config('ecommerce.brand'));
                            $payment->setStatus(Payment::STATUS_PAID);
                            $payment->setCurrency('USD');

                            $this->entityManager->persist($payment);
                            $this->entityManager->flush();
                            event(new PaymentEvent($payment));
                        }

                        $orderPayment = $this->orderPaymentRepository->getByPayment($payment);

                        if (!$orderPayment) {
                            $order = new Order();
                            $order->setUser($user);
                            $order->setTotalPaid($membershipIncludeFreePack ? 0 : $payment->getTotalPaid());
                            $order->setTotalDue($membershipIncludeFreePack ? 0 : $payment->getTotalDue());
                            $order->setTaxesDue(0);
                            $order->setShippingDue(0);
                            $order->setBrand(config('ecommerce.brand'));

                            $this->entityManager->persist($order);

                            $orderItem = new OrderItem();
                            $orderItem->setOrder($order);
                            $orderItem->setProduct($purchasedProduct);
                            $orderItem->setQuantity(1);
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
                    } else {
                        //assign user free product included with the membership
                        $this->userProductService->assignUserProduct(
                            $user,
                            $purchasedProduct,
                            null,
                            1
                        );
                    }
                }
            }
        }

        return $subscription;
    }

    /**
     * @param GoogleReceipt $receipt
     * @return Product|null
     * @throws ORMException
     */
    public function getPurchasedItem(GoogleReceipt $receipt, $isTrialPeriod = false)
    : array {
        $productsMap = config('ecommerce.google_store_products_map');
        if($isTrialPeriod){
            $productsMap = config('ecommerce.google_store_products_map_trial');
        }

        if (isset($productsMap[$receipt->getProductId()])) {
            return $this->productRepository->bySkus((array)$productsMap[$receipt->getProductId()]);
        }

        return [];
    }

    /**
     * @param array $purchasedItems
     * @return array
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function restoreAndSyncPurchasedItems(array $purchasedItems)
    {
        $shouldCreateAccount = false;
        $shouldLogin = false;
        $purchasedToken = null;
        $receiptUser = null;
        error_log(print_r($purchasedItems, true));
        foreach ($purchasedItems as $purchase) {
            //From mobile app we receive purchases for products that do not belong to our app
            if (!array_key_exists($purchase['product_id'], config('ecommerce.google_store_products_map'))) {
                continue;
            }

            $googleReceipt =
                $this->googleReceiptRepository->createQueryBuilder('gr')
                    ->where('gr.purchaseToken = :token')
                    ->andWhere('gr.email is not null')
                    ->setParameter('token', $purchase['purchase_token'])
                    ->orderBy('gr.id', 'desc')
                    ->getQuery()
                    ->getResult();

            if (!($googleReceipt)) {
                //check if purchases product is membership
                if (array_key_exists(
                    $purchase['product_id'],
                    config('ecommerce.google_store_products_map')
                )) {
                    try {
                        $this->googlePlayStoreGateway->getResponse(
                            $purchase['package_name'],
                            $purchase['product_id'],
                            $purchase['purchase_token']
                        );
                        $shouldCreateAccount = true;
                        $purchasedToken = $purchase;
                    } catch (\Exception $exception) {
                        //The subscription purchase is no longer available for query because it has been expired for too long
                        if ($exception->getCode() == 410) {
                            $shouldCreateAccount = true;
                        }
                        continue;
                    }

                } elseif ($this->userProvider->getCurrentUserId()) {
                    //pack purchase that should be restored
                    $receipt = new GoogleReceipt();

                    $receipt->setPackageName($purchase['package_name']);
                    $receipt->setProductId($purchase['product_id']);
                    $receipt->setEmail(
                        $this->userProvider->getCurrentUser()
                            ->getEmail()
                    );
                    $receipt->setPurchaseToken($purchase['purchase_token']);
                    $receipt->setBrand(config('ecommerce.brand'));
                    $receipt->setRequestType(GoogleReceipt::MOBILE_APP_REQUEST_TYPE);
                    $receipt->setPurchaseType(
                        GoogleReceipt::GOOGLE_PRODUCT_PURCHASE
                    );

                    $receiptUser = $this->processReceipt($receipt);
                }
            } else {

                $receiptUser = $this->userProvider->getUserByEmail($googleReceipt[0]->getEmail());

                if (!$this->userProvider->getCurrentUserId() ||
                    $this->userProvider->getCurrentUserId() != $receiptUser->getId()) {

                    $shouldLogin = true;

                } else {
                    //sync
                    $this->processReceipt($googleReceipt[0]);
                }
            }
        }

        return [
            'shouldCreateAccount' => $shouldCreateAccount,
            'shouldLogin' => $shouldLogin,
            'purchasedToken' => $purchasedToken,
            'receiptUser' => $receiptUser,
        ];

    }

    /**
     * @param array $purchasedItems
     * @return int
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function checkSignup(array $purchasedItems)
    {
        if (empty($purchasedItems)) {
            return self::SHOULD_SIGNUP;
        }

        $existsSubscription = false;
        $existsExpiredSubscriptions = false;

        foreach ($purchasedItems as $purchaseItem) {
            if (array_key_exists($purchaseItem['product_id'], config('ecommerce.google_store_products_map'))) {

                try {
                    $googleResponse = $this->googlePlayStoreGateway->getResponse(
                        $purchaseItem['package_name'],
                        $purchaseItem['product_id'],
                        $purchaseItem['purchase_token']
                    );

                    $googleReceipt =
                        $this->googleReceiptRepository->createQueryBuilder('gr')
                            ->where('gr.purchaseToken = :token')
                            ->andWhere('gr.email is not null')
                            ->setParameter('token', $purchaseItem['purchase_token'])
                            ->orderBy('gr.id', 'desc')
                            ->getQuery()
                            ->getResult();

                    if (Carbon::createFromTimestampMs($googleResponse->getExpiryTimeMillis()) >
                        Carbon::now()
                            ->subDays(
                                config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only', 1)
                            ) && ($googleResponse->getAutoRenewing() == 1)) {
                        if (!empty($googleReceipt)) {
                            $existsSubscription = true;
                            return self::SHOULD_LOGIN;
                        }
                    } else {
                        if (!empty($googleReceipt)) {
                            $existsSubscription = true;
                            $existsExpiredSubscriptions = true;
                        }
                    }

                } catch (\Exception $exception) {
                    continue;
                }

            }
        }

        if ($existsExpiredSubscriptions) {
            return self::SHOULD_RENEW;
        }

        if (!($existsSubscription)) {
            return self::SHOULD_SIGNUP;
        }
    }
}
