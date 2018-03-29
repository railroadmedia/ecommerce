<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;

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
            'method_type' => 'max:255',
            'update_method' => 'required',
            'card_year' => 'required_if:update_method,' .
                PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD . ',' .
                PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD,
            'card_month' => 'required_if:update_method,' .
                PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD . ',' .
                PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD,
            'card_fingerprint' => 'required_if:update_method,' . PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD,
            'card_number_last_four_digits' => 'required_if:update_method,' . PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD,
            'company_name' => 'required_if:update_method,' . PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD,
            'external_id' => 'required_if:update_method,' . PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD,


            'agreement_id' => 'required_if:update_method,' . PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL,
            'express_checkout_token' => 'required_if:update_method,' . PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL,
            'address_id' => 'required_if:update_method,' . PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL,
            'user_id' => 'numeric',
            'customer_id' => 'numeric|exists:'.ConfigService::$tableCustomer.',id'
        ];
    }

    /**
     * Customize some error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'card_year.required_if' => 'The card year field is required when create or update a credit card.',
            'card_month.required_if' => 'The card month field is required when create or update a credit card.',
            'card_fingerprint.required_if' => 'The card finger print field is required when create a new credit card.',
            'card_number_last_four_digits.required_if' => 'The card last four digits field is required when create a new credit card.',
            'company_name.required_if' => 'The company name field is required when create a new credit card.',
            'external_id.required_if' => 'The external ID field is required when create a new credit card.',
            'agreement_id.required_if' => 'The agreement id field is required when update payment method and use paypal.',
            'express_checkout_token.required_if' => 'The express checkout token field is required when update payment method and use paypal.',
            'address_id.required_if' => 'The address id field is required when update payment method and use paypal.'
        ];
    }
}