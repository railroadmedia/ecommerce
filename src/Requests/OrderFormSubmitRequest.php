<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Services\PaymentMethodService;

class OrderFormSubmitRequest extends FormRequest
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
        $minMonth = (request()->get('credit-card-year-selector') == date('Y')) ? '|min:'.intval(date('n')) : '';

        return [
            'payment-type-selector' => 'required',

            'billing-region'             => 'required|regex:/^[0-9a-zA-Z-_ ]+$/',
            'billing-zip-or-postal-code' => 'required|regex:/^[0-9a-zA-Z-_ ]+$/',
            'billing-country'            => 'required|regex:/^(?!Country$)/',

            'credit-card-month-selector' => 'required_if:payment-type-selector,' .
                PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE . '|numeric' . $minMonth,
            'credit-card-year-selector'  => 'required_if:payment-type-selector,' .
                PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE . '|numeric|min:' . intval(date('Y')),
            'credit-card-number'         => 'required_if:payment-type-selector,' .
                PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'credit-card-cvv'            => 'numeric|digits_between:3,4|required_if:payment-type-selector,' .
                PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE
        ];
    }
}