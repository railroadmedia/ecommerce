<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class ShippingCostUpdateRequest extends FormRequest
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
            'data.attributes.min' => 'min',
            'data.attributes.max' => 'max',
            'data.attributes.price' => 'price',
            'data.relationships.shippingOption.data.id' => 'shipping option'
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
            'data.type' => 'in:shippingCostsWeightRange',
            'data.relationships.shippingOption.data.id' => 'numeric|exists:'.'ecommerce_shipping_options'.',id',
            'data.attributes.min' => 'numeric|min:0',
            'data.attributes.max' =>'numeric|gte:data.attributes.min',
            'data.attributes.price' => 'numeric|min:0'
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return $this->only(
            [
                'data.attributes.min',
                'data.attributes.max',
                'data.attributes.price',
                'data.relationships.shippingOption'
            ]
        );
    }
}