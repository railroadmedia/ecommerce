<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Requests\AppleReceiptRequest;
use Railroad\Ecommerce\Services\AppleStoreKitService;
use Railroad\Ecommerce\Services\ResponseService;

class AppleStoreKitController extends Controller
{
    /**
     * @var AppleStoreKitService
     */
    private $appleStoreKitService;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * AppleStoreKitController constructor.
     *
     * @param CartService $cartService
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AppleStoreKitService $appleStoreKitService,
        UserProviderInterface $userProvider
    )
    {
        $this->appleStoreKitService = $appleStoreKitService;
        $this->userProvider = $userProvider;
    }

    /**
     *
     * @throws ReceiptValidationException
     * @throws Throwable
     */
    public function processReceipt(AppleReceiptRequest $receipt)
    {
        $receipt = new AppleReceipt();

        $this->jsonApiHydrator->hydrate($receipt, $request->onlyAllowed());

        $user = $this->appleStoreKitService->processReceipt($receipt); // exception may be thrown

        $userAuthToken = $this->userProvider->getUserAuthToken($user);

        return ResponseService::appleReceipt($receipt, $userAuthToken);
    }
}
