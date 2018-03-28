<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\AddressService;
use Railroad\Ecommerce\Services\ConfigService;

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
                        AddressService::BILLING_ADDRESS,
                        AddressService::SHIPPING_ADDRESS
                    ]
                ),
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'street_line_1' => 'required|max:255',
            'street_line_2' => 'nullable|max:255',
            'city' => 'required|max:255',
            'zip' => 'required|max:255',
            'state' => 'required|max:255',
            'country' => 'required|max:255|country',
            'user_id' => 'numeric',
            'customer_id' => 'numeric|exists:'.ConfigService::$tableCustomer.',id'
        ];
    }
}