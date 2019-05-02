<?php

namespace Railroad\Ecommerce\Controllers;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Requests\ProductCreateRequest;
use Railroad\Ecommerce\Requests\ProductUpdateRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Railroad\RemoteStorage\Services\RemoteStorageService;
use Spatie\Fractal\Fractal;
use Throwable;

class ProductJsonController extends Controller
{
    /**
     * @var DiscountRepository
     */
    private $discountRepository;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var RemoteStorageService
     */
    private $remoteStorageService;

    /**
     * ProductJsonController constructor.
     *
     * @param DiscountRepository $discountRepository
     * @param EcommerceEntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param OrderItemRepository $orderItemRepository
     * @param PermissionService $permissionService
     * @param ProductRepository $productRepository
     * @param RemoteStorageService $remoteStorageService
     */
    public function __construct(
        DiscountRepository $discountRepository,
        EcommerceEntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        OrderItemRepository $orderItemRepository,
        PermissionService $permissionService,
        ProductRepository $productRepository,
        RemoteStorageService $remoteStorageService
    ) {
        $this->discountRepository = $discountRepository;
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->orderItemRepository = $orderItemRepository;
        $this->permissionService = $permissionService;
        $this->productRepository = $productRepository;
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
         * @var $qb QueryBuilder
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
     *
     * @throws Throwable
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
     * @param int $productId
     *
     * @return JsonResponse|NotFoundException
     *
     * @throws Throwable
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

    // todo: use soft delete
    /**
     * Delete a product that it's not connected to orders or discounts and return a JsonResponse.
     *
     * @param int $productId
     *
     * @return JsonResponse
     *
     * @throws Throwable
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

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return ResponseService::empty(204);
    }

    /**
     * Upload product thumbnail on remote storage using remotestorage package.
     * Throw an error JSON response if the upload failed or return the uploaded thumbnail url.
     *
     * @param Request $request
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function uploadThumbnail(Request $request)
    {
        $target = $request->get('target');
        $upload = $this->remoteStorageService->put($target, $request->file('file'));

        throw_if(
            (!$upload),
            new NotFoundException('Upload product thumbnail failed')
        );

        return ResponseService::productThumbnail(
            $this->remoteStorageService->url($target)
        );
    }

    /**
     * Pull specific product
     *
     * @param $productId
     *
     * @return mixed
     *
     * @throws Throwable
     */
    public function show($productId)
    {
        $active = $this->permissionService->can(
            auth()->id(),
            'pull.inactive.products'
        ) ? [0, 1] : [1];

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->productRepository->createQueryBuilder('p');

        $qb
            ->where($qb->expr()->in('p.active', ':activity'))
            ->andWhere($qb->expr()->eq('p.id', ':id'));

        /**
         * @var $q Query
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