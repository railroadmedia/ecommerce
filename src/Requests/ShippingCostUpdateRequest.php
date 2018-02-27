<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class ShippingCostUpdateRequest extends FormRequest
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
            'shipping_option_id' => 'numeric|exists:'.ConfigService::$tableShippingOption.',id',
            'min' => 'numeric|min:0',
            'max' =>'numeric|min:'.request('min'),
            'price' => 'numeric|min:0'
        ];
    }
}