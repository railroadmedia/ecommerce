<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Entities\GoogleReceipt;

class GoogleReceiptRequest extends FormRequest
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
            'data.attributes.package_name' => 'package name',
            'data.attributes.product_id' => 'product id',
            'data.attributes.purchase_token' => 'purchase token',
            'data.attributes.email' => 'email',
            'data.attributes.password' => 'password',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'data.type' => 'in:googleReceipt',
            'data.attributes.package_name' => 'required',
            'data.attributes.product_id' => 'required',
            'data.attributes.purchase_token' => 'required',
        ];

        if (!auth()->user()) {
            $rules += [
                'data.attributes.email' => 'required|max:255|unique:' .
                    config('ecommerce.database_info_for_unique_user_email_validation.database_connection_name') .
                    '.' .
                    config('ecommerce.database_info_for_unique_user_email_validation.table') .
                    ',' .
                    config('ecommerce.database_info_for_unique_user_email_validation.email_column'),
                'data.attributes.password' => 'required|max:255',
            ];
        }

        return $rules;
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        $data = $this->only(
            [
                'data.attributes.type',
                'data.attributes.package_name',
                'data.attributes.product_id',
                'data.attributes.purchase_token',
                'data.attributes.currency',
                'data.attributes.price',
                'data.attributes.email',
                'data.attributes.password',
            ]
        );

        $data['data']['attributes']['brand'] = 'drumeo';
        $data['data']['attributes']['requestType'] = GoogleReceipt::MOBILE_APP_REQUEST_TYPE;

        return $data;
    }
}
