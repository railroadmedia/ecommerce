<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Services\ConfigService;

class DiscountCriteriaUpdateRequest extends FormRequest
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
            'data.attributes.name' => 'name',
            'data.attributes.type' => 'type',
            'data.attributes.min' => 'min',
            'data.attributes.max' => 'max'
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
            'data.attributes.name' => 'required|max:255',
            'data.attributes.type' => 'required|max:255',
            'data.relationships.product.id' => 'nullable|exists:'.ConfigService::$tableProduct.',id',
            'data.attributes.min' => 'required',
            'data.attributes.max' => 'required'
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
                'data.attributes.name',
                'data.attributes.type',
                'data.attributes.min',
                'data.attributes.max',
                'data.relationships.product'
            ]
        );
    }
}