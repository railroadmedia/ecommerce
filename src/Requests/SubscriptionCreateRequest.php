<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Entities\Subscription;

class SubscriptionCreateRequest extends FormRequest
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
            'data.attributes.brand' => 'brand',
            'data.attributes.type' => 'type',
            'data.attributes.is_active' => 'is_active',
            'data.attributes.start_date' => 'start_date',
            'data.attributes.paid_until' => 'paid_until',
            'data.attributes.canceled_on' => 'canceled_on',
            'data.attributes.note' => 'note',
            'data.attributes.total_price' => 'total_price',
            'data.attributes.currency' => 'currency',
            'data.attributes.interval_type' => 'interval_type',
            'data.attributes.interval_count' => 'interval_count',
            'data.attributes.total_cycles_due' => 'total_cycles_due',
            'data.attributes.total_cycles_paid' => 'total_cycles_paid',
            'data.relationships.order.data.id' => 'order',
            'data.relationships.product.data.id' => 'product',
            'data.relationships.paymentMethod.data.id' => 'paymentMethod',
            'data.relationships.user.data.id' => 'user id',
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
            'data.type' => 'in:subscription',
            'data.attributes.brand' => 'required|max:255',
            'data.attributes.type' => 'required|max:255|in:' . implode(
                    ',',
                    [
                        config('ecommerce.type_payment_plan'),
                        Subscription::TYPE_SUBSCRIPTION
                    ]
                ),
            'data.attributes.is_active' => 'required|boolean',
            'data.attributes.start_date' => 'required|date',
            'data.attributes.paid_until' => 'required|date',
            'data.attributes.canceled_on' => 'nullable|date',
            'data.attributes.note' => 'nullable',
            'data.attributes.total_price' => 'required|numeric|min:0',
            'data.attributes.currency' => 'required|max:3',
            'data.attributes.interval_type' => 'required|in:' . implode(
                    ',',
                    [
                        config('ecommerce.interval_type_yearly'),
                        config('ecommerce.interval_type_monthly'),
                        config('ecommerce.interval_type_daily')
                    ]
                ),
            'data.attributes.interval_count' => 'required|numeric|min:0',
            'data.attributes.total_cycles_due' => 'nullable|numeric|min:0',
            'data.attributes.total_cycles_paid' => 'required|numeric|min:0',
            'data.relationships.user.data.id' => 'required|integer',
            'data.relationships.order.data.id' => 'numeric|exists:' . 'ecommerce_orders' . ',id',
            'data.relationships.product.data.id' => 'numeric|exists:' . 'ecommerce_products' . ',id',
            'data.relationships.paymentMethod.data.id' => 'numeric|exists:' . 'ecommerce_payment_methods' . ',id',
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return $this->only(
            [
                'data.attributes.type',
                'data.attributes.brand',
                'data.attributes.is_active',
                'data.attributes.start_date',
                'data.attributes.paid_until',
                'data.attributes.canceled_on',
                'data.attributes.note',
                'data.attributes.total_price',
                'data.attributes.currency',
                'data.attributes.interval_type',
                'data.attributes.interval_count',
                'data.attributes.total_cycles_due',
                'data.attributes.total_cycles_paid',
                'data.relationships.order',
                'data.relationships.product',
                'data.relationships.paymentMethod',
                'data.relationships.user',
                'data.relationships.customer',
            ]
        );
    }
}