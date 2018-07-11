<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class RefundCreateRequest extends FormRequest
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
            'refund_amount' => 'required|numeric',
            'note' => 'max:255',
            'payment_id' => 'numeric|exists:' . ConfigService::$tablePayment . ',id',
            'gateway_name' => 'required'
        ];
    }
}