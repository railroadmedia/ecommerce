<?php

namespace Railroad\Ecommerce\Requests;


class ShippingOptionCreateRequest extends FormRequest
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
            'data.attributes.country' => 'country',
            'data.attributes.priority' => 'priority',
            'data.attributes.active' => 'active'
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
            'data.type' => 'in:shippingOption',
            'data.attributes.country' => 'required|max:255',
            'data.attributes.priority' => 'required|numeric|min:0',
            'data.attributes.active' => 'required|boolean'
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return $this->only(
            [
                'data.attributes.country',
                'data.attributes.priority',
                'data.attributes.active'
            ]
        );
    }
}
