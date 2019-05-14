<?php

namespace Railroad\Ecommerce\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as IlluminateFormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request - extend the EntityHydratorRequest class and handle the validation errors messages
 *
 * Class FormRequest
 *
 * @package Railroad\Railcontent\Requests
 */
class FormRequest extends IlluminateFormRequest
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
     *
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = [];

        foreach ($validator->errors()
                     ->getMessages() as $key => $value) {
            $errors[] = [
                "title" => 'Validation failed.',
                "source" => $key,
                "detail" => $value[0],
            ];
        }

        throw new HttpResponseException(
            response()->json(['errors' => $errors], 422)
        );
    }
}