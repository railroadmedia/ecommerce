<?php

return [
    'table_names' => [
        'address.update' => 'ecommerce_address',
        'address.delete' => 'ecommerce_address',
        'payment-method.update' => [
            'user_id' => 'ecommerce_user_payment_methods',
            'customer_id' => 'ecommerce_customer_payment_methods'
        ],
        'payment-method.delete' => [
            'user_id' => 'ecommerce_user_payment_methods',
            'customer_id' => 'ecommerce_customer_payment_methods'
        ],
        'payment.store' => [
            'user_id' => 'ecommerce_user_payment_methods',
            'customer_id' => 'ecommerce_customer_payment_methods'
        ],
        'refund.store' => 'ecommerce_payment'
    ],
    'column_names' => [
        'payment-method.update' => 'payment_method_id',
        'payment-method.delete' => 'payment_method_id',
        'payment.store' => 'payment_method_id'
    ],
    'additional_join_for_owner' => [
        'refund.store' => [
            'table' =>'ecommerce_user_payment_methods',
            'column1' => 'payment_method_id',
            'column2' => 'payment_method_id'
            ]
    ]
];
