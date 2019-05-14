<?php

namespace Railroad\Ecommerce\Requests;

class PaymentIndexRequest extends FormRequest
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
            'order_id' => 'numeric|exists:' . 'ecommerce_orders' . ',id|required_without:subscription_id',
            'subscription_id' => 'numeric|exists:' . 'ecommerce_subscriptions' . ',id|required_without:order_id',
        ];
    }
}