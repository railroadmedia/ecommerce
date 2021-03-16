<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Entities\Address;

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
            'data.attributes.brand' => 'brand',
            'data.attributes.type' => 'type',
            'data.attributes.first_name' => 'first name',
            'data.attributes.last_name' => 'last name',
            'data.attributes.street_line_1' => 'street line 1',
            'data.attributes.street_line_2' => 'street line 2',
            'data.attributes.city' => 'city',
            'data.attributes.zip' => 'zip',
            'data.attributes.region' => 'region',
            'data.attributes.country' => 'country',
            'data.attributes.note' => 'note',
            'data.attributes.user_id' => 'user id',
            'data.relationships.customer.data.type' => 'customer type',
            'data.relationships.customer.data.id' => 'customer id',
            'data.relationships.user.data.type' => 'user type',
            'data.relationships.user.data.id' => 'user id',
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
            'data.attributes.brand' => 'required',
            'data.attributes.type' => 'required|max:255|in:' . implode(
                    ',',
                    [
                        Address::BILLING_ADDRESS_TYPE,
                        Address::SHIPPING_ADDRESS_TYPE,
                    ]
                ),
            'data.attributes.first_name' => 'nullable|max:255',
            'data.attributes.last_name' => 'nullable|max:255',
            'data.attributes.street_line_1' => 'nullable|max:255',
            'data.attributes.street_line_2' => 'nullable|max:255',
            'data.attributes.city' => 'nullable|max:255',
            'data.attributes.zip' => 'nullable|max:255',
            'data.attributes.region' => 'nullable|max:255',
            'data.attributes.country' => 'required|max:255|in:' . implode(',', CountryListService::allWeCanShipTo()),
            'data.attributes.note' => 'nullable|string',
            'data.relationships.customer.data.type' => 'nullable|in:customer',
            'data.relationships.customer.data.id' => 'integer|nullable|exists:' . 'ecommerce_customers' . ',id',
            'data.relationships.user.data.type' => 'nullable|in:user',
            'data.relationships.user.data.id' => 'integer|nullable',
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
                    'data.attributes.brand',
                    'data.attributes.type',
                    'data.attributes.first_name',
                    'data.attributes.last_name',
                    'data.attributes.street_line_1',
                    'data.attributes.street_line_2',
                    'data.attributes.city',
                    'data.attributes.zip',
                    'data.attributes.region',
                    'data.attributes.country',
                    'data.attributes.note',
                    'data.relationships.user',
                    'data.relationships.customer',
                ]
            )
        );
    }
}
