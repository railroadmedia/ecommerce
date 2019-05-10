<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionCreated;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionDeleted;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Railroad\Ecommerce\Requests\SubscriptionCreateRequest;
use Railroad\Ecommerce\Requests\SubscriptionUpdateRequest;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\RenewalService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class UserProductJsonController extends Controller
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var UserProductService
     */
    private $userProductService;

    /**
     * @var UserProductRepository
     */
    private $userProductRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * SubscriptionJsonController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param UserProductService $userProductService
     * @param UserProductRepository $userProductRepository
     * @param PermissionService $permissionService
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        UserProductService $userProductService,
        UserProductRepository $userProductRepository,
        PermissionService $permissionService,
        UserProviderInterface $userProvider
    )
    {
        $this->entityManager = $entityManager;
        $this->userProductService = $userProductService;
        $this->userProductRepository = $userProductRepository;
        $this->permissionService = $permissionService;
        $this->userProvider = $userProvider;
    }

    /**
     * Pull subscriptions paginated
     *
     * @param Request $request
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.user-products');

        $user = $this->userProvider->getUserById(
            $request->get('user_id', $this->userProvider->getCurrentUserId())
        );

        $subscriptionsAndBuilder = $this->userProductRepository->indexByRequest($request, $user);

        return ResponseService::subscription(
            $subscriptionsAndBuilder->getResults(),
            $subscriptionsAndBuilder->getQueryBuilder()
        );
    }
}
