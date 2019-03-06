<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use HttpResponseException;
use Illuminate\Http\Request;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\SubscriptionCreateRequest;
use Railroad\Ecommerce\Requests\SubscriptionUpdateRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\RenewalService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Permissions\Services\PermissionService;

class SubscriptionJsonController extends BaseController
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var EntityRepository
     */
    private $subscriptionRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * @var RenewalService
     */
    private $renewalService;

    /**
     * @var UserProductService
     */
    private $userProductService;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * SubscriptionJsonController constructor.
     *
     * @param EntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param \Railroad\Permissions\Services\PermissionService $permissionService
     * @param RenewalService $renewalService
     * @param UserProductService $userProductService
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        EntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService,
        RenewalService $renewalService,
        UserProductService $userProductService,
        UserProviderInterface $userProvider
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->subscriptionRepository = $this->entityManager
                ->getRepository(Subscription::class);
        $this->permissionService = $permissionService;
        $this->renewalService = $renewalService;
        $this->userProductService = $userProductService;
        $this->userProvider = $userProvider;
    }

    /**
     * Pull subscriptions paginated
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.subscriptions');

        $alias = 's';
        $first = ($request->get('page', 1) - 1) * $request->get('limit', 10);
        $orderBy = $request->get('order_by_column', 'created_at');
        if (
            strpos($orderBy, '_') !== false
            || strpos($orderBy, '-') !== false
        ) {
            $orderBy = camel_case($orderBy);
        }
        $orderBy = $alias . '.' . $orderBy;
        $brands = $request->get('brands', [ConfigService::$availableBrands]);

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->subscriptionRepository->createQueryBuilder($alias);

        $qb
            ->where($qb->expr()->in($alias . '.brand', ':brands'))
            ->andWhere(
                $qb->expr()->isNull($alias . '.deletedAt')
            )
            ->setMaxResults($request->get('limit', 10))
            ->setFirstResult($first)
            ->orderBy($orderBy, $request->get('order_by_direction', 'desc'))
            ->setParameter('brands', $brands);

        if ($request->has('user_id')) {

            $user = $this->userProvider->getUserById($request->has('user_id'));

            $qb
                ->andWhere(
                    $qb->expr()->eq($alias . '.user', ':user')
                )
                ->setParameter('user', $user);
        }

        $subscriptions = $qb->getQuery()->getResult();

        return ResponseService::subscription($subscriptions, $qb);
    }

    /**
     * Soft delete a subscription if exists in the database
     *
     * @param int $subscriptionId
     *
     * @return JsonResponse
     */
    public function delete($subscriptionId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.subscription');

        $subscription = $this->subscriptionRepository->find($subscriptionId);

        throw_if(
            is_null($subscription),
            new NotFoundException('Delete failed, subscription not found with id: ' . $subscriptionId)
        );

        $subscription->setDeletedAt(Carbon::now());
        $this->entityManager->flush();

        return ResponseService::empty(204);
    }

    /**
     * Store a subscription and return data in JSON format
     *
     * @param \Railroad\Ecommerce\Requests\SubscriptionCreateRequest $request
     *
     * @return JsonResponse
     *
     * @throws \Railroad\Permissions\Exceptions\NotAllowedException
     */
    public function store(SubscriptionCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.subscription');

        $subscription = new Subscription();

        $this->jsonApiHydrator->hydrate(
            $subscription,
            $request->onlyAllowed()
        );

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $this->userProductService->updateSubscriptionProducts($subscription);

        return ResponseService::subscription($subscription);
    }

    /**
     * Update a subscription and returned updated data in JSON format
     *
     * @param int $subscriptionId
     * @param \Railroad\Ecommerce\Requests\SubscriptionUpdateRequest $request
     *
     * @return JsonResponse
     */
    public function update($subscriptionId, SubscriptionUpdateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'edit.subscription');

        $subscription = $this->subscriptionRepository->find($subscriptionId);

        throw_if(
            is_null($subscription),
            new NotFoundException(
                'Update failed, subscription not found with id: ' .
                $subscriptionId
            )
        );

        $this->jsonApiHydrator->hydrate(
            $subscription,
            $request->onlyAllowed()
        );

        if (
            $subscription->getIsActive() === false &&
            !$subscription->getCanceledOn()
        ) {
            $subscription->setCanceledOn(Carbon::now());
        }

        if ($subscription->getTotalPrice()) {

            $subscription->setTotalPrice(
                round($subscription->getTotalPrice(), 2)
            );
        }

        $this->entityManager->flush();

        $this->userProductService->updateSubscriptionProducts($subscription);

        return ResponseService::subscription($subscription);
    }

    /**
     * @param int $subscriptionId
     *
     * @return mixed
     */
    public function renew($subscriptionId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'renew.subscription');

        $subscription = $this->subscriptionRepository->find($subscriptionId);

        throw_if(
            is_null($subscription),
            new NotFoundException(
                'Renew failed, subscription not found with id: ' .
                $subscriptionId
            )
        );

        $response = null;

        try {

            $this->renewalService->renew($subscription);

            $response = ResponseService::subscription($subscription);

        } catch (Exception $exception) {

            $response = response()->json(
                [
                    'errors' => [
                        'title' => 'Subscription renew failed.',
                        'detail' => $exception->getMessage(),
                    ]
                ],
                402
            );
        }

        $this->userProductService->updateSubscriptionProducts($subscription);

        return $response;
    }
}