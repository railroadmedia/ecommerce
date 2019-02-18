<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class RefundCreateRequest extends FormRequest
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
            'data.attributes.refund_amount' => 'refund amount',
            'data.attributes.note' => 'note',
            'data.relationships.payment.data.id' => 'payment',
            'data.attributes.gateway_name' => 'gateway name',
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
            'data.type' => 'in:refund',
            'data.attributes.refund_amount' => 'required|numeric',
            'data.attributes.note' => 'max:255',
            'data.attributes.gateway_name' => 'required',
            'data.relationships.payment.data.id' => 'required|numeric|exists:' . ConfigService::$tablePayment . ',id'
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return $this->only(
            [
                'data.attributes.refund_amount',
                'data.attributes.note',
                'data.attributes.gateway_name',
                'data.relationships.payment',
            ]
        );
    }
}