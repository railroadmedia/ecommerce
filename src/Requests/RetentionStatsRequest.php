<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Ecommerce\Entities\RetentionStats;

class RetentionStatsRequest extends FormRequest
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
            'interval_type' => 'in:"' . RetentionStats::TYPE_ONE_MONTH
                                    . '", "' . RetentionStats::TYPE_SIX_MONTHS
                                    . '", "' . RetentionStats::TYPE_ONE_YEAR . '"',
            'brand' => 'string',
        ];
    }
}
