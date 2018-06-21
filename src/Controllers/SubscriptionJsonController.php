<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Responses\JsonPaginatedResponse;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Permissions\Services\PermissionService;

class SubscriptionJsonController extends Controller
{
    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * SubscriptionJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\SubscriptionRepository $subscriptionRepository
     * @param \Railroad\Permissions\Services\PermissionService        $permissionService
     */
    public function __construct(SubscriptionRepository $subscriptionRepository, PermissionService $permissionService)
    {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->permissionService      = $permissionService;
    }

    /** Pull subscriptions paginated
     * @param \Illuminate\Http\Request $request
     * @return \Railroad\Ecommerce\Responses\JsonPaginatedResponse
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.subscriptions');
        $subscriptions = $this->subscriptionRepository->query()
            ->whereIn('brand', $request->get('brand', [ConfigService::$brand]));

        if($request->has('user_id'))
        {
            $subscriptions = $subscriptions->where('user_id', $request->get('user_id'));
        }
        $subscriptions = $subscriptions->limit($request->get('limit', 100))
            ->skip(($request->get('page', 1) - 1) * $request->get('limit', 100))
            ->orderBy($request->get('order_by_column', 'created_on'), $request->get('order_by_direction', 'desc'))
            ->get();

        $subscriptionsCount = $this->subscriptionRepository->query()
            ->whereIn('brand', $request->get('brand', [ConfigService::$brand]));
        if($request->has('user_id'))
        {
            $subscriptionsCount = $subscriptionsCount->where('user_id', $request->get('user_id'));
        }
        $subscriptionsCount = $subscriptionsCount->count();

        return new JsonPaginatedResponse(
            $subscriptions,
            $subscriptionsCount,
            200);
    }

    /** Soft delete a subscription if exists in the database
     *
     * @param integer $subscriptionId
     * @return \Railroad\Ecommerce\Responses\JsonResponse
     */
    public function delete($subscriptionId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.subscription');

        $subscription = $this->subscriptionRepository->read($subscriptionId);

        throw_if(is_null($subscription),
            new NotFoundException('Delete failed, subscription not found with id: ' . $subscriptionId)
        );

        $this->subscriptionRepository->delete($subscriptionId);

        return new JsonResponse(null, 204);
    }
}