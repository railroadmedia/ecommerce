<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Location\Services\CountryListService;

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
            'billing_country' => 'nullable|in:' . implode(',', CountryListService::all()),
            'billing_region' => 'nullable',
            'billing_zip_or_postal_code' => 'nullable',
            'shipping_address_id' => 'nullable|integer',
            'shipping_address_line_1' => 'nullable',
            'shipping_city' => 'nullable',
            'shipping_country' => 'nullable|in:' . implode(',', CountryListService::allWeCanShipTo()),
            'shipping_first_name' => 'nullable',
            'shipping_last_name' => 'nullable',
            'shipping_region' => 'nullable',
            'shipping_zip' => 'nullable',
        ];
    }
}
