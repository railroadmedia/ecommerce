<?php

namespace Railroad\Ecommerce\Tests\Functional\Ordering;

use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\SubscriptionService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\Data\DataHelper;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Token;

class OneTimeProductTest extends EcommerceTestCase
{
    use WithoutMiddleware;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var CartAddressService
     */
    protected $cartAddressService;

    /**
     * @var TaxService
     */
    protected $taxService;

    /**
     * @var SubscriptionService
     */
    protected $subscriptionService;

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var MockObject|AuthManager
     */
    protected $authManagerMock;

    /**
     * @var MockObject|SessionGuard
     */
    protected $sessionGuardMock;
    protected $userProductRepository;
    protected $brand;

    protected function setUp()
    {
        parent::setUp();

        $this->cartService = $this->app->make(CartService::class);
        $this->cartAddressService = $this->app->make(CartAddressService::class);
        $this->taxService = $this->app->make(TaxService::class);
        $this->subscriptionService = $this->app->make(SubscriptionService::class);
        $this->subscriptionRepository = $this->app->make(SubscriptionRepository::class);
        $this->userProductRepository = $this->app->make(UserProductRepository::class);

        $this->brand = 'drumeo';
        config()->set('ecommerce.brand', $this->brand);
    }

    /**
     * @param array $data
     * @return Product
     */
    public function oneTimeProduct(array $override = []): array
    {
        $data = array_merge([
            'active' => 1,
            'is_physical' => 0,
            'weight' => 0,
            'brand' => $this->brand,
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'subscription_interval_type' => 'year',
            'subscription_interval_count' => 1,
            'digital_access_time_interval_type' => 'year',
            'digital_access_time_type' => 'one time',
            'digital_access_time_interval_length' => 1
        ], $override);
        return $this->fakeProduct($data);
    }


    /**
     * @param array $data
     * @return Product
     */
    public function subscriptionProduct(array $override = []): array
    {
        $data = array_merge([
            'active' => 1,
            'is_physical' => 0,
            'weight' => 0,
            'brand' => $this->brand,
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => 'year',
            'subscription_interval_count' => 1,
            'digital_access_time_interval_type' => 'year',
            'digital_access_time_type' => 'recurring',
            'digital_access_time_interval_length' => 1
        ], $override);
        return $this->fakeProduct($data);
    }

    public function getCreditCardRequestData()
    {
        return [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => 'Ohio',
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => 'United States',
            'gateway' => $this->brand,
            'payment_plan_number_of_payments' => 1,
            'currency' => 'USD',
        ];
    }

    /**
     * @param $fingerPrint
     */
    protected function newStripePaymentMocks($fingerPrint = null)
    {
        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer($this->faker->word . rand());
        $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);
        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card($this->faker->word);
        $fakerCard->fingerprint = $fingerPrint ?? $this->faker->word . $this->faker->randomNumber(6);
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->customer = $fakerCustomer->id;
        $fakerCard->name = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge($this->faker->word);
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);
    }

    public function purchaseProduct(array $product)
    {
        $this->newStripePaymentMocks();

        $this->cartService->addToCart($product['sku'], 1);

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $this->getCreditCardRequestData()
        );
    }

    public function test_one_time_product_extends_subscription()
    {
        $userId = $this->createAndLogInNewUser();

        $product = $this->subscriptionProduct();
        $this->purchaseProduct($product);
        $product = $this->oneTimeProduct();
        $this->purchaseProduct($product);

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'brand' => $this->brand,
                'user_id' => $userId,
                'is_active' => 1,
                'paid_until' => Carbon::now()->addYear(2)->toDateTimeString(),
            ]
        );
    }

    public function test_one_time_product_multiple_active_subscription()
    {
        $userId = $this->createAndLogInNewUser();

        $product1 = $this->subscriptionProduct();
        $this->purchaseProduct($product1);
        $product2 = $this->subscriptionProduct([
            'subscription_interval_type' => 'month',
            'digital_access_time_interval_type' => 'month'
        ]);
        $this->purchaseProduct($product2);
        $product3 = $this->oneTimeProduct();
        $this->purchaseProduct($product3);

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'brand' => $this->brand,
                'product_id' => $product1['id'],
                'user_id' => $userId,
                'is_active' => 1,
                'paid_until' => Carbon::now()->addYear(2)->toDateTimeString(),
            ]
        );
    }

    /**
     * Test buying another one time product
     * if original one time product is not expired
     * @return void
     */
    public function test_one_time_multiple_product_nonexpired()
    {
        $userId = $this->createAndLogInNewUser();

        $product1 = $this->oneTimeProduct();
        $this->purchaseProduct($product1);
        Carbon::setTestNow(Carbon::now()->addMonth(6));
        $product2 = $this->oneTimeProduct();
        $this->purchaseProduct($product2);

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $product2['id'],
                'expiration_date' => Carbon::now()->addMonth(18)->addDays(
                    config('ecommerce.days_before_access_revoked_after_expiry', 5)
                )->toDateTimeString(),
            ]
        );
    }

    /**
     * Test buying another one time product
     * if original one time product is expired
     * @return void
     */
    public function test_one_time_multiple_product_expired()
    {
        $userId = $this->createAndLogInNewUser();

        $product1 = $this->oneTimeProduct();
        $this->purchaseProduct($product1);
        Carbon::setTestNow(Carbon::now()->addMonth(18));
        $product2 = $this->oneTimeProduct();
        $this->purchaseProduct($product2);

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $product2['id'],
                'expiration_date' => Carbon::now()->addYear(1)->addDays(
                    config('ecommerce.days_before_access_revoked_after_expiry', 5)
                )->toDateTimeString(),
            ]
        );
    }

    /**
     * Test buying another one time product
     * if original one time product is expired
     * @return void
     */
    public function test_one_time_multiple_product_different_brands()
    {
        $userId = $this->createAndLogInNewUser();

        $product1 = $this->oneTimeProduct(['brand' => 'drumeo']);
        $this->purchaseProduct($product1);
        Carbon::setTestNow(Carbon::now()->addMonth(6));
        $product2 = $this->oneTimeProduct(['brand' => 'pianote']);
        $this->purchaseProduct($product2);

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $product2['id'],
                'expiration_date' => Carbon::now()->addYear(1)->addDays(
                    config('ecommerce.days_before_access_revoked_after_expiry', 5)
                )->toDateTimeString(),
            ]
        );
    }
}
