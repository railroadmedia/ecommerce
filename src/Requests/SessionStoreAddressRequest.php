<?php

namespace Railroad\Ecommerce\Requests;

class SessionStoreAddressRequest extends FormRequest
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
            'billing_address_id' => 'nullable|integer',
            'billing_email' => 'nullable|email',
            'billing_country' => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
            'billing_region' => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
            'billing_zip_or_postal_code' => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
            'shipping_address_id' => 'nullable|integer',
            'shipping_address_line_1' => 'nullable',
            'shipping_city' => 'nullable|regex:/^[a-zA-Z-_ ]+$/',
            'shipping_country' => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
            'shipping_first_name' => 'nullable|regex:/^[a-zA-Z-_\' ]+$/',
            'shipping_last_name' => 'nullable|regex:/^[a-zA-Z-_\' ]+$/',
            'shipping_region' => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
            'shipping_zip' => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
        ];
    }
}
