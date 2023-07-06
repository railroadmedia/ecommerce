<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\GoogleReceipt;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;
use Railroad\Ecommerce\Gateways\GooglePlayStoreGateway;
use Railroad\Ecommerce\Repositories\AppleReceiptRepository;
use Railroad\Ecommerce\Repositories\GoogleReceiptRepository;
use Railroad\Ecommerce\Services\AppleStoreKitService;
use Railroad\Ecommerce\Services\GooglePlayStoreService;
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
    protected $signature = 'MobileAppGoogleAppleHelper {googleOrApple} {commandName} {receiptString} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a tool for requesting data from the Google and Apple IAP APIs. Useful for debugging.';

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(
        AppleStoreKitGateway $appleStoreKitGateway,
        AppleStoreKitService $appleStoreKitService,
        AppleReceiptRepository $appleReceiptRepository,
        GooglePlayStoreGateway $googlePlayStoreGateway,
        GooglePlayStoreService $googlePlayStoreService,
        GoogleReceiptRepository $googleReceiptRepository,
        UserProviderInterface $userProvider
    ) {
        ini_set('xdebug.var_display_max_depth', '25');
        ini_set('xdebug.var_display_max_children', '1000');
        ini_set('xdebug.var_display_max_data', '5000');

        if ($this->argument('googleOrApple') == 'apple') {
            $this->apple(
                $appleStoreKitGateway,
                $appleStoreKitService,
                $appleReceiptRepository,
                $userProvider
            );
        } elseif ($this->argument('googleOrApple') == 'google') {
            $this->google(
                $googlePlayStoreGateway,
                $googlePlayStoreService,
                $googleReceiptRepository,
                $userProvider

            );
        } else {
            $this->info('First argument must be "google" or "apple".');
            $this->info('Example: php artisan MobileAppGoogleAppleHelper apple fetchReceipt 9h49ghhasfh0rhah');
        }
    }

    private function apple(
        AppleStoreKitGateway $appleStoreKitGateway,
        AppleStoreKitService $appleStoreKitService,
        AppleReceiptRepository $appleReceiptRepository,
        UserProviderInterface $userProvider
    ) {
        if ($this->argument('commandName') == 'fetchReceipt') {
            $response = $appleStoreKitGateway->getResponse($this->argument('receiptString'));

            $this->info("\n\n\n----------------------------------------------------");
            $this->info('Apple receipt response dump:');

            var_dump($response);
        } elseif ($this->argument('commandName') == 'resyncReceipt') {
            $receiptEntity = $appleReceiptRepository->findOneBy(['receipt' => $this->argument('receiptString')]);
            $response = $appleStoreKitGateway->getResponse($this->argument('receiptString'));

            // create user if doesn't exist
            $user = $userProvider->getUserByEmail($receiptEntity->getEmail());

            if (empty($user)) {
                $randomPassword = $this->generatePassword();
                $user = $userProvider->createUser($receiptEntity->getEmail(), $randomPassword);

                $this->info('User created. Email: ' . $user->getEmail() . ' - Password: ' . $randomPassword);
            } else {
                $this->info('Found user ' . $user->getId());
            }

            if (empty($receiptEntity)) {
                $this->info('Could not find that receipt row in the database.');

                return;
            }

            $appleStoreKitService->syncPurchasedItems($response, $receiptEntity, $user);

            $this->info('Synced successfully.');
        }
    }

    private function google( $googlePlayStoreGateway,
        $googlePlayStoreService,
        $googleReceiptRepository,
        $userProvider)
    {
        if ($this->argument('commandName') == 'fetchReceipt') {
            $receiptEntities =  $googleReceiptRepository->createQueryBuilder('gp')
                ->where('gp.purchaseToken  = :purchase_token')
                ->setParameter('purchase_token', $this->argument('receiptString'))
                ->getQuery()
                ->getResult();
            $receiptEntity = \Arr::first($receiptEntities);
            if(!$receiptEntity){
                $response = $googlePlayStoreGateway->getResponse('com.musoraapp', 'musora_monthly_subscription',$this->argument('receiptString'));

            }else {
                $response =
                    $googlePlayStoreGateway->getResponse(
                        $receiptEntity->getPackageName(),
                        $receiptEntity->getProductId(),
                        $this->argument('receiptString')
                    );
            }
            $this->info("\n\n\n----------------------------------------------------");
            $this->info('Google receipt response dump:');

            var_dump($response);
        } elseif ($this->argument('commandName') == 'resyncReceipt') {
            $receiptEntities =  $googleReceiptRepository->createQueryBuilder('gp')
                ->where('gp.purchaseToken  = :purchase_token')
                ->setParameter('purchase_token', $this->argument('receiptString'))
                ->getQuery()
                ->getResult();
            $receiptEntity = \Arr::first($receiptEntities);
            $response = $googlePlayStoreGateway->getResponse($receiptEntity->getPackageName(), $receiptEntity->getProductId(),$this->argument('receiptString'));

            // create user if doesn't exist
            $user = $userProvider->getUserByEmail($receiptEntity->getEmail());

            if (empty($user)) {
                $randomPassword = $this->generatePassword();
                $user = $userProvider->createUser($receiptEntity->getEmail(), $randomPassword);

                $this->info('User created. Email: ' . $user->getEmail() . ' - Password: ' . $randomPassword);
            } else {
                $this->info('Found user ' . $user->getId());
            }

            if (empty($receiptEntity)) {
                $this->info('Could not find that receipt row in the database.');

                return;
            }

            $googlePlayStoreService->syncPurchasedItems($receiptEntity, $response, $user);

            $this->info('Synced successfully.');
        }
    }

    private function generatePassword($len = 10)
    {
        $charRange = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789!@#$%^&*()";
        $pw = null;
        $length = strlen($charRange);
        for ($x = 0; $x < $len; $x++) {
            $n = rand(0, $length);
            $pw .= $charRange[$n];
        }
        return $pw;
    }
}
