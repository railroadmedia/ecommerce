<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\ProductCreateRequest;
use Railroad\Ecommerce\Requests\ProductUpdateRequest;
use Railroad\Ecommerce\Responses\JsonPaginatedResponse;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\RemoteStorage\Services\ConfigService;

class ProductJsonController extends Controller
{
    private $productService;

    /**
     * ProductJsonController constructor.
     *
     * @param $productService
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /** Pull paginated products
     *
     * @param Request $request
     * @return JsonPaginatedResponse
     */
    public function index(Request $request)
    {
        $products = $this->productService->getAllProducts(
            $request->get('page', 1),
            $request->get('limit', 10),
            $request->get('sort', '-created_on')
        );

        return new JsonPaginatedResponse(
            $products['results'],
            $products['total_results'],
            200);
    }

    /** Create a new product and return it in JSON format
     *
     * @param ProductCreateRequest $request
     * @return JsonResponse
     */
    public function store(ProductCreateRequest $request)
    {
        $product = $this->productService->store(
            $request->get('brand'),
            $request->get('name'),
            $request->get('sku'),
            $request->get('price'),
            $request->get('type'),
            $request->get('active'),
            $request->get('description'),
            $request->get('thumbnail_url'),
            $request->get('is_physical'),
            $request->get('weight'),
            $request->get('subscription_interval_type'),
            $request->get('subscription_interval_count'),
            $request->get('stock')
        );

        return new JsonResponse($product, 200);
    }

    /** Update a product based on product id and return it in JSON format
     *
     * @param ProductUpdateRequest $request
     * @param integer              $productId
     * @return JsonResponse
     */
    public function update(ProductUpdateRequest $request, $productId)
    {
        //update product with the data sent on the request
        $product = $this->productService->update(
            $productId,
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
            )
        );

        //if the update method response it's null the product not exist; we throw the proper exception
        throw_if(
            is_null($product),
            new NotFoundException('Update failed, product not found with id: ' . $productId)
        );

        return new JsonResponse($product, 201);
    }

    /** Delete a product that it's not connected to orders or discounts and return a JsonResponse.
     *  Throw  - NotFoundException if the product not exist
     *         - NotAllowedException if the product it's connected to orders or discounts
     *
     * @param integer $productId
     * @return JsonResponse
     */
    public function delete($productId)
    {
        $results = $this->productService->delete($productId);

        //if the delete method response it's null the product not exist; we throw the proper exception
        throw_if(
            is_null($results),
            new NotFoundException('Delete failed, product not found with id: ' . $productId)
        );

        throw_if(
            ($results === -1),
            new NotAllowedException('Delete failed, exists discounts defined for the selected product.')
        );

        throw_if(
            ($results === 0),
            new NotAllowedException('Delete failed, exists orders that contain the selected product.')
        );

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
        $upload = $this->productService->uploadThumbnailToRemoteStorage($target, $request->file('file'));
        throw_if(
            (!$upload),
            new JsonResponse('Upload product thumbnail failed', 400)
        );

        return new JsonResponse(
            $this->productService->url($target), 201
        );
    }
}