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
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'data.type' => 'json data type',
            'data.attributes.due' => 'name',
            'data.attributes.paid' => 'type',
            'data.attributes.refunded' => 'min',
            'data.relationships.paymentMethod.data.id' => 'payment method',
            'data.relationships.order.data.id' => 'order',
            'data.relationships.subscription.data.id' => 'subscription'
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'data.type' => 'in:payment',
            'data.attributes.due' => 'required|numeric',
            'data.attributes.paid' => 'numeric|nullable',
            'data.attributes.refunded' => 'numeric|nullable',
            'data.relationships.paymentMethod.data.id' =>
                'numeric|nullable|exists:'.ConfigService::$tablePaymentMethod.',id',
            'data.relationships.order.data.id' => 'numeric|exists:'.ConfigService::$tableOrder.',id',
            'data.relationships.subscription.data.id' => 'numeric|exists:'.ConfigService::$tableSubscription.',id',
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return $this->only(
            [
                'data.attributes.due',
                'data.attributes.paid',
                'data.attributes.refunded',
                'data.relationships.paymentMethod',
                'data.relationships.order',
                'data.relationships.subscription'
            ]
        );
    }
}
