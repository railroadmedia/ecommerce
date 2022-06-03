<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;
use Railroad\Ecommerce\Repositories\AppleReceiptRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Throwable;

class VerifyAppleNotifications extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'VerifyAppleNotifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify if apple subscriptions are correctly synchronized based on Apple notifications';

    /**
     * Execute the console command.
     *
     * @throws GuzzleException
     * @throws Throwable
     */
    public function handle(
        SubscriptionRepository $subscriptionRepository,
        AppleReceiptRepository $appleReceiptRepository,
        AppleStoreKitGateway $appleStoreKitGateway
    ) {
        $this->info('------------------Process Apple Expired Subscriptions command------------------');
        $notifications =
            $appleReceiptRepository->createQueryBuilder('s')
                ->where('s.requestType = :type')
                ->andWhere('s.notificationRequestData is not null')
                ->setParameter('type', 'notification')
                ->getQuery()
                ->getResult();
        foreach ($notifications as $notification) {
            $notificationRequestData = unserialize(base64_decode($notification->getNotificationRequestData()));
            $latestReceiptInfo = $notificationRequestData['unified_receipt']['latest_receipt_info'];
            $latestPurchasedInfo = $latestReceiptInfo[0];
            $subscription =
                $subscriptionRepository->createQueryBuilder('s')
                    ->where('s.id = :id')
                    ->setParameter(
                        'id',
                        $notification->getSubscription()
                            ->getId()
                    )
                    ->getQuery()
                    ->getOneOrNullResult();
            if ($subscription->getPaidUntil() == Carbon::parse($latestPurchasedInfo['expires_date'])) {
                $this->info(
                    'Apple Receipt id: ' .
                    $notification->getId() .
                    ' Subscription id: ' .
                    $subscription->getId() .
                    ' Paid until - OK'
                );
            } else {
                $expireDateFromReceipt =
                    ($appleStoreKitGateway->getResponse($notification->getReceipt())
                        ->getLatestReceiptInfo()[0]->getExpiresDate());
                if ($subscription->getPaidUntil() == Carbon::parse($expireDateFromReceipt)) {
                    $this->info(
                        'Apple Receipt id: ' .
                        $notification->getId() .
                        ' Subscription id: ' .
                        $subscription->getId() .
                        ' Paid until - OK'
                    );
                } else {
                    $this->error('Apple notification id: ' . $notification->getId() . '   not synchronized');
                }
            }
        }

        $this->info('-----------------End Process Apple Expired Subscriptions command-----------------------');
    }
}
