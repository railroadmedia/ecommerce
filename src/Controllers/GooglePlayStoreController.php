<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\GoogleReceipt;
use Railroad\Ecommerce\Exceptions\ReceiptValidationException;
use Railroad\Ecommerce\Requests\GoogleReceiptRequest;
use Railroad\Ecommerce\Services\GooglePlayStoreService;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Spatie\Fractal\Fractal;
use Throwable;

class GooglePlayStoreController extends Controller
{
	/**
     * @var GooglePlayStoreService
     */
    private $googlePlayStoreService;

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
     * @param GooglePlayStoreService $googlePlayStoreService
     * @param JsonApiHydrator $jsonApiHydrator
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        GooglePlayStoreService $googlePlayStoreService,
        JsonApiHydrator $jsonApiHydrator,
        UserProviderInterface $userProvider
    )
    {
        $this->googlePlayStoreService = $googlePlayStoreService;
        $this->jsonApiHydrator = $jsonApiHydrator;
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
        $receipt = new GoogleReceipt();

        $this->jsonApiHydrator->hydrate($receipt, $request->onlyAllowed());

        $receipt->setPassword($request->input('data.attributes.password'));

        $user = $this->googlePlayStoreService->processReceipt($receipt); // exception may be thrown

        $userAuthToken = $this->userProvider->getUserAuthToken($user);

        return ResponseService::googleReceipt($receipt, $userAuthToken);
    }
}
