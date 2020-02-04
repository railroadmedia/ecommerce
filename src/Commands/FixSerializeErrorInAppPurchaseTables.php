<?php

namespace Railroad\Ecommerce\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
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
    public function __construct(
        DatabaseManager $databaseManager
    ) {
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
        // todo: after run delete all apple ones still not fixed
        $this->info('Starting FixSerializeErrorInAppPurchaseTables.');

        $googleReceipts = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_google_receipts')
            ->get();

        foreach ($googleReceipts as $googleReceipt) {

            $unSerialized = null;
            $raw = $googleReceipt->raw_receipt_response;

            if (empty($raw)) {
                continue;
            }

            try {
                $unSerialized = unserialize($raw);
            } catch (Exception $exception) {
                $raw = preg_replace_callback('!s:(\d+):"(.*?)";!', function ($match) {
                    return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
                }, $raw);

                try {
                    $unSerialized = unserialize($raw);
                } catch (Exception $exception) {
                    continue;
                }
            }

            var_dump($googleReceipt);
            var_dump($unSerialized);

            $this->info('---------------------------------------------------------');

            continue;

            if (!empty($unSerialized)) {
                $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                    ->table('ecommerce_google_receipts')
                    ->where('id', $googleReceipt->id)
                    ->update(['raw_receipt_response' => base64_encode(serialize($unSerialized))]);
            }
        }

        die();

        $appleReceipts = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_apple_receipts')
            ->get();

        foreach ($appleReceipts as $appleReceipt) {

            $unSerialized = null;
            $raw = $appleReceipt->raw_receipt_response;

            if (empty($raw)) {
                continue;
            }

            try {
                $unSerialized = unserialize($raw);
            } catch (Exception $exception) {
                $raw = preg_replace_callback('!s:(\d+):"(.*?)";!s', function ($match) {
                    return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
                }, $raw);

                try {
                    $unSerialized = unserialize($raw);

                } catch (Exception $exception) {
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


        $this->info('Finished FixSerializeErrorInAppPurchaseTables.');
    }
}
