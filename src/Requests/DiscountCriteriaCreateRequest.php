<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Services\ConfigService;

class DiscountCriteriaCreateRequest extends FormRequest
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
            'name' => 'required|max:255',
            'type' => 'required|max:255',
            'product_id' => 'required|exists:'.ConfigService::$tableProduct.',id',
            'min' => 'numeric',
            'max' => 'numeric',
        ];
    }
}