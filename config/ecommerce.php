<?php

return [
    'cache_duration' => 60 * 60 * 24 * 30,
    'database_connection_name' => 'mysql',
    'connection_mask_prefix' => 'ecommerce_',
    'data_mode' => 'host',

    'table_prefix' => 'ecommerce_',
    'brand' => 'drumeo',
    'tax_rate' => [
        'Canada' => [
            'alberta' => 0.05,
            'ab' => 0.05,
            'british columbia' => 0.12,
            'bc' => 0.12,
            'manitoba' => 0.05,
            'mb' => 0.05,
            'new brunswick' => 0.13,
            'nb' => 0.13,
            'newfoundland' => 0.13,
            'nl' => 0.13,
            'newfoundland and labrador' => 0.13,
            'northwest territories' => 0.05,
            'nt' => 0.05,
            'nova scotia' => 0.15,
            'ns' => 0.15,
            'nunavut' => 0.05,
            'nu' => 0.05,
            'ontario' => 0.13,
            'on' => 0.13,
            'prince edward island' => 0.14,
            'pe' => 0.14,
            'pei' => 0.14,
            'quebec' => 0.05,
            'qc' => 0.05,
            'saskatchewan' => 0.05,
            'sk' => 0.05,
            'yukon' => 0.05,
            'yt' => 0.05
        ]
    ]
];