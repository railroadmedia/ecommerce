<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Mail;
use Railroad\Ecommerce\Entities\CartItem;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Mail\OrderInvoice;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Ecommerce\Transformers\OrderItemTransformer;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Token;

class OrderFormJsonControllerTest extends EcommerceTestCase
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

    protected function setUp()
    {
        parent::setUp();
        $this->cartService = $this->app->make(CartService::class);
        $this->cartAddressService = $this->app->make(CartAddressService::class);

        // OrderItemTransformer has transformed entities static registry, needed to handle cyclic entities relations
        // these must be reset every test
        OrderItemTransformer::$transformedOrders = [];
        OrderItemTransformer::$transformedProducts = [];
    }

    protected function getExpectedTaxes(float $price, string $billingCountry, string $billingState)
    {
        $taxService = $this->app->make(TaxService::class);

        $billingAddress = new Address($billingCountry, $billingState);

        return round($taxService->vat($price, $billingAddress), 2);
    }

    public function test_submit_order_validation_not_physical_products()
    {
        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $productOne = $this->fakeProduct(
            [
                'sku' => 'product-one',
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'sku' => 'product-two',
                'price' => 247,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productOneQuantity = 1;

        $this->cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity
        );

        $productTwoQuantity = 1;

        $this->cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity
        );

        $results = $this->call('PUT', '/order');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'payment_method_type',
                    'detail' => 'The payment method type field is required when payment method id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'payment_method_id',
                    'detail' => 'The payment method id field is required when payment method type is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'billing_country',
                    'detail' => 'The billing country field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'gateway',
                    'detail' => 'The gateway field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'title' => 'Validation failed.',
                    'source' => 'billing_email',
                    'detail' => 'The billing email field is required when account creation email is not present.',
                ],
                [
                    'title' => 'Validation failed.',
                    'source' => 'account_creation_email',
                    'detail' => 'The account creation email field is required when billing email is not present.',
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_submit_order_validation_customer_and_physical_products()
    {
        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $productOne = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'price' => 247,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productOneQuantity = 1;

        $this->cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity
        );

        $productTwoQuantity = 1;

        $this->cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity
        );

        $results = $this->call('PUT', '/order');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'payment_method_type',
                    'detail' => 'The payment method type field is required when payment method id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'payment_method_id',
                    'detail' => 'The payment method id field is required when payment method type is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'billing_country',
                    'detail' => 'The billing country field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'gateway',
                    'detail' => 'The gateway field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_address_id',
                    'detail' => 'The shipping address id field is required when none of shipping first name / shipping last name / shipping address line 1 / shipping city / shipping region / shipping zip or postal code / shipping country are present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_first_name',
                    'detail' => 'The shipping first name field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_last_name',
                    'detail' => 'The shipping last name field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_address_line_1',
                    'detail' => 'The shipping address line 1 field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_city',
                    'detail' => 'The shipping city field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_region',
                    'detail' => 'The shipping region field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_zip_or_postal_code',
                    'detail' => 'The shipping zip or postal code field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_country',
                    'detail' => 'The shipping country field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'title' => 'Validation failed.',
                    'source' => 'billing_email',
                    'detail' => 'The billing email field is required when account creation email is not present.',
                ],
                [
                    'title' => 'Validation failed.',
                    'source' => 'account_creation_email',
                    'detail' => 'The account creation email field is required when billing email is not present.',
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_submit_order_validation_member_and_physical_products()
    {
        $userId = $this->createAndLogInNewUser();

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $productOne = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'price' => 247,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productOneQuantity = 1;
        $productTwoQuantity = 1;

        $this->cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity,
            false,
            ''
        );

        $this->cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity,
            false,
            ''
        );

        $results = $this->call('PUT', '/order');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'payment_method_type',
                    'detail' => 'The payment method type field is required when payment method id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'payment_method_id',
                    'detail' => 'The payment method id field is required when payment method type is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'billing_country',
                    'detail' => 'The billing country field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'gateway',
                    'detail' => 'The gateway field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_address_id',
                    'detail' => 'The shipping address id field is required when none of shipping first name / shipping last name / shipping address line 1 / shipping city / shipping region / shipping zip or postal code / shipping country are present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_first_name',
                    'detail' => 'The shipping first name field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_last_name',
                    'detail' => 'The shipping last name field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_address_line_1',
                    'detail' => 'The shipping address line 1 field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_city',
                    'detail' => 'The shipping city field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_region',
                    'detail' => 'The shipping region field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_zip_or_postal_code',
                    'detail' => 'The shipping zip or postal code field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping_country',
                    'detail' => 'The shipping country field is required when shipping address id is not present.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_submit_order_validation_credit_card()
    {
        $this->stripeExternalHelperMock->method('createCardToken')
            ->willThrowException(
                new PaymentFailedException(
                    'The card number is incorrect. Check the card’s number or use a different card.'
                )
            );

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productQuantity = 1;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $password = $this->faker->word;

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'billing_region' => $this->faker->word,
                'billing_zip_or_postal_code' => $this->faker->postcode,
                'billing_country' => 'Canada',
                'account_creation_email' => $this->faker->email,
                'account_creation_password' => $password,
                'account_creation_password_confirmation' => $password,
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'card_token',
                    'detail' => 'The card token field is required when payment method type is credit_card.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_submit_order_validation_rules_for_canadian_users()
    {
        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productQuantity = 1;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $password = $this->faker->word;

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'card_token' => $this->faker->word,
                'billing_country' => 'Canada',
                'account_creation_email' => $this->faker->email,
                'account_creation_password' => $password,
                'account_creation_password_confirmation' => $password,
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'billing_region',
                    'detail' => 'The billing region field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'billing_zip_or_postal_code',
                    'detail' => 'The billing zip or postal code field is required.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_submit_order_credit_card_payment()
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();
        $fingerPrint = $this->faker->word;
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'card_token' => $fingerPrint,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => 'ab',
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => 'Canada',
            'currency' => $currency
        ];

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $fakerCustomer->id = $this->faker->word . rand();
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $fakerCard->customer = $fakerCustomer->id;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 1000,
                'price' => $shippingCostAmount,
            ]
        );

        $productOne = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 0.20,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'a' . $this->faker->word,
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'price' => 247,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'b' . $this->faker->word,
            ]
        );

        $productOneQuantity = 1;

        $this->cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity,
            false,
            ''
        );

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $expectedProductOneDiscountedPrice = 0;

        $productTwoQuantity = 1;

        $this->cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity,
            false,
            ''
        );

        $expectedProductTwoTotalPrice = $productTwo['price'] * $productTwoQuantity;

        $expectedProductTwoDiscountedPrice = 0;

        $expectedTotalFromItems = $expectedProductOneTotalPrice + $expectedProductTwoTotalPrice;

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems + $shippingCostAmount,
            $requestData['billing_country'],
            $requestData['billing_region']
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => $expectedTotalFromItems,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => 0,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()
                            ->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $productOne['id'],
                        'attributes' => array_diff_key(
                            $productOne,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'product',
                        'id' => $productTwo['id'],
                        'attributes' => array_diff_key(
                            $productTwo,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productOneQuantity,
                            'weight' => $productOne['weight'],
                            'initial_price' => $productOne['price'],
                            'total_discounted' => 0,
                            'final_price' => $productOne['price'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productOne['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productTwoQuantity,
                            'weight' => $productTwo['weight'],
                            'initial_price' => $productTwo['price'],
                            'total_discounted' => 0,
                            'final_price' => $productTwo['price'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productTwo['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $requestData['billing_zip_or_postal_code'],
                            'state' => $requestData['billing_region'],
                            'country' => $requestData['billing_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $requestData['shipping_first_name'],
                            'last_name' => $requestData['shipping_last_name'],
                            'street_line_1' => $requestData['shipping_address_line_1'],
                            'street_line_2' => null,
                            'city' => $requestData['shipping_city'],
                            'zip' => $requestData['shipping_zip_or_postal_code'],
                            'state' => $requestData['shipping_region'],
                            'country' => $requestData['shipping_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'expiration_date' => null,
            ]
        );
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'expiration_date' => null,
            ]
        );

        // creditCard
        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $fingerPrint,
                'last_four_digits' => $fakerCard->last4,
                'cardholder_name' => null,
                'company_name' => $fakerCard->brand,
                'expiration_date' => Carbon::createFromDate(
                    $fakerCard->exp_year,
                    $fakerCard->exp_month
                )
                    ->toDateTimeString(),
                'external_id' => $fakerCard->id,
                'external_customer_id' => $fakerCard->customer,
                'payment_gateway_name' => $requestData['gateway'],
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        // paymentMethod
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_paypal_payment_get_token_only()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $orderRequestData = [
            'payment_method_type' => PaymentMethod::TYPE_PAYPAL,
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => 'ab',
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => 'Canada',
            'currency' => $currency
        ];

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $productOne = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 0.20,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'price' => 247,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productOneQuantity = 1;
        $productTwoQuantity = 1;

        $this->cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity,
            false,
            ''
        );

        $this->cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity,
            false,
            ''
        );

        $paypalToken = $this->faker->word;

        $this->paypalExternalHelperMock->method('createBillingAgreementExpressCheckoutToken')
            ->willReturn($paypalToken);

        $response = $this->call(
            'PUT',
            '/order',
            $orderRequestData
        );

        // assert order data was set in the session
        $response->assertSessionHas('order-form-input', $orderRequestData);

        // assert response has redirect information
        $response->assertJsonStructure(
            [
                'data',
                'meta' => ['redirect']
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert the redirect link contains the token
        $this->assertContains(
            'token=' . $paypalToken,
            $decodedResponse['meta']['redirect']
        );

        /*
         * the paypal payment flow for an order requires
         * the user to be redirected back from paypal site with an agreement token
         * and this is a different action tested in OrderFormControllerTest
         */
    }

    public function test_submit_order_paypal_payment_with_token()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $orderRequestData = [
            'payment_method_type' => PaymentMethod::TYPE_PAYPAL,
            'token' => $this->faker->word . rand(),
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => 'ab',
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => 'Canada',
            'currency' => $currency
        ];

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $productOne = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 0.20,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'a' . $this->faker->word,
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'price' => 247,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'b' . $this->faker->word,
            ]
        );

        $productOneQuantity = 1;
        $productTwoQuantity = 1;

        $this->cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity,
            false,
            ''
        );

        $this->cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity,
            false,
            ''
        );

        $paypalToken = $orderRequestData['token'];

        $billingAgreementId = 'fakeBillingAgreementId' . rand();
        $transcationId = 'fakeTransactionId' . rand();

        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willReturn($billingAgreementId);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($transcationId);

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $expectedProductTwoTotalPrice = $productTwo['price'] * $productTwoQuantity;

        $expectedTotalFromItems = $expectedProductOneTotalPrice + $expectedProductTwoTotalPrice;

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems + $shippingCostAmount,
            $orderRequestData['billing_country'],
            $orderRequestData['billing_region']
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService->convertFromBase(round($expectedOrderTotalDue, 2), $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

        $this->session(['order-form-input' => $orderRequestData]);

        $response = $this->call(
            'PUT',
            '/order',
            ['token' => $paypalToken]
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => $expectedTotalFromItems,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()
                            ->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $productOne['id'],
                        'attributes' => array_diff_key(
                            $productOne,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'product',
                        'id' => $productTwo['id'],
                        'attributes' => array_diff_key(
                            $productTwo,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productOneQuantity,
                            'weight' => $productOne['weight'],
                            'initial_price' => $productOne['price'],
                            'total_discounted' => 0,
                            'final_price' => $productOne['price'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productOne['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productTwoQuantity,
                            'weight' => $productTwo['weight'],
                            'initial_price' => $productTwo['price'],
                            'total_discounted' => 0,
                            'final_price' => $productTwo['price'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productTwo['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'address',
                        'attributes' => []
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $orderRequestData['shipping_first_name'],
                            'last_name' => $orderRequestData['shipping_last_name'],
                            'street_line_1' => $orderRequestData['shipping_address_line_1'],
                            'street_line_2' => null,
                            'city' => $orderRequestData['shipping_city'],
                            'zip' => $orderRequestData['shipping_zip_or_postal_code'],
                            'state' => $orderRequestData['shipping_region'],
                            'country' => $orderRequestData['shipping_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'expiration_date' => null,
            ]
        );
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'expiration_date' => null,
            ]
        );

        // assert payment
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_id' => $transcationId,
                'external_provider' => 'paypal',
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'payment_method_id' => 1,
                'currency' => $currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_existing_payment_method_credit_card()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $billingData = [
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
        ];

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $fingerPrint = $this->faker->word;
        $externalId = 'card_' . $this->faker->password;
        $externalCustomerId = 'cus_' . $this->faker->password;
        $cardExpirationYear = 2019;
        $cardExpirationMonth = 12;
        $cardExpirationDate = Carbon::createFromDate(
            $cardExpirationYear,
            $cardExpirationMonth
        )
            ->toDateTimeString();

        $creditCard = $this->fakeCreditCard(
            [
                'fingerprint' => $fingerPrint,
                'last_four_digits' => $this->faker->randomNumber(4),
                'cardholder_name' => $this->faker->name,
                'company_name' => $this->faker->creditCardType,
                'expiration_date' => $cardExpirationDate,
                'external_id' => $externalId,
                'external_customer_id' => $externalCustomerId,
                'payment_gateway_name' => $brand
            ]
        );

        $billingAddress = $this->fakeAddress(
            [
                'user_id' => $userId,
                'first_name' => null,
                'last_name' => null,
                'street_line_1' => null,
                'street_line_2' => null,
                'city' => null,
                'type' => ConfigService::$billingAddressType,
                'zip' => $billingData['billing_zip_or_postal_code'],
                'state' => $billingData['billing_region'],
                'country' => $billingData['billing_country'],
            ]
        );

        $paymentMethod = $this->fakePaymentMethod(
            [
                'method_id' => $creditCard['id'],
                'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'currency' => $currency,
                'billing_address_id' => $billingAddress['id']
            ]
        );

        $userPaymentMethod = $this->fakeUserPaymentMethod(
            [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethod['id'],
                'is_primary' => true
            ]
        );

        $orderRequestData = [
                'payment_method_id' => $paymentMethod['id'],
                'company_name' => $this->faker->creditCardType,
                'gateway' => $brand,
                'shipping_first_name' => $this->faker->firstName,
                'shipping_last_name' => $this->faker->lastName,
                'shipping_address_line_1' => $this->faker->address,
                'shipping_city' => $this->faker->city,
                'shipping_region' => 'ab',
                'shipping_zip_or_postal_code' => $this->faker->postcode,
                'shipping_country' => 'Canada',
                'currency' => $currency
            ] + $billingData;

        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand = $creditCard['company_name'];
        $fakerCard->last4 = $creditCard['last_four_digits'];
        $fakerCard->exp_year = $cardExpirationYear;
        $fakerCard->exp_month = $cardExpirationMonth;
        $fakerCard->id = $externalId;
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn($fakerCard);

        $chargeAmount = $this->faker->numberBetween(100, 200);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $chargeAmount;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $productOne = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 0.20,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'a' . $this->faker->word,
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'price' => 247,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'b' . $this->faker->word,
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
                'amount' => 10
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $productOne['id'],
                'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                'min' => '2',
                'max' => '2000000',
            ]
        );

        $productOneQuantity = 1;
        $productTwoQuantity = 1;

        $this->cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity,
            false,
            ''
        );

        $this->cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity,
            false,
            ''
        );

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $expectedProductTwoTotalPrice = $productTwo['price'] * $productTwoQuantity;

        $expectedTotalFromItems = $expectedProductOneTotalPrice + $expectedProductTwoTotalPrice - $discount['amount'];

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems + $shippingCostAmount,
            $billingData['billing_country'],
            $billingData['billing_region']
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService->convertFromBase(round($expectedOrderTotalDue, 2), $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

        $response = $this->call(
            'PUT',
            '/order',
            $orderRequestData
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => $expectedTotalFromItems,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()
                            ->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $productOne['id'],
                        'attributes' => array_diff_key(
                            $productOne,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'product',
                        'id' => $productTwo['id'],
                        'attributes' => array_diff_key(
                            $productTwo,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productOneQuantity,
                            'weight' => $productOne['weight'],
                            'initial_price' => $productOne['price'],
                            'total_discounted' => 0,
                            'final_price' => $productOne['price'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productOne['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productTwoQuantity,
                            'weight' => $productTwo['weight'],
                            'initial_price' => $productTwo['price'],
                            'total_discounted' => 0,
                            'final_price' => $productTwo['price'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productTwo['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'address',
                        'attributes' => []
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $orderRequestData['shipping_first_name'],
                            'last_name' => $orderRequestData['shipping_last_name'],
                            'street_line_1' => $orderRequestData['shipping_address_line_1'],
                            'street_line_2' => null,
                            'city' => $orderRequestData['shipping_city'],
                            'zip' => $orderRequestData['shipping_zip_or_postal_code'],
                            'state' => $orderRequestData['shipping_region'],
                            'country' => $orderRequestData['shipping_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ],
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'expiration_date' => null,
            ]
        );
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'expiration_date' => null,
            ]
        );

        // assert payment
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_id' => $fakerCharge->id,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'payment_method_id' => $paymentMethod['id'],
                'currency' => $currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_existing_payment_method_paypal()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $billingData = [
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
        ];

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $billingAddress = $this->fakeAddress(
            [
                'user_id' => $userId,
                'first_name' => null,
                'last_name' => null,
                'street_line_1' => null,
                'street_line_2' => null,
                'city' => null,
                'type' => ConfigService::$billingAddressType,
                'zip' => $billingData['billing_zip_or_postal_code'],
                'state' => $billingData['billing_region'],
                'country' => $billingData['billing_country'],
            ]
        );

        $billingAgreementExternalId = 'B-' . $this->faker->password;

        $paypalAgreement = $this->fakePaypalBillingAgreement(
            [
                'external_id' => $billingAgreementExternalId,
                'payment_gateway_name' => $brand,
            ]
        );

        $paymentMethod = $this->fakePaymentMethod(
            [
                'method_id' => $paypalAgreement['id'],
                'method_type' => PaymentMethod::TYPE_PAYPAL,
                'currency' => $currency,
                'billing_address_id' => $billingAddress['id']
            ]
        );

        $userPaymentMethod = $this->fakeUserPaymentMethod(
            [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethod['id'],
                'is_primary' => true
            ]
        );

        $orderRequestData = [
                'payment_method_id' => $paymentMethod['id'],
                'company_name' => $this->faker->creditCardType,
                'gateway' => $brand,
                'shipping_first_name' => $this->faker->firstName,
                'shipping_last_name' => $this->faker->lastName,
                'shipping_address_line_1' => $this->faker->address,
                'shipping_city' => $this->faker->word,
                'shipping_region' => 'ab',
                'shipping_zip_or_postal_code' => $this->faker->postcode,
                'shipping_country' => 'Canada',
                'currency' => $currency
            ] + $billingData;

        $transactionId = rand(1, 100);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($transactionId);

        $productOne = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 0.20,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'a' . $this->faker->word,
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'price' => 247,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'b' . $this->faker->word,
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
                'amount' => 10
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $productOne['id'],
                'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                'min' => '2',
                'max' => '2000000',
            ]
        );

        $productOneQuantity = 1;

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $productTwoQuantity = 1;

        $this->cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity,
            false,
            ''
        );

        $this->cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity,
            false,
            ''
        );

        $expectedProductTwoTotalPrice = $productTwo['price'] * $productTwoQuantity;

        $expectedTotalFromItems = $expectedProductOneTotalPrice + $expectedProductTwoTotalPrice - $discount['amount'];

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems + $shippingCostAmount,
            $billingData['billing_country'],
            $billingData['billing_region']
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService->convertFromBase(round($expectedOrderTotalDue, 2), $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

        $response = $this->call(
            'PUT',
            '/order',
            $orderRequestData
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => $expectedTotalFromItems,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()
                            ->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $productOne['id'],
                        'attributes' => array_diff_key(
                            $productOne,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'product',
                        'id' => $productTwo['id'],
                        'attributes' => array_diff_key(
                            $productTwo,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productOneQuantity,
                            'weight' => $productOne['weight'],
                            'initial_price' => $productOne['price'],
                            'total_discounted' => 0,
                            'final_price' => $productOne['price'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productOne['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productTwoQuantity,
                            'weight' => $productTwo['weight'],
                            'initial_price' => $productTwo['price'],
                            'total_discounted' => 0,
                            'final_price' => $productTwo['price'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productTwo['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'address',
                        'attributes' => []
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $orderRequestData['shipping_first_name'],
                            'last_name' => $orderRequestData['shipping_last_name'],
                            'street_line_1' => $orderRequestData['shipping_address_line_1'],
                            'street_line_2' => null,
                            'city' => $orderRequestData['shipping_city'],
                            'zip' => $orderRequestData['shipping_zip_or_postal_code'],
                            'state' => $orderRequestData['shipping_region'],
                            'country' => $orderRequestData['shipping_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'expiration_date' => null,
            ]
        );
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'expiration_date' => null,
            ]
        );

        // assert payment
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_id' => $transactionId,
                'external_provider' => 'paypal',
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'payment_method_id' => $paymentMethod['id'],
                'currency' => $currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_existing_shipping_address()
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();
        $fingerPrint = $this->faker->word;
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $session = $this->app->make(Store::class);

        $shippingAddressData = [
            'type' => ConfigService::$shippingAddressType,
            'brand' => $brand,
            'user_id' => $userId,
            'customer_id' => null,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'street_line_1' => $this->faker->address,
            'street_line_2' => null,
            'city' => $this->faker->city,
            'zip' => $this->faker->postcode,
            'state' => 'AB',
            'country' => 'Canada'
        ];

        $shippingAddress = $this->fakeAddress($shippingAddressData);

        $sessionShippingAddress = new Address();

        $sessionShippingAddress->setCountry($shippingAddress['country'])
            ->setState($shippingAddress['state'])
            ->setZip($shippingAddress['zip'])
            ->setFirstName($shippingAddress['first_name'])
            ->setLastName($shippingAddress['last_name'])
            ->setStreetLine1($shippingAddress['street_line_1'])
            ->setCity($shippingAddress['city']);

        $this->cartAddressService->setAddress(
            $sessionShippingAddress,
            CartAddressService::SHIPPING_ADDRESS_TYPE
        );

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'billing_region' => $this->faker->word,
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => 'Canada',
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'card_token' => $fingerPrint,
            'shipping_address_id' => $shippingAddress['id'],
            'currency' => $currency
        ];

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $fakerCard->customer = $fakerCustomer->id;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $productOne = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 0.20,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'a' . $this->faker->word,
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'price' => 247,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'b' . $this->faker->word,
            ]
        );

        $productOneQuantity = 1;

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $productTwoQuantity = 1;

        $this->cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity,
            false,
            ''
        );

        $this->cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity,
            false,
            ''
        );

        $expectedProductTwoTotalPrice = $productTwo['price'] * $productTwoQuantity;

        $expectedTotalFromItems = $expectedProductOneTotalPrice + $expectedProductTwoTotalPrice;

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems + $shippingCostAmount,
            $requestData['billing_country'],
            $requestData['billing_region']
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService->convertFromBase(round($expectedOrderTotalDue, 2), $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => $expectedTotalFromItems,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => 0,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()
                            ->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => [
                                'type' => 'address',
                                'id' => $shippingAddress['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $productOne['id'],
                        'attributes' => array_diff_key(
                            $productOne,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'product',
                        'id' => $productTwo['id'],
                        'attributes' => array_diff_key(
                            $productTwo,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productOneQuantity,
                            'weight' => $productOne['weight'],
                            'initial_price' => $productOne['price'],
                            'total_discounted' => 0,
                            'final_price' => $productOne['price'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productOne['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productTwoQuantity,
                            'weight' => $productTwo['weight'],
                            'initial_price' => $productTwo['price'],
                            'total_discounted' => 0,
                            'final_price' => $productTwo['price'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productTwo['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $requestData['billing_zip_or_postal_code'],
                            'state' => $requestData['billing_region'],
                            'country' => $requestData['billing_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'id' => $shippingAddress['id'],
                        'attributes' => array_diff_key(
                            $shippingAddress,
                            [
                                'id' => true,
                                'user_id' => true,
                                'customer_id' => true
                            ]
                        ),
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'expiration_date' => null,
            ]
        );
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'expiration_date' => null,
            ]
        );

        // creditCard
        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $fingerPrint,
                'last_four_digits' => $fakerCard->last4,
                'cardholder_name' => null,
                'company_name' => $fakerCard->brand,
                'expiration_date' => Carbon::createFromDate(
                    $fakerCard->exp_year,
                    $fakerCard->exp_month
                )
                    ->toDateTimeString(),
                'external_id' => $fakerCard->id,
                'external_customer_id' => $fakerCard->customer,
                'payment_gateway_name' => $requestData['gateway'],
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        // assert payment
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_id' => $fakerCharge->id,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'currency' => $currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_subscription()
    {
        $userId = $this->createAndLogInNewUser();

        $brand = 'drumeo';
        ConfigService::$brand = $brand;
        $currency = $this->getCurrency();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeSubscription,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                'subscription_interval_count' => 1,
            ]
        );

        $productQuantity = 1;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => $this->faker->word,
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => 'Canada',
            'gateway' => $brand,
        ];

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_with_discount_based_on_shipping_requirements()
    {
        $userId = $this->createAndLogInNewUser();

        $brand = 'drumeo';
        ConfigService::$brand = $brand;
        $currency = $this->getCurrency();

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $cardToken = $this->faker->word;

        $orderRequestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => 'ab',
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => 'Canada',
            'currency' => $currency
        ];

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 2,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productQuantity = 1;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'product_id' => $product['id'],
                'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
                'amount' => 10
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $product['id'],
                'type' => DiscountCriteriaService::SHIPPING_TOTAL_REQUIREMENT_TYPE,
                'min' => '1',
                'max' => '2000',
            ]
        );

        $expectedProductTotalPrice = $product['price'] * $productQuantity;

        $expectedDiscountAmount = round($discount['amount'] * $productQuantity, 2);

        $expectedProductDiscountedPrice = round(
            $expectedProductTotalPrice - $expectedDiscountAmount,
            2
        );

        $expectedTotalFromItems = $expectedProductDiscountedPrice;

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems + $shippingCostAmount,
            $orderRequestData['billing_country'],
            $orderRequestData['billing_region']
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $response = $this->call(
            'PUT',
            '/order',
            $orderRequestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => $expectedProductDiscountedPrice,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()
                            ->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $product['id'],
                        'attributes' => array_diff_key(
                            $product,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productQuantity,
                            'weight' => $product['weight'],
                            'initial_price' => $product['price'],
                            'total_discounted' => $expectedDiscountAmount,
                            'final_price' => $expectedProductDiscountedPrice,
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $product['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $orderRequestData['billing_zip_or_postal_code'],
                            'state' => $orderRequestData['billing_region'],
                            'country' => $orderRequestData['billing_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $orderRequestData['shipping_first_name'],
                            'last_name' => $orderRequestData['shipping_last_name'],
                            'street_line_1' => $orderRequestData['shipping_address_line_1'],
                            'street_line_2' => null,
                            'city' => $orderRequestData['shipping_city'],
                            'zip' => $orderRequestData['shipping_zip_or_postal_code'],
                            'state' => $orderRequestData['shipping_region'],
                            'country' => $orderRequestData['shipping_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'total_due' => $expectedOrderTotalDue,
                'product_due' => $expectedProductDiscountedPrice,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => $shippingCostAmount,
                'finance_due' => 0,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => ConfigService::$brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_with_discount_based_on_product_quantity()
    {
        $userId = $this->createAndLogInNewUser();

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $brand = 'drumeo';
        ConfigService::$brand = $brand;
        $currency = $this->getCurrency();

        $cardToken = $this->faker->word;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $orderRequestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => 'ab',
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => 'Canada',
            'currency' => $currency
        ];

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 2,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productQuantity = 2;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'product_id' => $product['id'],
                'type' => 'product amount off',
                'amount' => 1.4,
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $product['id'],
                'type' => 'product quantity requirement',
                'min' => 2,
                'max' => 5,
            ]
        );

        $expectedProductTotalPrice = $product['price'] * $productQuantity;

        $expectedDiscountAmount = round($discount['amount'] * $productQuantity, 2);

        $expectedProductDiscountedPrice = round(
            $expectedProductTotalPrice - $expectedDiscountAmount,
            2
        );

        $expectedTotalFromItems = $expectedProductDiscountedPrice;

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems + $shippingCostAmount,
            $orderRequestData['billing_country'],
            $orderRequestData['billing_region']
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $response = $this->call(
            'PUT',
            '/order',
            $orderRequestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => $expectedProductDiscountedPrice,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()
                            ->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $product['id'],
                        'attributes' => array_diff_key(
                            $product,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productQuantity,
                            'weight' => $product['weight'],
                            'initial_price' => $product['price'],
                            'total_discounted' => $expectedDiscountAmount,
                            'final_price' => $expectedProductDiscountedPrice,
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $product['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $orderRequestData['billing_zip_or_postal_code'],
                            'state' => $orderRequestData['billing_region'],
                            'country' => $orderRequestData['billing_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $orderRequestData['shipping_first_name'],
                            'last_name' => $orderRequestData['shipping_last_name'],
                            'street_line_1' => $orderRequestData['shipping_address_line_1'],
                            'street_line_2' => null,
                            'city' => $orderRequestData['shipping_city'],
                            'zip' => $orderRequestData['shipping_zip_or_postal_code'],
                            'state' => $orderRequestData['shipping_region'],
                            'country' => $orderRequestData['shipping_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'total_due' => $expectedOrderTotalDue,
                'product_due' => $expectedProductDiscountedPrice,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => $shippingCostAmount,
                'finance_due' => 0,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => ConfigService::$brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_subscription_with_discount_free_days()
    {
        $userId = $this->createAndLogInNewUser();

        $brand = 'drumeo';
        ConfigService::$brand = $brand;
        $currency = $this->getCurrency();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeSubscription,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                'subscription_interval_count' => 1,
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'product_id' => $product['id'],
                'type' => DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE,
                'amount' => 10,
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $product['id'],
                'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
                'min' => Carbon::now()
                    ->subDay(1),
                'max' => Carbon::now()
                    ->addDays(3),
            ]
        );

        $productQuantity = 1;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => $this->faker->word,
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => 'Canada',
            'gateway' => $brand,
        ];

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'brand' => $brand,
                'product_id' => $product['id'],
                'user_id' => $userId,
                'is_active' => "1",
                'start_date' => Carbon::now()
                    ->toDateTimeString(),
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->addDays(10)
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->addDays(10)
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_submit_order_subscription_with_discount_recurring_amount()
    {
        $userId = $this->createAndLogInNewUser();

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeSubscription,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                'subscription_interval_count' => 1,
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'product_id' => $product['id'],
                'type' => DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE,
                'amount' => 10,
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $product['id'],
                'type' => 'date requirement',
                'min' => Carbon::now()
                    ->subDay(1),
                'max' => Carbon::now()
                    ->addDays(3),
            ]
        );

        $productQuantity = 1;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $expectedTotalPrice = round($product['price'] - $discount['amount'], 2);

        $response = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'card_token' => $cardToken,
                'billing_region' => $this->faker->word,
                'billing_zip_or_postal_code' => $this->faker->postcode,
                'billing_country' => 'Canada',
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        //assert the discount days are added to the paid_until data
        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'brand' => ConfigService::$brand,
                'product_id' => $product['id'],
                'user_id' => $userId,
                'is_active' => "1",
                'start_date' => Carbon::now()
                    ->toDateTimeString(),
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->toDateTimeString(),
                'total_price' => $expectedTotalPrice,
            ]
        );
    }

    public function test_submit_order_with_discount_order_total_amount()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
                'amount' => 10,
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $product['id'],
                'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                'min' => 5,
                'max' => 500,
            ]
        );

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
            'gateway' => 'drumeo',
        ];

        $productQuantity = 2;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $expectedTotalFromItems = round($product['price'] * $productQuantity - $discount['amount'], 2);

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems,
            $requestData['billing_country'],
            $requestData['billing_region']
        );

        $expectedPrice = round($expectedTotalFromItems + $expectedTaxes, 2);

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        // assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'total_due' => $expectedPrice,
                'product_due' => $expectedTotalFromItems,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => 0,
                'finance_due' => 0,
                'total_paid' => $expectedPrice,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => ConfigService::$brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_with_discount_order_total_percent()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();

        $cardToken = $this->faker->word;

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
            'gateway' => $brand,
            'currency' => $currency
        ];

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'type' => DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE,
                'amount' => 10.2,
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $product['id'],
                'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                'min' => 5,
                'max' => 500,
            ]
        );

        $productQuantity = 2;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $totalFromItemsBeforeDiscount = $product['price'] * $productQuantity;

        $expectedTotalFromItems =
            round($totalFromItemsBeforeDiscount - $discount['amount'] / 100 * $totalFromItemsBeforeDiscount, 2);

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems,
            $requestData['billing_country'],
            $requestData['billing_region']
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $expectedTaxes, 2);

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        // assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => 0,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );
    }

    public function test_submit_order_with_discount_product_amount()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
                'product_id' => $product['id'],
                'amount' => 10.2,
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $product['id'],
                'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                'min' => 5,
                'max' => 500,
            ]
        );

        $productQuantity = 2;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $expectedInitialProductPrice = $product['price'] * $productQuantity;

        $expectedProductDiscount = $discount['amount'] * $productQuantity;

        $expectedTotalFromItems = round($expectedInitialProductPrice - $expectedProductDiscount, 2);

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems,
            $country,
            $state
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $expectedTaxes, 2);

        $expectedDiscountAmount = round($expectedInitialProductPrice - ($expectedOrderTotalDue - $expectedTaxes), 2);

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'card_token' => $cardToken,
                'billing_region' => $state,
                'billing_zip_or_postal_code' => $zip,
                'billing_country' => $country,
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => 0,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'quantity' => $productQuantity,
                'initial_price' => $product['price'],
                'total_discounted' => $expectedDiscountAmount,
                'final_price' => $expectedTotalFromItems,
            ]
        );
    }

    public function test_submit_order_with_discount_product_percent()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
                'product_id' => $product['id'],
                'amount' => 10.2,
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $product['id'],
                'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                'min' => 5,
                'max' => 500,
            ]
        );

        $productQuantity = 2;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $expectedInitialProductPrice = $product['price'] * $productQuantity;

        $expectedProductDiscount = $discount['amount'] / 100 * $product['price'] * $productQuantity;

        $expectedTotalFromItems = round($expectedInitialProductPrice - $expectedProductDiscount, 2);

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems,
            $country,
            $state
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $expectedTaxes, 2);

        $expectedDiscountAmount = round($expectedInitialProductPrice - ($expectedOrderTotalDue - $expectedTaxes), 2);

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'card_token' => $cardToken,
                'billing_region' => $state,
                'billing_zip_or_postal_code' => $zip,
                'billing_country' => $country,
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        // assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => 0,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        // assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'quantity' => $productQuantity,
                'initial_price' => $product['price'],
                'total_discounted' => $expectedDiscountAmount,
                'final_price' => $expectedTotalFromItems,
            ]
        );
    }

    public function test_submit_order_with_discount_shipping_costs_amount()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 2,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'type' => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
                'amount' => 2,
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $product['id'],
                'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                'min' => 5,
                'max' => 500,
            ]
        );

        $productQuantity = 2;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $expectedInitialProductPrice = $product['price'] * $productQuantity;

        $expectedTotalFromItems = round($expectedInitialProductPrice, 2);

        $expectedShippingCostAmount = round($shippingCostAmount - $discount['amount'], 2);

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems + $expectedShippingCostAmount,
            $country,
            $state
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $expectedShippingCostAmount + $expectedTaxes, 2);

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'card_token' => $cardToken,
                'billing_region' => $state,
                'billing_zip_or_postal_code' => $zip,
                'billing_country' => $country,
                'gateway' => 'drumeo',
                'shipping_first_name' => $this->faker->firstName,
                'shipping_last_name' => $this->faker->lastName,
                'shipping_address_line_1' => $this->faker->address,
                'shipping_city' => $this->faker->city,
                'shipping_region' => $state,
                'shipping_zip_or_postal_code' => $this->faker->postcode,
                'shipping_country' => $country,
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        // assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => $expectedShippingCostAmount,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        // assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'quantity' => $productQuantity,
                'initial_price' => $product['price'],
                'total_discounted' => 0,
                'final_price' => $expectedTotalFromItems,
            ]
        );
    }

    public function test_submit_order_with_discount_shipping_costs_percent()
    {
        $userId = $this->createAndLogInNewUser();

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 2,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'type' => DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
                'amount' => 25,
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $product['id'],
                'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                'min' => 5,
                'max' => 500,
            ]
        );

        $productQuantity = 2;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $expectedInitialProductPrice = $product['price'] * $productQuantity;

        $expectedTotalFromItems = round($expectedInitialProductPrice, 2);

        $expectedShippingDiscount = round($discount['amount'] / 100 * $shippingCostAmount, 2);

        $expectedShippingCostAmount = round($shippingCostAmount - $expectedShippingDiscount, 2);

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems + $expectedShippingCostAmount,
            $country,
            $state
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $expectedShippingCostAmount + $expectedTaxes, 2);

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'card_token' => $cardToken,
                'billing_region' => $state,
                'billing_zip_or_postal_code' => $zip,
                'billing_country' => $country,
                'gateway' => $brand,
                'shipping_first_name' => $this->faker->firstName,
                'shipping_last_name' => $this->faker->lastName,
                'shipping_address_line_1' => $this->faker->address,
                'shipping_city' => $this->faker->city,
                'shipping_region' => $state,
                'shipping_zip_or_postal_code' => $this->faker->postcode,
                'shipping_country' => $country,
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        // assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => $expectedShippingCostAmount,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        // assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'initial_price' => $product['price'],
                'total_discounted' => 0,
                'final_price' => $expectedTotalFromItems,
            ]
        );
    }

    public function test_submit_order_with_discount_shipping_costs_overwrite()
    {
        $userId = $this->createAndLogInNewUser();

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 2,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'type' => DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
                'amount' => 12,
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $product['id'],
                'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                'min' => 5,
                'max' => 500,
            ]
        );

        $productQuantity = 2;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $expectedInitialProductPrice = $product['price'] * $productQuantity;

        $expectedTotalFromItems = round($expectedInitialProductPrice, 2);

        $expectedShippingCostAmount = round($discount['amount'], 2);

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems + $expectedShippingCostAmount,
            $country,
            $state
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $expectedShippingCostAmount + $expectedTaxes, 2);

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'card_token' => $cardToken,
                'billing_region' => $state,
                'billing_zip_or_postal_code' => $zip,
                'billing_country' => $country,
                'gateway' => $brand,
                'shipping_first_name' => $this->faker->firstName,
                'shipping_last_name' => $this->faker->lastName,
                'shipping_address_line_1' => $this->faker->address,
                'shipping_city' => $this->faker->city,
                'shipping_region' => $state,
                'shipping_zip_or_postal_code' => $this->faker->postcode,
                'shipping_country' => $country,
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        // assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => $expectedShippingCostAmount,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        // assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'initial_price' => $product['price'],
                'total_discounted' => 0,
                'final_price' => $expectedTotalFromItems,
            ]
        );
    }

    public function test_customer_submit_order()
    {
        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $currency = $this->getCurrency();

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => 'Canada',
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 0,
                'max' => 10,
                'price' => $shippingCostAmount,
            ]
        );

        $productOne = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 2,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'a' . $this->faker->word,
            ]
        );

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
                'amount' => 12,
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'product_id' => $productOne['id'],
                'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                'min' => 5,
                'max' => 500,
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'weight' => 2,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
                'sku' => 'b' . $this->faker->word,
            ]
        );

        $productOneQuantity = 2;
        $productTwoQuantity = 1;

        $this->cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity,
            false,
            ''
        );

        $this->cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity,
            false,
            ''
        );

        $expectedInitialProductOnePrice = $productOne['price'] * $productOneQuantity;
        $expectedInitialProductTwoPrice = $productTwo['price'] * $productTwoQuantity;

        $expectedTotalFromItems =
            round($expectedInitialProductOnePrice + $expectedInitialProductTwoPrice - $discount['amount'], 2);

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems + $shippingCostAmount,
            $country,
            $state
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $expectedDiscountAmount = round($discount['amount'], 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = round(
            $currencyService->convertFromBase(
                $expectedOrderTotalDue,
                $currency
            ),
            2
        );

        $expectedConversionRate = $currencyService->getRate($currency);

        $billingEmailAddress = $this->faker->email;
        $cardToken = $this->faker->word;

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $state,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'billing_email' => $billingEmailAddress,
            'currency' => $currency
        ];

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = $response->decodeResponseJson();

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => $expectedTotalFromItems,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()
                            ->toDateTimeString(),
                    ],
                    'relationships' => [
                        'customer' => [
                            'data' => [
                                'type' => 'customer'
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $productOne['id'],
                        'attributes' => array_diff_key(
                            $productOne,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'product',
                        'id' => $productTwo['id'],
                        'attributes' => array_diff_key(
                            $productTwo,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'customer',
                        'attributes' => [
                            'brand' => $brand,
                            'phone' => null,
                            'email' => $billingEmailAddress,
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ]
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productOneQuantity,
                            'weight' => $productOne['weight'],
                            'initial_price' => $productOne['price'],
                            'total_discounted' => 0,
                            'final_price' => $expectedInitialProductOnePrice,
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productOne['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productTwoQuantity,
                            'weight' => $productTwo['weight'],
                            'initial_price' => $productTwo['price'],
                            'total_discounted' => 0,
                            'final_price' => $expectedInitialProductTwoPrice,
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $productTwo['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $requestData['billing_zip_or_postal_code'],
                            'state' => $requestData['billing_region'],
                            'country' => $requestData['billing_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'customer' => [
                                'data' => [
                                    'type' => 'customer',
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $requestData['shipping_first_name'],
                            'last_name' => $requestData['shipping_last_name'],
                            'street_line_1' => $requestData['shipping_address_line_1'],
                            'street_line_2' => null,
                            'city' => $requestData['shipping_city'],
                            'zip' => $requestData['shipping_zip_or_postal_code'],
                            'state' => $requestData['shipping_region'],
                            'country' => $requestData['shipping_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'customer' => [
                                'data' => [
                                    'type' => 'customer',
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $decodedResponse
        );

        $customerId = null;

        foreach ($decodedResponse['included'] as $includedData) {
            if ($includedData['type'] == 'customer') {
                $customerId = $includedData['id'];
            }
        }

        $this->assertNotNull($customerId); // customer id provided in response

        $this->assertDatabaseHas(
            ConfigService::$tableCustomer,
            [
                'id' => $customerId,
                'email' => $billingEmailAddress,
                'brand' => ConfigService::$brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        // billingAddress
        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'type' => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand' => ConfigService::$brand,
                'user_id' => null,
                'customer_id' => $customerId,
                'zip' => $requestData['billing_zip_or_postal_code'],
                'state' => $requestData['billing_region'],
                'country' => $requestData['billing_country'],
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        // userPaymentMethods
        $this->assertDatabaseHas(
            ConfigService::$tableCustomerPaymentMethods,
            [
                'customer_id' => $customerId,
                'is_primary' => true,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => $brand,
                'user_id' => null,
                'customer_id' => $customerId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => $shippingCostAmount,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => $expectedPaymentTotalDue,
                'total_paid' => $expectedPaymentTotalDue,
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_id' => $fakerCharge->id,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'currency' => $currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        // orderItem
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'weight' => $productOne['weight'],
                'initial_price' => $productOne['price'],
                'total_discounted' => 0,
                'final_price' => $productOne['price'] * $productOneQuantity,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'weight' => $productTwo['weight'],
                'initial_price' => $productTwo['price'],
                'total_discounted' => 0,
                'final_price' => $productTwo['price'] * $productTwoQuantity,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        // orderItemFulfillment
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItemFulfillment,
            [
                'status' => ConfigService::$fulfillmentStatusPending,
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_new_user()
    {
        // todo - reviw & update
        // vendor/laravel/framework/src/Illuminate/Auth/AuthManager.php:84 - InvalidArgumentException: Auth guard [] is not defined.
        $authManagerMock =
            $this->getMockBuilder(AuthManager::class)
                ->disableOriginalConstructor()
                ->setMethods(['loginUsingId'])
                ->getMock();

        $authManagerMock->method('loginUsingId')->willReturn(true);

        $this->app->instance(Factory::class, $authManagerMock);

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $currency = $this->getCurrency();

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productQuantity = 2;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $expectedInitialProductPrice = $product['price'] * $productQuantity;

        $expectedTotalFromItems = round($expectedInitialProductPrice, 2);

        $expectedTaxes = $this->getExpectedTaxes(
            $expectedTotalFromItems,
            $country,
            $state
        );

        $expectedOrderTotalDue = round($expectedTotalFromItems + $expectedTaxes, 2);

        $accountCreationMail = $this->faker->email;
        $accountCreationPassword = $this->faker->password;

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $state,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'currency' => $currency,
            'account_creation_email' => $accountCreationMail,
            'account_creation_password' => $accountCreationPassword,
            'account_creation_password_confirmation' => $accountCreationPassword,
        ];

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        // assert response has newly created user information
        $response->assertJsonStructure(
            [
                'data' => [
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type',
                                'id',
                            ]
                        ]
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $userId = $decodedResponse['data']['relationships']['user']['data']['id'];

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => $expectedTotalFromItems,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => 0,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()
                            ->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $product['id'],
                        'attributes' => array_diff_key(
                            $product,
                            [
                                'id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'orderItem',
                        'attributes' => [
                            'quantity' => $productQuantity,
                            'weight' => $product['weight'],
                            'initial_price' => $product['price'],
                            'total_discounted' => 0,
                            'final_price' => $expectedInitialProductPrice,
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'product',
                                    'id' => $product['id']
                                ]
                            ]
                        ],
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $requestData['billing_zip_or_postal_code'],
                            'state' => $requestData['billing_region'],
                            'country' => $requestData['billing_country'],
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $decodedResponse
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => 0,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'initial_price' => $product['price'],
                'total_discounted' => 0,
                'final_price' => $expectedTotalFromItems,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'is_primary' => true,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_invoice_order_send_after_submit()
    {
        Mail::fake();

        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'type' => ConfigService::$typeProduct,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'weight' => 0,
                'subscription_interval_type' => '',
                'subscription_interval_count' => '',
            ]
        );

        $productQuantity = 2;

        $this->cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => $state,
            'billing_zip_or_postal_code' => $zip,
            'billing_country' => $country,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $state,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country
        ];

        $results = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        // Assert a message was sent to the given users...
        Mail::assertSent(
            OrderInvoice::class,
            function ($mail) {
                $mail->build();

                return $mail->hasTo(auth()->user()['email']) &&
                    $mail->hasFrom(config('ecommerce.invoice_sender')) &&
                    $mail->subject(config('ecommerce.invoicer_email_subject'));
            }
        );

        // assert a mailable was sent
        Mail::assertSent(OrderInvoice::class, 1);

        $this->cartService->refreshCart();

        // assert cart it's empty after submit
        $this->assertEmpty(
            $this->cartService->getCart()
                ->getItems()
        );
    }

    /*
    public function test_payment_plan()
    {
        $userId = $this->createAndLogInNewUser();
        // $currency = $this->defaultCurrency;
        $currency = $this->getCurrency();
        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $cardToken = $this->faker->word;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $this->setupTaxes($country, $state, $zip);

        $shippingOption = $this->fakeShippingOption([
            'country' => $country,
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 1,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $product = $this->fakeProduct([
            'price' => round($this->paymentPlanMinimumPrice * 2 , 2),
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 2.20,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productQuantity = 2;

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $paymentPlanOption = $this->getPaymentPlanOption();

        $financeCharge = 1;

        $expectedInitialProductPrice = $product['price'] * $productQuantity;

        $expectedTotalFromItems = round($expectedInitialProductPrice, 2);

        $expectedTaxes = $this->getExpectedTaxes($expectedTotalFromItems + $shippingCostAmount);

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes + $financeCharge, 2);

        $pricePerPayment = round(($expectedOrderTotalDue - $shippingCostAmount) / $paymentPlanOption, 2);

        $roundOff = $pricePerPayment * $paymentPlanOption - ($expectedOrderTotalDue - $shippingCostAmount);

        $expectedTotalPaid = round($pricePerPayment - $roundOff + $shippingCostAmount, 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService
            ->convertFromBase($expectedOrderTotalDue, $currency);

        $expectedPaymentTotalPaid = $currencyService
            ->convertFromBase($expectedTotalPaid, $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card-token' => $cardToken,
            'billing-region' => $state,
            'billing-zip-or-postal-code' => $zip,
            'billing_country' => $country,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $state,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'payment-plan-selector' => $paymentPlanOption,
            'currency' => $currency
        ];

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'type' => ConfigService::$paymentPlanType,
                'brand' => $brand,
                'user_id' => $userId,
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()
                    ->addMonth(1)
                    ->toDateTimeString(),
                'total_cycles_due' => $paymentPlanOption,
                'total_cycles_paid' => 1,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // order & based order prices
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'total_due' => round($expectedOrderTotalDue, 2),
                'product_due' => $expectedInitialProductPrice,
                'taxes_due' => round($expectedTaxes, 2),
                'shipping_due' => $shippingCostAmount,
                'finance_due' => $financeCharge,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => ConfigService::$brand,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => $expectedPaymentTotalDue,
                'total_paid' => $expectedPaymentTotalPaid,
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => 'order',
                'external_id' => $fakerCharge->id,
                'external_provider' => 'stripe',
                'status' => 'paid',
                'message' => '',
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_multiple_discounts()
    {
        $userId = $this->createAndLogInNewUser();
        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        // $currency = $this->defaultCurrency;
        $currency = $this->getCurrency();

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $this->setupTaxes($country, $state, $zip);

        $shippingOption = $this->fakeShippingOption([
            'country' => $country,
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 1,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $productOne = $this->fakeProduct([
            'price' => 147.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 79.42,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 5.10,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $discountOne = $this->fakeDiscount([
            'active' => true,
            'product_id' => $productOne['id'],
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => 20
        ]);

        $discountCriteriaOne = $this->fakeDiscountCriteria([
            'discount_id' => $discountOne['id'],
            'product_id' => $productOne['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => '1',
            'max' => '100',
        ]);

        $discountTwo = $this->fakeDiscount([
            'active' => true,
            'product_id' => $productTwo['id'],
            'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => 15
        ]);

        $discountCriteriaTwo = $this->fakeDiscountCriteria([
            'discount_id' => $discountTwo['id'],
            'product_id' => $productTwo['id'],
            'type' => DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE,
            'min' => '1',
            'max' => '100',
        ]);

        $productOneQuantity = 2;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $productOne['subscription_interval_type'],
            $productOne['subscription_interval_count'],
            [
                'product-id' => $productOne['id'],
            ]
        );

        $productTwoQuantity = 1;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $productTwo['subscription_interval_type'],
            $productTwo['subscription_interval_count'],
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $expectedProductOneTotalPrice = round($productOne['price'] * $productOneQuantity, 2);

        $expectedProductOneDiscountAmount = round($discountOne['amount'] * $productOneQuantity, 2);

        $expectedProductOneDiscountedPrice = round($expectedProductOneTotalPrice - $expectedProductOneDiscountAmount, 2);

        $expectedProductTwoTotalPrice = round($productTwo['price'] * $productTwoQuantity, 2);

        $expectedProductTwoDiscountAmount = round($discountTwo['amount'] * $productTwoQuantity, 2);

        $expectedProductTwoDiscountedPrice = round($expectedProductTwoTotalPrice - $expectedProductTwoDiscountAmount, 2);

        $expectedTotalFromItems = round($expectedProductOneDiscountedPrice + $expectedProductTwoDiscountedPrice, 2);

        $expectedTaxes = $this->getExpectedTaxes($expectedTotalFromItems + $shippingCostAmount);

        $expectedOrderTotalDue = $expectedTotalFromItems + $shippingCostAmount + $expectedTaxes;

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService
            ->convertFromBase($expectedOrderTotalDue, $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

        $cardToken = $this->faker->word;

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card-token' => $cardToken,
            'billing-region' => $state,
            'billing-zip-or-postal-code' => $zip,
            'billing_country' => $country,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $state,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'currency' => $currency
        ];

        $results = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => $shippingCostAmount,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => $expectedPaymentTotalDue,
                'total_paid' => $expectedPaymentTotalDue,
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => 'order',
                'external_id' => $fakerCharge->id,
                'external_provider' => 'stripe',
                'status' => 'paid',
                'message' => '',
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_prepare_form_order_empty_cart()
    {
        $session = $this->app->make(Store::class);
        $session->flush();
        $results = $this->call('GET', '/order');
        $this->assertEquals(404, $results->getStatusCode());
    }

    public function test_prepare_order_form()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $this->setupTaxes($country, $state, $zip);

        $sessionBillingAddress = $this->cartAddressService
                    ->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);

        $sessionShippingAddress = new Address();

        $sessionShippingAddress
            ->setCountry($country)
            ->setState($state)
            ->setZipOrPostalCode($zip);

        $this->cartAddressService->setAddress(
            $sessionShippingAddress,
            CartAddressService::SHIPPING_ADDRESS_TYPE
        );

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $productOne = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 247,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 5.10,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productOneQuantity = 2;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $productOne['subscription_interval_type'],
            $productOne['subscription_interval_count'],
            [
                'product-id' => $productOne['id'],
            ]
        );

        $productTwoQuantity = 1;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $productTwo['subscription_interval_type'],
            $productTwo['subscription_interval_count'],
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $expectedTotalFromItems = round($productOne['price'] * $productOneQuantity + $productTwo['price'] * $productTwoQuantity, 2);

        $expectedTaxes = $this->getExpectedTaxes($expectedTotalFromItems + $shippingCostAmount);

        $totalDueExpected = $expectedTotalFromItems + $shippingCostAmount + $expectedTaxes;

        $response = $this->call('GET', '/order');

        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = $response->decodeResponseJson();

        $this->assertArraySubset(
            [
                'data' => [
                    [
                        'type' => 'cartItem',
                        'attributes' => [
                            'name' => $productOne['name'],
                            'description' => $productOne['description'],
                            'quantity' => $productOneQuantity,
                            'price' => $productOne['price'],
                            'totalPrice' => $productOne['price'] * $productOneQuantity,
                            'requiresShippingAddress' => $productOne['is_physical'],
                            'requiresBillingAddress' => $productOne['is_physical'],
                            'subscriptionIntervalType' => $productOne['subscription_interval_type'],
                            'subscriptionIntervalCount' => $productOne['subscription_interval_count'],
                            'discountedPrice' => NULL,
                            'options' => [
                                'product-id' => $productOne['id'],
                            ]
                        ]
                    ],
                    [
                        'type' => 'cartItem',
                        'attributes' => [
                            'name' => $productTwo['name'],
                            'description' => $productTwo['description'],
                            'quantity' => $productTwoQuantity,
                            'price' => $productTwo['price'],
                            'totalPrice' => $productTwo['price'] * $productTwoQuantity,
                            'requiresShippingAddress' => $productTwo['is_physical'],
                            'requiresBillingAddress' => $productTwo['is_physical'],
                            'subscriptionIntervalType' => $productTwo['subscription_interval_type'],
                            'subscriptionIntervalCount' => $productTwo['subscription_interval_count'],
                            'discountedPrice' => NULL,
                            'options' => [
                                'product-id' => $productTwo['id'],
                            ]
                        ]
                    ]
                ],
                'meta' => [
                    'billingAddress' => $sessionBillingAddress->toArray(),
                    'shippingAddress' => $sessionShippingAddress->toArray()
                ]
            ],
            $decodedResponse
        );

        $this->assertTrue(isset($decodedResponse['meta']['paymentPlansPricing']));

        $this->assertTrue(is_array($decodedResponse['meta']['paymentPlansPricing']));

        $this->assertTrue(isset($decodedResponse['meta']['totalDue']));

        $this->assertEquals(
            $decodedResponse['meta']['totalDue'],
            $totalDueExpected
        );
    }

    public function test_order_with_promo_code()
    {
        $userId = $this->createAndLogInNewUser();
        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $this->setupTaxes($country, $state, $zip);

        $product = $this->fakeProduct([
            'price' => 142.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $promoCode = $this->faker->word;

        $discount = $this->fakeDiscount([
            'active' => true,
            'product_id' => $product['id'],
            'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => 10,
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'discount_id' => $discount['id'],
            'product_id' => $product['id'],
            'type' => DiscountCriteriaService::PROMO_CODE_REQUIREMENT_TYPE,
            'min' => $promoCode,
            'max' => $promoCode,
        ]);

        $productQuantity = 2;

        $this->cartService->setPromoCode($promoCode);

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $expectedTotalFromItems = $product['price'] * $productQuantity;
        $expectedTaxes = $this->getExpectedTaxes($expectedTotalFromItems);
        $expectedOrderTotalDue = $expectedTotalFromItems + $expectedTaxes - $discount['amount'];

        $cardToken = $this->faker->word;

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card-token' => $cardToken,
            'billing-region' => $state,
            'billing-zip-or-postal-code' => $zip,
            'billing_country' => $country,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $state,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country
        ];

        $results = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => 0,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );
    }

    public function test_user_products_updated_after_order_submit()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $this->setupTaxes($country, $state, $zip);

        $product = $this->fakeProduct([
            'price' => 142.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $existingUserProduct = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'quantity' => 1,
        ]);

        $productQuantity = 2;

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $expectedTotalFromItems = round($product['price'] * $productQuantity, 2);
        $expectedTaxes = round($this->getExpectedTaxes($expectedTotalFromItems), 2);
        $expectedOrderTotalDue = round($expectedTotalFromItems + $expectedTaxes, 2);

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'card-token' => $cardToken,
                'billing-region' => $state,
                'billing-zip-or-postal-code' => $zip,
                'billing_country' => $country,
                'gateway' => $brand,
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => 0,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        // assert new quantity is added to exiting
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => $existingUserProduct['quantity'] + $productQuantity,
                'expiration_date' => null,
            ]
        );
    }

    public function test_submit_order_with_discount_product_category()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $this->setupTaxes($country, $state, $zip);

        $productOne = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwoCategory = $this->faker->word;

        $productTwo = $this->fakeProduct([
            'price' => 24,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'category' => $productTwoCategory,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $discount = $this->fakeDiscount([
            'active' => true,
            'product_id' => $productOne['id'],
            'product_category' => $productTwoCategory,
            'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
            'amount' => 10,
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'discount_id' => $discount['id'],
            'product_id' => $productOne['id'],
            'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min' => 5,
            'max' => 500,
        ]);

        $productOneQuantity = 2;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $productOne['subscription_interval_type'],
            $productOne['subscription_interval_count'],
            [
                'product-id' => $productOne['id'],
            ]
        );

        $productTwoQuantity = 3;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $productTwo['subscription_interval_type'],
            $productTwo['subscription_interval_count'],
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $expectedProductOneTotalPrice = round($productOne['price'] * $productOneQuantity, 2);

        $expectedProductOneDiscountAmount = round($discount['amount'] / 100 * $productOne['price'] * $productOneQuantity, 2);

        $expectedProductOneDiscountedPrice = round($expectedProductOneTotalPrice - $expectedProductOneDiscountAmount, 2);

        $expectedProductTwoTotalPrice = round($productTwo['price'] * $productTwoQuantity, 2);

        $expectedProductTwoDiscountAmount = round($discount['amount'] / 100 * $productTwo['price'] * $productTwoQuantity, 2);

        $expectedProductTwoDiscountedPrice = round($expectedProductTwoTotalPrice - $expectedProductTwoDiscountAmount, 2);

        $expectedTotalFromItems = round($expectedProductOneDiscountedPrice + $expectedProductTwoDiscountedPrice, 2);

        $expectedTaxes = $this->getExpectedTaxes($expectedTotalFromItems);

        $expectedOrderTotalDue = round($expectedTotalFromItems + $expectedTaxes, 2);

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'card-token' => $cardToken,
                'billing-region' => $state,
                'billing-zip-or-postal-code' => $zip,
                'billing_country' => $country,
                'gateway' => $brand,
                'shipping_first_name' => $this->faker->firstName,
                'shipping_last_name' => $this->faker->lastName,
                'shipping_address_line_1' => $this->faker->address,
                'shipping_city' => $this->faker->city,
                'shipping_region' => $state,
                'shipping_zip_or_postal_code' => $this->faker->postcode,
                'shipping_country' => $country,
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => 0,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );
    }

    public function test_admin_submit_subscription_for_other_user()
    {
        $this->permissionServiceMock->method('can')
            ->willReturn(true);

        $randomUser = $this->fakeUser();

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $this->setupTaxes($country, $state, $zip);

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product = $this->fakeProduct([
            'price' => 142.95,
            'type' => ConfigService::$typeSubscription,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $productQuantity = 2;

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $totalProductPrice = round($product['price'] * $productQuantity, 2);

        $expectedTaxes = $this->getExpectedTaxes($totalProductPrice);

        $expectedOrderTotalDue = round($totalProductPrice + $expectedTaxes, 2);

        $orderData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card-token' => $cardToken,
            'billing-region' => $state,
            'billing-zip-or-postal-code' => $zip,
            'billing_country' => $country,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $state,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'user_id' => $randomUser['id'],
            'brand' => $brand
        ];

        $response = $this->call(
            'PUT',
            '/order',
            $orderData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => $brand,
                'user_id' => $randomUser['id'],
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => 0,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $randomUser['id'],
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'type' => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand' => ConfigService::$brand,
                'user_id' => $randomUser['id'],
                'customer_id' => null,
                'zip' => $orderData['billing-zip-or-postal-code'],
                'state' => $orderData['billing-region'],
                'country' => $orderData['billing_country'],
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $randomUser['id'],
                'product_id' => $product['id'],
                'quantity' => $productQuantity,
                'expiration_date' => Carbon::now()->addYear(1)->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'type' => ConfigService::$typeSubscription,
                'brand' => $brand,
                'user_id' => $randomUser['id'],
                'is_active' => 1,
                'product_id' => $product['id'],
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString(),
                'total_cycles_paid' => 1,
                'interval_type' => $product['subscription_interval_type'],
                'interval_count' => $product['subscription_interval_count'],
                'total_price' => $product['price'],
                'canceled_on' => null
            ]
        );
    }

    public function test_admin_submit_product_for_other_user()
    {
        $this->permissionServiceMock->method('can')
            ->willReturn(true);

        $randomUser = $this->fakeUser();

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $this->setupTaxes($country, $state, $zip);

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product = $this->fakeProduct([
            'price' => 142.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $productQuantity = 2;

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $totalProductPrice = round($product['price'] * $productQuantity, 2);

        $expectedTaxes = $this->getExpectedTaxes($totalProductPrice);

        $expectedOrderTotalDue = round($totalProductPrice + $expectedTaxes, 2);

        $orderData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card-token' => $cardToken,
            'billing-region' => $state,
            'billing-zip-or-postal-code' => $zip,
            'billing_country' => $country,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $state,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'user_id' => $randomUser['id'],
        ];

        $results = $this->call(
            'PUT',
            '/order',
            $orderData
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => $brand,
                'user_id' => $randomUser['id'],
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => 0,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $randomUser['id'],
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'type' => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand' => ConfigService::$brand,
                'user_id' => $randomUser['id'],
                'customer_id' => null,
                'zip' => $orderData['billing-zip-or-postal-code'],
                'state' => $orderData['billing-region'],
                'country' => $orderData['billing_country'],
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $randomUser['id'],
                'product_id' => $product['id'],
                'quantity' => $productQuantity,
                'expiration_date' => null,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_admin_submit_order_on_different_branch()
    {
        $this->permissionServiceMock->method('can')
            ->willReturn(true);

        $randomUser = $this->fakeUser();

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $this->setupTaxes($country, $state, $zip);

        $brand = $this->faker->word;

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();
        $fakerCustomer->id = $this->faker->word . rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product = $this->fakeProduct([
            'price' => 142.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $productQuantity = 2;

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ],
            $brand
        );

        $totalProductPrice = round($product['price'] * $productQuantity, 2);

        $expectedTaxes = $this->getExpectedTaxes($totalProductPrice);

        $expectedOrderTotalDue = round($totalProductPrice + $expectedTaxes, 2);

        $orderData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card-token' => $cardToken,
            'billing-region' => $state,
            'billing-zip-or-postal-code' => $zip,
            'billing_country' => $country,
            'gateway' => $brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $state,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'user_id' => $randomUser['id'],
            'brand' => $brand
        ];

        ConfigService::$paymentGateways['stripe'][$brand] = [
            'stripe_api_secret' => $this->faker->word
        ];

        $response = $this->call(
            'PUT',
            '/order',
            $orderData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => $brand,
                'user_id' => $randomUser['id'],
                'total_due' => $expectedOrderTotalDue,
                'taxes_due' => $expectedTaxes,
                'shipping_due' => 0,
                'total_paid' => $expectedOrderTotalDue,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $randomUser['id'],
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'type' => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand' => $brand,
                'user_id' => $randomUser['id'],
                'customer_id' => null,
                'zip' => $orderData['billing-zip-or-postal-code'],
                'state' => $orderData['billing-region'],
                'country' => $orderData['billing_country'],
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $randomUser['id'],
                'product_id' => $product['id'],
                'quantity' => $productQuantity,
                'expiration_date' => null,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }
    */
}
