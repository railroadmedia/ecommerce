<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionCreated;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionDeleted;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewFailed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Events\Subscriptions\UserSubscriptionRenewed;
use Railroad\Ecommerce\Events\Subscriptions\UserSubscriptionUpdated;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Requests\FailedBillingSubscriptionsRequest;
use Railroad\Ecommerce\Requests\FailedSubscriptionsRequest;
use Railroad\Ecommerce\Requests\SubscriptionCreateRequest;
use Railroad\Ecommerce\Requests\SubscriptionUpdateRequest;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Ecommerce\Services\SubscriptionService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
     * @var SubscriptionService
     */
    private $subscriptionService;

    /**
     * @var UserProductService
     */
    private $userProductService;

    /**
     * SubscriptionJsonController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param PermissionService $permissionService
     * @param SubscriptionService $subscriptionService
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProductService $userProductService
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService,
        SubscriptionService $subscriptionService,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService
    )
    {
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->permissionService = $permissionService;
        $this->subscriptionService = $subscriptionService;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
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

        if ($subscription->getTotalPrice()) {

            $subscription->setTotalPrice(
                round($subscription->getTotalPrice(), 2)
            );
        }

        $isUserMainSubscription = true;

        if (
            $subscription->getType() != Subscription::TYPE_PAYMENT_PLAN
            && (!$subscription->getIsActive() || $subscription->getCanceledOn())
        ) {
            // if the updated subscription is not active

            $user = $subscription->getUser();
            $product = $subscription->getProduct();
            $activeSubscription = $this->subscriptionRepository->getUserSubscriptionForProducts(
                $user,
                [$product->getId()],
                true
            );

            if ($activeSubscription && $activeSubscription->getId() != $subscription->getId()) {
                // if the user has an other active subscription, do not update user product based on this subscription
                $isUserMainSubscription = false;
            }
        }

        if ($isUserMainSubscription) {
            event(new SubscriptionUpdated($oldSubscription, $subscription));
            event(new UserSubscriptionUpdated($oldSubscription, $subscription));
        }

        $this->entityManager->flush();

        if ($isUserMainSubscription) {
            $this->userProductService->updateSubscriptionProducts($subscription);
        }

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

        try {

            $payment = $this->subscriptionService->renew($subscription);

            $response = ResponseService::subscription($subscription);

            if ($payment) {
                event(new UserSubscriptionRenewed($subscription, $payment));
            }

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

    /**
     * @param FailedBillingSubscriptionsRequest $request
     *
     * @return JsonResponse|BinaryFileResponse
     *
     * @throws Throwable
     */
    public function failedBilling(FailedBillingSubscriptionsRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.failed-billing');

        $subscriptionsAndBuilder = $this->subscriptionRepository->indexFailedBillingByRequest($request);

        if ($request->has('csv') && $request->get('csv') == true) {
            $rows = [];

            foreach ($subscriptionsAndBuilder->getResults() as $subscription) {
                $rows[] = [
                    $subscription->getId(),
                    $subscription->getTotalPrice(),
                    $subscription->getUser()
                        ->getEmail(),
                    $subscription->getOrder()
                        ->getId(),
                    !empty($subscription->getProduct()) ? $subscription->getProduct()
                        ->getId() : '',
                    !empty($subscription->getProduct()) ? $subscription->getProduct()
                        ->getName() : '',
                    !empty($subscription->getProduct()) ? $subscription->getProduct()
                        ->getSku() : '',
                    $subscription->getFailedPayment()
                        ->getId(),
                    $subscription->getFailedPayment()
                        ->getStatus(),
                    $subscription->getFailedPayment()
                        ->getMessage(),
                    $subscription->getFailedPayment()
                        ->getCreatedAt(),
                ];
            }

            $filePath = sys_get_temp_dir() . "/failed-billing-" . time() . ".csv";

            $f = fopen($filePath, "w");

            fputcsv(
                $f,
                [
                    'Subscription ID',
                    'Subscription Total Price',
                    'Order ID',
                    'Email',
                    'Product ID',
                    'Product Name',
                    'Product SKU',
                    'Payment ID',
                    'Payment Status',
                    'Payment Message',
                    'Payment Date',
                ]
            );

            foreach ($rows as $line) {
                fputcsv($f, $line);
            }

            return response()
                ->download($filePath)
                ->deleteFileAfterSend();
        }

        return ResponseService::subscription($subscriptionsAndBuilder->getResults(), $subscriptionsAndBuilder->getQueryBuilder())
            ->respond(200);
    }
}
