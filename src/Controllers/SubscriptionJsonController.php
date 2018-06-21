<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Responses\JsonResponse;
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
    public function __construct(SubscriptionRepository$subscriptionRepository, PermissionService $permissionService)
    {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->permissionService      = $permissionService;
    }

    /** Soft delete a subscription if exists in the database
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