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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max:255',
            'description' => 'required|max:255',
            'type' => 'required|max:255',
            'amount' => 'required|numeric',
            'active' => 'required|boolean'
        ];
    }
}