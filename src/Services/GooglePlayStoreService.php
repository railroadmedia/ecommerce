<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\ORMException;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\GoogleReceipt;
use Railroad\Ecommerce\Entities\Order;
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
use Railroad\Ecommerce\Gateways\GooglePlayStoreGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
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
     * GooglePlayStoreService constructor.
     *
     * @param GooglePlayStoreGateway $googlePlayStoreGateway ,
     * @param EcommerceEntityManager $entityManager ,
     * @param ProductRepository $productRepository ,
     * @param SubscriptionRepository $subscriptionRepository ,
     * @param UserProductService $userProductService ,
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        GooglePlayStoreGateway $googlePlayStoreGateway,
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService,
        UserProviderInterface $userProvider
    ) {
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

        // save it to the database
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

        // sync the subscription
        $subscription = $this->syncSubscription($receipt, $googleResponse, $user);

        // note: a payment should never be created for these requests, since its either a trial or the payment
        // was already created from a notification
        // todo: this is only setup to handle trial orders at the moment

        event(new MobileOrderEvent(null, null, $subscription));

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

        $subscription = $this->syncSubscription($receipt, $googleResponse, $subscription->getUser());

        if ($receipt->getNotificationType() == GoogleReceipt::GOOGLE_RENEWAL_NOTIFICATION_TYPE) {

            $payment = $this->createSubscriptionRenewalPayment($subscription);

            $subscription->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1);

            $receipt->setPayment($payment);

            $this->entityManager->flush();

            event(new MobileSubscriptionRenewed($subscription, $payment, MobileSubscriptionRenewed::ACTOR_SYSTEM));

        } else {

            event(new MobileSubscriptionCanceled($subscription, MobileSubscriptionRenewed::ACTOR_SYSTEM));
        }

        $this->userProductService->updateSubscriptionProductsApp($subscription);
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
        $payment->setType(Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL);
        $payment->setExternalId('');
        $payment->setExternalProvider(Payment::EXTERNAL_PROVIDER_GOOGLE);
        $payment->setGatewayName(config('ecommerce.brand'));
        $payment->setStatus(Payment::STATUS_PAID);
        $payment->setCurrency(config('ecommerce.default_currency'));
        $payment->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);

        $subscriptionPayment = new SubscriptionPayment();

        $subscriptionPayment->setSubscription($subscription);
        $subscriptionPayment->setPayment($payment);

        $this->entityManager->persist($subscriptionPayment);

        return $payment;
    }
    
    /**
     * @param GoogleReceipt $googleReceipt
     * @param SubscriptionResponse $googleSubscriptionResponse
     * @param User $user
     * @return Subscription
     *
     * @throws ORMException
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function syncSubscription(
        GoogleReceipt $googleReceipt,
        SubscriptionResponse $googleSubscriptionResponse,
        User $user
    ): Subscription {

        $purchasedProduct = $this->getPurchasedItem($googleReceipt);

        if (!$purchasedProduct) {
            throw new ReceiptValidationException('Purchased google in app product not found in config.');
        }

        // if a subscription with this external id already exists, just update it
        $subscription = $this->subscriptionRepository->getByExternalAppStoreId($googleReceipt->getPurchaseToken());

        if (empty($subscription)) {
            $subscription = new Subscription();
            $subscription->setCreatedAt(Carbon::now());
            $subscription->setTotalCyclesPaid(1);
        }

        $subscription->setBrand(config('ecommerce.brand'));
        $subscription->setType(Subscription::TYPE_GOOGLE_SUBSCRIPTION);
        $subscription->setUser($user);
        $subscription->setProduct($purchasedProduct);

        $subscription->setIsActive($googleSubscriptionResponse->getAutoRenewing());
        $subscription->setStartDate(Carbon::createFromTimestampMs($googleSubscriptionResponse->getStartTimeMillis()));
        $subscription->setPaidUntil(Carbon::createFromTimestampMs($googleSubscriptionResponse->getExpiryTimeMillis()));

        if (!empty($googleSubscriptionResponse->getUserCancellationTimeMillis())) {
            $subscription->setCanceledOn(Carbon::createFromTimestampMs($googleSubscriptionResponse->getUserCancellationTimeMillis()));
            $subscription->setCancellationReason($googleSubscriptionResponse->getCancelReason());
        }

        $subscription->setTotalPrice($purchasedProduct->getPrice());
        $subscription->setTax(0);
        $subscription->setCurrency(config('ecommerce.default_currency'));

        $subscription->setIntervalType($purchasedProduct->getSubscriptionIntervalType());
        $subscription->setIntervalCount($purchasedProduct->getSubscriptionIntervalCount());
        $subscription->setTotalCyclesDue(null);

        $subscription->setExternalAppStoreId($googleReceipt->getPurchaseToken());
        $subscription->setUpdatedAt(Carbon::now());

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

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
