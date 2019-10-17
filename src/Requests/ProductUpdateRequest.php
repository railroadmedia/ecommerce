<?php

namespace Railroad\Ecommerce\Requests;

use Illuminate\Support\Facades\Request;
use Railroad\Ecommerce\Entities\Product;

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
            'data.attributes.note' => 'note',
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
            'data.attributes.name' => 'max:255',
            'data.attributes.sku' => 'unique:' .
                'ecommerce_products' .
                ',sku,' .
                Request::route('productId') .
                '|max:255',
            'data.attributes.price' => 'numeric|min:0',
            'data.attributes.type' => 'max:255|in:' . implode(
                    ',',
                    [
                        Product::TYPE_DIGITAL_ONE_TIME,
                        Product::TYPE_DIGITAL_SUBSCRIPTION,
                        Product::TYPE_PHYSICAL_ONE_TIME
                    ]
                ),
            'data.attributes.active' => 'boolean',
            'data.attributes.is_physical' => 'boolean',
            'data.attributes.weight' => 'required_if:data.attributes.is_physical,true',
            'data.attributes.stock' => 'nullable|numeric',
            'data.attributes.subscription_interval_type' => 'required_if:data.attributes.type,' .
                Product::TYPE_DIGITAL_SUBSCRIPTION,
            'data.attributes.subscription_interval_count' => 'required_if:data.attributes.type,' .
                Product::TYPE_DIGITAL_SUBSCRIPTION,
            'data.attributes.note' => 'nullable|string',
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
                'data.attributes.note',
            ]
        );
    }
}
