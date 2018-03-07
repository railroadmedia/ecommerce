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
            'card_year' => 'required_if:method_type,' . PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card_month' => 'required_if:method_type,' . PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card_fingerprint' => 'required_if:method_type,' . PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card_number_last_four_digits' => 'required_if:method_type,' . PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'company_name' => 'required_if:method_type,' . PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'external_id' => 'required_if:method_type,' . PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'agreement_id'  => 'required_if:method_type,' . PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'express_checkout_token'  => 'required_if:method_type,' . PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'address_id'  => 'required_if:method_type,' . PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
        ];
    }
}