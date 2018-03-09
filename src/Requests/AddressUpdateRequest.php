<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\AddressService;

class AddressUpdateRequest extends FormRequest
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
            'type' => 'max:255|in:' .
                implode(
                    ',',
                    [
                        AddressService::BILLING_ADDRESS,
                        AddressService::SHIPPING_ADDRESS
                    ]
                ),
            'first_name' => 'max:255',
            'last_name' => 'max:255',
            'street_line_1' => 'max:255',
            'street_line_2' => 'max:255',
            'city' => 'max:255',
            'zip' => 'max:255',
            'state' => 'max:255',
            'country' => 'max:255',
            'user_id' => 'required_without:customer_id',
            'customer_id' => 'required_without:user_id'
        ];
    }
}