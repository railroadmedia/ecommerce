<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class PaymentGatewayUpdateRequest extends FormRequest
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
            'type' => 'nullable|max:255',
            'name' => 'nullable|max:255',
            'config' => 'nullable|max:255',
            'brand' => 'max:255|nullable'
        ];
    }
}