<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class OrderFulfilledRequest extends FormRequest
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
            'tracking_number' => 'required|max:255',
            'shipping_company' => 'required|max:255',
            'order_item_id'  => 'integer|nullable|exists:'.ConfigService::$tableOrderItemFulfillment.',order_item_id',
            'order_id' => 'integer|required|exists:'.ConfigService::$tableOrderItemFulfillment.',order_id'
        ];
    }
}