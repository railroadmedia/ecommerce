<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class AppleStoreKitControllerTest extends EcommerceTestCase
{
    use WithoutMiddleware;

    /**
     * @var MockObject|AuthManager
     */
    protected $authManagerMock;

    /**
     * @var MockObject|SessionGuard
     */
    protected $sessionGuardMock;

    protected function setUp()
    {
        parent::setUp();
    }

    public function test_process_receipt_validation()
    {
        $response = $this->call('POST', '/apple/verify-receipt-and-process-payment', []);

        //assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals([
            [
                'title' => 'Validation failed.',
                'source' => 'data.attributes.receipt',
                'detail' => 'The receipt field is required.',
            ],
            [
                'title' => 'Validation failed.',
                'source' => 'data.attributes.email',
                'detail' => 'The email field is required.',
            ],
            [
                'title' => 'Validation failed.',
                'source' => 'data.attributes.password',
                'detail' => 'The password field is required.',
            ]
        ], $response->decodeResponseJson('errors'));
    }

    public function test_process_receipt()
    {
        $this->assertTrue(true);

        $receipt = $this->faker->word;
        $email = $this->faker->email;

        $this->authManagerMock =
            $this->getMockBuilder(AuthManager::class)
                ->disableOriginalConstructor()
                ->setMethods(['guard'])
                ->getMock();

        $this->sessionGuardMock =
            $this->getMockBuilder(SessionGuard::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->authManagerMock->method('guard')
            ->willReturn($this->sessionGuardMock);

        $this->app->instance(Factory::class, $this->authManagerMock);

        $this->sessionGuardMock->method('loginUsingId')
            ->willReturn(true);

        $response = $this->call(
            'POST',
            '/apple/verify-receipt-and-process-payment',
            [
                'data' => [
                    'attributes' => [
                        'receipt' => $this->faker->word,
                        'email' => $email,
                        'password' => $this->faker->word,
                    ]
                ]
            ]
        );

        // assert the response status code
        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = $response->decodeResponseJson();

        // assert response has meta key with auth code
        $this->assertTrue(isset($decodedResponse['meta']['auth_code']));

        // add db asserts for data created on request processing
    }
}
