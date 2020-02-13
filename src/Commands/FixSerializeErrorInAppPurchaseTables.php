<?php

namespace Railroad\Ecommerce\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;
use Throwable;

class FixSerializeErrorInAppPurchaseTables extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'FixSerializeErrorInAppPurchaseTables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'FixSerializeErrorInAppPurchaseTables';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var AppleStoreKitGateway
     */
    private $appleStoreKitGateway;

    /**
     * RenewalDueSubscriptions constructor.
     *
     * @param DatabaseManager $databaseManager
     * @param AppleStoreKitGateway $appleStoreKitGateway
     */
    public function __construct(
        DatabaseManager $databaseManager,
        AppleStoreKitGateway $appleStoreKitGateway
    )
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
        $this->appleStoreKitGateway = $appleStoreKitGateway;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        ini_set('xdebug.var_display_max_depth', '100');
        ini_set('xdebug.var_display_max_children', '256');
        ini_set('xdebug.var_display_max_data', '1024');

        // todo: after run delete all apple ones still not fixed
        $this->info('Starting FixSerializeErrorInAppPurchaseTables.');

//        $googleReceipts = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
//            ->table('ecommerce_google_receipts')
//            ->get();
//
//        foreach ($googleReceipts as $googleReceipt) {
//
//            $unSerialized = null;
//            $raw = $googleReceipt->raw_receipt_response;
//
//            if (empty($raw)) {
//                continue;
//            }
//
//            try {
//                $unSerialized = unserialize($raw);
//            } catch (Exception $exception) {
//                $raw = preg_replace_callback('!s:(\d+):"(.*?)";!', function ($match) {
//                    return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
//                }, $raw);
//
//                try {
//                    $unSerialized = unserialize($raw);
//                } catch (Exception $exception) {
//                    continue;
//                }
//            }
//
//            var_dump($googleReceipt);
//            var_dump($unSerialized);
//
//            $this->info('---------------------------------------------------------');
//
//            continue;
//
//            if (!empty($unSerialized)) {
//                $this->databaseManager->connection(config('ecommerce.database_connection_name'))
//                    ->table('ecommerce_google_receipts')
//                    ->where('id', $googleReceipt->id)
//                    ->update(['raw_receipt_response' => base64_encode(serialize($unSerialized))]);
//            }
//        }
//
//        die();


        $appleReceipts = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_apple_receipts')
            ->orderBy('id', 'desc')
            ->skip(150)
            ->limit(100)
            ->get();

        foreach ($appleReceipts as $appleReceipt) {

            try {
                $response = $this->appleStoreKitGateway->getResponse($appleReceipt->receipt);

                if (!empty($response->getLatestReceiptInfo()[0]) &&
                    !empty($response->getLatestReceiptInfo()[0]->getCancellationDate())) {
                    dd($response);
                }

                sleep(1);
                $this->info(1);
            } catch (Throwable $exception) {
                $this->info('error');
                continue;

            }

            continue;

//            dd($this->appleStoreKitGateway->getResponse($appleReceipt->receipt));

            $unSerialized = null;
            $raw = $appleReceipt->raw_receipt_response;

            if (empty($raw)) {
                continue;
            }

            try {
                $unSerialized = unserialize($raw);
            } catch (Exception $exception) {
                $raw = preg_replace_callback(
                    '!s:(\d+):"(.*?)";!s',
                    function ($match) {
                        return ($match[1] == strlen($match[2])) ? $match[0] :
                            's:' . strlen($match[2]) . ':"' . $match[2] . '";';
                    },
                    $raw
                );

                try {
                    $unSerialized = unserialize($raw);

                } catch (Exception $exception) {
                    continue;
                }
            }

            var_dump($appleReceipt);
            var_dump($unSerialized);

            $this->info('---------------------------------------------------------');

            continue;

            if (!empty($unSerialized)) {
                $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                    ->table('ecommerce_apple_receipts')
                    ->where('id', $appleReceipt->id)
                    ->update(['raw_receipt_response' => base64_encode(serialize($unSerialized))]);
            }
        }


        $this->info('Finished FixSerializeErrorInAppPurchaseTables.');
    }
}
