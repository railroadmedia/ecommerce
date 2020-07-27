<?php

namespace Railroad\Ecommerce\Requests;

class UserProductCreateRequest extends FormRequest
{
    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'data.type' => 'json data type',
            'data.attributes.quantity' => 'quantity',
            'data.relationships.product.data.id' => 'product',
            'data.relationships.user.data.id' => 'user id',
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
            'data.type' => 'in:userProduct',
            'data.attributes.quantity' => 'required|numeric',
            'data.attributes.start_date' => 'date|nullable',
            'data.attributes.expiration_date' => 'date|nullable',
            'data.relationships.user.data.id' => 'required|integer',
            'data.relationships.product.data.id' => 'required|numeric|exists:' . 'ecommerce_products' . ',id',
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return array_merge(
            $this->only(
                [
                    'data.attributes.quantity',
                    'data.attributes.start_date',
                    'data.attributes.expiration_date',
                    'data.relationships.user',
                    'data.relationships.product',
                ]
            )
        );
    }
}
