<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Services\ConfigService;

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
            'access_code' => 'required|max:24|exists:'
            . ConfigService::$databaseConnectionName . '.'
            . ConfigService::$tableAccessCode . ',code,is_claimed,0',
            'claim_for_user_id' => 'required|integer'
        ];
    }
}
