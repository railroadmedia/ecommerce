<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Entities\PaymentMethod;

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
            'card_token'  => 'required_if:method_type,' . PaymentMethod::TYPE_CREDIT_CARD,
            'gateway'     => 'required',
            'token'       => 'required_if:method_type,' . PaymentMethod::TYPE_PAYPAL,
            'address_id'  => 'required_if:method_type,' . PaymentMethod::TYPE_PAYPAL,
            'user_id'     => 'required_without:customer_id',
            'customer_id' => 'required_without:user_id'
        ];
    }
}
