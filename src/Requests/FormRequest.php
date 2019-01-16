<?php

namespace Railroad\Ecommerce\Requests;

use Railroad\Doctrine\Requests\EntityHydratorRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request - extend the EntityHydratorRequest class and handle the validation errors messages
 *
 * Class FormRequest
 * @package Railroad\Railcontent\Requests
 */
class FormRequest extends EntityHydratorRequest
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

    /** Get the failed validation response in json format
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = [];

        foreach ($validator->errors()->getMessages() as $key => $value) {
            $errors[] = [
                "source" => $key,
                "detail" => $value[0]
            ];
        }

        throw new HttpResponseException(reply()->json(
            null,
            [
                'code' => 422,
                'totalResults' => 0,
                'errors' => $errors
            ]
        ));
    }
}