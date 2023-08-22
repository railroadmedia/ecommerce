<?php

namespace Railroad\Ecommerce\Commands;


use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Gateways\RevenueCatGateway;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;
use ReceiptValidator\GooglePlay\SubscriptionResponse;
use ReceiptValidator\iTunes\ProductionResponse;
use ReceiptValidator\iTunes\SandboxResponse;
use Throwable;

class SyncRevenueCat extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'SyncRevenueCat {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync RevenueCat';

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(
        DatabaseManager $databaseManager,
        RevenueCatGateway $revenueCatGateway,
        AppleStoreKitGateway $appleStoreGateway,
        UserProviderInterface $userProvider
    ) {
        ini_set('xdebug.var_display_max_depth', '100');
        ini_set('xdebug.var_display_max_children', '256');
        ini_set('xdebug.var_display_max_data', '1024');

        $this->info('Starting SyncRevenueCat command.');

//        $googleReceipts =
//            $databaseManager->connection(config('ecommerce.database_connection_name'))
//                ->table('ecommerce_google_receipts')
//                ->where('ecommerce_google_receipts.id', '=', $this->argument('id'))
//                ->get();
//
//        foreach ($googleReceipts as $googleReceipt) {
//            $user = $userProvider->getUserByEmail($googleReceipt->email);
//
//            if ($user) {
//                $revenueCatGateway->sendRequest(
//                    $googleReceipt->purchase_token,
//                    $user,
//                    $googleReceipt->product_id,
//                    'android'
//                );
//            }
//        }
//
//        $this->info('Google done.');
//        return;

        $appleReceipts =
            $databaseManager->connection(config('ecommerce.database_connection_name'))
                ->table('ecommerce_apple_receipts')
                ->join('ecommerce_subscriptions', 'ecommerce_apple_receipts.subscription_id', '=', 'ecommerce_subscriptions.id')
                ->orderBy('ecommerce_apple_receipts.id', 'desc')
//                ->whereNull('product_id')
//                ->whereNotNull('subscription_id')
                //->where('request_type', '=', 'mobile')
                ->where('ecommerce_apple_receipts.id', '=', $this->argument('id'))
                ->chunk(
                    500,
                    function (Collection $appleReceipts) use ( $databaseManager, $appleStoreGateway, $userProvider, $revenueCatGateway) {

                        foreach ($appleReceipts as $appleReceipt) {
                            $this->info('Processing apple receipt: ' . $appleReceipt->id);
                            try {
                                $notificationRequestData =
                                    unserialize(base64_decode($appleReceipt->raw_receipt_response, true));

                                if ($notificationRequestData !== false && $notificationRequestData !== null) {
                                    $latestReceiptInfo = $notificationRequestData->getLatestReceiptInfo();
                                    if (!empty($latestReceiptInfo) ) {

                                        $user = $userProvider->getUserById($appleReceipt->user_id);

                                                                if ($user) {
                                                                    $revenueCatGateway->sendRequest(
                                                                        $appleReceipt->receipt,
                                                                        $user,
                                                                        $latestReceiptInfo[0]->getProductId(),
                                                                        'ios',
                                                                        $appleReceipt->local_price,
                                                                        $appleReceipt->local_currency,
                                                                    );
                                                                }
                                    }else{
                                        $this->error('raw_receipt_response exists, but No latestReceiptInfo for apple receipt: ' . $appleReceipt->id);
                                        $response = $appleStoreGateway->getResponse($appleReceipt->receipt);

                                        $latestReceiptInfo = $response->getLatestReceiptInfo();
                                        if (!empty($latestReceiptInfo)) {
                                            $user = $userProvider->getUserByEmail($appleReceipt->email);

                                                                    if ($user) {
                                                                        $revenueCatGateway->sendRequest(
                                                                            $appleReceipt->receipt,
                                                                            $user,
                                                                            $latestReceiptInfo[0]->getProductId(),
                                                                            'ios',
                                                                            $appleReceipt->local_price,
                                                                            $appleReceipt->local_currency,
                                                                        );
                                                                    }
                                        }else{
                                            $this->error('No latestReceiptInfo for apple receipt: ' . $appleReceipt->id);
                                        }
                                    }
                                    //                    if (!empty($latestReceiptInfo)) {
                                    //                        $latestPurchasedInfo = $latestReceiptInfo[0];
                                    //                        dd($appleReceipt);
                                    //dd( $latestPurchasedInfo->getProductId());
                                    //                        $user = $userProvider->getUserByEmail($appleReceipt->email);
                                    //
                                    //                        if ($user) {
                                    //                            $revenueCatGateway->sendRequest(
                                    //                                $appleReceipt->receipt,
                                    //                                $user,
                                    //                                $latestPurchasedInfo->getProductId(),
                                    //                                'ios',
                                    //                                $appleReceipt->local_price,
                                    //                                $appleReceipt->local_currency,
                                    //                            );
                                    //                        }
                                    //                    }
                                }else{
                                    $response = $appleStoreGateway->getResponse($appleReceipt->receipt);
                                    $latestReceiptInfo = $response->getLatestReceiptInfo();
                                    if (!empty($latestReceiptInfo)) {
                                        $user = $userProvider->getUserByEmail($appleReceipt->email);

                                                                if ($user) {
                                                                    $revenueCatGateway->sendRequest(
                                                                        $appleReceipt->receipt,
                                                                        $user,
                                                                        $latestReceiptInfo[0]->getProductId(),
                                                                        'ios',
                                                                        $appleReceipt->local_price,
                                                                        $appleReceipt->local_currency,
                                                                    );
                                                                }
                                    }else{
                                        $this->error('No latestReceiptInfo for apple receipt: ' . $appleReceipt->id);
                                    }
                                }
                            } catch (Exception $exception) {
                                $this->info('Exception for apple receipt: ' . $appleReceipt->id);
                               // dd($appleStoreGateway);
                                $response = $appleStoreGateway->getResponse($appleReceipt->receipt);
                                $latestReceiptInfo = $response->getLatestReceiptInfo();

                                if (!empty($latestReceiptInfo)) {
                                    $user = $userProvider->getUserByEmail($appleReceipt->email);

                                                            if ($user) {
                                                                $revenueCatGateway->sendRequest(
                                                                    $appleReceipt->receipt,
                                                                    $user,
                                                                    $latestReceiptInfo[0]->getProductId(),
                                                                    'ios',
                                                                    $appleReceipt->local_price,
                                                                    $appleReceipt->local_currency,
                                                                );
                                                            }
                                }else{
                                  //  dd($response);
                                    $this->error('No latestReceiptInfo for apple receipt: ' . $appleReceipt->id);
                                }
                               // dd($response->getLatestReceiptInfo());
                                //continue;
                                //dd($exception);
                            }
                        }
                    }
            );

        $this->info('Finished SyncRevenueCat.');
    }
}
