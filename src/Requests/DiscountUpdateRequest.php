<?php

namespace Railroad\Ecommerce\Requests;

class DiscountUpdateRequest extends FormRequest
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
            'data.attributes.expiration_date' => 'expiration date',
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
            'data.attributes.name' => 'max:255',
            'data.attributes.description' => 'max:255',
            'data.attributes.type' => 'max:255',
            'data.attributes.amount' => 'numeric',
            'data.attributes.active' => 'boolean',
            'data.attributes.visible' => 'boolean',
            'data.attributes.expiration_date' => 'nullable|date',
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
                'data.attributes.type',
                'data.attributes.name',
                'data.attributes.description',
                'data.attributes.type',
                'data.attributes.product_category',
                'data.attributes.amount',
                'data.attributes.active',
                'data.attributes.visible',
                'data.attributes.aux',
                'data.attributes.expiration_date',
                'data.attributes.note',
                'data.relationships.product',
            ]
        );
    }
}