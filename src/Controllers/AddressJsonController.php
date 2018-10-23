<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Requests\AddressCreateRequest;
use Railroad\Ecommerce\Requests\AddressDeleteRequest;
use Railroad\Ecommerce\Requests\AddressUpdateRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class AddressJsonController extends BaseController
{
    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * AddressJsonController constructor.
     *
     * @param AddressRepository $addressRepository
     * @param PermissionService $permissionService
     */
    public function __construct(AddressRepository $addressRepository, PermissionService $permissionService)
    {
        parent::__construct();

        $this->addressRepository = $addressRepository;
        $this->permissionService = $permissionService;
    }

    /**
     * @param Request $request
     * @throws \Railroad\Permissions\Exceptions\NotAllowedException
     */
    public function index(Request $request)
    {
        if ($request->get('user_id') !== auth()->id()) {
            $this->permissionService->canOrThrow(auth()->id(), 'pull.user.payment.method');
        }

        $addresses = $this->addressRepository->query()
            ->where('user_id', $request->get('user_id', auth()->id()))
            ->get();

        return reply()->json($addresses);
    }

    /**
     * Call the method to store a new address based on request parameters.
     * Return a JsonResponse with the new created address.
     *
     * @param AddressCreateRequest $request
     * @return JsonResponse
     */
    public function store(AddressCreateRequest $request)
    {
        $address = $this->addressRepository->create(
            array_merge(
                $request->only(
                    [
                        'type',
                        'user_id',
                        'customer_id',
                        'first_name',
                        'last_name',
                        'street_line_1',
                        'street_line_2',
                        'city',
                        'zip',
                        'state',
                        'country',
                    ]
                ),
                [
                    'brand' => $request->input('brand', ConfigService::$brand),
                    'created_on' => Carbon::now()->toDateTimeString(),
                ]
            )

        );

        return reply()->json($address);
    }

    /**
     * Update an address based on address id and requests parameters.
     * Return - NotFoundException if the address not exists
     *        - NotAllowedException if the user have not rights to access it
     *        - JsonResponse with the updated address
     *
     * @param AddressUpdateRequest $request
     * @param int $addressId
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(AddressUpdateRequest $request, $addressId)
    {
        $address = $this->addressRepository->read($addressId);
        throw_if(
            is_null($address),
            new NotFoundException('Update failed, address not found with id: ' . $addressId)
        );

        throw_if(
            (
                (!$this->permissionService->is(auth()->id(), 'administrator'))
                && (auth()->id() !== intval($address['user_id']))
                && ($request->get('customer_id', 0) !== $address['customer_id'])
            ),
            new NotAllowedException('This action is unauthorized.')
        );

        //update address with the data sent on the request
        $addressU = $this->addressRepository->update(
            $addressId,
            array_merge(
                $request->only(
                    [
                        'type',
                        'brand',
                        'first_name',
                        'last_name',
                        'street_line_1',
                        'street_line_2',
                        'city',
                        'zip',
                        'state',
                        'country',
                    ]
                ),
                [
                    'updated_on' => Carbon::now()->toDateTimeString(),
                ]
            )
        );

        return reply()->json($addressU, [
            'code' => 201
        ]);
    }

    /**
     * Delete an address based on the id.
     * Return - NotFoundException if the address not exists
     *        - NotAllowedException if the address it's in used (exists orders defined for the selected address)  or
     * the user have not rights to access it
     *        - JsonResponse with code 204 otherwise
     *
     * @param integer $addressId
     * @param AddressDeleteRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function delete($addressId, AddressDeleteRequest $request)
    {
        $address = $this->addressRepository->read($addressId);
        throw_if(
            is_null($address),
            new NotFoundException('Delete failed, address not found with id: ' . $addressId)
        );

        throw_if(
            (
                (!$this->permissionService->is(auth()->id(), 'administrator'))
                && (auth()->id() !== intval($address['user_id']))
                && ($request->get('customer_id', 0) !== $address['customer_id'])
            ),
            new NotAllowedException('This action is unauthorized.')
        );

        $results = $this->addressRepository->destroy($addressId);

        //if the delete method response it's null the product not exist; we throw the proper exception

        throw_if(
            ($results === -1),
            new NotAllowedException('Delete failed, exists orders defined for the selected address.')
        );

        return reply()->json(null, ['code' => 204]);
    }
}