<?php

use Carbon\Carbon;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\AppleStoreKitService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use ReceiptValidator\iTunes\SandboxResponse;

class AppleStoreKitServiceTest extends EcommerceTestCase
{
    /**
     * @var Store
     */
    protected $session;

    protected function setUp()
    {
        parent::setUp();
    }

    public function test_process_subscription_renewal_success()
    {
        $em = $this->app->make(EcommerceEntityManager::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $country = 'Canada';
        $region = 'alberta';
        $brand = 'drumeo';

        $currency = $this->getCurrency();

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product->setBrand($this->faker->word);
        $product->setName($this->faker->word);
        $product->setSku($this->faker->word);
        $product->setPrice($this->faker->randomNumber(4));
        $product->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $product->setActive(true);
        $product->setIsPhysical(false);
        $product->setPrice(123.58);
        $product->setAutoDecrementStock(false);
        $product->setSubscriptionIntervalType(config('ecommerce.interval_type_yearly'));
        $product->setSubscriptionIntervalCount(1);

        $em->persist($product);
        $em->flush();

        $subscription = new Subscription();

        $subscription->setBrand($brand);
        $subscription->setType(Subscription::TYPE_APPLE_SUBSCRIPTION);
        $subscription->setIsActive(true);
        $subscription->setProduct($product);
        $subscription->setUser($user);
        $subscription->setStartDate(Carbon::now());
        $subscription->setPaidUntil(Carbon::now()->subDay(1));
        $subscription->setTotalPrice(round($product->getPrice(), 2));
        $subscription->setTax(0);
        $subscription->setCurrency($currency);
        $subscription->setIntervalType($product->getSubscriptionIntervalType());
        $subscription->setIntervalCount(1);
        $subscription->setTotalCyclesPaid($this->faker->randomNumber(3));
        $subscription->setCreatedAt(Carbon::now());
        $subscription->setUpdatedAt(Carbon::now());

        $em->persist($subscription);

        $em->flush();

        $appleReceipt = new AppleReceipt();

        $receipt = $this->faker->word;
        $transactionId = $this->faker->randomNumber(4);

        $appleReceipt->setReceipt($receipt);
        $appleReceipt->setTransactionId($transactionId);
        $appleReceipt->setRequestType(AppleReceipt::MOBILE_APP_REQUEST_TYPE);
        $appleReceipt->setEmail($email);
        $appleReceipt->setBrand($brand);
        $appleReceipt->setValid(true);
        $appleReceipt->setSubscription($subscription);

        $em->persist($appleReceipt);

        $subscription->setAppleReceipt($appleReceipt);

        $em->flush();

        $webOrderLineItemOneId = $this->faker->word;

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $product->getSku(),
            ]
        );

        $productsData = [
            $product->getSku() => [
                'web_order_line_item_id' => $webOrderLineItemOneId,
            ],
        ];

        $validationResponse = $this->getReceiptValidationResponse($productsData);

        $this->appleStoreKitGatewayMock->method('getResponse')
            ->willReturn($validationResponse);

        $appleStoreKitService = $this->app->make(AppleStoreKitService::class);

        $appleStoreKitService->processSubscriptionRenewal($subscription);

        $subscriptionsRepository = $this->app->make(SubscriptionRepository::class);

        $refreshedSubscription = $subscriptionsRepository->findOneBy(['id' => $subscription->getId()]);

        $this->assertTrue($refreshedSubscription->getIsActive());
        $this->assertNull($refreshedSubscription->getCanceledOn());
        $this->assertEquals(
            Carbon::now()
                ->addYears($subscription->getIntervalCount())
                ->startOfDay(),
            $refreshedSubscription->getPaidUntil()
        );
    }

    public function test_process_subscription_renewal_cancelation()
    {
        $em = $this->app->make(EcommerceEntityManager::class);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $country = 'Canada';
        $region = 'alberta';
        $brand = 'drumeo';

        $currency = $this->getCurrency();

        $user = $userProvider->createUser($email, $password);

        $product = new Product();

        $product->setBrand($this->faker->word);
        $product->setName($this->faker->word);
        $product->setSku($this->faker->word);
        $product->setPrice($this->faker->randomNumber(4));
        $product->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $product->setActive(true);
        $product->setIsPhysical(false);
        $product->setPrice(123.58);
        $product->setAutoDecrementStock(false);
        $product->setSubscriptionIntervalType(config('ecommerce.interval_type_yearly'));
        $product->setSubscriptionIntervalCount(1);

        $em->persist($product);
        $em->flush();

        $subscription = new Subscription();

        $paidUntil = Carbon::now()->subDay(1);

        $subscription->setBrand($brand);
        $subscription->setType(Subscription::TYPE_APPLE_SUBSCRIPTION);
        $subscription->setIsActive(true);
        $subscription->setProduct($product);
        $subscription->setUser($user);
        $subscription->setStartDate(Carbon::now());
        $subscription->setPaidUntil($paidUntil);
        $subscription->setTotalPrice(round($product->getPrice(), 2));
        $subscription->setTax(0);
        $subscription->setCurrency($currency);
        $subscription->setIntervalType($product->getSubscriptionIntervalType());
        $subscription->setIntervalCount(1);
        $subscription->setTotalCyclesPaid($this->faker->randomNumber(3));
        $subscription->setCreatedAt(Carbon::now());
        $subscription->setUpdatedAt(Carbon::now());

        $em->persist($subscription);

        $em->flush();

        $appleReceipt = new AppleReceipt();

        $receipt = $this->faker->word;
        $transactionId = $this->faker->randomNumber(4);

        $appleReceipt->setReceipt($receipt);
        $appleReceipt->setTransactionId($transactionId);
        $appleReceipt->setRequestType(AppleReceipt::MOBILE_APP_REQUEST_TYPE);
        $appleReceipt->setEmail($email);
        $appleReceipt->setBrand($brand);
        $appleReceipt->setValid(true);
        $appleReceipt->setSubscription($subscription);

        $em->persist($appleReceipt);

        $subscription->setAppleReceipt($appleReceipt);

        $em->flush();

        $webOrderLineItemOneId = $this->faker->word;

        config()->set(
            'ecommerce.apple_store_products_map',
            [
                $this->faker->word => $product->getSku(),
                'drumeo_app_1_year_member' => $product->getSku(),
            ]
        );

        $productsData = [
            $product->getSku() => [
                'web_order_line_item_id' => $webOrderLineItemOneId,
                'expires_date_ms' => Carbon::now()->subMonth()
            ],
        ];

        $validationResponse = $this->getReceiptValidationResponse($productsData, Carbon::now()->subMonth());

        $this->appleStoreKitGatewayMock->method('getResponse')
            ->willReturn($validationResponse);

        $appleStoreKitService = $this->app->make(AppleStoreKitService::class);

        $appleStoreKitService->processSubscriptionRenewal($subscription);

        $subscriptionsRepository = $this->app->make(SubscriptionRepository::class);

        $refreshedSubscription = $subscriptionsRepository->findOneBy(['id' => $subscription->getId()]);

        $this->assertNotNull($refreshedSubscription->getCanceledOn());
        $this->assertEquals(Carbon::now(), $refreshedSubscription->getCanceledOn());
        $this->assertEquals(
            $paidUntil,
            $refreshedSubscription->getPaidUntil()
        );
    }

    protected function getReceiptValidationResponse(
        $productsData,
        $receiptCreationDate = null,
        $receiptStatus = 0,
        $transactionId = null
    )
    {
        /*
        // $productsData structure example
        $productsData = [
            $someProduct->getSku() => [
                'quantity' => 1,
                'expires_date_ms' => Carbon::now()->addMonth(),
                'web_order_line_item_id' => $this->faker->word,
                'product_id' => key of config('ecommerce.apple_store_products_map'),
            ]
        ];
        */

        $appleProductsMap = array_flip(config('ecommerce.apple_store_products_map'));

        if (!$receiptCreationDate) {
            $receiptCreationDate = Carbon::now();
        }

        if (!$transactionId) {
            $transactionId = $this->faker->word;
        }

        $rawData = [
            'status' => $receiptStatus,
            'environment' => 'Sandbox',
            'receipt' => [
                'receipt_type' => 'ProductionSandbox',
                'app_item_id' => 0,
                'receipt_creation_date_ms' => $receiptCreationDate->tz('UTC')->getTimestamp() * 1000,
                'in_app' => []
            ],
            'latest_receipt_info' => [
            ]
        ];

        $defaultItemData = [
            'quantity' => 1,
            'expires_date_ms' => $receiptCreationDate->copy()->addMonth(),
            'web_order_line_item_id' => $this->faker->word,
            'transaction_id' => $transactionId,
            'purchase_date_ms' => $receiptCreationDate->tz('UTC')->getTimestamp() * 1000,
        ];

        foreach ($productsData as $productSku => $purchaseItemData) {

            $purchaseItemData = array_merge($defaultItemData, $purchaseItemData);

            $purchaseItemData['product_id'] = $appleProductsMap[$productSku];
            $purchaseItemData['expires_date_ms'] = $purchaseItemData['expires_date_ms']->tz('UTC')->getTimestamp() * 1000;

            $rawData['receipt']['in_app'][] = $purchaseItemData;
            $rawData['latest_receipt_info'][] = $purchaseItemData;
        }

        return new SandboxResponse($rawData);
    }
}