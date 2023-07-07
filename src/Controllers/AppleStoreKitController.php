<?php

namespace Railroad\Ecommerce\Controllers;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Gateways\RevenueCatGateway;
use Railroad\Ecommerce\Requests\AppleReceiptRequest;
use Railroad\Ecommerce\Services\AppleStoreKitService;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Spatie\Fractal\Fractal;
use Throwable;

class AppleStoreKitController extends Controller
{
    /**
     * @var AppleStoreKitService
     */
    private $appleStoreKitService;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    private RevenueCatGateway $revenueCatGateway;

    /**
     * AppleStoreKitController constructor.
     *
     * @param AppleStoreKitService $appleStoreKitService
     * @param JsonApiHydrator $jsonApiHydrator
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AppleStoreKitService $appleStoreKitService,
        JsonApiHydrator $jsonApiHydrator,
        UserProviderInterface $userProvider,
        RevenueCatGateway $revenueCatGateway
    ) {
        $this->appleStoreKitService = $appleStoreKitService;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->userProvider = $userProvider;
        $this->revenueCatGateway = $revenueCatGateway;
    }

    /**
     * @param AppleReceiptRequest $request
     *
     * @return Fractal
     *
     * @throws GuzzleException
     * @throws Throwable
     */
    public function processReceipt(AppleReceiptRequest $request)
    {
        //Log::info('AppleStoreKitController processReceipt Request Dump --------------------------------------------------');
        //Log::info(var_export($request->input(), true));

        $receipt = new AppleReceipt();

        $this->jsonApiHydrator->hydrate($receipt, $request->onlyAllowed());

        if (auth()->check()) {
            $currentUser = $this->userProvider->getCurrentUser();
            $receipt->setEmail($currentUser->getEmail());
        }

        $receipt->setPassword($request->input('data.attributes.password', ''));
        $receipt->setPurchaseType(
            $request->input('data.attribute.purchase_type', AppleReceipt::APPLE_SUBSCRIPTION_PURCHASE)
        );

        if ($request->has('data.attributes.currency')) {
            $receipt->setLocalCurrency($request->input('data.attributes.currency'));
        }

        if ($request->has('data.attributes.price') && !is_null($request->input('data.attributes.price'))) {
            $receipt->setLocalPrice($request->input('data.attributes.price'));
        }

        if($request->has('data.attributes.app')){
            $app = $request->input('data.attributes.app');
            if(config('ecommerce.payment_gateways.apple_store_kit.'.$app.'.shared_secret')) {
                config()->set(
                    'ecommerce.payment_gateways.apple_store_kit.shared_secret',
                    config('ecommerce.payment_gateways.apple_store_kit.'.$app.'.shared_secret')
                );
            }
        }

        $user = $this->appleStoreKitService->processReceipt($receipt); // exception may be thrown

        $userAuthToken = $this->userProvider->getUserAuthToken($user);

        return ResponseService::appleReceipt($receipt, $userAuthToken);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws GuzzleException
     * @throws Throwable
     */
    public function processNotification(Request $request)
    {
        Log::debug('Processing AppleStoreKitController processNotification');
        Log::debug(var_export($request->all(), true));

        if (!$request->has('unified_receipt') && !$request->has('latest_receipt')) {
            Log::debug(
                'AppleStoreKitController processNotification -------- Missing unified_receipt and latest_receipt in Apple request'
            );

            return response()->json();
        }

        $notificationType = $request->get('notification_type');

        $receipt = new AppleReceipt();

        $receipt->setReceipt($request->get('unified_receipt')['latest_receipt'] ?? $request->get('latest_receipt', ''));
        $receipt->setRequestType(AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE);
        $receipt->setNotificationType($notificationType);
        $receipt->setNotificationRequestData(base64_encode(serialize($request->all())));
        $receipt->setBrand(config('ecommerce.brand'));

        try {
            $this->appleStoreKitService->processNotification($receipt);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json();
        }

        return response()->json();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function signup(Request $request)
    {
        Log::info('Attempting to apple signup for receipt: ' . $request->get('receipt'));

        if($request->has('app')){
            $app = $request->input('app');
            if(config('ecommerce.payment_gateways.apple_store_kit.'.$app.'.shared_secret')) {
                config()->set(
                    'ecommerce.payment_gateways.apple_store_kit.shared_secret',
                    config('ecommerce.payment_gateways.apple_store_kit.'.$app.'.shared_secret')
                );
            }
        }

        $action = $this->appleStoreKitService->checkSignup($request->get('receipt'));

        switch ($action) {
            case AppleStoreKitService::SHOULD_RENEW:
                return response()->json([
                                            'shouldRenew' => true,
                                            'message' => 'You can not create multiple '.
                                                ucfirst(config('ecommerce.brand')).
                                                ' accounts under the same apple account. You already have an expired/cancelled membership. Please renew your membership.',
                                        ]);
            case AppleStoreKitService::SHOULD_LOGIN:
                return response()->json([
                                            'shouldLogin' => true,
                                            'message' => 'You have an active '.
                                                ucfirst(config('ecommerce.brand')).
                                                ' account. Please login into your account. If you want to modify your payment plan please cancel your active subscription from device settings before.',
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
     * @throws GuzzleException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Railroad\Ecommerce\Exceptions\ReceiptValidationException
     * @throws \Throwable
     */
    public function restorePurchase(Request $request)
    {
        $receipt = $request->get('receipt', []);

        if (empty($receipt)) {
            Log::info('NoReceiptOnTheRequest'.var_export($request->input(), true));

            return response()->json(
                [
                    'message' => 'No receipt on the request',
                ],
                500
            );
        }

        if($request->has('app')){
            $app = $request->input('app');
            if(config('ecommerce.payment_gateways.apple_store_kit.'.$app.'.shared_secret')) {
                config()->set(
                    'ecommerce.payment_gateways.apple_store_kit.shared_secret',
                    config('ecommerce.payment_gateways.apple_store_kit.'.$app.'.shared_secret')
                );
            }
        }

        Log::info('Attempting to restore apple purchases for receipt: ' . $receipt);

        $results = $this->appleStoreKitService->restoreAndSyncPurchasedItems($receipt);

        Log::info('Apple response for restore purchase attempt: ' . var_export($results, true));

        if (!$results) {
            Log::info('restorePurchaseNoValidPurchasedItemsInAppleResponse '.var_export($request->input(), true));

            return response()->json(
                [
                    'success' => true,
                    'message' => 'No valid purchased items in Apple response',
                ],
                200
            );
        }
        if ($results['shouldLogin'] == true) {
            Log::info('restorePurchaseShouldLoginWithEmail '.var_export($results['receiptUser']->getEmail(), true));
            auth()->logout();

            return response()->json([
                                        'shouldLogin' => true,
                                        'email' => $results['receiptUser']->getEmail(),
                                    ]);
        } elseif ($results['shouldCreateAccount'] == true) {
            Log::info('restorePurchaseShouldCreateAccount '.var_export($receipt, true));
            return response()->json([
                                        'shouldCreateAccount' => true,
                                    ]);
        } elseif ($results['receiptUser']) {
            $user = $results['receiptUser'] ?? auth()->user();
            $userAuthToken = $this->userProvider->getUserAuthToken($user);

            Log::info('restorePurchaseUserExists '.var_export($user, true));

            return response()->json([
                                        'success' => true,
                                        'token' => $userAuthToken,
                                        'tokenType' => 'bearer',
                                        'userId' => $user->getId(),
                                    ]);
        }

        Log::info('IMPORTANT restoreWithoutCreateAccountOrLogin  receipt '.var_export($receipt, true));
        Log::info('IMPORTANT restoreWithoutCreateAccountOrLogin  responseFromBERestore '.var_export($results, true));

        return response()->json([
                                    'success' => true,
                                    'message' => 'No valid purchased items in Apple response',
                                ]);
    }

}
