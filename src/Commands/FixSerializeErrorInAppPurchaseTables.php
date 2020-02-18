<?php

namespace Railroad\Ecommerce\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;
use ReceiptValidator\GooglePlay\SubscriptionResponse;
use ReceiptValidator\iTunes\ProductionResponse;
use ReceiptValidator\iTunes\SandboxResponse;
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
     * RenewalDueSubscriptions constructor.
     *
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager)
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
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

        $this->info('Starting FixSerializeErrorInAppPurchaseTables.');

        $googleReceipts = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_google_receipts')
            ->get();

        foreach ($googleReceipts as $googleReceipt) {

            $unSerialized = null;
            $raw = $googleReceipt->raw_receipt_response;

            if (empty($raw) || base64_encode(base64_decode($raw, true)) === $raw) {
                $this->info('Already processed, skipping.');
                continue;
            } else {
                $this->info('Processing 1.');
            }

            try {
                $unSerialized = unserialize($raw);
            } catch (Exception $exception) {
                $raw = preg_replace_callback(
                    '!s:(\d+):"(.*?)";!',
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

            if (!empty($unSerialized)) {
                $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                    ->table('ecommerce_google_receipts')
                    ->where('id', $googleReceipt->id)
                    ->update(['raw_receipt_response' => base64_encode(serialize($unSerialized))]);
            }
        }

        $this->info('Google done.');

        $appleReceipts = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_apple_receipts')
            ->orderBy('id', 'desc')
            ->get();

        foreach ($appleReceipts as $appleReceipt) {

            $unSerialized = null;
            $raw = $appleReceipt->raw_receipt_response;

            if (empty($raw) || base64_encode(base64_decode($raw, true)) === $raw) {
                $this->info('Already processed, skipping.');
                continue;
            } else {
                $this->info('Processing 1.');
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
                    var_dump($exception->getMessage());
                    continue;
                }
            }

            if (!empty($unSerialized)) {
                $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                    ->table('ecommerce_apple_receipts')
                    ->where('id', $appleReceipt->id)
                    ->update(['raw_receipt_response' => base64_encode(serialize($unSerialized))]);
            }
        }

        // do a test to make sure they are all valid
        $googleReceipts = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_google_receipts')
            ->get()
            ->toArray();

        $appleReceipts = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_apple_receipts')
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();

        foreach (array_merge($googleReceipts, $appleReceipts) as $receipt) {
            $raw = $receipt->raw_receipt_response;

            if (!empty($raw)) {
                try {
                    $decoded = base64_decode($raw);

                    $unSerialized = unserialize(base64_decode($raw));
                } catch (Exception $exception) {
                    $this->info('Error unserializing (will delete the raw response column data): ' . $receipt->id);
                    $this->info('Bad: ' . $receipt->id);
                }

                if ($unSerialized instanceof SubscriptionResponse ||
                    $unSerialized instanceof ProductionResponse ||
                    $unSerialized instanceof SandboxResponse) {
                    continue;
                }

                $this->info('Invalid response receipt ID: ' . $receipt->id);
            }
        }

        $this->info('Finished FixSerializeErrorInAppPurchaseTables, all data is valid.');
    }
}