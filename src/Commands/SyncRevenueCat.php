<?php

namespace Railroad\Ecommerce\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Gateways\RevenueCatGateway;
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
    protected $name = 'SyncRevenueCat';

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
        UserProviderInterface $userProvider
    ) {
        ini_set('xdebug.var_display_max_depth', '100');
        ini_set('xdebug.var_display_max_children', '256');
        ini_set('xdebug.var_display_max_data', '1024');

        $this->info('Starting SyncRevenueCat command.');

        $googleReceipts =
            $databaseManager->connection(config('ecommerce.database_connection_name'))
                ->table('ecommerce_google_receipts')
                ->get();

        foreach ($googleReceipts as $googleReceipt) {
            $user = $userProvider->getUserByEmail($googleReceipt->email);

            $revenueCatGateway->sendRequest(
                $googleReceipt->purchase_token,
                $user,
                $googleReceipt->product_id,
                'android'
            );
        }

        $this->info('Google done.');

        $appleReceipts =
            $databaseManager->connection(config('ecommerce.database_connection_name'))
                ->table('ecommerce_apple_receipts')
                ->orderBy('id', 'desc')
                ->get();

        foreach ($appleReceipts as $appleReceipt) {
            $notificationRequestData = unserialize(base64_decode($appleReceipt->raw_receipt_response, true));
            if ($notificationRequestData !== false) {
                $latestReceiptInfo = $notificationRequestData->getLatestReceiptInfo();
                if (!empty($latestReceiptInfo)) {
                    $latestPurchasedInfo = $latestReceiptInfo[0];

                    $user = $userProvider->getUserByEmail($appleReceipt->email);

                    $revenueCatGateway->sendRequest(
                        $appleReceipt->receipt,
                        $user,
                        $latestPurchasedInfo->getProductId(),
                        'ios'
                    );
                }
            }
        }

        $this->info('Finished SyncRevenueCat.');
    }
}