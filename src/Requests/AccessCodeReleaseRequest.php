<?php

namespace Railroad\Ecommerce\Requests;


use Railroad\Ecommerce\Services\ConfigService;

class AccessCodeReleaseRequest extends FormRequest
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
            'access_code_id' => 'required|max:24|exists:'
            . config('ecommerce.database_connection_name') . '.'
            . 'ecommerce_access_codes' . ',id,is_claimed,1'
        ];
    }
}
