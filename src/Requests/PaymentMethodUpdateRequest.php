<?php

namespace Railroad\Ecommerce\Requests;

class PaymentMethodUpdateRequest extends FormRequest
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
            'gateway' => 'required',
            'year' => 'required|numeric',
            'month' => 'required|numeric',
            'country' => 'required|string',
        ];
    }
}