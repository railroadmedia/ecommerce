<?php

namespace Railroad\Ecommerce\Requests;

class PaymentGatewayCreateRequest extends FormRequest
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
            'type' => 'required|max:255',
            'name' => 'required|max:255',
            'config' => 'required|max:255',
            'brand' => 'max:255|nullable'
        ];
    }
}