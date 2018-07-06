<?php

namespace Railroad\Ecommerce\Requests;


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
            'city' => 'required|max:255',
            'zip' => 'required|max:255',
            'state' => 'required|max:255',
            'country' => 'required|max:255|in:' . implode(',', LocationService::countries()),
            'user_id' => 'integer|nullable',
            'customer_id' => 'integer|nullable|exists:'.ConfigService::$tableCustomer.',id'
        ];
    }
}