<?php

namespace Railroad\Ecommerce\Requests;

class AppleReceiptRequest extends FormRequest
{
    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'data.type' => 'json data type',
            'data.attributes.receipt' => 'receipt',
            'data.attributes.email' => 'email',
            'data.attributes.password' => 'password',
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
            'data.type' => 'in:appleReceipt',
            'data.attributes.receipt' => 'required',
            'data.attributes.email' => 'nullable|max:255',
            'data.attributes.password' => 'nullable|max:255',
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return array_merge(
            $this->only(
                [
                    'data.attributes.type',
                    'data.attributes.receipt',
                    'data.attributes.email',
                    'data.attributes.password',
                ]
            ),
            [
                'data.attributes.brand' => $this->input('brand', config('ecommerce.brand')),
            ]
        );
    }
}
