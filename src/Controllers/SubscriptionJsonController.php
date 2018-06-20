<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Responses\JsonResponse;

class SubscriptionJsonController extends Controller
{
    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * SubscriptionJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\SubscriptionRepository $subscriptionRepository
     */
    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /** Soft delete a subscription if exists in the database
     * @param integer $subscriptionId
     * @return \Railroad\Ecommerce\Responses\JsonResponse
     */
    public function delete($subscriptionId)
    {
        $subscription = $this->subscriptionRepository->read($subscriptionId);

        throw_if(is_null($subscription),
            new NotFoundException('Delete failed, subscription not found with id: ' . $subscriptionId)
        );

        $this->subscriptionRepository->delete($subscriptionId);

        return new JsonResponse(null, 204);
    }
}