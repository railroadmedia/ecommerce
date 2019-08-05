<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Events\UserProducts\UserProductCreated;
use Railroad\Ecommerce\Events\UserProducts\UserProductDeleted;
use Railroad\Ecommerce\Events\UserProducts\UserProductUpdated;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Railroad\Ecommerce\Requests\UserProductCreateRequest;
use Railroad\Ecommerce\Requests\UserProductUpdateRequest;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;
use ReflectionException;
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
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * SubscriptionJsonController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param UserProductService $userProductService
     * @param UserProductRepository $userProductRepository
     * @param PermissionService $permissionService
     * @param UserProviderInterface $userProvider
     * @param JsonApiHydrator $jsonApiHydrator
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        UserProductService $userProductService,
        UserProductRepository $userProductRepository,
        PermissionService $permissionService,
        UserProviderInterface $userProvider,
        JsonApiHydrator $jsonApiHydrator
    )
    {
        $this->entityManager = $entityManager;
        $this->userProductService = $userProductService;
        $this->userProductRepository = $userProductRepository;
        $this->permissionService = $permissionService;
        $this->userProvider = $userProvider;
        $this->jsonApiHydrator = $jsonApiHydrator;
    }

    /**
     * Pull subscriptions paginated
     *
     * @param Request $request
     *
     * @return Fractal
     *
     * @throws Throwable
     * @throws NotAllowedException
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.user-products');

        $user = $this->userProvider->getUserById(
            $request->get('user_id', $this->userProvider->getCurrentUserId())
        );

        $userProductsAndBuilder = $this->userProductRepository->indexByRequest($request, $user);

        return ResponseService::userProduct(
            $userProductsAndBuilder->getResults(),
            $userProductsAndBuilder->getQueryBuilder()
        );
    }

    /**
     * @param UserProductCreateRequest $request
     *
     * @return JsonResponse
     *
     * @throws NotAllowedException
     * @throws DBALException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ReflectionException
     */
    public function store(UserProductCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.user-products');

        $userProduct = new UserProduct();

        $this->jsonApiHydrator->hydrate($userProduct, $request->onlyAllowed());

        $this->entityManager->persist($userProduct);

        $this->entityManager->flush();

        $userProduct = $this->userProductRepository->find($userProduct->getId());

        event(new UserProductCreated($userProduct));

        return ResponseService::userProduct($userProduct)
            ->respond(201);
    }

    /**
     * @param UserProductUpdateRequest $request
     * @param $userProductId
     *
     * @return JsonResponse
     *
     * @throws NotAllowedException
     * @throws Throwable
     * @throws DBALException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ReflectionException
     */
    public function update(UserProductUpdateRequest $request, $userProductId)
    {
        $userProduct = $this->userProductRepository->find($userProductId);

        throw_if(
            is_null($userProduct),
            new NotFoundException(
                'Update failed, user product not found with id: ' . $userProductId
            )
        );

        $oldUserProduct = clone ($userProduct);

        if (!$this->permissionService->can(auth()->id(), 'update.user-products')) {
            throw new NotAllowedException(
                'This action is unauthorized.'
            );
        }

        $this->jsonApiHydrator->hydrate($userProduct, $request->onlyAllowed());

        $this->entityManager->flush();

        event(new UserProductUpdated($userProduct, $oldUserProduct));

        return ResponseService::userProduct($userProduct)
            ->respond(200);
    }

    /**
     * @param $userProductId
     *
     * @return JsonResponse
     *
     * @throws Throwable
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete($userProductId)
    {
        $userProduct = $this->userProductRepository->find($userProductId);

        throw_if(
            is_null($userProduct),
            new NotFoundException(
                'Delete failed, user product not found with id: ' . $userProductId
            )
        );

        $this->permissionService->canOrThrow(auth()->id(), 'delete.user-products');

        $userProduct->setDeletedAt(Carbon::now());
        $this->entityManager->flush();

        event(new UserProductDeleted($userProduct));

        return ResponseService::empty(204);
    }
}
