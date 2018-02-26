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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'country' => 'required|max:255',
            'priority' => 'required|numeric|min:0',
            'active' => 'required|boolean'
        ];
    }
}