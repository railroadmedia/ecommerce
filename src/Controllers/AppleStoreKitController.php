<?php

namespace Railroad\Ecommerce\Controllers;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
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
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * AppleStoreKitController constructor.
     *
     * @param AppleStoreKitService $appleStoreKitService
     * @param JsonApiHydrator $jsonApiHydrator
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AppleStoreKitService $appleStoreKitService,
        JsonApiHydrator $jsonApiHydrator,
        SubscriptionRepository $subscriptionRepository,
        UserProviderInterface $userProvider
    )
    {
        $this->appleStoreKitService = $appleStoreKitService;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProvider = $userProvider;
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
        error_log(
            'AppleStoreKitController processReceipt Request Dump --------------------------------------------------'
        );
        error_log(var_export($request->input(), true));

        $receipt = new AppleReceipt();

        $this->jsonApiHydrator->hydrate($receipt, $request->onlyAllowed());

        $receipt->setPassword($request->input('data.attributes.password'));
        $receipt->setPurchaseType($request->input('data.attribute.purchase_type', AppleReceipt::APPLE_SUBSCRIPTION_PURCHASE));

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
        error_log(
            'AppleStoreKitController processNotification Request Dump --------------------------------------------------'
        );
        error_log(var_export($request->get('notification_type'), true));
        error_log(var_export($request->input(), true));

        $notificationType = $request->get('notification_type');

        $receipt = new AppleReceipt();

        $receipt->setReceipt($request->get('latest_receipt'));
        $receipt->setRequestType(AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE);
        $receipt->setNotificationType($notificationType);
        $receipt->setBrand(config('ecommerce.brand'));

        try {
            $this->appleStoreKitService->processNotification($receipt);
        } catch (Exception $e) {
            error_log($e);

            return response()->json();
        }

        return response()->json();
    }
}
