<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class ProductUpdateRequest extends FormRequest
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
            'name' => 'max:255',
            'sku' => 'unique:'.ConfigService::$tableProduct.'|max:255',
            'price' => 'numeric|min:0',
            'type' => 'max:255|in:' .
                implode(
                    ',',
                    [
                        config('constants.TYPE_PRODUCT'),
                        config('constants.TYPE_SUBSCRIPTION')
                    ]
                ),
            'active' => 'boolean',
            'is_physical' => 'boolean',
            'weight' => 'required_if:is_physical,true',
            'stock' => 'numeric',
            'subscription_interval_type' => 'required_if:type,' . config('constants.TYPE_SUBSCRIPTION'),
            'subscription_interval_count' => 'required_if:type,' . config('constants.TYPE_SUBSCRIPTION')
        ];
    }
}