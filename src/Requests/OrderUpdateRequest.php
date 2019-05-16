<?php

namespace Railroad\Ecommerce\Requests;

class OrderUpdateRequest extends FormRequest
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
            'data.attributes.total_due' => 'total due',
            'data.attributes.taxes_due' => 'taxes due',
            'data.attributes.shipping_due' => 'shipping due',
            'data.attributes.total_paid' => 'total paid',
            'data.attributes.note' => 'note',
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
            'data.type' => 'in:order',
            'data.attributes.total_due' => 'numeric|min:0',
            'data.attributes.taxes_due' => 'numeric|min:0',
            'data.attributes.shipping_due' => 'numeric|min:0',
            'data.attributes.total_paid' => 'numeric|min:0',
            'data.attributes.note' => 'nullable|string',
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return $this->only(
            [
                'data.attributes.total_due',
                'data.attributes.taxes_due',
                'data.attributes.shipping_due',
                'data.attributes.total_paid',
                'data.attributes.note',
            ]
        );
    }
}
