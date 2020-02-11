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
use Railroad\Ecommerce\Repositories\ProductRepository;
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

    const RENEWAL_EXPIRATION_REASON = [
        1 => 'Apple in-app: Customer canceled their subscription.',
        2 => 'Apple in-app: Billing error.',
        3 => 'Apple in-app: Customer did not agree to a recent price modification.',
        4 => 'Apple in-app: Product was not available for purchase at the time of renewal.',
        5 => 'Apple in-app: Unknown error.',
    ];

    const RENEWAL_FAILED_MESSAGE = 'Subscription renewal failed, invalid receipt validation response';
    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

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
     */
    public function __construct(
        AppleReceiptRepository $appleReceiptRepository,
        AppleStoreKitGateway $appleStoreKitGateway,
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository,
        UserProductService $userProductService,
        UserProviderInterface $userProvider,
        SubscriptionRepository $subscriptionRepository
    )
    {
        $this->appleReceiptRepository = $appleReceiptRepository;
        $this->appleStoreKitGateway = $appleStoreKitGateway;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->userProductService = $userProductService;
        $this->userProvider = $userProvider;
        $this->subscriptionRepository = $subscriptionRepository;
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
            $validationResponse = $this->appleStoreKitGateway->validate($receipt->getReceipt());

            $purchasedItem = $this->getLatestPurchasedItem($validationResponse);

            if (!$purchasedItem) {
                throw new ReceiptValidationException('All purchased items are expired', null, $validationResponse);
            }

            $receipt->setValid(true);
            $receipt->setRawReceiptResponse(base64_encode(serialize($validationResponse)));

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

        if ($receipt->getNotificationType() == AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE) {

            $payment = $this->createSubscriptionRenewalPayment($subscription);

            $this->renewSubscription($subscription, $purchasedItem);

            $receipt->setPayment($payment);

            $this->entityManager->flush();

            event(new MobileSubscriptionRenewed($subscription, $payment, MobileSubscriptionRenewed::ACTOR_SYSTEM));

        } else {

            $this->cancelSubscription($subscription, $receipt);

            $this->entityManager->flush();

            event(new MobileSubscriptionCanceled($subscription, MobileSubscriptionRenewed::ACTOR_SYSTEM));
        }

        $this->userProductService->updateSubscriptionProducts($subscription);
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

        $purchasedItem = $validationResponse = null;

        try {

            $validationResponse = $this->appleStoreKitGateway->validate($receipt->getReceipt());

            $purchasedItem = $this->getLatestPurchasedItem($validationResponse);

            if (!$purchasedItem) {
                throw new ReceiptValidationException('All purchased items are expired', null, $validationResponse);
            }

            $receipt->setValid($purchasedItem->getExpiresDate() > Carbon::now());
            $receipt->setValidationError(null);
            $receipt->setRawReceiptResponse(base64_encode(serialize($validationResponse)));

        } catch (ReceiptValidationException $exception) {

            $receipt->setValid(false);
            $receipt->setValidationError($exception->getMessage());
            $receipt->setRawReceiptResponse(base64_encode(serialize($exception->getAppleResponse())));

            $this->entityManager->persist($receipt);
            $this->entityManager->flush();
        }

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        if ($receipt->getValid()) {
            $transactionId = $purchasedItem->getTransactionId();

            $receipt->setTransactionId($transactionId);

            $this->entityManager->persist($receipt);

            $payment = $this->createSubscriptionRenewalPayment($subscription);

            $this->renewSubscription($subscription, $purchasedItem);

            $receipt->setPayment($payment);

            $this->entityManager->flush();

            event(new MobileSubscriptionRenewed($subscription, $payment, MobileSubscriptionRenewed::ACTOR_CONSOLE));

        } else {

            if (!$validationResponse ||
                !is_array($validationResponse->getPendingRenewalInfo()) ||
                empty($validationResponse->getPendingRenewalInfo()) ||
                !$validationResponse->getPendingRenewalInfo()[0]->getAutoRenewStatus()) {

                $intent =
                    $validationResponse &&
                    is_array($validationResponse->getPendingRenewalInfo()) &&
                    !empty($validationResponse->getPendingRenewalInfo()) ?
                        $validationResponse->getPendingRenewalInfo()[0]->getExpirationIntent() : null;

                $note =
                    ($intent && isset(self::RENEWAL_EXPIRATION_REASON[$intent])) ?
                        self::RENEWAL_EXPIRATION_REASON[$intent] :
                        ($receipt->getValidationError() ?? self::RENEWAL_FAILED_MESSAGE);

                $this->cancelSubscription($subscription, $receipt, $note);

                $this->entityManager->flush();

                event(new MobileSubscriptionCanceled($subscription, MobileSubscriptionRenewed::ACTOR_CONSOLE));
            } else {

                $exceptionFormat = 'Apple renewal process still pending for receipt id: %s, subscription id: %s';

                throw new ReceiptValidationException(
                    sprintf(
                        $exceptionFormat,
                        $receipt->getId(),
                        $subscription->getId()
                    )
                );
            }
        }

        $this->userProductService->updateSubscriptionProducts($subscription);
    }

    /**
     * @param Subscription $subscription
     *
     * @return Payment
     *
     * @throws Throwable
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
     * @param PurchaseItem $purchasedItem
     *
     * @throws ReceiptValidationException
     */
    public function renewSubscription(
        Subscription $subscription,
        PurchaseItem $purchasedItem
    )
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
                throw new ReceiptValidationException("Subscription interval type not configured");
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
        $subscription->setAppleExpirationDate($purchasedItem->getExpiresDate());
        $subscription->setUpdatedAt(Carbon::now());
    }

    /**
     * @param Subscription $subscription
     * @param AppleReceipt $receipt
     * @param string $note
     */
    public function cancelSubscription($subscription, $receipt, $note = null)
    {
        if (!$note) {
            $noteFormat = 'Canceled by apple notification, receipt id: %s';
            $note = sprintf($noteFormat, $receipt->getId());
        }

        $subscription->setCanceledOn(Carbon::now());
        $subscription->setUpdatedAt(Carbon::now());
        $subscription->setNote($note);
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
            return $validationResponse->getLatestReceiptInfo()[0];
        }

        return null;
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
        $payment->setType(Payment::TYPE_APPLE_INITIAL_ORDER);
        $payment->setExternalId(''); // todo
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
     * @param ResponseInterface $appleResponse
     * @param AppleReceipt $receipt
     * @param User $user
     * @return null
     */
    public function syncSubscription(ResponseInterface $appleResponse, AppleReceipt $receipt, User $user)
    {
        $latestPurchasedItem = $this->getLatestPurchasedItem($appleResponse);

        if (empty($latestPurchasedItem)) {
            return null;
        }

        $product = $this->getProductByAppleStoreId($latestPurchasedItem->getProductId());

        if (!$product) {
            return null;
        }

        // if a subscription with this external id already exists, just update it
        $subscription = $this->subscriptionRepository->getByExternalAppStoreId($latestPurchasedItem->getWebOrderLineItemId());

        if (empty($subscription)) {
            $subscription = new Subscription();
            $subscription->setCreatedAt(Carbon::now());
            $subscription->setTotalCyclesPaid(1);
        }

        $subscription->setBrand(config('ecommerce.brand'));
        $subscription->setType(Subscription::TYPE_APPLE_SUBSCRIPTION);
        $subscription->setUser($user);
        $subscription->setProduct($product);

        $subscription->setIsActive($latestPurchasedItem->getExpiresDate() > Carbon::now());
        $subscription->setStartDate($latestPurchasedItem->getOriginalPurchaseDate());
        $subscription->setPaidUntil($latestPurchasedItem->getExpiresDate());
        $subscription->setAppleExpirationDate($latestPurchasedItem->getExpiresDate());
        $subscription->setTotalPrice($product->getPrice());
        $subscription->setTax(0);
        $subscription->setCurrency('USD');
        $subscription->setIntervalType($product->getSubscriptionIntervalType());
        $subscription->setIntervalCount($product->getSubscriptionIntervalCount());
        $subscription->setTotalCyclesPaid(1);
        $subscription->setTotalCyclesDue(null);
        $subscription->setExternalAppStoreId($latestPurchasedItem->getWebOrderLineItemId());
        $subscription->setAppleReceipt($receipt);
        $subscription->setCreatedAt(Carbon::now());

        // sync payments
        // 1 payment for every purchase item
        foreach ($appleResponse->getLatestReceiptInfo() as $purchaseItem) {

        }

        $receipt->setSubscription($subscription);
    }

    /**
     * @param PurchaseItem $purchasedItem
     * @param Order $order
     * @param Payment|null $payment
     * @param AppleReceipt $receipt
     * @param boolean $isTrial
     * @return Subscription|null
     * @throws Throwable
     * @throws \Doctrine\ORM\ORMException
     */
    public function createOrderSubscription(
        PurchaseItem $purchasedItem,
        Order $order,
        ?Payment $payment,
        AppleReceipt $receipt,
        $isTrial
    ): ?Subscription
    {
        $subscription = new Subscription();

        $product = $this->getProductByAppleStoreId($purchasedItem->getProductId());

        if (!$product) {
            return null;
        }

        $nextBillDate = Carbon::now();

        if ($isTrial) {
            $nextBillDate =
                Carbon::now()
                    ->addDays(config('ecommerce.trial_days_number', 7) + 1);
        } elseif (!empty($product->getSubscriptionIntervalType())) {
            if ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_monthly')) {
                $nextBillDate =
                    Carbon::now()
                        ->addMonths($product->getSubscriptionIntervalCount())->addDays(1);

            } elseif ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_yearly')) {
                $nextBillDate =
                    Carbon::now()
                        ->addYears($product->getSubscriptionIntervalCount())->addDays(1);

            } elseif ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_daily')) {
                $nextBillDate =
                    Carbon::now()
                        ->addDays($product->getSubscriptionIntervalCount() + 1);
            }
        }

        $intervalType = $product ? $product->getSubscriptionIntervalType() : config('ecommerce.interval_type_monthly');

        $intervalCount = $product ? $product->getSubscriptionIntervalCount() : 1;

        $subscription->setBrand(config('ecommerce.brand'));
        $subscription->setType(Subscription::TYPE_APPLE_SUBSCRIPTION);
        $subscription->setUser($order->getUser());
        $subscription->setOrder($order);
        $subscription->setProduct($product);
        $subscription->setIsActive(true);
        $subscription->setStartDate(Carbon::now());
        $subscription->setPaidUntil($nextBillDate);
        $subscription->setAppleExpirationDate($purchasedItem->getExpiresDate());
        $subscription->setTotalPrice($product->getPrice());
        $subscription->setTax(0);
        $subscription->setCurrency('');
        $subscription->setIntervalType($intervalType);
        $subscription->setIntervalCount($intervalCount);
        $subscription->setTotalCyclesPaid(1);
        $subscription->setTotalCyclesDue(1);
        $subscription->setExternalAppStoreId($purchasedItem->getWebOrderLineItemId());
        $subscription->setAppleReceipt($receipt);
        $subscription->setCreatedAt(Carbon::now());

        $receipt->setSubscription($subscription);

        if ($payment) {
            $subscriptionPayment = new SubscriptionPayment();

            $subscriptionPayment->setSubscription($subscription);
            $subscriptionPayment->setPayment($payment);

            $this->entityManager->persist($subscriptionPayment);
        }

        $this->entityManager->persist($subscription);

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
