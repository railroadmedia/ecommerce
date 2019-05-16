<?php

namespace Railroad\Ecommerce\Requests;

class DiscountCreateRequest extends FormRequest
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
            'data.attributes.description' => 'description',
            'data.attributes.type' => 'type',
            'data.attributes.amount' => 'amount',
            'data.attributes.active' => 'active',
            'data.attributes.visible' => 'visible',
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
            'data.type' => 'in:discount',
            'data.attributes.name' => 'required|max:255',
            'data.attributes.description' => 'required|max:255',
            'data.attributes.type' => 'required|max:255',
            'data.attributes.amount' => 'required|numeric',
            'data.attributes.active' => 'required|boolean',
            'data.attributes.visible' => 'required|boolean',
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
                'data.attributes.name',
                'data.attributes.description',
                'data.attributes.type',
                'data.attributes.product_category',
                'data.attributes.amount',
                'data.attributes.active',
                'data.attributes.visible',
                'data.attributes.note',
                'data.relationships.product'
            ]
        );
    }
}