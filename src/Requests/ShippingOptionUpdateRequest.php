<?php

namespace Railroad\Ecommerce\Requests;


class ShippingOptionUpdateRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'country' => 'max:255',
            'priority' => 'numeric|min:0',
            'active' => 'boolean'
        ];
    }
}