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
     *
     * @param $addressService
     */
    public function __construct(AddressService $addressService)
    {
        $this->addressService = $addressService;
    }

    /** Call the method to store a new address based on request parameters.
     * Return a JsonResponse with the new created address.
     *
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

    /** Update an address based on address id and requests parameters.
     * Return - NotFoundException if the address not exists
     *        - NotAllowedException if the user have not rights to access it
     *        - JsonResponse with the updated address
     *
     * @param AddressUpdateRequest $request
     * @param int                  $addressId
     * @return JsonResponse
     */
    public function update(AddressUpdateRequest $request, $addressId)
    {
        //update address with the data sent on the request
        $address = $this->addressService->update(
            $addressId,
            $request->only([
                'type',
                'brand',
                'first_name',
                'last_name',
                'street_line_1',
                'street_line_2',
                'city',
                'zip',
                'state',
                'country'
            ])
        );

        //if the update method response it's null the address not exist; we throw the proper exception
        throw_if(
            is_null($address),
            new NotFoundException('Update failed, address not found with id: ' . $addressId)
        );

        return new JsonResponse($address, 201);
    }

    /** Delete an address based on the id.
     * Return - NotFoundException if the address not exists
     *        - NotAllowedException if the address it's in used (exists orders defined for the selected address)  or the user have not rights to access it
     *        - JsonResponse with code 204 otherwise
     *
     * @param integer              $addressId
     * @param AddressDeleteRequest $request
     * @return JsonResponse
     */
    public function delete($addressId, AddressDeleteRequest $request)
    {
        $results = $this->addressService->delete($addressId);

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