<?php
namespace Railroad\Ecommerce\Tests\Functional\Commands;

use Carbon\Carbon;

use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;

use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;

class RenewalDueSubscriptionsTest extends EcommerceTestCase
{
    /**
     * @var \Railroad\Ecommerce\Repositories\CreditCardRepository
     */
    protected $creditCardRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentMethodRepository
     */
    protected $paymentMethodRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository
     */
    protected $subscriptionPaymentRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    protected $productRepository;

    public function setUp()
    {
        parent::setUp();
        $this->creditCardRepository = $this->app->make(CreditCardRepository::class);
        $this->paymentMethodRepository = $this->app->make(PaymentMethodRepository::class);
        $this->paymentRepository = $this->app->make(PaymentRepository::class);
        $this->subscriptionRepository = $this->app->make(SubscriptionRepository::class);
        $this->subscriptionPaymentRepository = $this->app->make(SubscriptionPaymentRepository::class);
        $this->productRepository = $this->app->make(ProductRepository::class);
    }

    public function test_command()
    {
        $userId         = $this->createAndLogInNewUser();

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge           = new Charge();
        $fakerCharge->currency = 'CAD';
        $fakerCharge->amount   = rand();
        $fakerCharge->status   = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        for($i = 0; $i < 10; $i++)
        {
            $creditCard   = $this->creditCardRepository->create($this->faker->creditCard());
            $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
                'method_id' => $creditCard['id'],
                'method_type' => config('constants.CREDIT_CARD_PAYMENT_METHOD_TYPE'),
                'currency'    => 'CAD',
                'created_on'  => time(),
                'updated_on'  => time(),
            ]));
            $payment = $this->paymentRepository->create($this->faker->payment([
                'payment_method_id' => $paymentMethod['id'],
                'currency'    => 'CAD',
                'due' => $this->faker->numberBetween(1,100)
            ]));
            $product = $this->productRepository->create($this->faker->product([
                'type' => config('constants.TYPE_SUBSCRIPTION')
            ]));
            $subscription = $this->subscriptionRepository->create($this->faker->subscription([
                'user_id' => $userId,
                'type' => config('constants.TYPE_SUBSCRIPTION'),
                'start_date' => Carbon::now()->subYear(2),
                'paid_until' => Carbon::now()->subYear(1),
                'product_id' => $product['id'],
                'currency'    => 'CAD',
                'interval_type' => config('constants.INTERVAL_TYPE_YEARLY'),
                'interval_count' => 1,
                'total_cycles_paid' => 1,
                'total_price_per_payment' => $payment['due'],
                'payment_method_id' => $paymentMethod['id']
            ]));

            $subscriptionPayment = $this->subscriptionPaymentRepository->create([
                'subscription_id' => $subscription['id'],
                'payment_id' => $payment['id'],
                'created_on' => Carbon::now()->toDateTimeString()
            ]);


            $initialSubscriptions[] = $subscription;
        }

         $this->artisan('renewalDueSubscriptions');

        for($i = 0; $i < 10; $i++)
        {
            $this->assertDatabaseHas(ConfigService::$tableSubscription,
                [
                    'id'                => $initialSubscriptions[$i]['id'],
                    'paid_until'        => Carbon::now()->addYear($initialSubscriptions[$i]['interval_count'])->toDateTimeString(),
                    'is_active'         => 1,
                    'total_cycles_paid' => $initialSubscriptions[$i]['total_cycles_paid'] + 1,
                    'updated_on'        => Carbon::now()->toDateTimeString()
                ]);
        }
    }
}