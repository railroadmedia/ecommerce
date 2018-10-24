<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Exception;
use HttpResponseException;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Requests\SubscriptionCreateRequest;
use Railroad\Ecommerce\Requests\SubscriptionUpdateRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\RenewalService;
use Railroad\Permissions\Services\PermissionService;

class SubscriptionJsonController extends BaseController
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
     * @var RenewalService
     */
    private $renewalService;

    /**
     * SubscriptionJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\SubscriptionRepository $subscriptionRepository
     * @param \Railroad\Permissions\Services\PermissionService $permissionService
     */
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        PermissionService $permissionService,
        RenewalService $renewalService
    ) {
        parent::__construct();

        $this->subscriptionRepository = $subscriptionRepository;
        $this->permissionService = $permissionService;
        $this->renewalService = $renewalService;
    }

    /** Pull subscriptions paginated
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.subscriptions');
        $subscriptions = $this->subscriptionRepository->query()
            ->whereIn('brand', $request->get('brands', [ConfigService::$brand]));

        if ($request->has('user_id')) {
            $subscriptions = $subscriptions->where('user_id', $request->get('user_id'));
        }
        $subscriptions = $subscriptions->limit($request->get('limit', 100))
            ->skip(($request->get('page', 1) - 1) * $request->get('limit', 100))
            ->orderBy($request->get('order_by_column', 'created_on'), $request->get('order_by_direction', 'desc'))
            ->get();

        $subscriptionsCount = $this->subscriptionRepository->query()
            ->whereIn('brand', $request->get('brand', [ConfigService::$brand]));
        if ($request->has('user_id')) {
            $subscriptionsCount = $subscriptionsCount->where('user_id', $request->get('user_id'));
        }
        $subscriptionsCount = $subscriptionsCount->count();

        return reply()->json($subscriptions, [
            'totalResults' => $subscriptionsCount
        ]);
    }

    /** Soft delete a subscription if exists in the database
     *
     * @param integer $subscriptionId
     * @return JsonResponse
     */
    public function delete($subscriptionId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.subscription');

        $subscription = $this->subscriptionRepository->read($subscriptionId);

        throw_if(is_null($subscription),
            new NotFoundException('Delete failed, subscription not found with id: ' . $subscriptionId)
        );

        $this->subscriptionRepository->delete($subscriptionId);

        return reply()->json(null, [
            'code' => 204
        ]);
    }

    /** Update a subscription and returned updated data in JSON format
     *
     * @param  integer $subscriptionId
     * @param \Railroad\Ecommerce\Requests\SubscriptionUpdateRequest $request
     * @return JsonResponse
     * @throws \Railroad\Permissions\Exceptions\NotAllowedException
     */
    public function store(SubscriptionCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.subscription');

        $updatedSubscription = $this->subscriptionRepository->create(
            array_merge(
                $request->only(
                    [
                        'brand',
                        'user_id',
                        'customer_id',
                        'interval_type',
                        'interval_count',
                        'total_cycles_due',
                        'total_cycles_paid',
                        'type',
                        'order_id',
                        'product_id',
                        'is_active',
                        'note',
                        'payment_method_id',
                        'currency',
                        'total_price_per_payment',
                        'start_date',
                        'paid_until',
                        'canceled_on',
                    ]
                ), ['created_on' => Carbon::now()->toDateTimeString()
            ])
        );
        return reply()->json($updatedSubscription, [
            'code' => 201
        ]);
    }

    /** Update a subscription and returned updated data in JSON format
     *
     * @param  integer $subscriptionId
     * @param \Railroad\Ecommerce\Requests\SubscriptionUpdateRequest $request
     * @return JsonResponse
     */
    public function update($subscriptionId, SubscriptionUpdateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'edit.subscription');

        $subscription = $this->subscriptionRepository->read($subscriptionId);

        throw_if(is_null($subscription),
            new NotFoundException('Update failed, subscription not found with id: ' . $subscriptionId)
        );
        $cancelDate = null;
        if ($request->has('canceled_on') || ($request->get('is_active') === false)) {
            $cancelDate = Carbon::parse($request->get('canceled_on', Carbon::now()->toDateTimeString()));
        }

        $updatedSubscription = $this->subscriptionRepository->update(
            $subscriptionId,
            array_merge(
                $request->only(
                    [
                        'interval_type',
                        'interval_count',
                        'total_cycles_due',
                        'total_cycles_paid',
                        'type',
                        'order_id',
                        'product_id',
                        'is_active',
                        'note',
                        'payment_method_id',
                        'currency'
                    ]
                ),
                [
                    'total_price_per_payment' => round($request->get('total_price_per_payment', $subscription['total_price_per_payment']), 2),
                    'start_date' => ($request->has('start_date')) ? Carbon::parse($request->get('start_date')) : $subscription['start_date'],
                    'paid_until' => ($request->has('paid_until')) ? Carbon::parse($request->get('paid_until')) : $subscription['paid_until'],
                    'canceled_on' => $cancelDate,
                    'updated_on' => Carbon::now()->toDateTimeString()
                ]
            )
        );
        return reply()->json($updatedSubscription, [
            'code' => 201
        ]);
    }

    /**
     * @param Request $request
     * @param $subscriptionId
     * @return mixed
     */
    public function renew(Request $request, $subscriptionId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'renew.subscription');

        try {
            $updatedSubscription = $this->renewalService->renew($subscriptionId);

            return reply()->json($updatedSubscription, [
                'code' => 201
            ]);
        } catch (Exception $exception) {
            return reply()->json(
                null,
                [
                    'code' => 422,
                    'totalResults' => 0,
                    'errors' => [$exception->getCode() => $exception->getMessage()]
                ]
            );
        }
    }
}