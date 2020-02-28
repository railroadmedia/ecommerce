<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Entities\MembershipStats;

class MembershipStatsRequest extends FormRequest
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
            'small_date_time' => 'date',
            'big_date_time' => 'date',
            'interval_type' => 'in:"' . MembershipStats::TYPE_ONE_MONTH
                                    . '", "' . MembershipStats::TYPE_SIX_MONTHS
                                    . '", "' . MembershipStats::TYPE_ONE_YEAR
                                    . '", "' . MembershipStats::TYPE_LIFETIME . '"',
        ];
    }
}
