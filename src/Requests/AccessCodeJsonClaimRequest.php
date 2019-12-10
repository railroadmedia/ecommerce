<?php

namespace Railroad\Ecommerce\Requests;

class AccessCodeJsonClaimRequest extends FormRequest
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
            'access_code' => 'required|max:24|exists:' .
                config('ecommerce.database_connection_name') .
                '.' .
                'ecommerce_access_codes' .
                ',code,is_claimed,0',
            'claim_for_user_id' => 'required|integer',
            'context' => 'string|nullable',
        ];
    }
}
