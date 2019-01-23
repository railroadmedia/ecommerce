<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Http\Request;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Requests\ProductCreateRequest;
use Railroad\Ecommerce\Requests\ProductUpdateRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Railroad\RemoteStorage\Services\RemoteStorageService;
use Railroad\Resora\Entities\Entity;

class ProductJsonController extends BaseController
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    private $productRepository;

    /**
     * @var \Railroad\RemoteStorage\Services\RemoteStorageService
     */
    private $remoteStorageService;

    /**
     * ProductJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\ProductRepository $productRepository
     * @param \Railroad\Permissions\Services\PermissionService $permissionService
     * @param \Railroad\RemoteStorage\Services\RemoteStorageService $remoteStorageService
     */
    public function __construct(
        EntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        // ProductRepository $productRepository,
        PermissionService $permissionService,
        RemoteStorageService $remoteStorageService
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->permissionService = $permissionService;
        $this->productRepository = $this->entityManager
                                        ->getRepository(Product::class);
        $this->remoteStorageService = $remoteStorageService;
    }

    /**
     * Pull paginated products
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $active = $this->permissionService->can(auth()->id(), 'pull.inactive.products') ? [0, 1] : [1];

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
        $qb = $this->productRepository->createQueryBuilder($alias);

        $qb
            ->setMaxResults($request->get('limit', 10))
            ->setFirstResult($first)
            ->where($qb->expr()->in($alias . '.brand', ':brands'))
            ->andWhere($qb->expr()->in($alias . '.active', ':activity'))
            ->orderBy($orderBy, $request->get('order_by_direction', 'desc'))
            ->setParameter('brands', $brands)
            ->setParameter('activity', $active);

        $products = $qb->getQuery()->getResult();

        return ResponseService::product($products, $qb)->respond();
    }

    /**
     * Create a new product and return it in JSON format
     *
     * @param ProductCreateRequest $request
     *
     * @return JsonResponse
     */
    public function store(ProductCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.product');

        $product = new Product();

        $this->jsonApiHydrator->hydrate($product, $request->onlyAllowed());

        if (!$product->getBrand()) {
            $product->setBrand(ConfigService::$brand);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return ResponseService::product($product)->respond();
    }

    /**
     * Update a product based on product id and return it in JSON format
     *
     * @param ProductUpdateRequest $request
     * @param integer $productId
     *
     * @return JsonResponse|NotFoundException
     */
    public function update(ProductUpdateRequest $request, $productId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'update.product');

        $product = $this->productRepository->find($productId);

        if (is_null($product)) {
            throw new NotFoundException(
                'Update failed, product not found with id: ' . $productId
            );
        }

        $this->jsonApiHydrator->hydrate($product, $request->onlyAllowed());

        $this->entityManager->flush();

        return ResponseService::product($product)->respond();
    }

    /**
     * Delete a product that it's not connected to orders or discounts and return a JsonResponse.
     *
     * @param integer $productId
     *
     * @return JsonResponse
     *
     * @throws NotFoundException - if the product not exist or the user have not rights to delete the product
     * @throws NotAllowedException - if the product it's connected to orders or discounts
     */
    public function delete($productId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.product');

        $product = $this->productRepository->find($productId);

        if (is_null($product)) {
            throw new NotFoundException(
                'Delete failed, product not found with id: ' . $productId
            );
        }

        // todo - get details about relations, update

        // throw_if(
        //     (count($product->order) > 0),
        //     new NotAllowedException('Delete failed, exists orders that contain the selected product.')
        // );

        // throw_if(
        //     (count($product->discounts) > 0),
        //     new NotAllowedException('Delete failed, exists discounts defined for the selected product.')
        // );

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return ResponseService::empty(204);
    }

    /** Upload product thumbnail on remote storage using remotestorage package.
     * Throw an error JSON response if the upload failed or return the uploaded thumbnail url.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadThumbnail(Request $request)
    {
        // $target = $request->get('target');
        // $upload = $this->remoteStorageService->put($target, $request->file('file'));

        // throw_if(
        //     (!$upload),
        //     reply()->json(
        //         new Entity(['message' => 'Upload product thumbnail failed']),
        //         [
        //             'code' => 400,
        //         ]
        //     )
        // );

        // return reply()->json(
        //     new Entity(['url' => $this->remoteStorageService->url($target)]),
        //     [
        //         'code' => 201,
        //     ]
        // );
    }

    /**
     * Pull specific product
     *
     * @param Request $request
     * @param $productId
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function show(Request $request, $productId)
    {
        $active = $this->permissionService->can(
            auth()->id(),
            'pull.inactive.products'
        ) ? [0, 1] : [1];

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->productRepository->createQueryBuilder('p');

        $qb
            ->where($qb->expr()->in('p.active', ':activity'))
            ->andWhere($qb->expr()->eq('p.id', ':id'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q
            ->setParameter('activity', $active)
            ->setParameter('id', $productId);

        /**
         * @var $product Product
         */
        $product = $q->getOneOrNullResult();

        throw_if(
            is_null($product),
            new NotFoundException(
                'Pull failed, product not found with id: ' . $productId
            )
        );

        return ResponseService::product($product)->respond();
    }
}