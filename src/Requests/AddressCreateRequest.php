<?php

namespace Railroad\Ecommerce\Requests;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Location\Services\LocationService;

class AddressCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => 'required|max:255|in:' .
                implode(
                    ',',
                    [
                        ConfigService::$billingAddressType,
                        ConfigService::$shippingAddressType
                    ]
                ),
            'first_name' => 'nullable|max:255',
            'last_name' => 'nullable|max:255',
            'street_line_1' => 'nullable|max:255',
            'street_line_2' => 'nullable|max:255',
            'city' => 'nullable|max:255',
            'zip' => 'nullable|max:255',
            'state' => 'nullable|max:255',
            'country' => 'required|max:255|in:' . implode(',', LocationService::countries()),
            'user_id' => 'integer|nullable',
            'customer_id' => 'integer|nullable|exists:'.ConfigService::$tableCustomer.',id'
        ];
    }

    /**
     * @return Address
     */
    public function toEntity()
    {
        return $this->fromArray(
            Address::class,
            array_merge(
                $this->only(
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
                    'brand' => $this->input('brand', ConfigService::$brand),
                    'created_at' => Carbon::now(),
                ]
            )
        );
    }
}
