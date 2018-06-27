<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Requests\ProductCreateRequest;
use Railroad\Ecommerce\Requests\ProductUpdateRequest;
use Railroad\Ecommerce\Responses\JsonPaginatedResponse;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Permissions\Services\PermissionService;
use Railroad\RemoteStorage\Services\RemoteStorageService;

class ProductJsonController extends BaseController
{
    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    private $productRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * @var \Railroad\RemoteStorage\Services\RemoteStorageService
     */
    private $remoteStorageService;

    /**
     * ProductJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\ProductRepository    $productRepository
     * @param \Railroad\Permissions\Services\PermissionService      $permissionService
     * @param \Railroad\RemoteStorage\Services\RemoteStorageService $remoteStorageService
     */
    public function __construct(
        ProductRepository $productRepository,
        PermissionService $permissionService,
        RemoteStorageService $remoteStorageService
    ) {
        parent::__construct();

        $this->productRepository    = $productRepository;
        $this->permissionService    = $permissionService;
        $this->remoteStorageService = $remoteStorageService;
    }

    /** Pull paginated products
     *
     * @param Request $request
     * @return JsonPaginatedResponse
     */
    public function index(Request $request)
    {
        $active = $this->permissionService->is(auth()->id(), 'admin') ? [0, 1] : [1];

        $products = $this->productRepository->query()
            ->whereIn('active', $active)
            ->whereIn('brand', $request->get('brand',[ConfigService::$brand]))
            ->limit($request->get('limit', 10))
            ->skip(($request->get('page', 1) - 1) * $request->get('limit', 10))
            ->orderBy($request->get('order_by_column', 'created_on'), $request->get('order_by_direction', 'desc'))
            ->get();

        $productsCount = $this->productRepository->query()
            ->whereIn('active', $active)
            ->count();

        return new JsonPaginatedResponse(
            $products,
            $productsCount,
            200);
    }

    /** Create a new product and return it in JSON format
     *
     * @param ProductCreateRequest $request
     * @return JsonResponse
     */
    public function store(ProductCreateRequest $request)
    {
        if(!$this->permissionService->is(auth()->id(), 'admin'))
        {
            throw new NotAllowedException('This action is unauthorized.');
        }

        $product = $this->productRepository->create(
            array_merge(
                $request->only([
                    'name',
                    'sku',
                    'price',
                    'type',
                    'active',
                    'description',
                    'thumbnail_url',
                    'is_physical',
                    'weight',
                    'subscription_interval_type',
                    'subscription_interval_count',
                    'stock'
                ]),
                [
                    'brand'      => $request->input('brand', ConfigService::$brand),
                    'created_on' => Carbon::now()->toDateTimeString()
                ]
            ));

        return new JsonResponse($product, 200);
    }

    /** Update a product based on product id and return it in JSON format
     *
     * @param ProductUpdateRequest $request
     * @param integer              $productId
     * @return JsonResponse|NotFoundException
     */
    public function update(ProductUpdateRequest $request, $productId)
    {
        $product = $this->productRepository->read($productId);

        if(!$this->permissionService->is(auth()->id(), 'admin') || (is_null($product)))
        {
            throw new NotFoundException('Update failed, product not found with id: ' . $productId);
        }

        //update product with the data sent on the request
        $product = $this->productRepository->update(
            $productId,
            array_merge(
                $request->only(
                    [
                        'brand',
                        'name',
                        'sku',
                        'price',
                        'type',
                        'active',
                        'description',
                        'thumbnail_url',
                        'is_physical',
                        'weight',
                        'subscription_interval_type',
                        'subscription_interval_count',
                        'stock'
                    ]
                ), [
                'updated_on' => Carbon::now()->toDateTimeString()
            ])
        );

        return new JsonResponse($product, 201);
    }

    /** Delete a product that it's not connected to orders or discounts and return a JsonResponse.
     *  Throw  - NotFoundException if the product not exist or the user have not rights to delete the product
     *         - NotAllowedException if the product it's connected to orders or discounts
     *
     * @param integer $productId
     * @return JsonResponse
     */
    public function delete($productId)
    {
        $product = $this->productRepository->read($productId);

        if(!$this->permissionService->is(auth()->id(), 'admin') || (is_null($product)))
        {
            throw new NotFoundException('Delete failed, product not found with id: ' . $productId);
        }

        throw_if(
            (count($product->order) > 0),
            new NotAllowedException('Delete failed, exists orders that contain the selected product.')
        );

        throw_if(
            (count($product->discounts) > 0),
            new NotAllowedException('Delete failed, exists discounts defined for the selected product.')
        );

        $this->productRepository->destroy($productId);

        return new JsonResponse(null, 204);
    }

    /** Upload product thumbnail on remote storage using remotestorage package.
     * Throw an error JSON response if the upload failed or return the uploaded thumbnail url.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadThumbnail(Request $request)
    {
        $target = $request->get('target');
        $upload = $this->remoteStorageService->put($target, $request->file('file'));

        throw_if(
            (!$upload),
            new JsonResponse('Upload product thumbnail failed', 400)
        );

        return new JsonResponse(
            $this->remoteStorageService->url($target), 201
        );
    }
}