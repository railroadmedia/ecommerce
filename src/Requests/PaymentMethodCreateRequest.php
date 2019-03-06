<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Services\PaymentMethodService;

class PaymentMethodCreateRequest extends FormRequest
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
            'method_type' => 'required|max:255',
            'card_token'  => 'required_if:method_type,' . PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'gateway'     => 'required',
            'token'       => 'required_if:method_type,' . PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'address_id'  => 'required_if:method_type,' . PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'user_id'     => 'required_without:customer_id',
            'customer_id' => 'required_without:user_id'
        ];
    }
}
