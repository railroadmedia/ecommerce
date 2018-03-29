<?php

return [
    'table_names' => [
        'address.update' => 'ecommerce_address',
        'address.delete' => 'ecommerce_address',
        'payment-method.update' => [
            'user_id' => 'ecommerce_user_payment_methods',
            'customer_id' => 'ecommerce_customer_payment_methods'
        ],
        'payment-method.delete' => 'ecommerce_user_payment_methods',
    ],
    'column_names' => [
        'payment-method.update' => 'payment_method_id',
        'payment-method.delete' => 'payment_method_id',
    ]
];
