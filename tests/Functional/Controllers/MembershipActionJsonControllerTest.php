<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class MembershipActionJsonControllerTest extends EcommerceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_pull()
    {
        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);

        $subscription = $this->fakeSubscription(
            [
                'user_id' => $userId,
                'payment_method_id' => null,
                'product_id' => null,
            ]
        );

        $membershipAction = $this->fakeMembershipAction(
            [
                'user_id' => $userId,
                'subscription_id' => $subscription['id'],
            ]
        );

        $response = $this->call(
            'GET',
            '/membership-action',
            []
        );

        $this->assertEquals(200, $response->getStatusCode());

        // soft deleted address will not be returned in response
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(
            [
                [
                    'type' => 'membershipAction',
                    'id' => $membershipAction['id'],
                    'attributes' => array_merge(
                        array_diff_key(
                            $membershipAction,
                            [
                                'id' => true,
                                'user_id' => true,
                                'subscription_id' => true,
                            ]
                        )
                    ),
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'subscription' => [
                            'data' => [
                                'type' => 'subscription',
                                'id' => $subscription['id']
                            ]
                        ]
                    ]
                ],
            ],
            $decodedResponse['data']
        );

        $subscription['state'] = Subscription::STATE_ACTIVE;

        $this->assertEquals(
            [
                [
                    'type' => 'user',
                    'id' => $userId,
                    'attributes' => [
                        'email' => $userEmail
                    ]
                ],
                [
                    'type' => 'subscription',
                    'id' => $subscription['id'],
                    'attributes' => array_diff_key(
                        $subscription,
                        [
                            'id' => true,
                            'product_id' => true,
                            'payment_method_id' => true,
                            'user_id' => true,
                            'order_id' => true,
                            'customer_id' => true,
                        ]
                    ),
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                    ]
                ],
            ],
            $decodedResponse['included']
        );
    }
}
