<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Entities\Product;

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
            'data.attributes.fulfillment_sku' => 'fulfillment sku',
            'data.attributes.inventory_control_sku' => 'inventory control sku',
            'data.attributes.price' => 'price',
            'data.attributes.type' => 'type',
            'data.attributes.active' => 'active',
            'data.attributes.category' => 'category',
            'data.attributes.description' => 'description',
            'data.attributes.thumbnail_url' => 'thumbnail url',
            'data.attributes.sales_page_url' => 'sales page url',
            'data.attributes.is_physical' => 'is physical',
            'data.attributes.weight' => 'weight',
            'data.attributes.subscription_interval_type' => 'subscription interval type',
            'data.attributes.subscription_interval_count' => 'subscription interval count',
            'data.attributes.stock' => 'stock',
            'data.attributes.min_stock_level' => 'minimum stock level',
            'data.attributes.auto_decrement_stock' => 'auto decrement stock',
            'data.attributes.note' => 'note',
            'data.attributes.public_stock_count' => 'public stock count',
            'data.attributes.digital_access_permission_names' => 'digital access permission names',
            'data.attributes.digital_access_time_interval_length' => 'digital access time interval length',
            'data.attributes.digital_access_time_type' => 'digital access time type',
            'data.attributes.digital_access_time_interval_type' => 'digital access time interval type',
            'data.attributes.digital_access_type' => 'digital access type',
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
            'data.attributes.sku' => 'required|unique:' . 'ecommerce_products' . ',sku|max:255',
            'data.attributes.fulfillment_sku' => 'max:255',
            'data.attributes.inventory_control_sku' => 'max:255',
            'data.attributes.price' => 'required|numeric|min:0',
            'data.attributes.type' => 'required|max:255|in:' . implode(
                    ',',
                    [
                        Product::TYPE_DIGITAL_ONE_TIME,
                        Product::TYPE_DIGITAL_SUBSCRIPTION,
                        Product::TYPE_PHYSICAL_ONE_TIME
                    ]
                ),
            'data.attributes.active' => 'required|boolean',
            'data.attributes.is_physical' => 'required|boolean',
            'data.attributes.weight' => 'required_if:data.attributes.is_physical,true',
            'data.attributes.stock' => 'nullable|numeric',
            'data.attributes.min_stock_level' => 'nullable|numeric',
            'data.attributes.auto_decrement_stock' => 'boolean',
            'data.attributes.subscription_interval_type' => 'required_if:data.attributes.type,' .
                Product::TYPE_DIGITAL_SUBSCRIPTION,
            'data.attributes.subscription_interval_count' => 'required_if:data.attributes.type,' .
                Product::TYPE_DIGITAL_SUBSCRIPTION,
            'data.attributes.note' => 'nullable|string',
            'data.attributes.public_stock_count' => 'nullable|numeric',
            'data.attributes.digital_access_time_interval_length' => 'nullable|numeric',
            'data.attributes.digital_access_time_type' => 'nullable|max:255',
            'data.attributes.digital_access_time_interval_type' => 'nullable|max:255',
            'data.attributes.digital_access_type' => 'nullable|max:255',
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
                'data.attributes.fulfillment_sku',
                'data.attributes.inventory_control_sku',
                'data.attributes.price',
                'data.attributes.type',
                'data.attributes.active',
                'data.attributes.category',
                'data.attributes.description',
                'data.attributes.thumbnail_url',
                'data.attributes.sales_page_url',
                'data.attributes.is_physical',
                'data.attributes.weight',
                'data.attributes.subscription_interval_type',
                'data.attributes.subscription_interval_count',
                'data.attributes.stock',
                'data.attributes.min_stock_level',
                'data.attributes.auto_decrement_stock',
                'data.attributes.note',
                'data.attributes.public_stock_count',
                'data.attributes.digital_access_permission_names',
                'data.attributes.digital_access_time_interval_length',
                'data.attributes.digital_access_time_type',
                'data.attributes.digital_access_time_interval_type',
                'data.attributes.digital_access_type',
            ]
        );
    }
}