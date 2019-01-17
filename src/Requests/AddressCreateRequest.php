<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Location\Services\LocationService;

class AddressCreateRequest extends FormRequest
{
    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'data.type' => 'json data type',
            'data.attributes.type' => 'type',
            'data.attributes.first_name' => 'first name',
            'data.attributes.last_name' => 'last name',
            'data.attributes.street_line_1' => 'street line 1',
            'data.attributes.street_line_2' => 'street line 2',
            'data.attributes.city' => 'city',
            'data.attributes.zip' => 'zip',
            'data.attributes.state' => 'state',
            'data.attributes.country' => 'country',
            'data.attributes.user_id' => 'user id',
            'data.attributes.customer_id' => 'customer id',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'data.type' => 'in:address',
            'data.attributes.type' => 'required|max:255|in:' . implode(
                    ',',
                    [
                        ConfigService::$billingAddressType,
                        ConfigService::$shippingAddressType,
                    ]
                ),
            'data.attributes.first_name' => 'nullable|max:255',
            'data.attributes.last_name' => 'nullable|max:255',
            'data.attributes.street_line_1' => 'nullable|max:255',
            'data.attributes.street_line_2' => 'nullable|max:255',
            'data.attributes.city' => 'nullable|max:255',
            'data.attributes.zip' => 'nullable|max:255',
            'data.attributes.state' => 'nullable|max:255',
            'data.attributes.country' => 'required|max:255|in:' . implode(',', LocationService::countries()),

            // todo: use proper json API spec structure for changing relationships

//            'data.attributes.user_id' => 'integer|nullable',
//            'data.attributes.customer_id' => 'integer|nullable|exists:' . ConfigService::$tableCustomer . ',id',
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return array_merge(
            $this->only(
                [
                    'data.attributes.type',
                    // todo: use proper json API spec structure for changing relationships
//                    'data.attributes.user_id',
//                    'data.attributes.customer_id',
                    'data.attributes.first_name',
                    'data.attributes.last_name',
                    'data.attributes.street_line_1',
                    'data.attributes.street_line_2',
                    'data.attributes.city',
                    'data.attributes.zip',
                    'data.attributes.state',
                    'data.attributes.country',
                    'data.relationships.user',
                    'data.relationships.customer',
                ]
            )
        );
    }
}
