<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class StripeWebhookControllerTest extends EcommerceTestCase
{
    /**
     * @var CreditCardRepository
     */
    protected $creditCardRepository;

    public function setUp()
    {
        parent::setUp();
        $this->creditCardRepository = $this->app->make(CreditCardRepository::class);
    }

    public function testHandleCustomerSourceUpdated()
    {
        $externalId = $this->faker->word;

        $this->creditCardRepository->create(
            [
                'fingerprint' => $this->faker->creditCardNumber,
                'last_four_digits' => $this->faker->randomNumber(4),
                'company_name' => $this->faker->creditCardType,
                'expiration_date' => Carbon::now()
                    ->toDateTimeString(),
                'external_id' => $externalId,
                'payment_gateway_name' => 'stripe',
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
        $results = $this->json(
            'POST',
            '/stripe/webhook',
            json_decode(
                '{
                      "created": 1326853478,
                      "livemode": false,
                      "id": "evt_00000000000000",
                      "type": "customer.source.updated",
                      "object": "event",
                      "request": null,
                      "pending_webhooks": 1,
                      "api_version": "2018-07-27",
                      "data": {
                        "object": {
                         "id": "' . $externalId . '",
                          "object": "card",
                          "last4": "3110",
                          "exp_month": 11,
                          "exp_year": 2020,
                          "customer": "cus_8h42pwFc41m2",
                          "metadata": {
                          }
                        },
                        "previous_attributes": {
                          "exp_year": "2013"
                        }
                      }
                    }',
                true
            )
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'external_id' => $externalId,
                'expiration_date' => Carbon::create(2020,11)->toDateTimeString()
            ]
        );
    }
}
