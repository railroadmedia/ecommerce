<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Services\ConfigService;

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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type'                    => 'max:255|in:' .
                implode(
                    ',',
                    [
                        ConfigService::$paymentPlanType,
                        ConfigService::$typeSubscription
                    ]
                ),
            'order_id'                => 'numeric|exists:' . ConfigService::$tableOrder . ',id',
            'product_id'              => 'numeric|exists:' . ConfigService::$tableProduct . ',id',
            'is_active'               => 'nullable|boolean',
            'start_date'              => 'nullable|date',
            'paid_until'              => 'nullable|date',
            'canceled_on'             => 'nullable|date',
            'note'                    => 'max:255',
            'total_price_per_payment' => 'nullable|numeric|min:0',
            'interval_type'           => 'nullable|in:' .
                implode(
                    ',',
                    [
                        ConfigService::$intervalTypeYearly,
                        ConfigService::$intervalTypeMonthly,
                        ConfigService::$intervalTypeDaily
                    ]
                ),
            'interval_count'          => 'nullable|numeric|min:0',
            'total_cycles_due'        => 'nullable|numeric|min:0',
            'total_cycles_paid'       => 'nullable|numeric|min:0',
            'payment_method_id'       => 'numeric|exists:' . ConfigService::$tablePaymentMethod . ',id'
        ];
    }
}