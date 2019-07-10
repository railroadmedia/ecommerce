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
}
