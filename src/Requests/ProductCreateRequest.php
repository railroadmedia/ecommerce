<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class ProductCreateRequest extends FormRequest
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
            'name' => 'required|max:255',
            'sku' => 'required|unique:'.ConfigService::$tableProduct.'|max:255',
            'price' => 'required|numeric|min:0',
            'type' => 'required|max:255|in:' .
                implode(
                    ',',
                    [
                        ConfigService::$typeProduct,
                        ConfigService::$typeSubscription
                    ]
                ),
            'active' => 'required|boolean',
            'is_physical' => 'required|boolean',
            'weight' => 'required_if:is_physical,true',
            'stock' => 'nullable|numeric',
            'subscription_interval_type' => 'required_if:type,' . ConfigService::$typeSubscription,
            'subscription_interval_count' => 'required_if:type,' . ConfigService::$typeSubscription
        ];
    }
}