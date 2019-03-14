<?php

namespace Railroad\Ecommerce\Listeners;

use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Throwable;

class UserDefaultPaymentMethodListener
{
    /**
     * @var EcommerceEntityManager
     */
    protected $entityManager;

    /**
     * @var PaymentMethodRepository
     */
    protected $paymentMethodRepository;

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @param EcommerceEntityManager $entityManager
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        PaymentMethodRepository $paymentMethodRepository,
        SubscriptionRepository $subscriptionRepository,
        UserProviderInterface $userProvider
    ) {
        $this->entityManager = $entityManager;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProvider = $userProvider;
    }

    /**
     * @param UserDefaultPaymentMethodEvent $event
     *
     * @throws Throwable
     */
    public function handle(UserDefaultPaymentMethodEvent $event)
    {
        $paymentMethod = $this->paymentMethodRepository
                ->find(
                    $event->getDefaultPaymentMethodId()
                );

        /**
         * @var $user \Railroad\Ecommerce\Contracts\UserInterface
         */
        $user = $this->userProvider->getUserById($event->getUserId());

        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb
            ->select('s')
            ->where($qb->expr()->eq('s.user', ':user'))
            ->setParameter('user', $user);

        $subscriptions = $qb->getQuery()->getResult();

        foreach ($subscriptions as $subscription) {
            /**
             * @var $subscription \Railroad\Ecommerce\Entities\Subscription
             */
            $subscription
                ->setPaymentMethod($paymentMethod);
        }

        $this->entityManager->flush();
    }
}
