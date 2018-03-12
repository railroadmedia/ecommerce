<?php

namespace Railroad\Ecommerce\Requests;


class AddressDeleteRequest extends FormRequest
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
            'user_id' => 'required_without:customer_id',
            'customer_id' => 'required_without:user_id'
        ];
    }
}