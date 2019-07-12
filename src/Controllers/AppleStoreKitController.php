<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Requests\AppleReceiptRequest;
use Railroad\Ecommerce\Services\AppleStoreKitService;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Exception;

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
     * @param CartService $cartService
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
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function processReceipt(AppleReceiptRequest $request)
    {
        $receipt = new AppleReceipt();

        $this->jsonApiHydrator->hydrate($receipt, $request->onlyAllowed());

        $receipt->setPassword($request->input('data.attributes.password'));

        $user = $this->appleStoreKitService->processReceipt($receipt); // exception may be thrown

        $userAuthToken = $this->userProvider->getUserAuthToken($user);

        return ResponseService::appleReceipt($receipt, $userAuthToken);
    }

    /**
     * @param Request $request
     *
     * @throws Throwable
     */
    public function processNotification(Request $request)
    {
        if (strtolower($request->get('notification_type')) == 'renewal' ||
            strtolower($request->get('notification_type')) == 'cancel') {

            $notificationType = strtolower($request->get('notification_type')) == 'renewal' ?
                AppleReceipt::APPLE_RENEWAL_NOTIFICATION_TYPE:
                AppleReceipt::APPLE_CANCEL_NOTIFICATION_TYPE;

            $receipt = new AppleReceipt();

            $receipt->setReceipt($request->get('latest_receipt'));
            $receipt->setRequestType(AppleReceipt::APPLE_NOTIFICATION_REQUEST_TYPE);
            $receipt->setNotificationType($notificationType);
            $receipt->setBrand(config('ecommerce.brand'));

            $webOrderLineItemId = $request->get('web_order_line_item_id');

            try {
                $this->appleStoreKitService->processNotification($receipt, $webOrderLineItemId);
            } catch (Exception $e) {
                return response()->json(['data' => $e->getMessage()], 500);
            }
        }

        return response()->json();
    }
}
