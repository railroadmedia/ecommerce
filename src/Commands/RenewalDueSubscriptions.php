<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Console\Command;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Events\Subscriptions\CommandSubscriptionRenewed;
use Railroad\Ecommerce\Events\Subscriptions\CommandSubscriptionRenewFailed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionDeactivated;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\RenewalService;
use Railroad\Ecommerce\Services\UserProductService;
use Throwable;

class RenewalDueSubscriptions extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'renewalDueSubscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renewal of due subscriptions.';

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var RenewalService
     */
    private $renewalService;

    /**
     * @var UserProductService
     */
    private $userProductService;

    const DEACTIVATION_NOTE = 'Ancient subscription. De-activated.';

    /**
     * RenewalDueSubscriptions constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param RenewalService $renewalService
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProductService $userProductService
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        RenewalService $renewalService,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService
    )
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->renewalService = $renewalService;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        $this->info('------------------Renewal Due Subscriptions command------------------');

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb->select(['s'])
            ->where(
                $qb->expr()
                    ->eq('s.brand', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->lt('s.paidUntil', ':now')
            )
            ->andWhere(
                $qb->expr()
                    ->gte('s.paidUntil', ':cutoff')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.isActive', ':active')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('s.canceledOn')
            )
            ->andWhere(
                $qb->expr()
                    ->in('s.type', ':types')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->isNull('s.totalCyclesDue'),
                        $qb->expr()
                            ->eq('s.totalCyclesDue', ':zero'),
                        $qb->expr()
                            ->lt('s.totalCyclesPaid', 's.totalCyclesDue')
                    )
            )
            ->setParameter('brand', config('ecommerce.brand'))
            ->setParameter('now', Carbon::now())
            ->setParameter(
                'cutoff',
                Carbon::now()
                    ->subMonths(
                        config('ecommerce.paypal.subscription_renewal_date') ?? 1
                    )
            )
            ->setParameter('active', true)
            ->setParameter('zero', 0)
            ->setParameter(
                'types',
                [
                    Subscription::TYPE_SUBSCRIPTION,
                    Subscription::TYPE_PAYMENT_PLAN,
                ]
            );

        $dueSubscriptions =
            $qb->getQuery()
                ->getResult();
        $this->info('Attempting to renew subscriptions. Count: ' . count($dueSubscriptions));

        foreach ($dueSubscriptions as $dueSubscription) {

            /** @var $dueSubscription Subscription */

            $oldSubscriptionState = clone($dueSubscription);

            try {

                $payment = $this->renewalService->renew($dueSubscription);

                if ($payment) {
                    /** @var $payment Payment */
                    event(new CommandSubscriptionRenewed($dueSubscription, $payment));
                }

            } catch (Throwable $throwable) {

                error_log($throwable);

                $payment = null;

                if ($throwable instanceof PaymentFailedException) {
                    /** @var $payment Payment */
                    $payment = $throwable->getPayment();
                }

                event(new CommandSubscriptionRenewFailed($dueSubscription, $oldSubscriptionState, $payment));

                $this->info(
                    'Failed to renew subscription ID: ' . $dueSubscription->getId() . ' - ' . $throwable->getMessage()
                );
            }
        }

        // deactivate ancient subscriptions

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb->select(['s'])
            ->where(
                $qb->expr()
                    ->eq('s.brand', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->lt('s.paidUntil', ':cutoff')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.isActive', ':active')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('s.canceledOn')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->isNull('s.totalCyclesDue'),
                        $qb->expr()
                            ->eq('s.totalCyclesDue', ':zero'),
                        $qb->expr()
                            ->lt('s.totalCyclesPaid', 's.totalCyclesDue')
                    )
            )
            ->andWhere(
                $qb->expr()
                    ->in('s.type', ':types')
            )
            ->setParameter('brand', config('ecommerce.brand'))
            ->setParameter(
                'cutoff',
                Carbon::now()
                    ->subMonths(
                        config('ecommerce.paypal.subscription_renewal_date') ?? 1
                    )
            )
            ->setParameter('active', true)
            ->setParameter('zero', 0)
            ->setParameter(
                'types',
                [
                    Subscription::TYPE_SUBSCRIPTION,
                    Subscription::TYPE_PAYMENT_PLAN,
                ]
            );

        $ancientSubscriptions =
            $qb->getQuery()
                ->getResult();

        $this->info('De-activate ancient subscriptions. Count: ' . count($ancientSubscriptions));

        foreach ($ancientSubscriptions as $ancientSubscription) {

            /** @var $ancientSubscription Subscription */

            $oldSubscriptionState = clone($ancientSubscription);

            $ancientSubscription->setIsActive(false);
            $ancientSubscription->setCanceledOn(Carbon::now());
            $ancientSubscription->setUpdatedAt(Carbon::now());

            event(new SubscriptionDeactivated($ancientSubscription, $oldSubscriptionState));
        }

        $this->entityManager->flush();

        $this->info('-----------------End Renewal Due Subscriptions command-----------------------');
    }
}