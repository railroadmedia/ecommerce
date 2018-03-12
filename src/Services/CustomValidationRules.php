<?php

namespace Railroad\Ecommerce\Services;


use Illuminate\Validation\Validator;
use Webpatser\Countries\Countries;

class CustomValidationRules extends Validator
{
    private $_custom_messages = array(
        "country" => "The :attribute field it's invalid.",
    );

    public function __construct($translator, $data, $rules, $messages = array(), $customAttributes = array())
    {
        parent::__construct($translator, $data, $rules, $messages, $customAttributes);

        $this->_set_custom_stuff();
    }

    /**
     * Setup custom error messages
     *
     * @return void
     */
    protected function _set_custom_stuff()
    {
        //setup our custom error messages
        $this->setCustomMessages($this->_custom_messages);
    }

    /** Check if country value exist in the countries array provided by https://github.com/webpatser/laravel-countries package.
     * @param string $attribute
     * @param string $value
     * @return bool
     */
    public function validateCountry($attribute, $value)
    {
        return (in_array($value, array_column(Countries::getCountries(), 'full_name')));
    }
}