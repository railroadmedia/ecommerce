<?php

namespace Railroad\Ecommerce\Listeners;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Contracts\UserInterface;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

class UserDefaultPaymentMethodListener
{
    /**
     * @var EcommerceEntityManager
     */
    protected $entityManager;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    public function __construct(
        EcommerceEntityManager $entityManager,
        UserProviderInterface $userProvider
    ) {
        $this->entityManager = $entityManager;
        $this->userProvider = $userProvider;
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

        /**
         * @var $user \Railroad\Ecommerce\Contracts\UserInterface
         */
        $user = $this->userProvider->getUserById($event->getUserId());

        $qb = $subscriptionRepository
                        ->createQueryBuilder('s');

        $qb
            ->select('s')
            ->where($qb->expr()->eq('s.user', ':user'))
            ->setParameter('user', $user);

        $subscriptions = $qb->getQuery()->getResult();

        foreach ($subscriptions as $subscription) {
            $subscription
                ->setPaymentMethod($paymentMethod);
        }

        $this->entityManager->flush();
    }
}
