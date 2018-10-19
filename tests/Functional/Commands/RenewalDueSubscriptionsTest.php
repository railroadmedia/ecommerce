<?php
namespace Railroad\Ecommerce\Tests\Functional\Commands;

use Carbon\Carbon;

use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
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

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    public function setUp()
    {
        parent::setUp();

        $this->creditCardRepository = $this->app->make(CreditCardRepository::class);
        $this->paymentMethodRepository = $this->app->make(PaymentMethodRepository::class);
        $this->paymentRepository = $this->app->make(PaymentRepository::class);
        $this->subscriptionRepository = $this->app->make(SubscriptionRepository::class);
        $this->subscriptionPaymentRepository = $this->app->make(SubscriptionPaymentRepository::class);
        $this->productRepository = $this->app->make(ProductRepository::class);
        $this->orderRepository = $this->app->make(OrderRepository::class);
        $this->orderItemRepository = $this->app->make(OrderItemRepository::class);
    }

    public function test_command()
    {
        $userId = $this->createAndLogInNewUser();
        $due = $this->faker->numberBetween(0, 1000);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        for ($i = 0; $i < 10; $i++) {
            $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
            $paymentMethod = $this->paymentMethodRepository->create(
                $this->faker->paymentMethod(
                    [
                        'method_id' => $creditCard['id'],
                        'method_type' => ConfigService::$creditCartPaymentMethodType,
                        'currency' => 'CAD',
                        'created_on' => time(),
                        'updated_on' => time(),
                    ]
                )
            );
            $payment = $this->paymentRepository->create(
                $this->faker->payment(
                    [
                        'payment_method_id' => $paymentMethod['id'],
                        'currency' => 'CAD',
                        'due' => $this->faker->numberBetween(1, 100),
                    ]
                )
            );

            $product = $this->productRepository->create(
                $this->faker->product(
                    [
                        'type' => ConfigService::$typeSubscription,
                    ]
                )
            );
            $order = $this->orderRepository->create($this->faker->order());
            $orderItem = $this->orderItemRepository->create($this->faker->orderItem([
                'order_id' => $order['id'],
                'product_id' => $product['id'],
                'quantity' => 1
            ]));
            $subscription = $this->subscriptionRepository->create(
                $this->faker->subscription(
                    [
                        'user_id' => $userId,
                        'type' => $this->faker->randomElement(
                            [ConfigService::$typeSubscription, ConfigService::$paymentPlanType]
                        ),
                        'start_date' => Carbon::now()
                            ->subYear(2),
                        'paid_until' => Carbon::now()
                            ->subDay(1),
                        'product_id' => $product['id'],
                        'currency' => 'CAD',
                        'order_id' => $order['id'],
                        'brand' => ConfigService::$brand,
                        'interval_type' => ConfigService::$intervalTypeMonthly,
                        'interval_count' => 1,
                        'total_cycles_paid' => 1,
                        'total_cycles_due' => $this->faker->numberBetween(2, 5),
                        'total_price_per_payment' => $payment['due'],
                        'payment_method_id' => $paymentMethod['id'],
                    ]
                )
            );

            $subscriptionPayment = $this->subscriptionPaymentRepository->create(
                [
                    'subscription_id' => $subscription['id'],
                    'payment_id' => $payment['id'],
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );

            if (($subscription['type'] != ConfigService::$paymentPlanType) ||
                ((int)$subscription['total_cycles_paid'] < (int)$subscription['total_cycles_due'])) {
                $initialSubscriptions[] = $subscription;
            }

        }

        $this->artisan('renewalDueSubscriptions');

        for ($i = 0; $i < count($initialSubscriptions); $i++) {
            $this->assertDatabaseHas(
                ConfigService::$tableSubscription,
                [
                    'id' => $initialSubscriptions[$i]['id'],
                    'paid_until' => Carbon::now()
                        ->addMonth($initialSubscriptions[$i]['interval_count'])
                        ->startOfDay()
                        ->toDateTimeString(),
                    'is_active' => 1,
                    'total_cycles_paid' => $initialSubscriptions[$i]['total_cycles_paid'] + 1,
                    'updated_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );

            //assert user products assignation
            $this->assertDatabaseHas(
                ConfigService::$tableUserProduct,
                [
                    'user_id' => $initialSubscriptions[$i]['user_id'],
                    'product_id' => $initialSubscriptions[$i]['product_id'],
                    'quantity' => 1,
                    'expiration_date' => Carbon::now()
                        ->addMonth($initialSubscriptions[$i]['interval_count'])
                        ->startOfDay()
                        ->toDateTimeString(),
                ]
            );
        }
    }

    public function test_ancient_subscriptions_deactivation()
    {
        $userId = $this->createAndLogInNewUser();
        $due = $this->faker->numberBetween(0, 1000);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        //ancient subscriptions
        for ($i = 0; $i < 2; $i++) {
            $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
            $paymentMethod = $this->paymentMethodRepository->create(
                $this->faker->paymentMethod(
                    [
                        'method_id' => $creditCard['id'],
                        'method_type' => ConfigService::$creditCartPaymentMethodType,
                        'currency' => 'CAD',
                        'created_on' => time(),
                        'updated_on' => time(),
                    ]
                )
            );
            $payment = $this->paymentRepository->create(
                $this->faker->payment(
                    [
                        'payment_method_id' => $paymentMethod['id'],
                        'currency' => 'CAD',
                        'due' => $this->faker->numberBetween(1, 100),
                    ]
                )
            );

            $product = $this->productRepository->create(
                $this->faker->product(
                    [
                        'type' => ConfigService::$typeSubscription,
                    ]
                )
            );
            $order = $this->orderRepository->create($this->faker->order());
            $orderItem = $this->orderItemRepository->create($this->faker->orderItem([
                'order_id' => $order['id'],
                'product_id' => $product['id'],
                'quantity' => 1
            ]));
            $oldSubscriptions[] = $this->subscriptionRepository->create(
                $this->faker->subscription(
                    [
                        'user_id' => $userId,
                        'type' => $this->faker->randomElement(
                            [ConfigService::$typeSubscription, ConfigService::$paymentPlanType]
                        ),
                        'start_date' => Carbon::now()
                            ->subYear(2),
                        'paid_until' => Carbon::now()
                            ->subMonths(ConfigService::$subscriptionRenewalDateCutoff + 1),
                        'product_id' => $product['id'],
                        'order_id' => $order['id'],
                        'currency' => 'CAD',
                        'brand' => ConfigService::$brand,
                        'interval_type' => ConfigService::$intervalTypeMonthly,
                        'interval_count' => 1,
                        'total_cycles_paid' => 1,
                        'total_cycles_due' => $this->faker->numberBetween(2, 5),
                        'total_price_per_payment' => $payment['due'],
                        'payment_method_id' => $paymentMethod['id'],
                    ]
                )
            );
        }

        for ($i = 0; $i < 10; $i++) {
            $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
            $paymentMethod = $this->paymentMethodRepository->create(
                $this->faker->paymentMethod(
                    [
                        'method_id' => $creditCard['id'],
                        'method_type' => ConfigService::$creditCartPaymentMethodType,
                        'currency' => 'CAD',
                        'created_on' => time(),
                        'updated_on' => time(),
                    ]
                )
            );
            $payment = $this->paymentRepository->create(
                $this->faker->payment(
                    [
                        'payment_method_id' => $paymentMethod['id'],
                        'currency' => 'CAD',
                        'due' => $this->faker->numberBetween(1, 100),
                    ]
                )
            );

            $product = $this->productRepository->create(
                $this->faker->product(
                    [
                        'type' => ConfigService::$typeSubscription,
                    ]
                )
            );
            $order = $this->orderRepository->create($this->faker->order());
            $orderItem = $this->orderItemRepository->create($this->faker->orderItem([
                'order_id' => $order['id'],
                'product_id' => $product['id'],
                'quantity' => 1
            ]));
            $subscription = $this->subscriptionRepository->create(
                $this->faker->subscription(
                    [
                        'user_id' => $userId,
                        'type' => $this->faker->randomElement(
                            [ConfigService::$typeSubscription, ConfigService::$paymentPlanType]
                        ),
                        'start_date' => Carbon::now()
                            ->subYear(2),
                        'paid_until' => Carbon::now()
                            ->subDay(1),
                        'product_id' => $product['id'],
                        'order_id' => $order['id'],
                        'currency' => 'CAD',
                        'brand' => ConfigService::$brand,
                        'interval_type' => ConfigService::$intervalTypeMonthly,
                        'interval_count' => 1,
                        'total_cycles_paid' => 1,
                        'total_cycles_due' => $this->faker->numberBetween(2, 5),
                        'total_price_per_payment' => $payment['due'],
                        'payment_method_id' => $paymentMethod['id'],
                    ]
                )
            );

            $subscriptionPayment = $this->subscriptionPaymentRepository->create(
                [
                    'subscription_id' => $subscription['id'],
                    'payment_id' => $payment['id'],
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );

            if (($subscription['type'] != ConfigService::$paymentPlanType) ||
                ((int)$subscription['total_cycles_paid'] < (int)$subscription['total_cycles_due'])) {
                $initialSubscriptions[] = $subscription;
            }

        }

        $this->artisan('renewalDueSubscriptions');
        foreach ($oldSubscriptions as $deactivatedSubscription) {
            $this->assertDatabaseHas(
                ConfigService::$tableSubscription,
                [
                    'id' => $deactivatedSubscription['id'],
                    'is_active' => false,
                    'updated_on' => Carbon::now()->toDateTimeString(),
                    'canceled_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );
        }
        for ($i = 0; $i < count($initialSubscriptions); $i++) {
            $this->assertDatabaseHas(
                ConfigService::$tableSubscription,
                [
                    'id' => $initialSubscriptions[$i]['id'],
                    'paid_until' => Carbon::now()
                        ->addMonth($initialSubscriptions[$i]['interval_count'])
                        ->startOfDay()
                        ->toDateTimeString(),
                    'is_active' => 1,
                    'total_cycles_paid' => $initialSubscriptions[$i]['total_cycles_paid'] + 1,
                    'updated_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );

            //assert user products assignation
            $this->assertDatabaseHas(
                ConfigService::$tableUserProduct,
                [
                    'user_id' => $initialSubscriptions[$i]['user_id'],
                    'product_id' => $initialSubscriptions[$i]['product_id'],
                    'quantity' => 1,
                    'expiration_date' => Carbon::now()
                        ->addMonth($initialSubscriptions[$i]['interval_count'])
                        ->startOfDay()
                        ->toDateTimeString(),
                ]
            );
        }
    }
}