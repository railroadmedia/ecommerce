<?php

namespace Railroad\Ecommerce\Listeners;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;

class UserDefaultPaymentMethodListener
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function handle(UserDefaultPaymentMethodEvent $event)
    {
        $subscriptionRepository = $this->entityManager
                ->getRepository(Subscription::class);

        $paymentMethod = $this->entityManager
                ->getRepository(PaymentMethod::class)
                ->find(
                    $event->getDefaultPaymentMethodId()
                );

        $qb = $subscriptionRepository
                        ->createQueryBuilder('s');

        $qb
            ->select('s')
            ->where($qb->expr()->eq('IDENTITY(s.user)', ':id'))
            ->setParameter('id', $event->getUserId());

        $subscriptions = $qb->getQuery()->getResult();

        foreach ($subscriptions as $subscription) {
            $subscription
                ->setPaymentMethod($paymentMethod);
        }

        $this->entityManager->flush();
    }
}
