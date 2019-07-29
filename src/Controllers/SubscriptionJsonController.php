<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\ActionLog\Services\ActionLogService;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionCreated;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionDeleted;
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
     * @var ActionLogService
     */
    private $actionLogService;

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
     * @param ActionLogService $actionLogService
     * @param EcommerceEntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param PermissionService $permissionService
     * @param RenewalService $renewalService
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProductService $userProductService
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        ActionLogService $actionLogService,
        EcommerceEntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService,
        RenewalService $renewalService,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService,
        UserProviderInterface $userProvider
    )
    {
        $this->actionLogService = $actionLogService;
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

        /** @var $currentUser User */
        $currentUser = $this->userProvider->getCurrentUser();

        $brand = $subscription->getBrand();
        $actor = $currentUser->getEmail();
        $actorId = $currentUser->getId();
        $actorRole = $currentUser->getId() == $subscription->getUser()->getId() ?
                        ActionLogService::ROLE_USER:
                        ActionLogService::ROLE_ADMIN;

        try {

            $payment = $this->renewalService->renew($subscription);

            $response = ResponseService::subscription($subscription);

            $this->actionLogService->recordAction($brand, Subscription::ACTION_RENEW, $subscription, $actor, $actorId, $actorRole);
            $this->actionLogService->recordAction($brand, ActionLogService::ACTION_CREATE, $payment, $actor, $actorId, $actorRole);

        } catch (Exception $exception) {

            if ($exception instanceof PaymentFailedException) {

                // if a payment record/entity was created

                $this->actionLogService->recordAction(
                    $brand,
                    Payment::ACTION_FAILED_RENEW,
                    $exception->getPayment(),
                    $actor,
                    $actorId,
                    $actorRole
                );
            }

            if ($subscription->getNote() == RenewalService::DEACTIVATION_MESSAGE &&
                $subscription->getIsActive() != $oldSubscriptionState->getIsActive()) {

                // if subscription was deactivated in current request

                $this->actionLogService->recordAction(
                    $brand,
                    Subscription::ACTION_DEACTIVATED,
                    $subscription,
                    $actor,
                    $actorId,
                    $actorRole
                );
            }

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
