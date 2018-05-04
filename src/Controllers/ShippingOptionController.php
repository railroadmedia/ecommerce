<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\ShippingOptionCreateRequest;
use Railroad\Ecommerce\Requests\ShippingOptionUpdateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\ShippingOptionService;

class ShippingOptionController extends Controller
{
    private $shippingOptionService;

    /**
     * ShippingOptionController constructor.
     *
     * @param $shippingOptionService
     */
    public function __construct(ShippingOptionService $shippingOptionService)
    {
        $this->shippingOptionService = $shippingOptionService;
    }

    /** Create a new shipping option and return it in JSON format
     *
     * @param ShippingOptionCreateRequest $request
     * @return JsonResponse
     */
    public function store(ShippingOptionCreateRequest $request)
    {
        $shippingOption = $this->shippingOptionService->store(
            $request->get('country'),
            $request->get('priority'),
            $request->get('active')
        );

        return new JsonResponse($shippingOption, 200);
    }

    /** Update a shipping option based on id and return it in JSON format or proper exception if the shipping option not exist
     *
     * @param ShippingOptionUpdateRequest $request
     * @param integer                     $shippingOptionId
     * @return JsonResponse
     */
    public function update(ShippingOptionUpdateRequest $request, $shippingOptionId)
    {
        //update shipping option with the data sent on the request
        $shippingOption = $this->shippingOptionService->update(
            $shippingOptionId,
            $request->only(
                [
                    'country',
                    'priority',
                    'active'
                ]
            )
        );

        //if the update method response it's null the shipping option not exist; we throw the proper exception
        throw_if(
            is_null($shippingOption),
            new NotFoundException('Update failed, shipping option not found with id: ' . $shippingOptionId)
        );

        return new JsonResponse($shippingOption, 201);
    }

    /** Delete a shipping option if exist in the database.
     *  Throw proper exception if the shipping option not exist in the database or a json response with status 204.
     *
     * @param integer $shippingOptionId
     * @return JsonResponse
     */
    public function delete($shippingOptionId)
    {
        $results = $this->shippingOptionService->delete($shippingOptionId);

        //if the delete method response it's null the shipping option not exist; we throw the proper exception
        throw_if(
            is_null($results),
            new NotFoundException('Delete failed, shipping option not found with id: ' . $shippingOptionId)
        );

        return new JsonResponse(null, 204);
    }
}