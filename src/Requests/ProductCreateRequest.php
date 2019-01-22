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
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'data.type' => 'json data type',
            'data.attributes.brand' => 'brand',
            'data.attributes.name' => 'name',
            'data.attributes.sku' => 'sku',
            'data.attributes.price' => 'price',
            'data.attributes.type' => 'type',
            'data.attributes.active' => 'active',
            'data.attributes.category' => 'category',
            'data.attributes.description' => 'description',
            'data.attributes.thumbnail_url' => 'thumbnail url',
            'data.attributes.is_physical' => 'is physical',
            'data.attributes.weight' => 'weight',
            'data.attributes.subscription_interval_type' => 'subscription interval type',
            'data.attributes.subscription_interval_count' => 'subscription interval count',
            'data.attributes.stock' => 'stock',
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
            'data.attributes.name' => 'required|max:255',
            'data.attributes.sku' => 'required|unique:'.ConfigService::$tableProduct.',sku|max:255',
            'data.attributes.price' => 'required|numeric|min:0',
            'data.attributes.type' => 'required|max:255|in:' .
                implode(
                    ',',
                    [
                        ConfigService::$typeProduct,
                        ConfigService::$typeSubscription
                    ]
                ),
            'data.attributes.active' => 'required|boolean',
            'data.attributes.is_physical' => 'required|boolean',
            'data.attributes.weight' => 'required_if:is_physical,true',
            'data.attributes.stock' => 'nullable|numeric',
            'data.attributes.subscription_interval_type' => 'required_if:type,' . ConfigService::$typeSubscription,
            'data.attributes.subscription_interval_count' => 'required_if:type,' . ConfigService::$typeSubscription
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return $this->only(
                [
                    'data.type',
                    'data.attributes.name',
                    'data.attributes.brand',
                    'data.attributes.sku',
                    'data.attributes.price',
                    'data.attributes.type',
                    'data.attributes.active',
                    'data.attributes.category',
                    'data.attributes.description',
                    'data.attributes.thumbnail_url',
                    'data.attributes.is_physical',
                    'data.attributes.weight',
                    'data.attributes.subscription_interval_type',
                    'data.attributes.subscription_interval_count',
                    'data.attributes.stock',
                ]
            );
    }
}