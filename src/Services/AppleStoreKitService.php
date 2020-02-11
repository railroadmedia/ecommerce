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
    )
    {
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
    public function processReceipt(AppleReceipt $receipt): User
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

        $user = $this->userProvider->getUserByEmail($receipt->getEmail());

        if (!$user) {
            $user = $this->userProvider->createUser($receipt->getEmail(), $receipt->getPassword());

            auth()->loginUsingId($user->getId());
        }

        $subscription = $this->syncSubscription($appleResponse, $receipt, $user);

        $this->entityManager->flush();

        event(new MobileOrderEvent(null, null, $subscription));

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
    public function processNotification(
        AppleReceipt $receipt,
        Subscription $subscription
    )
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

            event(new MobileSubscriptionRenewed($subscription, end($subscription->getPayments()), MobileSubscriptionRenewed::ACTOR_SYSTEM));

        } elseif (!empty($subscription->getCanceledOn())) {

            event(new MobileSubscriptionCanceled($subscription, MobileSubscriptionRenewed::ACTOR_SYSTEM));

        }

        $this->userProductService->updateSubscriptionProducts($subscription);

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

        $purchasedItem = $appleResponse = null;

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

        $this->userProductService->updateSubscriptionProducts($subscription);
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

        $product = $this->getProductByAppleStoreId($firstPurchasedItem->getProductId());

        if (!$product) {
            return null;
        }

        // if a subscription with this external id already exists, just update it
        // the subscription external ID should always be set to the first purchase item web order line item ID
        $subscription = $this->subscriptionRepository->getByExternalAppStoreId($firstPurchasedItem->getWebOrderLineItemId());

        if (empty($subscription)) {
            $subscription = new Subscription();
            $subscription->setCreatedAt(Carbon::now());
            $subscription->setTotalCyclesPaid(1);
            $subscription->setUser($user);
        }

        $subscription->setBrand(config('ecommerce.brand'));
        $subscription->setType(Subscription::TYPE_APPLE_SUBSCRIPTION);
        $subscription->setProduct($product);

        if (!empty($appleResponse->getPendingRenewalInfo()[0]) && !$appleResponse->getPendingRenewalInfo()[0]->getAutoRenewStatus()) {
            $subscription->setCanceledOn($latestPurchaseItem->getCancellationDate());
            $subscription->setCancellationReason(
                self::RENEWAL_EXPIRATION_REASON[$appleResponse->getPendingRenewalInfo()[0]->getExpirationIntent()] ?? ''
            );
            $subscription->setIsActive(false);
        } else {
            $subscription->setCanceledOn(null);
            $subscription->setIsActive($latestPurchaseItem->getExpiresDate() > Carbon::now());
        }

        $subscription->setStartDate($latestPurchaseItem->getOriginalPurchaseDate());
        $subscription->setPaidUntil($latestPurchaseItem->getExpiresDate());
        $subscription->setAppleExpirationDate($latestPurchaseItem->getExpiresDate());

        $subscription->setTotalPrice($product->getPrice());
        $subscription->setTax(0);
        $subscription->setCurrency('USD');

        $subscription->setIntervalType($product->getSubscriptionIntervalType());
        $subscription->setIntervalCount($product->getSubscriptionIntervalCount());

        $subscription->setTotalCyclesPaid(0);
        $subscription->setTotalCyclesDue(null);

        // external app store id should always be the first purchase item
        $subscription->setExternalAppStoreId($firstPurchasedItem->getWebOrderLineItemId());
        $subscription->setAppleReceipt($receipt);

        $subscription->setCreatedAt(Carbon::now());

        // sync payments
        // 1 payment for every purchase item
        foreach ($appleResponse->getLatestReceiptInfo() as $purchaseItem) {
            $subscription->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1);

            $existingPayment = $this->paymentRepository->getByExternalIdAndProvider($purchaseItem->getTransactionId(), Payment::EXTERNAL_PROVIDER_APPLE);

            if (empty($existingPayment)) {
                $existingPayment = new Payment();
                $existingPayment->setCreatedAt(Carbon::now());
            } else {
                $existingPayment->setUpdatedAt(Carbon::now());
            }

            $existingPayment->setTotalDue($product->getPrice());
            $existingPayment->setTotalPaid($product->getPrice());

            $existingPayment->setTotalRefunded(0);
            $existingPayment->setConversionRate(1);

            $existingPayment->setType(Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL);
            $existingPayment->setExternalId($purchaseItem->getTransactionId());
            $existingPayment->setExternalProvider(Payment::EXTERNAL_PROVIDER_APPLE);

            $existingPayment->setGatewayName(config('ecommerce.brand'));
            $existingPayment->setStatus(Payment::STATUS_PAID);
            $existingPayment->setCurrency('USD');

            $this->entityManager->persist($existingPayment);

            // save the payment to the subscription
            $subscriptionPayment = $this->subscriptionPaymentRepository->getByPayment($existingPayment);

            if (empty($subscriptionPayment)) {
                $subscriptionPayment = new SubscriptionPayment();
            }

            $subscriptionPayment->setSubscription($subscription);
            $subscriptionPayment->setPayment($existingPayment);

            $this->entityManager->persist($subscriptionPayment);
        }

        $receipt->setSubscription($subscription);

        $this->entityManager->persist($receipt);
        $this->entityManager->persist($subscription);

        $this->entityManager->flush();

        return $subscription;
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

        return null;
    }
}
