<?php

namespace Railroad\Ecommerce\Requests;

class DiscountCriteriaUpdateRequest extends FormRequest
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
            'data.attributes.max' => 'max'
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
            'data.attributes.type' => 'max:255',
            'data.relationships.product.id' => 'nullable|exists:' . 'ecommerce_products' . ',id',
            'data.attributes.min' => '',
            'data.attributes.max' => ''
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
                'data.relationships.product'
            ]
        );
    }
}