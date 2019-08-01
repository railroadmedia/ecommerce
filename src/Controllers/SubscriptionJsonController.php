<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionCreated;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionDeleted;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewFailed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Requests\FailedSubscriptionsRequest;
use Railroad\Ecommerce\Requests\SubscriptionCreateRequest;
use Railroad\Ecommerce\Requests\SubscriptionUpdateRequest;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\RenewalService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class SubscriptionJsonController extends Controller
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var PermissionService
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
     * @param EcommerceEntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param PermissionService $permissionService
     * @param RenewalService $renewalService
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProductService $userProductService
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService,
        RenewalService $renewalService,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService,
        UserProviderInterface $userProvider
    )
    {
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->permissionService = $permissionService;
        $this->renewalService = $renewalService;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
        $this->userProvider = $userProvider;
    }

    /**
     * Pull subscriptions paginated
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.subscriptions');

        $subscriptionsAndBuilder = $this->subscriptionRepository->indexByRequest($request);

        return ResponseService::subscription($subscriptionsAndBuilder->getResults(), $subscriptionsAndBuilder->getQueryBuilder())
            ->respond(200);
    }

    /**
     * Soft delete a subscription if exists in the database
     *
     * @param int $subscriptionId
     *
     * @return JsonResponse
     *
     * @throws Throwable
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

        event(new SubscriptionDeleted($subscription));

        $this->entityManager->flush();

        return ResponseService::empty(204);
    }

    /**
     * Store a subscription and return data in JSON format
     *
     * @param SubscriptionCreateRequest $request
     *
     * @return Fractal
     *
     * @throws Throwable
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

        event(new SubscriptionCreated($subscription));

        $this->userProductService->updateSubscriptionProducts($subscription);

        return ResponseService::subscription($subscription);
    }

    /**
     * Update a subscription and returned updated data in JSON format
     *
     * @param int $subscriptionId
     * @param SubscriptionUpdateRequest $request
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function update($subscriptionId, SubscriptionUpdateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'edit.subscription');

        $subscription = $this->subscriptionRepository->find($subscriptionId);

        throw_if(
            is_null($subscription),
            new NotFoundException(
                'Update failed, subscription not found with id: ' . $subscriptionId
            )
        );

        $oldSubscription = clone $subscription;

        $this->jsonApiHydrator->hydrate(
            $subscription,
            $request->onlyAllowed()
        );

        if ($subscription->getIsActive() === false && !$subscription->getCanceledOn()) {
            $subscription->setCanceledOn(Carbon::now());
        }

        if ($subscription->getTotalPrice()) {

            $subscription->setTotalPrice(
                round($subscription->getTotalPrice(), 2)
            );
        }

        event(new SubscriptionUpdated($oldSubscription, $subscription));

        $this->entityManager->flush();

        $this->userProductService->updateSubscriptionProducts($subscription);

        return ResponseService::subscription($subscription);
    }

    /**
     * @param int $subscriptionId
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function renew($subscriptionId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'renew.subscription');

        $subscription = $this->subscriptionRepository->find($subscriptionId);

        throw_if(
            is_null($subscription),
            new NotFoundException(
                'Renew failed, subscription not found with id: ' . $subscriptionId
            )
        );

        $oldSubscriptionState = clone($subscription);

        $response = $payment = null;

        try {

            $payment = $this->renewalService->renew($subscription);

            $response = ResponseService::subscription($subscription);

        } catch (Exception $exception) {

            $payment = null;

            if ($exception instanceof PaymentFailedException) {

                $payment = $exception->getPayment();
            }

            event(new SubscriptionRenewFailed($subscription, $oldSubscriptionState, $payment));

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

    /**
     * @param FailedSubscriptionsRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function failed(FailedSubscriptionsRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.failed-subscriptions');

        $subscriptionsAndBuilder = $this->subscriptionRepository->indexFailedByRequest($request);

        return ResponseService::subscription($subscriptionsAndBuilder->getResults(), $subscriptionsAndBuilder->getQueryBuilder())
            ->respond(200);
    }
}
