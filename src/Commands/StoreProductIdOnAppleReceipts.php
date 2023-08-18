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

class StoreProductIdOnAppleReceipts extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'StoreProductIdOnAppleReceipts {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'StoreProductIdOnAppleReceipts';

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

        $appleReceipts =
            $databaseManager->connection(config('ecommerce.database_connection_name'))
                ->table('ecommerce_apple_receipts')
                ->orderBy('id', 'desc')
                ->whereNull('product_id')
                ->whereNotNull('subscription_id')
                ->where('request_type', '=', 'mobile')
             //   ->where('id', '=', $this->argument('id'))
                ->chunk(
                    500,
                    function (Collection $appleReceipts) use ( $databaseManager, $appleStoreGateway) {

                        foreach ($appleReceipts as $appleReceipt) {
                            $this->info('Processing apple receipt: ' . $appleReceipt->id);
                            try {
                                $notificationRequestData =
                                    unserialize(base64_decode($appleReceipt->raw_receipt_response, true));

                                if ($notificationRequestData !== false && $notificationRequestData !== null) {
                                    $latestReceiptInfo = $notificationRequestData->getLatestReceiptInfo();
                                    if (!empty($latestReceiptInfo)) {
                                        $databaseManager->connection(config('ecommerce.database_connection_name'))
                                            ->table('ecommerce_apple_receipts')
                                            ->where('id', '=', $appleReceipt->id)
                                            ->update(['product_id' => $latestReceiptInfo[0]->getProductId()]);
                                    }else{
                                        $this->error('raw_receipt_response exists, but No latestReceiptInfo for apple receipt: ' . $appleReceipt->id);
                                        $response = $appleStoreGateway->getResponse($appleReceipt->receipt);

                                        $latestReceiptInfo = $response->getLatestReceiptInfo();
                                        if (!empty($latestReceiptInfo)) {
                                            $databaseManager->connection(config('ecommerce.database_connection_name'))
                                                ->table('ecommerce_apple_receipts')
                                                ->where('id', '=', $appleReceipt->id)
                                                ->update(['product_id' => $latestReceiptInfo[0]->getProductId()]);
                                        }else{
                                            $this->error('No latestReceiptInfo for apple receipt: ' . $appleReceipt->id);
                                        }
                                    }

                                }else{
                                    $response = $appleStoreGateway->getResponse($appleReceipt->receipt);
                                    $latestReceiptInfo = $response->getLatestReceiptInfo();
                                    if (!empty($latestReceiptInfo)) {

                                        $databaseManager->connection(config('ecommerce.database_connection_name'))
                                            ->table('ecommerce_apple_receipts')
                                            ->where('id', '=', $appleReceipt->id)
                                            ->update(['product_id' => $latestReceiptInfo[0]->getProductId()]);
                                    }else{
                                        $this->error('No latestReceiptInfo for apple receipt: ' . $appleReceipt->id);
                                    }
                                }
                            } catch (Exception $exception) {
                                $this->info('Exception for apple receipt: ' . $appleReceipt->id);

                                $response = $appleStoreGateway->getResponse($appleReceipt->receipt);
                                $latestReceiptInfo = $response->getLatestReceiptInfo();

                                if (!empty($latestReceiptInfo)) {

                                    $databaseManager->connection(config('ecommerce.database_connection_name'))
                                        ->table('ecommerce_apple_receipts')
                                        ->where('id', '=', $appleReceipt->id)
                                        ->update(['product_id' => $latestReceiptInfo[0]->getProductId()]);
                                }else{
                                  //  dd($response);
                                    $this->error('No latestReceiptInfo for apple receipt: ' . $appleReceipt->id);
                                }
                               // dd($response->getLatestReceiptInfo());

                            }
                        }
                    }
            );

        $this->info('Finished SyncRevenueCat.');
    }
}
