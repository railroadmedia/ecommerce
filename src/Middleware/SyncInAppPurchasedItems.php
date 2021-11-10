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
        $purchases = $request->get('purchases', []);

        //there are different style to sent in-app purchases on request for different mobile app builds
        $appleReceipt = $request->get('receipt', $request->get('purchases')['receipt'] ?? null);
        $googlePurchases = (!empty($purchases) && is_array($purchases) && array_key_exists('product_id', $purchases[0]??[])) ? $purchases : [];

        $platform = $request->get('platform');
        error_log('SyncInAppPurchasedItems request  :::  email address:' .$request->get('email') . '    platform:'.$platform);

        if ($platform) {
            if (($platform == 'ios') && ($appleReceipt)) {
                error_log('SyncInAppPurchasedItems - is iOS and the receipt :::');
                error_log(var_export($appleReceipt, true));
                $this->appleStoreKitService->restoreAndSyncPurchasedItems($appleReceipt);
            } elseif (($platform == 'android') && (!empty($googlePurchases))) {
                error_log('SyncInAppPurchasedItems - is Android and the purchases :::');
                error_log(var_export($googlePurchases, true));
                $this->googlePlayStoreService->restoreAndSyncPurchasedItems($googlePurchases);

            }
        } else {
            //for old mobile app build where the platform was not sent on request
            if ($appleReceipt) {
                error_log('SyncInAppPurchasedItems - is iOS and the receipt :::');
                error_log(var_export($appleReceipt, true));
                $this->appleStoreKitService->restoreAndSyncPurchasedItems($appleReceipt);
            }

            if (!empty($googlePurchases)) {
                error_log('SyncInAppPurchasedItems - is Android and the purchases :::');
                error_log(var_export($googlePurchases, true));
                $this->googlePlayStoreService->restoreAndSyncPurchasedItems($googlePurchases);
            }
        }
    }
}