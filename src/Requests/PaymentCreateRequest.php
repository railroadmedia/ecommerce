<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class PaymentCreateRequest extends FormRequest
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
            'due' => 'required|numeric',
            'paid' => 'numeric|nullable',
            'refunded' => 'numeric|nullable',
            'type' => 'required',
            'payment_method_id' => 'numeric|nullable|exists:'.ConfigService::$tablePaymentMethod.',id',
            'order_id' => 'numeric|exists:'.ConfigService::$tableOrder.',id',
            'subscription_id'  => 'numeric|exists:'.ConfigService::$tableSubscription.',id',
        ];
    }
}