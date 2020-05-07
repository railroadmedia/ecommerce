<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;
use Railroad\Ecommerce\Repositories\AppleReceiptRepository;
use Railroad\Ecommerce\Services\AppleStoreKitService;
use Throwable;

class MobileAppGoogleAppleHelper extends Command
{
    /**
     * The console command name.
     *
     * MobileAppGoogleAppleHelper apple fetchReceipt 8hw4e9h234hj30shg0ws3eg9wshg4ehsg
     * MobileAppGoogleAppleHelper google fetchReceipt sd0s09hg3w29hg0pshhzadhgdp4h
     *
     * @var string
     */
    protected $signature = 'MobileAppGoogleAppleHelper {googleOrApple} {commandName} {receiptString}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a tool for requesting data from the Google and Apple IAP APIs. Useful for debugging.';

    /**
     * @var AppleStoreKitGateway
     */
    private $appleStoreKitGateway;

    /**
     * @var AppleStoreKitService
     */
    private $appleStoreKitService;

    /**
     * @var AppleReceiptRepository
     */
    private $appleReceiptRepository;

    /**
     * MobileAppGoogleAppleHelper constructor.
     *
     * @param AppleStoreKitGateway $appleStoreKitGateway
     * @param AppleStoreKitService $appleStoreKitService
     * @param AppleReceiptRepository $appleReceiptRepository
     */
    public function __construct(
        AppleStoreKitGateway $appleStoreKitGateway,
        AppleStoreKitService $appleStoreKitService,
        AppleReceiptRepository $appleReceiptRepository
    )
    {
        parent::__construct();

        $this->appleStoreKitGateway = $appleStoreKitGateway;
        $this->appleStoreKitService = $appleStoreKitService;
        $this->appleReceiptRepository = $appleReceiptRepository;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        ini_set('xdebug.var_display_max_depth', '25');
        ini_set('xdebug.var_display_max_children', '1000');
        ini_set('xdebug.var_display_max_data', '5000');

        if ($this->argument('googleOrApple') == 'apple') {
            $this->apple();
        } elseif ($this->argument('googleOrApple') == 'google') {
            $this->google();
        } else {
            $this->info('First argument must be "google" or "apple".');
            $this->info('Example: php artisan MobileAppGoogleAppleHelper apple fetchReceipt 9h49ghhasfh0rhah');
        }

    }

    private function apple()
    {
        if ($this->argument('commandName') == 'fetchReceipt') {

            $response = $this->appleStoreKitGateway->getResponse($this->argument('receiptString'));

            $this->info("\n\n\n----------------------------------------------------");
            $this->info('Apple receipt response dump:');

            var_dump($response);

        } elseif ($this->argument('commandName') == 'resyncReceipt') {

            $receiptEntity = $this->appleReceiptRepository->findOneBy(['receipt' => $this->argument('receiptString')]);
            $response = $this->appleStoreKitGateway->getResponse($this->argument('receiptString'));

            if (empty($receiptEntity)) {
                $this->info('Could not find that receipt row in the database.');

                return;
            }

            $this->appleStoreKitService->syncPurchasedItems($response, $receiptEntity);
            $this->info('Synced successfully.');

        }
    }

    private function google()
    {

    }
}
