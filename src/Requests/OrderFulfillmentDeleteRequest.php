<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class OrderFulfillmentDeleteRequest extends FormRequest
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
            'order_item_id'  => 'integer|nullable|exists:'.ConfigService::$tableOrderItemFulfillment.',order_item_id',
            'order_id' => 'integer|required|exists:'.ConfigService::$tableOrderItemFulfillment.',order_id'
        ];
    }
}