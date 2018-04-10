<?php


use Railroad\Ecommerce\ExternalHelpers\Stripe;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class StripeTest extends EcommerceTestCase
{
    /**
     * @var Stripe
     */
    private $classBeingTested;

    const VALID_VISA_CARD_NUM = '4242424242424242';
    const VALID_VISA_DEBIT_CARD_NUM = '4000056655665556';
    const VALID_MASTER_CARD_NUM = '5555555555554444';
    const VALID_MASTER_CARD_DEBIT_NUM = '5200828282828210';
    const VALID_MASTER_CARD_PREPAID_NUM = '5105105105105100';
    const VALID_AMERICAN_EXPRESS_CARD_NUM = '378282246310005';

    const ALWAYS_VALID_CARD_NUMBER = '4000000000000077';

    const VALID_CARD_NUMBER_ZIP_LINE1_AVS_FAIL = '4000000000000010';
    const VALID_CARD_NUMBER_LINE1_AVS_FAIL = '4000000000000028';
    const VALID_CARD_NUMBER_ZIP_AVS_FAIL = '4000000000000036';
    const VALID_CARD_NUMBER_AVS_UNAVAILABLE = '4000000000000044';
    const VALID_CARD_NUMBER_UNLESS_CVC_ENTERED = '4000000000000101';

    const CARD_NUMBER_CAN_BE_ADDED_BUT_CHARGES_FAIL = '4000000000000341';
    const CARD_DECLINED_NUMBER = '4000000000000002';
    const CARD_DECLINED_FRAUD_REASON_NUMBER = '4100000000000019';
    const CARD_DECLINED_INCORRECT_CVC_NUMBER = '4000000000000127';
    const CARD_DECLINED_EXPIRED_NUMBER = '4000000000000069';
    const CARD_DECLINED_PROCESSING_ERROR_NUMBER = '4000000000000119';

    const VALID_CANADIAN_CARD_NUMBER = '4000001240000000';
    const VALID_BRAZIL_CARD_NUMBER = '4000001240000000';

    public function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(Stripe::class);
    }

    public function test_create_card_token_visa()
    {
        $number = self::VALID_VISA_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);
        $cvc = 892;
        $cardholderName = $this->faker->name;
        $city = $this->faker->city;
        $country = $this->faker->country;
        $addressLineOne = $this->faker->address;
        $addressLineTwo = $this->faker->streetAddress;
        $state = $this->faker->state;
        $zip = $this->faker->postcode;

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear,
            $cvc,
            $cardholderName,
            $city,
            $country,
            $addressLineOne,
            $addressLineTwo,
            $state,
            $zip
        );

        $this->assertEquals($city, $token->card->address_city);
        $this->assertEquals($country, $token->card->address_country);
        $this->assertEquals($addressLineOne, $token->card->address_line1);
        $this->assertEquals($addressLineTwo, $token->card->address_line2);
        $this->assertEquals($state, $token->card->address_state);
        $this->assertEquals($zip, $token->card->address_zip);
        $this->assertEquals('Visa', $token->card->brand);
        $this->assertEquals($expirationMonth, $token->card->exp_month);
        $this->assertEquals($expirationYear, $token->card->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $token->card->last4);
        $this->assertEquals('unchecked', $token->card->address_line1_check);
        $this->assertEquals('unchecked', $token->card->address_zip_check);
        $this->assertEquals('unchecked', $token->card->cvc_check);
        $this->assertNotEmpty($token->id);
    }

    public function test_create_card_token_visa_debit()
    {
        $number = self::VALID_VISA_DEBIT_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);
        $cvc = 892;
        $cardholderName = $this->faker->name;
        $city = $this->faker->city;
        $country = $this->faker->country;
        $addressLineOne = $this->faker->address;
        $addressLineTwo = $this->faker->streetAddress;
        $state = $this->faker->state;
        $zip = $this->faker->postcode;

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear,
            $cvc,
            $cardholderName,
            $city,
            $country,
            $addressLineOne,
            $addressLineTwo,
            $state,
            $zip
        );

        $this->assertEquals($city, $token->card->address_city);
        $this->assertEquals($country, $token->card->address_country);
        $this->assertEquals($addressLineOne, $token->card->address_line1);
        $this->assertEquals($addressLineTwo, $token->card->address_line2);
        $this->assertEquals($state, $token->card->address_state);
        $this->assertEquals($zip, $token->card->address_zip);
        $this->assertEquals('Visa', $token->card->brand);
        $this->assertEquals($expirationMonth, $token->card->exp_month);
        $this->assertEquals($expirationYear, $token->card->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $token->card->last4);
        $this->assertEquals('unchecked', $token->card->address_line1_check);
        $this->assertEquals('unchecked', $token->card->address_zip_check);
        $this->assertEquals('unchecked', $token->card->cvc_check);
        $this->assertNotEmpty($token->id);
    }

    public function test_create_card_token_mastercard()
    {
        $number = self::VALID_MASTER_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);
        $cvc = 892;
        $cardholderName = $this->faker->name;
        $city = $this->faker->city;
        $country = $this->faker->country;
        $addressLineOne = $this->faker->address;
        $addressLineTwo = $this->faker->streetAddress;
        $state = $this->faker->state;
        $zip = $this->faker->postcode;

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear,
            $cvc,
            $cardholderName,
            $city,
            $country,
            $addressLineOne,
            $addressLineTwo,
            $state,
            $zip
        );

        $this->assertEquals($city, $token->card->address_city);
        $this->assertEquals($country, $token->card->address_country);
        $this->assertEquals($addressLineOne, $token->card->address_line1);
        $this->assertEquals($addressLineTwo, $token->card->address_line2);
        $this->assertEquals($state, $token->card->address_state);
        $this->assertEquals($zip, $token->card->address_zip);
        $this->assertEquals('MasterCard', $token->card->brand);
        $this->assertEquals($expirationMonth, $token->card->exp_month);
        $this->assertEquals($expirationYear, $token->card->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $token->card->last4);
        $this->assertEquals('unchecked', $token->card->address_line1_check);
        $this->assertEquals('unchecked', $token->card->address_zip_check);
        $this->assertEquals('unchecked', $token->card->cvc_check);
        $this->assertNotEmpty($token->id);
    }

    public function test_create_card_token_visa_bad_number()
    {
        $number = self::VALID_VISA_CARD_NUM . '111111';
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);
        $cvc = 892;
        $cardholderName = $this->faker->name;
        $city = $this->faker->city;
        $country = $this->faker->country;
        $addressLineOne = $this->faker->address;
        $addressLineTwo = $this->faker->streetAddress;
        $state = $this->faker->state;
        $zip = $this->faker->postcode;

        $this->expectException('\Stripe\Error\Card');

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear,
            $cvc,
            $cardholderName,
            $city,
            $country,
            $addressLineOne,
            $addressLineTwo,
            $state,
            $zip
        );
    }

    public function test_create_card_token_visa_bad_expired()
    {
        $number = self::VALID_VISA_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2000, 2010);
        $cvc = 892;
        $cardholderName = $this->faker->name;
        $city = $this->faker->city;
        $country = $this->faker->country;
        $addressLineOne = $this->faker->address;
        $addressLineTwo = $this->faker->streetAddress;
        $state = $this->faker->state;
        $zip = $this->faker->postcode;

        $this->expectException('\Stripe\Error\Card');

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear,
            $cvc,
            $cardholderName,
            $city,
            $country,
            $addressLineOne,
            $addressLineTwo,
            $state,
            $zip
        );
    }

    public function test_create_card_token_visa_minimum_info()
    {
        $number = self::VALID_VISA_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $this->assertEquals('Visa', $token->card->brand);
        $this->assertEquals($expirationMonth, $token->card->exp_month);
        $this->assertEquals($expirationYear, $token->card->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $token->card->last4);
        $this->assertNotEmpty($token->id);
    }

    public function test_create_card_token_visa_minimum_avs_info()
    {
        $number = self::VALID_VISA_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);
        $cvc = 892;
        $addressLineOne = $this->faker->address;
        $zip = $this->faker->postcode;

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear,
            $cvc,
            null,
            null,
            null,
            $addressLineOne,
            null,
            null,
            $zip
        );

        $this->assertEquals($addressLineOne, $token->card->address_line1);
        $this->assertEquals($zip, $token->card->address_zip);
        $this->assertEquals('Visa', $token->card->brand);
        $this->assertEquals($expirationMonth, $token->card->exp_month);
        $this->assertEquals($expirationYear, $token->card->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $token->card->last4);
        $this->assertEquals('unchecked', $token->card->address_line1_check);
        $this->assertEquals('unchecked', $token->card->address_zip_check);
        $this->assertEquals('unchecked', $token->card->cvc_check);
        $this->assertNotEmpty($token->id);
    }

    public function test_create_customer()
    {
        $email = $this->faker->email;

        $customer = $this->classBeingTested->createCustomer(['email' => $email]);

        $this->assertEquals($email, $customer->email);
    }

    public function test_retrieve_customer()
    {
        $email = $this->faker->email;

        $customer = $this->classBeingTested->createCustomer(['email' => $email]);

        $responseCustomer = $this->classBeingTested->retrieveCustomer($customer->id);

        $this->assertEquals($email, $responseCustomer->email);
    }


    public function test_retrieve_customer_none_found()
    {
        $this->expectException('\Stripe\Error\InvalidRequest');

        $responseCustomer = $this->classBeingTested->retrieveCustomer('none');
    }

    public function test_create_card()
    {
        $number = self::VALID_VISA_DEBIT_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);
        $cvc = '892';
        $cardholderName = $this->faker->name;
        $city = $this->faker->city;
        $country = $this->faker->country;
        $addressLineOne = $this->faker->address;
        $addressLineTwo = $this->faker->streetAddress;
        $state = $this->faker->state;
        $zip = $this->faker->postcode;

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear,
            $cvc,
            $cardholderName,
            $city,
            $country,
            $addressLineOne,
            $addressLineTwo,
            $state,
            $zip
        );

        $email = $this->faker->email;
        $customer = $this->classBeingTested->createCustomer(['email' => $email]);

        $card = $this->classBeingTested->createCard($customer, $token);

        $this->assertEquals($city, $card->address_city);
        $this->assertEquals($country, $card->address_country);
        $this->assertEquals($addressLineOne, $card->address_line1);
        $this->assertEquals($addressLineTwo, $card->address_line2);
        $this->assertEquals($state, $card->address_state);
        $this->assertEquals($zip, $card->address_zip);
        $this->assertEquals('Visa', $card->brand);
        $this->assertEquals($expirationMonth, $card->exp_month);
        $this->assertEquals($expirationYear, $card->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $card->last4);
        $this->assertEquals('pass', $card->address_line1_check);
        $this->assertEquals('pass', $card->address_zip_check);
        $this->assertEquals('pass', $card->cvc_check);
        $this->assertNotEmpty($card->id);
    }

    public function test_create_card_minimum_info()
    {
        $number = self::VALID_VISA_DEBIT_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $email = $this->faker->email;
        $customer = $this->classBeingTested->createCustomer(['email' => $email]);

        $card = $this->classBeingTested->createCard($customer, $token);

        $this->assertEquals('Visa', $card->brand);
        $this->assertEquals($expirationMonth, $card->exp_month);
        $this->assertEquals($expirationYear, $card->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $card->last4);
        $this->assertEquals(null, $card->address_line1_check);
        $this->assertEquals(null, $card->address_zip_check);
        $this->assertEquals(null, $card->cvc_check);
        $this->assertNotEmpty($card->id);
    }

    public function test_create_card_american_express()
    {
        $number = self::VALID_AMERICAN_EXPRESS_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $email = $this->faker->email;
        $customer = $this->classBeingTested->createCustomer(['email' => $email]);

        $card = $this->classBeingTested->createCard($customer, $token);

        $this->assertEquals('American Express', $card->brand);
        $this->assertEquals($expirationMonth, $card->exp_month);
        $this->assertEquals($expirationYear, $card->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $card->last4);
        $this->assertEquals(null, $card->address_line1_check);
        $this->assertEquals(null, $card->address_zip_check);
        $this->assertEquals(null, $card->cvc_check);
        $this->assertNotEmpty($card->id);
    }

    public function test_create_card_zip_avs_fail()
    {
        $number = self::VALID_CARD_NUMBER_ZIP_AVS_FAIL;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);
        $cvc = $this->faker->randomNumber(4);
        $cardholderName = $this->faker->name;
        $city = $this->faker->city;
        $country = $this->faker->country;
        $addressLineOne = $this->faker->address;
        $addressLineTwo = $this->faker->streetAddress;
        $state = $this->faker->state;
        $zip = $this->faker->postcode;

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear,
            $cvc,
            $cardholderName,
            $city,
            $country,
            $addressLineOne,
            $addressLineTwo,
            $state,
            $zip
        );

        $email = $this->faker->email;
        $customer = $this->classBeingTested->createCustomer(['email' => $email]);

       // $this->expectException('\Stripe\Error\Card');

        $card = $this->classBeingTested->createCard($customer, $token);

        $this->assertEquals($city, $card->address_city);
        $this->assertEquals($country, $card->address_country);
        $this->assertEquals($addressLineOne, $card->address_line1);
        $this->assertEquals($addressLineTwo, $card->address_line2);
        $this->assertEquals($state, $card->address_state);
        $this->assertEquals($zip, $card->address_zip);
        $this->assertEquals('Visa', $card->brand);
        $this->assertEquals($expirationMonth, $card->exp_month);
        $this->assertEquals($expirationYear, $card->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $card->last4);
        $this->assertEquals('pass', $card->address_line1_check);
        $this->assertEquals('fail', $card->address_zip_check);
        $this->assertEquals('pass', $card->cvc_check);
        $this->assertNotEmpty($card->id);
    }


    public function test_create_card_street_avs_fail()
    {
        $number = self::VALID_CARD_NUMBER_LINE1_AVS_FAIL;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);
        $cvc = $this->faker->randomNumber(4);
        $cardholderName = $this->faker->name;
        $city = $this->faker->city;
        $country = $this->faker->country;
        $addressLineOne = $this->faker->address;
        $addressLineTwo = $this->faker->streetAddress;
        $state = $this->faker->state;
        $zip = $this->faker->postcode;

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear,
            $cvc,
            $cardholderName,
            $city,
            $country,
            $addressLineOne,
            $addressLineTwo,
            $state,
            $zip
        );

        $email = $this->faker->email;
        $customer = $this->classBeingTested->createCustomer(['email' => $email]);

        $card = $this->classBeingTested->createCard($customer, $token);

        $this->assertEquals($city, $card->address_city);
        $this->assertEquals($country, $card->address_country);
        $this->assertEquals($addressLineOne, $card->address_line1);
        $this->assertEquals($addressLineTwo, $card->address_line2);
        $this->assertEquals($state, $card->address_state);
        $this->assertEquals($zip, $card->address_zip);
        $this->assertEquals('Visa', $card->brand);
        $this->assertEquals($expirationMonth, $card->exp_month);
        $this->assertEquals($expirationYear, $card->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $card->last4);
        $this->assertEquals('fail', $card->address_line1_check);
        $this->assertEquals('pass', $card->address_zip_check);
        $this->assertEquals('pass', $card->cvc_check);
        $this->assertNotEmpty($card->id);
    }


    public function test_create_card_all_avs_fail()
    {
        $number = self::VALID_CARD_NUMBER_ZIP_LINE1_AVS_FAIL;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);
        $cvc = $this->faker->randomNumber(4);
        $cardholderName = $this->faker->name;
        $city = $this->faker->city;
        $country = $this->faker->country;
        $addressLineOne = $this->faker->address;
        $addressLineTwo = $this->faker->streetAddress;
        $state = $this->faker->state;
        $zip = $this->faker->postcode;

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear,
            $cvc,
            $cardholderName,
            $city,
            $country,
            $addressLineOne,
            $addressLineTwo,
            $state,
            $zip
        );

        $email = $this->faker->email;
        $customer = $this->classBeingTested->createCustomer(['email' => $email]);

        $card = $this->classBeingTested->createCard($customer, $token);

        $this->assertEquals($city, $card->address_city);
        $this->assertEquals($country, $card->address_country);
        $this->assertEquals($addressLineOne, $card->address_line1);
        $this->assertEquals($addressLineTwo, $card->address_line2);
        $this->assertEquals($state, $card->address_state);
        $this->assertEquals($zip, $card->address_zip);
        $this->assertEquals('Visa', $card->brand);
        $this->assertEquals($expirationMonth, $card->exp_month);
        $this->assertEquals($expirationYear, $card->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $card->last4);
        $this->assertEquals('fail', $card->address_line1_check);
        $this->assertEquals('fail', $card->address_zip_check);
        $this->assertEquals('pass', $card->cvc_check);
        $this->assertNotEmpty($card->id);
    }

    public function test_create_card_cvc_check_fail()
    {
        $number = self::CARD_DECLINED_INCORRECT_CVC_NUMBER;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);
        $cvc = $this->faker->randomNumber(4);
        $cardholderName = $this->faker->name;
        $city = $this->faker->city;
        $country = $this->faker->country;
        $addressLineOne = $this->faker->address;
        $addressLineTwo = $this->faker->streetAddress;
        $state = $this->faker->state;
        $zip = $this->faker->postcode;

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear,
            $cvc,
            $cardholderName,
            $city,
            $country,
            $addressLineOne,
            $addressLineTwo,
            $state,
            $zip
        );

        $email = $this->faker->email;
        $customer = $this->classBeingTested->createCustomer(['email' => $email]);

        $this->expectException('\Stripe\Error\Card');

        $card = $this->classBeingTested->createCard($customer, $token);
    }

    public function test_retrieve_card_american_express()
    {
        $number = self::VALID_AMERICAN_EXPRESS_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $email = $this->faker->email;
        $customer = $this->classBeingTested->createCustomer(['email' => $email]);

        $card = $this->classBeingTested->createCard($customer, $token);

        $cardResponse = $this->classBeingTested->retrieveCard($customer, $card->id);

        $this->assertEquals('American Express', $cardResponse->brand);
        $this->assertEquals($expirationMonth, $cardResponse->exp_month);
        $this->assertEquals($expirationYear, $cardResponse->exp_year);
        $this->assertEquals(substr($number, strlen($number) - 4), $cardResponse->last4);
        $this->assertEquals(null, $cardResponse->address_line1_check);
        $this->assertEquals(null, $cardResponse->address_zip_check);
        $this->assertEquals(null, $cardResponse->cvc_check);
        $this->assertEquals($card->id, $cardResponse->id);
    }


    public function test_retrieve_card_not_found()
    {
        $email = $this->faker->email;
        $customer = $this->classBeingTested->createCustomer(['email' => $email]);

        $this->expectException('\Stripe\Error\InvalidRequest');

        $cardResponse = $this->classBeingTested->retrieveCard($customer, 'random');
    }

    public function test_create_charge_american_express()
    {
        $amount = rand(100, 100000);
        $number = self::VALID_AMERICAN_EXPRESS_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $email = $this->faker->email;

        $customer = $this->classBeingTested->createCustomer(['email' => $email]);
        $card = $this->classBeingTested->createCard($customer, $token);

        $charge = $this->classBeingTested->createCharge($amount, $customer, $card);

        $this->assertNotEmpty($charge->id);
        $this->assertEquals($amount, $charge->amount);
        $this->assertEquals(0, $charge->amount_refunded);
        $this->assertEquals('usd', $charge->currency);
        $this->assertEquals(true, $charge->captured);
        $this->assertEquals($card->id, $charge->source->id);
        $this->assertEquals('succeeded', $charge->status);
        $this->assertEquals($customer->id, $charge->customer);
    }

    public function test_create_charge_visa()
    {
        $amount = rand(100, 100000);
        $number = self::VALID_VISA_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $email = $this->faker->email;

        $customer = $this->classBeingTested->createCustomer(['email' => $email]);
        $card = $this->classBeingTested->createCard($customer, $token);

        $charge = $this->classBeingTested->createCharge($amount, $customer, $card);

        $this->assertNotEmpty($charge->id);
        $this->assertEquals($amount, $charge->amount);
        $this->assertEquals(0, $charge->amount_refunded);
        $this->assertEquals('usd', $charge->currency);
        $this->assertEquals(true, $charge->captured);
        $this->assertEquals($card->id, $charge->source->id);
        $this->assertEquals('succeeded', $charge->status);
        $this->assertEquals($customer->id, $charge->customer);
    }

    public function test_create_charge_brazil()
    {
        $amount = rand(100, 100000);
        $number = self::VALID_BRAZIL_CARD_NUMBER;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $email = $this->faker->email;

        $customer = $this->classBeingTested->createCustomer(['email' => $email]);
        $card = $this->classBeingTested->createCard($customer, $token);

        $charge = $this->classBeingTested->createCharge($amount, $customer, $card);

        $this->assertNotEmpty($charge->id);
        $this->assertEquals($amount, $charge->amount);
        $this->assertEquals(0, $charge->amount_refunded);
        $this->assertEquals('usd', $charge->currency);
        $this->assertEquals(true, $charge->captured);
        $this->assertEquals($card->id, $charge->source->id);
        $this->assertEquals('succeeded', $charge->status);
        $this->assertEquals($customer->id, $charge->customer);
    }

    public function test_create_charge_visa_charge_failed()
    {
        $amount = rand(100, 100000);
        $number = self::CARD_NUMBER_CAN_BE_ADDED_BUT_CHARGES_FAIL;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $email = $this->faker->email;

        $customer = $this->classBeingTested->createCustomer(['email' => $email]);
        $card = $this->classBeingTested->createCard($customer, $token);

        $this->expectException('\Stripe\Error\Card');

        $charge = $this->classBeingTested->createCharge($amount, $customer, $card);
    }

    public function test_retrieve_charge()
    {
        $amount = rand(100, 100000);
        $number = self::VALID_VISA_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $email = $this->faker->email;

        $customer = $this->classBeingTested->createCustomer(['email' => $email]);
        $card = $this->classBeingTested->createCard($customer, $token);
        $charge = $this->classBeingTested->createCharge($amount, $customer, $card);

        $responseCharge = $this->classBeingTested->retrieveCharge($charge->id);

        $this->assertEquals($charge->id, $responseCharge->id);
        $this->assertEquals($charge->amount, $responseCharge->amount);
    }

    public function test_retrieve_charge_none_found()
    {
        $this->expectException('\Stripe\Error\InvalidRequest');

        $responseCharge = $this->classBeingTested->retrieveCharge('none');
    }

    public function test_create_refund()
    {
        $amount = rand(100, 100000);
        $reason = 'duplicate';
        $number = self::VALID_AMERICAN_EXPRESS_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $email = $this->faker->email;

        $customer = $this->classBeingTested->createCustomer(['email' => $email]);
        $card = $this->classBeingTested->createCard($customer, $token);

        $charge = $this->classBeingTested->createCharge($amount, $customer, $card);

        $refund = $this->classBeingTested->createRefund($amount, $charge->id, $reason);

        $this->assertEquals($charge->id, $refund->charge);
        $this->assertEquals($amount, $refund->amount);
        $this->assertEquals('succeeded', $refund->status);
    }

    public function test_create_refund_partial()
    {
        $amount = rand(1000, 100000);
        $reason = 'duplicate';
        $number = self::VALID_AMERICAN_EXPRESS_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $email = $this->faker->email;

        $customer = $this->classBeingTested->createCustomer(['email' => $email]);
        $card = $this->classBeingTested->createCard($customer, $token);

        $charge = $this->classBeingTested->createCharge($amount, $customer, $card);

        $refund = $this->classBeingTested->createRefund($amount - 500, $charge->id, $reason);

        $this->assertEquals($charge->id, $refund->charge);
        $this->assertEquals($amount - 500, $refund->amount);
        $this->assertEquals('succeeded', $refund->status);

        $refund = $this->classBeingTested->createRefund(500, $charge->id, $reason);

        $this->assertEquals($charge->id, $refund->charge);
        $this->assertEquals(500, $refund->amount);
        $this->assertEquals('succeeded', $refund->status);

        $chargeAfterRefunds = $this->classBeingTested->retrieveCharge($charge->id);

        $this->assertEquals($amount, $chargeAfterRefunds->amount_refunded);
    }

    public function test_create_refund_already_refunded()
    {
        $amount = rand(100, 100000);
        $reason = 'duplicate';
        $number = self::VALID_AMERICAN_EXPRESS_CARD_NUM;
        $expirationMonth = rand(1, 12);
        $expirationYear = rand(2030, 2040);

        $token = $this->classBeingTested->createCardToken(
            $number,
            $expirationMonth,
            $expirationYear
        );

        $email = $this->faker->email;

        $customer = $this->classBeingTested->createCustomer(['email' => $email]);
        $card = $this->classBeingTested->createCard($customer, $token);

        $charge = $this->classBeingTested->createCharge($amount, $customer, $card);

        $refund = $this->classBeingTested->createRefund($amount, $charge->id, $reason);

        $this->assertEquals($charge->id, $refund->charge);
        $this->assertEquals($amount, $refund->amount);
        $this->assertEquals('succeeded', $refund->status);

        $this->expectException('\Stripe\Error\InvalidRequest');

        $refund = $this->classBeingTested->createRefund($amount, $charge->id, $reason);
    }

}
