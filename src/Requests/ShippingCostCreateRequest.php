<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class ShippingCostCreateRequest extends FormRequest
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
            'shipping_option_id' => 'required|numeric|exists:'.ConfigService::$tableShippingOption.',id',
            'min' => 'required|numeric|min:0',
            'max' =>'required|numeric|min:0',
            'price' => 'required|numeric|min:0'
        ];
    }
}