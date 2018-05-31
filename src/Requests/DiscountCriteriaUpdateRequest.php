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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'max:255',
            'type' => 'max:255',
            'product_id' => 'exists:'.ConfigService::$tableProduct.',id',
            'min' => 'numeric',
            'max' => 'numeric',
        ];
    }
}