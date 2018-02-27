<?php

namespace Railroad\Ecommerce\Controllers;


use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\ShippingCostCreateRequest;
use Railroad\Ecommerce\Requests\ShippingCostUpdateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\ShippingCostsService;

class ShippingCostsWeightRangeController extends Controller
{
    private $shippingCostsService;

    /**
     * ShippingCostsWeightRangeController constructor.
     * @param $shippingCostsService
     */
    public function __construct(ShippingCostsService $shippingCostsService)
    {
        $this->shippingCostsService = $shippingCostsService;
    }

    /** Store a shipping cost weight range in the database and return it in JSON format if the shipping option exist.
     * Return a JSON response with the shopping cost weight range or throw the proper exception.
     * @param ShippingCostCreateRequest $request
     * @return JsonResponse
     */
    public function store(ShippingCostCreateRequest $request)
    {
        $shippingCosts = $this->shippingCostsService->store(
            $request->get('shipping_option_id'),
            $request->get('min'),
            $request->get('max'),
            $request->get('price')
        );

        //if the store method response it's null the shipping option not exist; we throw the proper exception
        throw_if(
            is_null($shippingCosts),
            new NotFoundException('Creation failed, shipping option not found with id: ' . $request->get('shipping_option_id'))
        );

        return new JsonResponse($shippingCosts, 200);
    }

    /**Update a shipping cost weight range based on id and return it in JSON format or proper exception if the shipping cost weight range not exist
     * @param ShippingCostUpdateRequest $request
     * @param integer $shippingCostId
     * @return JsonResponse
     */
    public function update(ShippingCostUpdateRequest $request, $shippingCostId)
    {
        //update shipping costs weight range with the data sent on the request
        $shippingCost = $this->shippingCostsService->update(
            $shippingCostId,
            array_intersect_key(
                $request->all(),
                [
                    'shipping_option_id' => '',
                    'min' => '',
                    'max' => '',
                    'price' => ''
                ]
            )
        );

        //if the update method response it's null the shipping option not exist; we throw the proper exception
        throw_if(
            is_null($shippingCost),
            new NotFoundException('Update failed, shipping cost weight range not found with id: ' . $shippingCostId)
        );

        return new JsonResponse($shippingCost, 201);
    }

    /**Delete a shipping cost weight range if exist in the database.
     *  Throw proper exception if the shipping cost weight range not exist in the database or a json response with status 204.
     * @param integer $shippingCostId
     * @return JsonResponse
     */
    public function delete($shippingCostId)
    {
        $results = $this->shippingCostsService->delete($shippingCostId);

        //if the delete method response it's null the shipping cost not exist; we throw the proper exception
        throw_if(
            is_null($results),
            new NotFoundException('Delete failed, shipping cost weight range not found with id: ' . $shippingCostId)
        );

        return new JsonResponse(null, 204);
    }

}