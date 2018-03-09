<?php

namespace Railroad\Ecommerce\Controllers;


use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\AddressCreateRequest;
use Railroad\Ecommerce\Requests\AddressDeleteRequest;
use Railroad\Ecommerce\Requests\AddressUpdateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\AddressService;

class AddressJsonController extends Controller
{
    private $addressService;

    /**
     * AddressJsonController constructor.
     * @param $addressService
     */
    public function __construct(AddressService $addressService)
    {
        $this->addressService = $addressService;
    }

    /**
     * @param AddressCreateRequest $request
     * @return JsonResponse
     */
    public function store(AddressCreateRequest $request)
    {
        $address = $this->addressService->store(
            $request->get('type'),
            $request->get('brand'),
            $request->get('user_id'),
            $request->get('customer_id'),
            $request->get('first_name'),
            $request->get('last_name'),
            $request->get('street_line_1'),
            $request->get('street_line_2'),
            $request->get('city'),
            $request->get('zip'),
            $request->get('state'),
            $request->get('country')
        );

        return new JsonResponse($address, 200);
    }

    /**
     * @param AddressUpdateRequest $request
     * @param $addressId
     * @return JsonResponse
     */
    public function update(AddressUpdateRequest $request, $addressId)
    {
        //update address with the data sent on the request
        $address = $this->addressService->update(
            $addressId,
            array_intersect_key(
                $request->all(),
                [
                    'type' => '',
                    'brand' => '',
                    'user_id' => '',
                    'customer_id' => '',
                    'first_name' => '',
                    'last_name' => '',
                    'street_line_1' => '',
                    'street_line_2' => '',
                    'city' => '',
                    'zip' => '',
                    'state' => '',
                    'country' => ''
                ]
            )
        );

        //if the update method response it's null the address not exist; we throw the proper exception
        throw_if(
            is_null($address),
            new NotFoundException('Update failed, address not found with id: ' . $addressId)
        );

        return new JsonResponse($address, 201);
    }

    /**
     * @param $addressId
     * @param AddressDeleteRequest $request
     * @return JsonResponse
     */
    public function delete($addressId, AddressDeleteRequest $request)
    {
        $results = $this->addressService->delete($addressId, $request->get('user_id'), $request->get('customer_id'));

        //if the delete method response it's null the product not exist; we throw the proper exception
        throw_if(
            is_null($results),
            new NotFoundException('Delete failed, address not found with id: ' . $addressId)
        );

        throw_if(
            ($results === -1),
            new NotAllowedException('Delete failed, exists orders defined for the selected address.')
        );

        return new JsonResponse(null, 204);
    }
}