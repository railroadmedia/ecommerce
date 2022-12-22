<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Railroad\Ecommerce\Entities\GoogleReceipt;
use Railroad\Ecommerce\Gateways\GooglePlayStoreGateway;
use Railroad\Ecommerce\Repositories\GoogleReceiptRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\GooglePlayStoreService;
use Throwable;

class VerifyGoogleNotifications extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'VerifyGoogleNotifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify if Google subscriptions are correctly synchronized based on Google notifications';

    /**
     * Execute the console command.
     *
     * @throws GuzzleException
     * @throws Throwable
     */
    public function handle(
        SubscriptionRepository $subscriptionRepository,
        GoogleReceiptRepository $googleReceiptRepository,
        GooglePlayStoreService $googlePlayStoreService,
        GooglePlayStoreGateway $googlePlayStoreGateway
    ) {
        $this->info('------------------Process Google Expired Subscriptions command------------------');
        $subscriptions =
            $subscriptionRepository->createQueryBuilder('s')
                ->where('s.type = :type')
                ->andWhere('s.brand = :brand')
                ->andWhere('s.canceledOn is null')
                ->andWhere('s.externalAppStoreId is not null')
                ->andWhere('s.paidUntil < :now')
                ->andWhere('s.paidUntil > :lastDate')
                ->setParameter('type', 'google_subscription')
                ->setParameter('brand', 'pianote')
                ->setParameter(
                    'now',
                    Carbon::now()
                        ->toDateString()
                )
                ->setParameter(
                    'lastDate',
                    Carbon::now()
                        ->subMonth()
                        ->toDateString()
                )
                ->getQuery()
                ->getResult();

        $cancelled = 0;
        $ren = 0;
        foreach ($subscriptions as $subscription) {
            $product =
                ($subscription->getIntervalType() == 'month') ? 'pianote_app_1_month_2021' : 'pianote_app_1_year_2021';

            $googleSubscriptionResponse = $googlePlayStoreGateway->getResponse(
                'com.pianote2',
                $product,
                $subscription->getExternalAppStoreId()
            );

            $oldReceipt =
                $googleReceiptRepository->createQueryBuilder('gp')
                    ->where('gp.purchaseToken  = :purchase_token')
                    ->andWhere('gp.purchaseType = :purchase_type')
                    ->setParameter('purchase_token', $subscription->getExternalAppStoreId())
                    ->setParameter('purchase_type', GoogleReceipt::GOOGLE_SUBSCRIPTION_PURCHASE)
                    ->getQuery()
                    ->getResult();

            $receipt = new GoogleReceipt();
            $receipt->setPurchaseToken($subscription->getExternalAppStoreId());
            $receipt->setPackageName('com.pianote2');
            $receipt->setProductId($product);
            $receipt->setRequestType(GoogleReceipt::GOOGLE_NOTIFICATION_REQUEST_TYPE);
            $receipt->setBrand('pianote');

            if (!empty($oldReceipt)) {
                if ($oldReceipt[0]->getLocalCurrency()) {
                    $receipt->setLocalCurrency($oldReceipt[0]->getLocalCurrency());
                }
                if ($oldReceipt[0]->getLocalPrice()) {
                    $receipt->setLocalPrice($oldReceipt[0]->getLocalPrice());
                }
            }

            if (!empty($googleSubscriptionResponse->getUserCancellationTimeMillis()) ||
                !empty($googleSubscriptionResponse->getCancelReason())) {
                $notificationType = GoogleReceipt::GOOGLE_CANCEL_NOTIFICATION_TYPE;
                $this->info(
                    'Subscription was cancelled - subscription id:: '.
                    $subscription->getId().
                    '        cancellation date:: '.
                    Carbon::createFromTimestampMs(
                        $googleSubscriptionResponse->getUserCancellationTimeMillis()
                    )
                );
                $cancelled++;
            } else {
                $notificationType = GoogleReceipt::GOOGLE_RENEWAL_NOTIFICATION_TYPE;
                if (Carbon::createFromTimestampMs(
                        $googleSubscriptionResponse->getExpiryTimeMillis()
                    ) > Carbon::now()) {
                    $this->info(
                        'Should be renew - subscription id:: '.
                        $subscription->getId().
                        '                   user_id:::'.
                        $subscription->getUser()
                            ->getId()
                    );
                    $ren++;
                } else {
                    $this->info(
                        'No renewed and no cancelled - subscription id ::  '.
                        $subscription->getId().
                        '       purchase_token:: '.
                        $subscription->getExternalAppStoreId()
                    );
                }
            }

            $receipt->setNotificationType($notificationType);

            try {
                $googlePlayStoreService->processNotification($receipt, $subscription);
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }

        $this->info('Cancelled:: '.$cancelled);
        $this->info('Renewed:: '.$ren);
        error_log('---------------------- End VerifyGoogleNotifications command ----------------------------'.'Cancelled:: '.$cancelled.' --------------------- '.'Renewed:: '.$ren);
        $this->info('-----------------End Process Expired Subscriptions command-----------------------');
    }
}
