<?php

namespace Railroad\Ecommerce\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\GoogleReceipt;
use Railroad\Ecommerce\Exceptions\ReceiptValidationException;
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
        UserProviderInterface $userProvider
    )
    {
        $this->googlePlayStoreService = $googlePlayStoreService;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProvider = $userProvider;
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
        error_log('GooglePlayStoreController processReceipt Request Dump --------------------------------------------------');
        error_log(var_export($request->input(), true));
        
        $receipt = new GoogleReceipt();

        $this->jsonApiHydrator->hydrate($receipt, $request->onlyAllowed());

        $receipt->setPassword($request->input('data.attributes.password'));
        $receipt->setPurchaseType($request->input('data.attribute.purchase_type', GoogleReceipt::GOOGLE_SUBSCRIPTION_PURCHASE));

        $user = $this->googlePlayStoreService->processReceipt($receipt); // exception may be thrown

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
        error_log('GooglePlayStoreController processNotification Request Dump --------------------------------------------------');
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

                $receipt = new GoogleReceipt();
                $notificationType = strtolower($subscriptionNotification->notificationType) == self::SUBSCRIPTION_RENEWED ?
                    GoogleReceipt::GOOGLE_RENEWAL_NOTIFICATION_TYPE:
                    GoogleReceipt::GOOGLE_CANCEL_NOTIFICATION_TYPE;

                $receipt->setPurchaseToken($subscriptionNotification->purchaseToken);
                $receipt->setPackageName($data->packageName);
                $receipt->setProductId($subscriptionNotification->subscriptionId);
                $receipt->setRequestType(GoogleReceipt::GOOGLE_NOTIFICATION_REQUEST_TYPE);
                $receipt->setNotificationType($notificationType);
                $receipt->setBrand(config('ecommerce.brand'));

                $subscription = $this->subscriptionRepository
                    ->findOneBy(['externalAppStoreId' => $subscriptionNotification->purchaseToken]);

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
}
