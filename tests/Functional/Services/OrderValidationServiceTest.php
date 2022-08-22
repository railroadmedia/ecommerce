<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address as AddressStructure;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Services\OrderValidationService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Token;

class OrderValidationServiceTest extends EcommerceTestCase
{
    /**
     * @var OrderValidationService
     */
    protected $orderValidationService;

    /**
     * @var TaxService
     */
    protected $taxService;

    /**
     * @var CartService
     */
    protected $cartService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderValidationService = app()->make(OrderValidationService::class);
        $this->taxService = app()->make(TaxService::class);
        $this->cartService = app()->make(CartService::class);
    }

    public function test_rejected_due_to_recent_trial_order()
    {
        $brand = 'drumeo';
        $country = 'canada';
        $region = 'alberta';
        $currency = 'USD';

        $userId = $this->createAndLogInNewUser(); // current user

        $user = $this->fakeUser(['email' => $this->faker->email]); // order placed for

        $trialProductPrice = $this->faker->randomFloat(2, 15, 20);

        $trialProduct = new Product();

        $trialProduct->setBrand($brand);
        $trialProduct->setName($this->faker->word);
        $trialProduct->setSku('trial-' . $this->faker->word . rand());
        $trialProduct->setPrice($trialProductPrice);
        $trialProduct->setCategory($this->faker->word . $this->faker->word);
        $trialProduct->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $trialProduct->setSubscriptionIntervalType(Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_MONTH);
        $trialProduct->setSubscriptionIntervalCount(1);
        $trialProduct->setDigitalAccessType(Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS);
        $trialProduct->setDigitalAccessTimeType(Product::DIGITAL_ACCESS_TIME_TYPE_RECURRING);
        $trialProduct->setDigitalAccessTimeIntervalType(Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_MONTH);
        $trialProduct->setDigitalAccessTimeIntervalLength(1);
        $trialProduct->setActive(true);
        $trialProduct->setIsPhysical(false);
        $trialProduct->setWeight($this->faker->randomFloat(2, 15, 20));
        $trialProduct->setStock(50);
        $trialProduct->setAutoDecrementStock(false);
        $trialProduct->setCreatedAt(Carbon::now());

        $this->entityManager->persist($trialProduct);

        $this->entityManager->flush();

        $trialDiscount = new Discount();

        $trialDiscount->setName($this->faker->word);
        $trialDiscount->setDescription($this->faker->word);
        $trialDiscount->setType(DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE);
        $trialDiscount->setAmount(14);
        $trialDiscount->setActive(true);
        $trialDiscount->setVisible(true);
        $trialDiscount->setProduct($trialProduct);
        $trialDiscount->setCreatedAt(Carbon::now());

        $trialDiscountCriteria = new DiscountCriteria();

        $trialDiscountCriteria->setName($this->faker->word);
        $trialDiscountCriteria->setType(DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE);
        $trialDiscountCriteria->addProduct($trialProduct);
        $trialDiscountCriteria->setProductsRelationType(DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY);
        $trialDiscountCriteria->setMin(1);
        $trialDiscountCriteria->setMax(1);
        $trialDiscountCriteria->setDiscount($trialDiscount);
        $trialDiscountCriteria->setCreatedAt(Carbon::now());

        $trialDiscount->addDiscountCriteria($trialDiscountCriteria);

        $this->entityManager->persist($trialDiscount);
        $this->entityManager->persist($trialDiscountCriteria);

        $this->entityManager->flush();

        $purchaser = new Purchaser();

        $purchaser->setId($user['id']);
        $purchaser->setEmail($user['email']);
        $purchaser->setBrand($brand);
        $purchaser->setType(Purchaser::USER_TYPE);

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);
        $billingAddress->setType(Address::BILLING_ADDRESS_TYPE);

        $billingAddressStructure = new AddressStructure();
        $billingAddressStructure->setCountry($country);
        $billingAddressStructure->setRegion($region);

        $brand = 'drumeo';
        config()->set('ecommerce.brand', $brand);
        $currency = $this->getCurrency();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer($this->faker->word . rand());

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer, $fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card($this->faker->word);
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->name = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard, $fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge, $fakerCharge);

        $productQuantity = 1;

        $this->cartService->addToCart(
            $trialProduct->getSku(),
            $productQuantity
        );

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken, $fakerToken);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => 'Alberta',
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => 'Canada',
            'gateway' => $brand,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        // place another test order for the same trial product, make sure it gets rejected
        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn($fakerCustomer);

        $productQuantity = 1;

        $this->cartService->addToCart(
            $trialProduct->getSku(),
            $productQuantity
        );

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => 'Alberta',
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => 'Canada',
            'gateway' => $brand,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(
            '{"errors":[{"title":"Payment failed.","detail":"This account is not eligible for a free trial period. Please choose a regular membership."}]}',
            $response->getContent()
        );
    }

    public function test_accepted_due_to_past_time_limit()
    {
        $originalNow = Carbon::now();

        Carbon::setTestNow(Carbon::now()->subYear());

        $brand = 'drumeo';
        $country = 'canada';
        $region = 'alberta';
        $currency = 'USD';

        $userId = $this->createAndLogInNewUser(); // current user

        $user = $this->fakeUser(['email' => $this->faker->email]); // order placed for

        $trialProductPrice = $this->faker->randomFloat(2, 15, 20);

        $trialProduct = new Product();

        $trialProduct->setBrand($brand);
        $trialProduct->setName($this->faker->word);
        $trialProduct->setSku('trial-' . $this->faker->word . rand());
        $trialProduct->setPrice($trialProductPrice);
        $trialProduct->setCategory($this->faker->word . $this->faker->word);
        $trialProduct->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $trialProduct->setSubscriptionIntervalType(Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_MONTH);
        $trialProduct->setSubscriptionIntervalCount(1);
        $trialProduct->setDigitalAccessType(Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS);
        $trialProduct->setDigitalAccessTimeType(Product::DIGITAL_ACCESS_TIME_TYPE_RECURRING);
        $trialProduct->setDigitalAccessTimeIntervalType(Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_MONTH);
        $trialProduct->setDigitalAccessTimeIntervalLength(1);
        $trialProduct->setActive(true);
        $trialProduct->setIsPhysical(false);
        $trialProduct->setWeight($this->faker->randomFloat(2, 15, 20));
        $trialProduct->setStock(50);
        $trialProduct->setAutoDecrementStock(false);
        $trialProduct->setCreatedAt(Carbon::now());

        $this->entityManager->persist($trialProduct);

        $this->entityManager->flush();

        $trialDiscount = new Discount();

        $trialDiscount->setName($this->faker->word);
        $trialDiscount->setDescription($this->faker->word);
        $trialDiscount->setType(DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE);
        $trialDiscount->setAmount(14);
        $trialDiscount->setActive(true);
        $trialDiscount->setVisible(true);
        $trialDiscount->setProduct($trialProduct);
        $trialDiscount->setCreatedAt(Carbon::now());

        $trialDiscountCriteria = new DiscountCriteria();

        $trialDiscountCriteria->setName($this->faker->word);
        $trialDiscountCriteria->setType(DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE);
        $trialDiscountCriteria->addProduct($trialProduct);
        $trialDiscountCriteria->setProductsRelationType(DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY);
        $trialDiscountCriteria->setMin(1);
        $trialDiscountCriteria->setMax(1);
        $trialDiscountCriteria->setDiscount($trialDiscount);
        $trialDiscountCriteria->setCreatedAt(Carbon::now());

        $trialDiscount->addDiscountCriteria($trialDiscountCriteria);

        $this->entityManager->persist($trialDiscount);
        $this->entityManager->persist($trialDiscountCriteria);

        $this->entityManager->flush();

        $purchaser = new Purchaser();

        $purchaser->setId($user['id']);
        $purchaser->setEmail($user['email']);
        $purchaser->setBrand($brand);
        $purchaser->setType(Purchaser::USER_TYPE);

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);
        $billingAddress->setType(Address::BILLING_ADDRESS_TYPE);

        $billingAddressStructure = new AddressStructure();
        $billingAddressStructure->setCountry($country);
        $billingAddressStructure->setRegion($region);

        $brand = 'drumeo';
        config()->set('ecommerce.brand', $brand);
        $currency = $this->getCurrency();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer($this->faker->word . rand());

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer, $fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card($this->faker->word);
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->name = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard, $fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge, $fakerCharge);

        $productQuantity = 1;

        $this->cartService->addToCart(
            $trialProduct->getSku(),
            $productQuantity
        );

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken, $fakerToken);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => 'Alberta',
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => 'Canada',
            'gateway' => $brand,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        // place another test order for the same trial product, make sure it gets rejected
        Carbon::setTestNow($originalNow);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn($fakerCustomer);

        $productQuantity = 1;

        $this->cartService->addToCart(
            $trialProduct->getSku(),
            $productQuantity
        );

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => 'Alberta',
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => 'Canada',
            'gateway' => $brand,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_can_still_order_paid_membership_products()
    {
        $brand = 'drumeo';
        $country = 'canada';
        $region = 'alberta';
        $currency = 'USD';

        $userId = $this->createAndLogInNewUser(); // current user

        $user = $this->fakeUser(['email' => $this->faker->email]); // order placed for

        $trialProductPrice = $this->faker->randomFloat(2, 15, 20);

        $trialProduct = new Product();

        $trialProduct->setBrand($brand);
        $trialProduct->setName($this->faker->word);
        $trialProduct->setSku('trial-' . $this->faker->word . rand());
        $trialProduct->setPrice($trialProductPrice);
        $trialProduct->setCategory($this->faker->word . $this->faker->word);
        $trialProduct->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $trialProduct->setSubscriptionIntervalType(Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_MONTH);
        $trialProduct->setSubscriptionIntervalCount(1);
        $trialProduct->setDigitalAccessType(Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS);
        $trialProduct->setDigitalAccessTimeType(Product::DIGITAL_ACCESS_TIME_TYPE_RECURRING);
        $trialProduct->setDigitalAccessTimeIntervalType(Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_MONTH);
        $trialProduct->setDigitalAccessTimeIntervalLength(1);
        $trialProduct->setActive(true);
        $trialProduct->setIsPhysical(false);
        $trialProduct->setWeight($this->faker->randomFloat(2, 15, 20));
        $trialProduct->setStock(50);
        $trialProduct->setAutoDecrementStock(false);
        $trialProduct->setCreatedAt(Carbon::now());

        $this->entityManager->persist($trialProduct);

        $this->entityManager->flush();

        $trialDiscount = new Discount();

        $trialDiscount->setName($this->faker->word);
        $trialDiscount->setDescription($this->faker->word);
        $trialDiscount->setType(DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE);
        $trialDiscount->setAmount(14);
        $trialDiscount->setActive(true);
        $trialDiscount->setVisible(true);
        $trialDiscount->setProduct($trialProduct);
        $trialDiscount->setCreatedAt(Carbon::now());

        $trialDiscountCriteria = new DiscountCriteria();

        $trialDiscountCriteria->setName($this->faker->word);
        $trialDiscountCriteria->setType(DiscountCriteriaService::PRODUCT_QUANTITY_REQUIREMENT_TYPE);
        $trialDiscountCriteria->addProduct($trialProduct);
        $trialDiscountCriteria->setProductsRelationType(DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY);
        $trialDiscountCriteria->setMin(1);
        $trialDiscountCriteria->setMax(1);
        $trialDiscountCriteria->setDiscount($trialDiscount);
        $trialDiscountCriteria->setCreatedAt(Carbon::now());

        $trialDiscount->addDiscountCriteria($trialDiscountCriteria);

        $this->entityManager->persist($trialDiscount);
        $this->entityManager->persist($trialDiscountCriteria);

        $this->entityManager->flush();

        $purchaser = new Purchaser();

        $purchaser->setId($user['id']);
        $purchaser->setEmail($user['email']);
        $purchaser->setBrand($brand);
        $purchaser->setType(Purchaser::USER_TYPE);

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);
        $billingAddress->setType(Address::BILLING_ADDRESS_TYPE);

        $billingAddressStructure = new AddressStructure();
        $billingAddressStructure->setCountry($country);
        $billingAddressStructure->setRegion($region);

        $brand = 'drumeo';
        config()->set('ecommerce.brand', $brand);
        $currency = $this->getCurrency();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer($this->faker->word . rand());

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer, $fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card($this->faker->word);
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->name = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard, $fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge, $fakerCharge);

        $productQuantity = 1;

        $this->cartService->addToCart(
            $trialProduct->getSku(),
            $productQuantity
        );

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken, $fakerToken);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => 'Alberta',
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => 'Canada',
            'gateway' => $brand,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        // place another test order for the same trial product, make sure it gets rejected
        $membershipProduct = new Product();

        $membershipProduct->setBrand($brand);
        $membershipProduct->setName($this->faker->word);
        $membershipProduct->setSku('membership-' . $this->faker->word . rand());
        $membershipProduct->setPrice($trialProductPrice);
        $membershipProduct->setCategory($this->faker->word . $this->faker->word);
        $membershipProduct->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $membershipProduct->setSubscriptionIntervalType(Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_MONTH);
        $membershipProduct->setSubscriptionIntervalCount(1);
        $membershipProduct->setDigitalAccessType(Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS);
        $membershipProduct->setDigitalAccessTimeType(Product::DIGITAL_ACCESS_TIME_TYPE_RECURRING);
        $membershipProduct->setDigitalAccessTimeIntervalType(Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_MONTH);
        $membershipProduct->setDigitalAccessTimeIntervalLength(1);
        $membershipProduct->setActive(true);
        $membershipProduct->setIsPhysical(false);
        $membershipProduct->setWeight($this->faker->randomFloat(2, 15, 20));
        $membershipProduct->setStock(50);
        $membershipProduct->setAutoDecrementStock(false);
        $membershipProduct->setCreatedAt(Carbon::now());

        $this->entityManager->persist($membershipProduct);
        $this->entityManager->flush();

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn($fakerCustomer);

        $productQuantity = 1;

        $this->cartService->addToCart(
            $membershipProduct->getSku(),
            $productQuantity
        );

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $cardToken,
            'billing_region' => 'Alberta',
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => 'Canada',
            'gateway' => $brand,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

}
