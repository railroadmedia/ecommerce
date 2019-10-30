<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Entities\Subscription;

class FailedBillingSubscriptionsRequest extends FormRequest
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
            'type' => 'required|in:"' . Subscription::TYPE_SUBSCRIPTION . '", "' . Subscription::TYPE_PAYMENT_PLAN . '"',
            'small_date_time' => 'nullable|date',
            'big_date_time' => 'nullable|date',
        ];
    }
}
