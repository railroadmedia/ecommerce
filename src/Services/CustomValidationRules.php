<?php

namespace Railroad\Ecommerce\Services;

use Illuminate\Validation\Validator;
use Webpatser\Countries\Countries;
use Railroad\Location\Services\CountryListService;

class CustomValidationRules extends Validator
{
    /**
     * @var array
     */
    private $_custom_messages = array(
        "country" => "The :attribute field it's invalid.",
    );

    /**
     * CustomValidationRules constructor.
     * @param $translator
     * @param $data
     * @param $rules
     * @param array $messages
     * @param array $customAttributes
     */
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

    /** Check if country name is valid per our railroad/location package (... as configured in this app)
     * @param string $attribute
     * @param string $value
     * @return bool
     */
    public function validateCountry($attribute, $value)
    {
        return (in_array($value, CountryListService::all()));
    }
}