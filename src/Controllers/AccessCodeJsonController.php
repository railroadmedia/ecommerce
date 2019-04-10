<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Requests\AccessCodeJsonClaimRequest;
use Railroad\Ecommerce\Requests\AccessCodeReleaseRequest;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\AccessCodeService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class AccessCodeJsonController extends BaseController
{
    /**
     * @var AccessCodeRepository
     */
    private $accessCodeRepository;

    /**
     * @var AccessCodeService
     */
    private $accessCodeService;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * AccessCodeJsonController constructor.
     *
     * @param AccessCodeRepository $accessCodeRepository
     * @param AccessCodeService $accessCodeService
     * @param EcommerceEntityManager $entityManager
     * @param PermissionService $permissionService
     * @param ProductRepository $productRepository
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AccessCodeRepository $accessCodeRepository,
        AccessCodeService $accessCodeService,
        EcommerceEntityManager $entityManager,
        PermissionService $permissionService,
        ProductRepository $productRepository,
        UserProviderInterface $userProvider
    ) {
        parent::__construct();

        $this->accessCodeRepository = $accessCodeRepository;
        $this->accessCodeService = $accessCodeService;
        $this->entityManager = $entityManager;
        $this->permissionService = $permissionService;
        $this->productRepository = $productRepository;
        $this->userProvider = $userProvider;
    }

    /**
     * Paginated list of access codes, for admins only
     *
     * @param Request $request
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.access_codes');

        // parse request params and prepare db query parms
        $alias = 'a';
        $orderBy = $request->get('order_by_column', 'created_at');
        if (
            strpos($orderBy, '_') !== false
            || strpos($orderBy, '-') !== false
        ) {
            $orderBy = camel_case($orderBy);
        }
        $orderBy = $alias . '.' . $orderBy;
        $first = ($request->get('page', 1) - 1) * $request->get('limit', 10);
        $brands = $request->get('brands', [ConfigService::$availableBrands]);

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->accessCodeRepository->createQueryBuilder($alias);

        $qb
            ->select($alias)
            ->setMaxResults($request->get('limit', 10))
            ->setFirstResult($first)
            ->where($qb->expr()->in($alias . '.brand', ':brands'))
            ->orderBy($orderBy, $request->get('order_by_direction', 'desc'))
            ->setParameter('brands', $brands);

        $accessCodes = $qb->getQuery()->getResult();

        // fetch related products, as a dictionary
        $products = $this->productRepository->byAccessCodes($accessCodes);

        // map products to ease access
        $productsMap = [];

        /**
         * @var $product \Railroad\Ecommerce\Entities\Product
         */
        foreach ($products as $product) {
            $productsMap[$product->getId()] = $product;
        }

        return ResponseService::decoratedAccessCode(
            $accessCodes,
            $productsMap,
            $qb
        );
    }

    /**
     * Search for access codes, for admins only
     *
     * @param Request $request
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function search(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.access_codes');

        $alias = 'a';
        $brands = $request->get('brands', [ConfigService::$availableBrands]);

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->accessCodeRepository->createQueryBuilder($alias);
        $qb
            ->select($alias)
            ->where($qb->expr()->in($alias . '.brand', ':brands'))
            ->andWhere($qb->expr()->like($alias . '.code', ':term'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('brands', $brands);
        $q->setParameter('term', '%' . $request->get('term') . '%');

        $accessCodes = $q->getResult();

        // fetch related products, as a dictionary
        $products = $this->productRepository
                            ->byAccessCodes($accessCodes);

        // map products to ease access
        $productsMap = [];

        /**
         * @var $product \Railroad\Ecommerce\Entities\Product
         */
        foreach ($products as $product) {
            $productsMap[$product->getId()] = $product;
        }

        return ResponseService::decoratedAccessCode($accessCodes, $productsMap);
    }

    /**
     * Claim an access code
     *
     * @param AccessCodeJsonClaimRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function claim(AccessCodeJsonClaimRequest $request)
    {
        $this->permissionService->canOrThrow(
            auth()->id(),
            'claim.access_codes'
        );

        /**
         * @var $user \Railroad\Ecommerce\Entities\User
         */
        $user = $this->userProvider->getUserById(
                $request->get('claim_for_user_id')
            );

        throw_if(
            is_null($user),
            new NotFoundException(
                'Claim failed, user not found with id: '
                . $request->get('claim_for_user_id')
            )
        );

        $accessCode = $this->accessCodeRepository
            ->findOneBy(['code' => $request->get('access_code')]);

        $this->accessCodeService->claim($accessCode, $user);

        return ResponseService::accessCode($accessCode)->respond(200);
    }

    /**
     * Release an access code
     *
     * @param AccessCodeReleaseRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function release(AccessCodeReleaseRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'release.access_codes');

        $accessCode = $this->accessCodeRepository
                        ->find($request->get('access_code_id'));
        $accessCode
            ->setIsClaimed(false)
            ->setClaimer(null)
            ->setClaimedOn(null)
            ->setUpdatedAt(Carbon::now());

        $this->entityManager->flush();

        return ResponseService::accessCode($accessCode)->respond(200);
    }
}
