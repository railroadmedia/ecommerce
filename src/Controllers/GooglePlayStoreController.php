<?php

namespace Railroad\Ecommerce\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\GoogleReceipt;
use Railroad\Ecommerce\Exceptions\ReceiptValidationException;
use Railroad\Ecommerce\Repositories\GoogleReceiptRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Requests\GoogleReceiptRequest;
use Railroad\Ecommerce\Services\GooglePlayStoreService;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Spatie\Fractal\Fractal;
use Throwable;

class GooglePlayStoreController extends Controller
{
    const SUBSCRIPTION_RENEWED = 2;
    const SUBSCRIPTION_CANCELED = 3;

    /**
     * @var GooglePlayStoreService
     */
    private $googlePlayStoreService;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var $googleReceiptRepository
     */
    private $googleReceiptRepository;

    /**
     * AppleStoreKitController constructor.
     *
     * @param GooglePlayStoreService $googlePlayStoreService
     * @param JsonApiHydrator $jsonApiHydrator
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        GooglePlayStoreService $googlePlayStoreService,
        JsonApiHydrator $jsonApiHydrator,
        SubscriptionRepository $subscriptionRepository,
        UserProviderInterface $userProvider,
        GoogleReceiptRepository $googleReceiptRepository
    ) {
        $this->googlePlayStoreService = $googlePlayStoreService;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProvider = $userProvider;
        $this->googleReceiptRepository = $googleReceiptRepository;
    }

    /**
     * @param GoogleReceiptRequest $request
     *
     * @return Fractal
     *
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function processReceipt(GoogleReceiptRequest $request)
    {
        error_log(
            'GooglePlayStoreController processReceipt Request Dump --------------------------------------------------'
        );
        error_log(var_export($request->input(), true));

        $receipt = new GoogleReceipt();

        $this->jsonApiHydrator->hydrate($receipt, $request->onlyAllowed());

        if (auth()->check()) {
            $currentUser = $this->userProvider->getCurrentUser();
            $receipt->setEmail($currentUser->getEmail());
        }

        $receipt->setPassword($request->input('data.attributes.password', ''));
        $receipt->setPurchaseType(
            $request->input('data.attributes.purchase_type', GoogleReceipt::GOOGLE_SUBSCRIPTION_PURCHASE)
        );

        if ($request->has('data.attributes.currency')) {
            $receipt->setLocalCurrency($request->input('data.attributes.currency'));
        }

        if ($request->has('data.attributes.price') && !is_null($request->input('data.attributes.price'))) {
            $receipt->setLocalPrice($request->input('data.attributes.price'));
        }

        if ($request->has('data.attributes.app')) {
            $app = $request->input('data.attributes.app');
            if (config('ecommerce.payment_gateways.google_play_store.'.$app.'.credentials')) {
                config()->set(
                    'ecommerce.payment_gateways.google_play_store.credentials',
                    config('ecommerce.payment_gateways.google_play_store.'.$app.'.credentials')
                );
            }
            if (config('ecommerce.payment_gateways.google_play_store.'.$app.'.application_name')) {
                config()->set(
                    'ecommerce.payment_gateways.google_play_store.application_name',
                    config('ecommerce.payment_gateways.google_play_store.'.$app.'.application_name')
                );
            }
        }

        $user = $this->googlePlayStoreService->processReceipt($receipt, $app ?? 'Musora'); // exception may be thrown

        $userAuthToken = $this->userProvider->getUserAuthToken($user);

        return ResponseService::googleReceipt($receipt, $userAuthToken);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function processNotification(Request $request)
    {
        error_log(
            'GooglePlayStoreController processNotification Request Dump --------------------------------------------------'
        );
        error_log(var_export($request->input(), true));

        $message = $request->get('message');

        if ($message) {
            $encodedData = $message['data'];

            $data = json_decode(base64_decode($encodedData));

            // we should return something for test notifications
            if (!empty($data->testNotification)) {
                return response()->json();
            }

            $subscriptionNotification = $data->subscriptionNotification;

            if (strtolower($subscriptionNotification->notificationType) == self::SUBSCRIPTION_RENEWED ||
                strtolower($subscriptionNotification->notificationType) == self::SUBSCRIPTION_CANCELED) {
                $oldReceipt =
                    $this->googleReceiptRepository->createQueryBuilder('gp')
                        ->where('gp.purchaseToken  = :purchase_token')
                        ->andWhere('gp.purchaseType = :purchase_type')
                        ->setParameter('purchase_token', $subscriptionNotification->purchaseToken)
                        ->setParameter('purchase_type', GoogleReceipt::GOOGLE_SUBSCRIPTION_PURCHASE)
                        ->getQuery()
                        ->getResult();

                $receipt = new GoogleReceipt();
                $notificationType =
                    strtolower($subscriptionNotification->notificationType) == self::SUBSCRIPTION_RENEWED ?
                        GoogleReceipt::GOOGLE_RENEWAL_NOTIFICATION_TYPE :
                        GoogleReceipt::GOOGLE_CANCEL_NOTIFICATION_TYPE;
                $brand =
                    ($data->packageName == 'com.pianote2') ? 'pianote' :
                        ($data->packageName == 'com.drumeo' ? 'drumeo' : 'musora');

                $app = ucfirst($brand);
                if (config('ecommerce.payment_gateways.google_play_store.'.$app.'.credentials')) {
                    config()->set(
                        'ecommerce.payment_gateways.google_play_store.credentials',
                        config('ecommerce.payment_gateways.google_play_store.'.$app.'.credentials')
                    );
                }
                if (config('ecommerce.payment_gateways.google_play_store.'.$app.'.application_name')) {
                    config()->set(
                        'ecommerce.payment_gateways.google_play_store.application_name',
                        config('ecommerce.payment_gateways.google_play_store.'.$app.'.application_name')
                    );
                }

                $receipt->setPurchaseToken($subscriptionNotification->purchaseToken);
                $receipt->setPackageName($data->packageName);
                $receipt->setProductId($subscriptionNotification->subscriptionId);
                $receipt->setRequestType(GoogleReceipt::GOOGLE_NOTIFICATION_REQUEST_TYPE);
                $receipt->setNotificationType($notificationType);
                $receipt->setBrand($brand);

                if (!empty($oldReceipt)) {
                    if ($oldReceipt[0]->getLocalCurrency()) {
                        $receipt->setLocalCurrency($oldReceipt[0]->getLocalCurrency());
                    }
                    if ($oldReceipt[0]->getLocalPrice()) {
                        $receipt->setLocalPrice($oldReceipt[0]->getLocalPrice());
                    }
                }

                $subscription =
                    $this->subscriptionRepository->findOneBy(
                            ['externalAppStoreId' => $subscriptionNotification->purchaseToken]
                        );

                if ($subscription) {
                    try {
                        $this->googlePlayStoreService->processNotification($receipt, $subscription);
                    } catch (Exception $e) {
                        error_log($e);

                        return response()->json();
                    }
                }
            }
        }

        return response()->json();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Railroad\Ecommerce\Exceptions\ReceiptValidationException
     * @throws \Throwable
     */
    public function signup(Request $request)
    {
        error_log(
            'Signup purchases request :: '.print_r($request->all(), true)
        );
        if ($request->has('app')) {
            $app = $request->input('app');
            if (config('ecommerce.payment_gateways.google_play_store.'.$app.'.credentials')) {
                config()->set(
                    'ecommerce.payment_gateways.google_play_store.credentials',
                    config('ecommerce.payment_gateways.google_play_store.'.$app.'.credentials')
                );
            }
            if (config('ecommerce.payment_gateways.google_play_store.'.$app.'.application_name')) {
                config()->set(
                    'ecommerce.payment_gateways.google_play_store.application_name',
                    config('ecommerce.payment_gateways.google_play_store.'.$app.'.application_name')
                );
            }
        }
        $action = $this->googlePlayStoreService->checkSignup($request->get('purchases', []));

        error_log(
            'Signup purchases response :: '.print_r($action, true)
        );

        switch ($action) {
            case GooglePlayStoreService::SHOULD_RENEW:
                return response()->json([
                                            'shouldRenew' => true,
                                            'message' => 'You can not create multiple Musora accounts under the same google account. You already have an expired/cancelled membership. Please renew your membership.',
                                        ]);
            case GooglePlayStoreService::SHOULD_LOGIN:
                return response()->json([
                                            'shouldLogin' => true,
                                            'message' => 'You have an active Musora account. Please login into your account. If you want to modify your payment plan please cancel your active subscription from device settings before.',
                                        ]);
            default:
                return response()->json([
                                            'shouldSignup' => true,
                                        ]);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Railroad\Ecommerce\Exceptions\ReceiptValidationException
     * @throws \Throwable
     */
    public function restorePurchase(Request $request)
    {
        $purchases = $request->get('purchases', []);

        error_log(
            'Restore purchases :: '.print_r($request->all(), true)
        );

        if (empty($purchases)) {
            return response()->json(
                [
                    'message' => 'No purchases on the request',
                ],
                500
            );
        }
        if ($request->has('app')) {
            $app = $request->input('app');
            if (config('ecommerce.payment_gateways.google_play_store.'.$app.'.credentials')) {
                config()->set(
                    'ecommerce.payment_gateways.google_play_store.credentials',
                    config('ecommerce.payment_gateways.google_play_store.'.$app.'.credentials')
                );
            }
            if (config('ecommerce.payment_gateways.google_play_store.'.$app.'.application_name')) {
                config()->set(
                    'ecommerce.payment_gateways.google_play_store.application_name',
                    config('ecommerce.payment_gateways.google_play_store.'.$app.'.application_name')
                );
            }
        }
        $results = $this->googlePlayStoreService->restoreAndSyncPurchasedItems($purchases);

        if ($results['shouldLogin'] == true) {
            return response()->json([
                                        'shouldLogin' => true,
                                        'email' => $results['receiptUser']->getEmail(),
                                    ]);
        } elseif ($results['shouldCreateAccount'] == true) {
            return response()->json([
                                        'shouldCreateAccount' => true,
                                        'purchase' => $results['purchasedToken'],
                                    ]);
        } else {
            if ($results['receiptUser']) {
                $user = $results['receiptUser'];
                $userAuthToken = $this->userProvider->getUserAuthToken($user);

                return response()->json([
                                            'success' => true,
                                            'token' => $userAuthToken,
                                            'tokenType' => 'bearer',
                                            'userId' => $user->getId(),
                                        ]);
            }
        }

        return response()->json(
            [
                'success' => true,
            ]
        );
    }
}
