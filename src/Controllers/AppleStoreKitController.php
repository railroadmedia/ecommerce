<?php

namespace Railroad\Ecommerce\Controllers;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AppleReceipt;
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
        UserProviderInterface $userProvider
    )
    {
        $this->appleStoreKitService = $appleStoreKitService;
        $this->jsonApiHydrator = $jsonApiHydrator;
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

        if (auth()->check()) {
            $currentUser = $this->userProvider->getCurrentUser();
            $receipt->setEmail($currentUser->getEmail());
        }

        $receipt->setPassword($request->input('data.attributes.password',''));
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

        if(!$request->has('unified_receipt') && !$request->has('latest_receipt')){
            error_log(
                'AppleStoreKitController processNotification -------- Missing unified_receipt and latest_receipt in Apple request'
            );
            return response()->json();
        }

        error_log(var_export($request->get('unified_receipt')['latest_receipt_info'], true));

        $notificationType = $request->get('notification_type');

        $receipt = new AppleReceipt();

        $receipt->setReceipt($request->get('unified_receipt')['latest_receipt']??$request->get('latest_receipt',''));
        $receipt->setRequestType(AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE);
        $receipt->setNotificationType($notificationType);
        $receipt->setNotificationRequestData(base64_encode(serialize($request->all())));
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
