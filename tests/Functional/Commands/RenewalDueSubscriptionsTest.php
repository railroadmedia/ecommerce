<?php
namespace Railroad\Ecommerce\Tests\Functional\Commands;

use Carbon\Carbon;

use Railroad\Ecommerce\Services\ConfigService;

use Railroad\Ecommerce\Tests\EcommerceTestCase;

class RenewalDueSubscriptionsTest extends EcommerceTestCase
{


    public function setUp()
    {
        parent::setUp();

    }

    public function test_command()
    {
        $userId         = $this->createAndLogInNewUser();
        $paymentGateway = $this->paymentGateway->store(ConfigService::$brand, 'stripe', 'stripe_1');
        for($i = 0; $i < 10; $i++)
        {
            $creditCard   = [
                'type'                 => $this->faker->creditCardType,
                'fingerprint'          => $this->faker->creditCardNumber,
                'last_four_digits'     => $this->faker->randomNumber(4),
                'company_name'         => $this->faker->word,
                'external_id'          => 'card_1CQF4CE2yPYKc9YRou0O5ghP',
                'external_customer_id' => 'cus_CputG11eqRn0UO',
                'external_provider'    => $this->faker->word,
                'expiration_date'      => $this->faker->creditCardExpirationDateString,
                'payment_gateway_id'   => $paymentGateway['id'],
                'created_on'           => time()
            ];
            $creditCardId = $this->databaseManager->table(ConfigService::$tableCreditCard)
                ->insertGetId($creditCard);
            $this->databaseManager->table(ConfigService::$tableUserStripeCustomer)
                ->insertGetId([
                    'user_id'            => $userId,
                    'stripe_customer_id' => 'cus_CputG11eqRn0UO',
                    'created_on'         => time()
                ]);
            $paymentMethod = [
                'method_id'   => $creditCardId,
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'currency'    => 'usd',
                'created_on'  => time(),
                'updated_on'  => time(),
            ];

            $paymentMethodId = $this->databaseManager->table(ConfigService::$tablePaymentMethod)
                ->insertGetId($paymentMethod);

            $this->databaseManager->table(ConfigService::$tableUserPaymentMethods)
                ->insertGetId([
                    'payment_method_id' => $paymentMethodId,
                    'user_id'           => $userId,
                    'created_on'        => time()
                ]);
            $payment   = [
                'due'               => 50,
                'paid'              => 50,
                'type'              => 'order',
                'external_provider' => 'stripe',
                'external_id'       => $this->faker->word,
                'status'            => 1,
                'currency'          => $paymentMethod['currency'],
                'payment_method_id' => $paymentMethodId
            ];
            $paymentId = $this->databaseManager->table(ConfigService::$tablePayment)
                ->insertGetId($payment);

            $subscription = $this->subscriptionFactory->store('subscription',
                $userId,
                null,
                null,
                rand(),
                1,
                Carbon::now()->subYear(2),
                Carbon::now()->subYear(1),
                rand(),
                0,
                0,
                'cad',
                'year',
                $this->faker->randomNumber(1),
                0,
                1);
            $this->databaseManager->table(ConfigService::$tableSubscriptionPayment)
                ->insertGetId([
                    'subscription_id' => $subscription['id'],
                    'payment_id'      => $paymentId,
                    'created_on'      => time()
                ]);
            $initialSubscriptions[] = $subscription;
        }

        $this->artisan('renewalDueSubscriptions');

        for($i = 0; $i < 10; $i++)
        {
            $this->assertDatabaseHas(ConfigService::$tableSubscription,
                [
                    'id'                => $initialSubscriptions[$i]['id'],
                    'paid_until'        => Carbon::now()->addYear($initialSubscriptions[$i]['interval_count']),
                    'is_active'         => 1,
                    'total_cycles_paid' => $initialSubscriptions[$i]['total_cycles_paid'] + 1,
                    'updated_on'        => Carbon::now()->toDateTimeString()
                ]);
        }
    }
}