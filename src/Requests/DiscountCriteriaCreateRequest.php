<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Entities\DiscountCriteria;

class DiscountCriteriaCreateRequest extends FormRequest
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
            'data.attributes.name' => 'name',
            'data.attributes.type' => 'type',
            'data.attributes.min' => 'min',
            'data.attributes.max' => 'max',
            'data.attributes.products_relation_type' => 'products relation type',
            'data.relationships.products.data.*.type' => 'product type',
            'data.relationships.products.data.*.id' => 'product id',
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
            'data.type' => 'in:discountCriteria',
            'data.attributes.name' => 'required|max:255',
            'data.attributes.type' => 'required|max:255',
            'data.attributes.min' => 'required',
            'data.attributes.max' => 'required',
            'data.attributes.products_relation_type' => 'required|in:' . implode(
                    ',',
                    [
                        DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY,
                        DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
                    ]
                ),
            'data.relationships.products.data.*.type' => 'required|in:product',
            'data.relationships.products.data.*.id' => 'required|numeric|exists:ecommerce_products,id',
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return $this->only(
            [
                'data.attributes.type',
                'data.attributes.name',
                'data.attributes.type',
                'data.attributes.min',
                'data.attributes.max',
                'data.attributes.products_relation_type',
                'data.relationships.products',
            ]
        );
    }
}