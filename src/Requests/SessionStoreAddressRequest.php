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
            'billing-email'              => 'nullable|email',
            'billing-country'            => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
            'billing-region'             => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
            'billing-zip-or-postal-code' => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
            'shipping-address-line-1'    => 'nullable',
            'shipping-city'              => 'nullable|regex:/^[a-zA-Z-_ ]+$/',
            'shipping-country'           => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
            'shipping-first-name'        => 'nullable|regex:/^[a-zA-Z-_\' ]+$/',
            'shipping-last-name'         => 'nullable|regex:/^[a-zA-Z-_\' ]+$/',
            'shipping-region'            => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
            'shipping-zip'               => 'nullable|regex:/^[0-9a-zA-Z-_ ]+$/',
        ];
    }
}
