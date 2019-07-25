<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use DateTimeInterface;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ActionLogService;
use Railroad\Ecommerce\Services\RenewalService;
use Railroad\Ecommerce\Services\UserProductService;
use Throwable;

class RenewalDueSubscriptions extends \Illuminate\Console\Command
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
     * @var ActionLogService
     */
    private $actionLogService;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
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
     * @param ActionLogService $actionLogService
     * @param EcommerceEntityManager $entityManager
     * @param RenewalService $renewalService
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProductService $userProductService
     */
    public function __construct(
        ActionLogService $actionLogService,
        EcommerceEntityManager $entityManager,
        RenewalService $renewalService,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService
    )
    {
        parent::__construct();

        $this->actionLogService = $actionLogService;
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
         * @var $qb \Doctrine\ORM\QueryBuilder
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

            $oldSubscriptionState = clone($dueSubscription);
            $brand = $dueSubscription->getBrand();

            try {

                $payment = $this->renewalService->renew($dueSubscription);

                $this->actionLogService->recordCommandAction(
                    $brand,
                    Subscription::ACTION_RENEW,
                    $dueSubscription
                );

                $this->actionLogService->recordCommandAction(
                    $brand,
                    ActionLogService::ACTION_CREATE,
                    $payment
                );

            } catch (Throwable $throwable) {

                if ($throwable instanceof PaymentFailedException) {

                    // if a payment record/entity was created

                    $this->actionLogService->recordCommandAction(
                        $brand,
                        Payment::ACTION_FAILED_RENEW,
                        $throwable->getPayment()
                    );
                }

                if ($dueSubscription->getNote() == RenewalService::DEACTIVATION_MESSAGE &&
                    $dueSubscription->getIsActive() != $oldSubscriptionState->getIsActive()) {

                    // if subscription was deactivated in current iteration

                    $this->actionLogService->recordCommandAction(
                        $brand,
                        Subscription::ACTION_DEACTIVATED,
                        $dueSubscription
                    );
                }

                $this->info(
                    'Failed to renew subscription ID: ' . $dueSubscription->getId() . ' - ' . $throwable->getMessage()
                );
            }
        }

        // deactivate ancient subscriptions

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
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
            $ancientSubscription->setIsActive(false);
            $ancientSubscription->setNote(self::DEACTIVATION_NOTE);
            $ancientSubscription->setCanceledOn(Carbon::now());
            $ancientSubscription->setUpdatedAt(Carbon::now());

            $this->actionLogService->recordCommandAction(
                $ancientSubscription->getBrand(),
                Subscription::ACTION_DEACTIVATED,
                $ancientSubscription
            );
        }

        $this->entityManager->flush();

        $this->info('-----------------End Renewal Due Subscriptions command-----------------------');
    }
}