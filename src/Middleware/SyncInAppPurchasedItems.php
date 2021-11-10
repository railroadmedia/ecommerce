<?php

namespace Railroad\Ecommerce\Middleware;

use Closure;
use Railroad\Ecommerce\Services\AppleStoreKitService;
use Railroad\Ecommerce\Services\GooglePlayStoreService;

class SyncInAppPurchasedItems
{
    /**
     * @var GooglePlayStoreService
     */
    private $googlePlayStoreService;

    /**
     * @var AppleStoreKitService
     */
    private $appleStoreKitService;

    /**
     * SyncInAppPurchasedItems constructor.
     *
     * @param GooglePlayStoreService $googlePlayStoreService
     * @param AppleStoreKitService $appleStoreKitService
     */
    public function __construct(
        GooglePlayStoreService $googlePlayStoreService,
        AppleStoreKitService $appleStoreKitService
    ) {
        $this->googlePlayStoreService = $googlePlayStoreService;
        $this->appleStoreKitService = $appleStoreKitService;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    /**
     * @param $request
     * @param $response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Railroad\Ecommerce\Exceptions\ReceiptValidationException
     * @throws \Throwable
     */
    public function terminate($request, $response)
    {
        error_log('SyncInAppPurchasedItems request:::');
        error_log(print_r($request->all(), true));

        $purchases = $request->get('purchases', []);
        if (!empty($purchases)) {
            $this->googlePlayStoreService->restoreAndSyncPurchasedItems($purchases);
        }

        if ($request->has('receipt')) {
            $this->appleStoreKitService->restoreAndSyncPurchasedItems($request->get('receipt'));
        }
    }
}