# Subscriptions API

[Table Schema](../schema/table-schema.md#table-ecommerce_subscriptions)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ GET /*/subscriptions }`

List subscriptions.

### Permissions

- Must be logged in
- Must have the 'pull.subscriptions' permission
- Must have the 'show_deleted' permission to pull soft-deleted subscriptions

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md) | [Branded](request_brand_filtering_parameters.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|user_id||logged in user id|||
|query|view_deleted|no|false|'true' or 'false'|requires 'show_deleted' permission to include soft-deleted subscriptions|

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/subscriptions',
    data: {
        user_id: 1,
        order_by_column: 'id', 
        order_by_direction: 'desc', 
        page: 3, 
        limit: 1, 
        brands: ['drumeo', 'pianote']
    }, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```200 OK```

```json
{
    "data":[
        {
            "type":"subscription",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "type":"subscription",
                "is_active":true,
                "start_date":"2019-05-01 15:21:37",
                "paid_until":"2020-05-01 15:21:37",
                "canceled_on":null,
                "note":null,
                "total_price":599,
                "tax":123,
                "currency":"CAD",
                "interval_type":"year",
                "interval_count":883379,
                "total_cycles_due":149169717,
                "total_cycles_paid":3,
                "deleted_at":null,
                "created_at":"2019-05-01 15:21:37",
                "updated_at":null
            },
            "relationships":{
                "product":{
                    "data":{
                        "type":"product",
                        "id":"1"
                    }
                },
                "user":{
                    "data":{
                        "type":"user",
                        "id":"1"
                    }
                },
                "order":{
                    "data":{
                        "type":"order",
                        "id":"1"
                    }
                },
                "paymentMethod":{
                    "data":{
                        "type":"paymentMethod",
                        "id":"1"
                    }
                },
                "failedPayment":{
                    "data":{
                        "type":"payment",
                        "id":"5"
                    }
                }
            }
        }
    ],
    "included":[
        {
            "type":"product",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"user",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"order",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"paymentMethod",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"payment",
            "id":"5",
            "attributes":[

            ]
        }
    ],
    "meta":{
        "pagination":{
            "total":1,
            "count":1,
            "per_page":10,
            "current_page":1,
            "total_pages":1
        }
    },
    "links":{
        "self":"http:\/\/localhost\/subscriptions?page=1&limit=10&order_by_column=id&order_by_direction=asc",
        "first":"http:\/\/localhost\/subscriptions?page=1&limit=10&order_by_column=id&order_by_direction=asc",
        "last":"http:\/\/localhost\/subscriptions?page=1&limit=10&order_by_column=id&order_by_direction=asc"
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PUT /*/subscription }`

Create a new subscription.

### Permissions

- Must be logged in
- Must have the 'create.subscription' permission

### Request Parameters

[Notable](request_notable_parameter.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.type|yes||must be 'subscription'||
|body|data.attributes.brand|yes||||
|body|data.attributes.type|yes||'payment plan' or 'subscription'||
|body|data.attributes.is_active|yes||||
|body|data.attributes.start_date|yes||date time string||
|body|data.attributes.paid_until|yes||date time string||
|body|data.attributes.canceled_on|yes||date time string||
|body|data.attributes.total_price|yes||the price EXCLUDING tax||
|body|data.attributes.tax||will be calculated automatically from the payment method billing address if not set or set to null|||
|body|data.attributes.interval_type|yes||'year' or 'month'||
|body|data.attributes.interval_count|yes||||
|body|data.attributes.total_cycles_due|yes||||
|body|data.attributes.total_cycles_paid|yes||||
|body|data.relationships.order.data.id|||||
|body|data.relationships.product.data.id|yes||||
|body|data.relationships.paymentMethod.data.id|yes||||
|body|data.relationships.user.data.id|yes||||

### Validation Rules

```php
[
    'data.type' => 'in:subscription',
    'data.attributes.brand' => 'required|max:255',
    'data.attributes.type' => 'required|max:255|in:' .
        implode(
            ',',
            [
                config('ecommerce.type_payment_plan'),
                Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        ),
    'data.attributes.is_active' => 'required|boolean',
    'data.attributes.start_date' => 'required|date',
    'data.attributes.paid_until' => 'required|date',
    'data.attributes.canceled_on' => 'nullable|date',
    'data.attributes.note' => 'nullable',
    'data.attributes.total_price' => 'required|numeric|min:0',
    'data.attributes.tax' => 'nullable|numeric|min:0',
    'data.attributes.currency' => 'required|max:3',
    'data.attributes.interval_type' => 'required|in:' .
        implode(
            ',',
            [
                config('ecommerce.interval_type_yearly'),
                config('ecommerce.interval_type_monthly'),
                config('ecommerce.interval_type_daily')
            ]
        ),
    'data.attributes.interval_count' => 'required|numeric|min:0',
    'data.attributes.total_cycles_due' => 'nullable|numeric|min:0',
    'data.attributes.total_cycles_paid' => 'required|numeric|min:0',
    'data.relationships.user.data.id' => 'required|integer',
    'data.relationships.order.data.id' => 'numeric|exists:' . 'ecommerce_orders' . ',id',
    'data.relationships.product.data.id' => 'numeric|exists:' . 'ecommerce_products' . ',id',
    'data.relationships.paymentMethod.data.id' => 'numeric|exists:' . 'ecommerce_payment_methods' . ',id',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/address',
    data: {
        type: "address",
        attributes: {
            brand: "brand",
            type: "subscription",
            user_id: 1,
            customer_id: null,
            order_id: 1,
            product_id: 1,
            is_active: 1,
            start_date: "2019-05-01 15:32:45",
            paid_until: "2020-05-01 15:32:45",
            canceled_on: null,
            note: null,
            total_price: 458,
            tax: 31,
            currency: "CAD",
            interval_type: "year",
            interval_count: 335434,
            total_cycles_due: 51,
            total_cycles_paid: 11557926,
            payment_method_id: 1,
            created_at: "2019-05-01 15:32:45",
        },
        relationships: {
            product: {
                data: {
                    type: 'product',
                    id: 1
                }
            },
            user: {
                data: {
                    type: 'user',
                    id: 42
                }
            },
            order: {
                data: {
                    type: 'order',
                    id: 21
                }
            },
            paymentMethod: {
                data: {
                    type: 'paymentMethod',
                    id: 84
                }
            }
        }
    }, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```201 OK```

```json
{
    "data":{
        "type":"subscription",
        "id":"1",
        "attributes":{
            "brand":"brand",
            "type":"subscription",
            "is_active":true,
            "start_date":"2019-05-01 15:33:24",
            "paid_until":"2020-05-01 15:33:24",
            "canceled_on":null,
            "note":null,
            "total_price":628,
            "tax":54,
            "currency":"CAD",
            "interval_type":"year",
            "interval_count":76614,
            "total_cycles_due":78732121,
            "total_cycles_paid":99829743,
            "deleted_at":null,
            "created_at":"2019-05-01 15:33:24",
            "updated_at":"2019-05-01 15:33:24"
        },
        "relationships":{
            "product":{
                "data":{
                    "type":"product",
                    "id":"1"
                }
            },
            "user":{
                "data":{
                    "type":"user",
                    "id":"1"
                }
            },
            "order":{
                "data":{
                    "type":"order",
                    "id":"1"
                }
            },
            "paymentMethod":{
                "data":{
                    "type":"paymentMethod",
                    "id":"1"
                }
            }
        }
    },
    "included":[
        {
            "type":"product",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"user",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"order",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"paymentMethod",
            "id":"1",
            "attributes":[

            ]
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PATCH /*/subscription/{ID} }`

Update an existing subscription.

### Permissions

- Must be logged in
- Must have the 'update.subscription' permission

### Request Parameters

[Notable](request_notable_parameter.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|subscription id|yes||||
|body|data.type|yes||must be 'subscription'||
|body|data.attributes.brand|||||
|body|data.attributes.type|||'payment plan' or 'subscription'||
|body|data.attributes.is_active|||||
|body|data.attributes.start_date|||date time string||
|body|data.attributes.paid_until|||date time string||
|body|data.attributes.canceled_on|||date time string||
|body|data.attributes.total_price|||the price EXCLUDING tax||
|body|data.attributes.tax||will be calculated automatically from the payment method billing address if not set or set to null|||
|body|data.attributes.currency|||||
|body|data.attributes.interval_type|||'year' or 'month'||
|body|data.attributes.interval_count|||||
|body|data.attributes.total_cycles_due|||||
|body|data.attributes.total_cycles_paid|||||
|body|data.relationships.order.data.id|||||
|body|data.relationships.product.data.id|||||
|body|data.relationships.paymentMethod.data.id|||||
|body|data.relationships.user.data.id|yes||||

### Validation Rules

```php
[
    'data.type' => 'in:subscription',
    'data.attributes.brand' => 'nullable|max:255',
    'data.attributes.type' => 'max:255|in:' .
        implode(
            ',',
            [
                config('ecommerce.type_payment_plan'),
                Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        ),
    'data.attributes.is_active' => 'nullable|boolean',
    'data.attributes.start_date' => 'nullable|date',
    'data.attributes.paid_until' => 'nullable|date',
    'data.attributes.canceled_on' => 'nullable|date',
    'data.attributes.note' => 'max:255',
    'data.attributes.total_price' => 'nullable|numeric|min:0',
    'data.attributes.tax' => 'nullable|numeric|min:0',
    'data.attributes.currency' => 'nullable|max:3',
    'data.attributes.interval_type' => 'nullable|in:' .
        implode(
            ',',
            [
                config('ecommerce.interval_type_yearly'),
                config('ecommerce.interval_type_monthly'),
                config('ecommerce.interval_type_daily')
            ]
        ),
    'data.attributes.interval_count' => 'nullable|numeric|min:0',
    'data.attributes.total_cycles_due' => 'nullable|numeric|min:0',
    'data.attributes.total_cycles_paid' => 'nullable|numeric|min:0',
    'data.relationships.order.data.id' => 'numeric|exists:' . 'ecommerce_orders' . ',id',
    'data.relationships.product.data.id' => 'numeric|exists:' . 'ecommerce_products' . ',id',
    'data.relationships.paymentMethod.data.id' => 'numeric|exists:' . 'ecommerce_payment_methods' . ',id',
    'data.relationships.user.data.id' => 'integer',

];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/address/4',
    type: 'patch',
    data: {
        type: "address",
        attributes: {
            total_price: 458,
        },
    }, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```200 OK```

```json
{
    "data":{
        "type":"subscription",
        "id":"1",
        "attributes":{
            "brand":"brand",
            "type":"subscription",
            "is_active":true,
            "start_date":"2019-05-01 15:40:54",
            "paid_until":"2020-05-01 15:40:54",
            "canceled_on":null,
            "note":null,
            "total_price":981355223,
            "tax":23453,
            "currency":"CAD",
            "interval_type":"year",
            "interval_count":75181,
            "total_cycles_due":90,
            "total_cycles_paid":2242,
            "deleted_at":null,
            "created_at":"2019-05-01 15:40:54",
            "updated_at":"2019-05-01 15:40:54"
        },
        "relationships":{
            "product":{
                "data":{
                    "type":"product",
                    "id":"1"
                }
            },
            "user":{
                "data":{
                    "type":"user",
                    "id":"1"
                }
            },
            "order":{
                "data":{
                    "type":"order",
                    "id":"1"
                }
            },
            "paymentMethod":{
                "data":{
                    "type":"paymentMethod",
                    "id":"1"
                }
            }
        }
    },
    "included":[
        {
            "type":"product",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"user",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"order",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"paymentMethod",
            "id":"1",
            "attributes":[

            ]
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ POST /*/subscription-renew/{ID} }`

Renew a subscription. The users product access and renewals dates will be updated automatically.

### Permissions

- Must be logged in
- Must have the 'renew.subscription' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|subscription id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/subscription-renew/4',
    type: 'post',
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```200 OK```

```json
{
    "data":{
        "type":"subscription",
        "id":"1",
        "attributes":{
            "brand":"brand",
            "type":"subscription",
            "is_active":true,
            "start_date":"2019-05-01 16:09:59",
            "paid_until":"2020-05-01 00:00:00",
            "canceled_on":null,
            "note":"some note.",
            "total_price":940,
            "tax":0,
            "currency":"CAD",
            "interval_type":"year",
            "interval_count":1,
            "total_cycles_due":604508,
            "total_cycles_paid":84167,
            "deleted_at":null,
            "created_at":"2019-05-01 16:09:59",
            "updated_at":"2019-05-01 16:09:59"
        },
        "relationships":{
            "product":{
                "data":{
                    "type":"product",
                    "id":"1"
                }
            },
            "user":{
                "data":{
                    "type":"user",
                    "id":"1"
                }
            },
            "order":{
                "data":{
                    "type":"order",
                    "id":"55"
                }
            },
            "paymentMethod":{
                "data":{
                    "type":"paymentMethod",
                    "id":"1"
                }
            }
        }
    },
    "included":[
        {
            "type":"product",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"user",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"order",
            "id":"55",
            "attributes":[

            ]
        },
        {
            "type":"paymentMethod",
            "id":"1",
            "attributes":[

            ]
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/subscription/{ID} }`

Delete an existing subscription.

_Uses soft deletes._

### Permissions

- Must be logged in
- Must have the 'delete.subscription' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|subscription id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/subscription/1',
    type: 'delete', 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```204 OK```

```json

```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ GET /*/failed-subscriptions }`

Pulls the failed subscriptions

### Permissions

- Must be logged in
- Must have the 'pull.failed-subscriptions' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|type|yes| |'payment plan' or 'subscription'||
|query|page|no|1|||
|query|limit|no|10|||
|query|order_by_column|no|created_at|||
|query|order_by_direction|no|desc|||
|query|brands|no||||
|query|user_id|no| | |If specified, pull only failed subscriptions of specified user|
|query|big_date_time|no|today|||
|query|small_date_time|no|1 month before today|||

### Request Example

```js
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/failed-subscriptions',
    data: {
        type: 'subscription',
        order_by_column: 'id',
        order_by_direction: 'desc',
        page: 1,
        limit: 10,
    },
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```json
{
    "data":[
        {
            "type":"subscription",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "type":"subscription",
                "is_active":false,
                "start_date":"2019-05-01 15:21:37",
                "paid_until":"2020-05-01 15:21:37",
                "canceled_on":null,
                "note":null,
                "total_price":599,
                "tax":123,
                "currency":"CAD",
                "interval_type":"year",
                "interval_count":883379,
                "total_cycles_due":149169717,
                "total_cycles_paid":3,
                "deleted_at":null,
                "created_at":"2019-05-01 15:21:37",
                "updated_at":null
            },
            "relationships":{
                "product":{
                    "data":{
                        "type":"product",
                        "id":"1"
                    }
                },
                "user":{
                    "data":{
                        "type":"user",
                        "id":"1"
                    }
                },
                "order":{
                    "data":{
                        "type":"order",
                        "id":"1"
                    }
                },
                "paymentMethod":{
                    "data":{
                        "type":"paymentMethod",
                        "id":"1"
                    }
                }
            }
        }
    ],
    "included":[
        {
            "type":"product",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"user",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"order",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"paymentMethod",
            "id":"1",
            "attributes":[

            ]
        }
    ],
    "meta":{
        "pagination":{
            "total":1,
            "count":1,
            "per_page":10,
            "current_page":1,
            "total_pages":1
        }
    },
    "links":{
        "self":"http:\/\/localhost\/failed-subscriptions?page=1&limit=10&order_by_column=id&order_by_direction=asc&type=subscription",
        "first":"http:\/\/localhost\/failed-subscriptions?page=1&limit=10&order_by_column=id&order_by_direction=asc&type=subscription",
        "last":"http:\/\/localhost\/failed-subscriptions?page=1&limit=10&order_by_column=id&order_by_direction=asc&type=subscription"
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ GET /*/failed-billing }`

Pulls the failed billing subscriptions

### Permissions

- Must be logged in
- Must have the 'list.failed-billing' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|type|yes| |'payment plan' or 'subscription'||
|query|page|no|1|||
|query|limit|no|10|||
|query|order_by_column|no|created_at|||
|query|order_by_direction|no|desc|||
|query|brands|no||||
|query|big_date_time|no|today|||
|query|small_date_time|no|14 days before today|||
|query|csv||false|if set to true, will return CSV file download||

### Request Example

```js
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/failed-billing',
    data: {
        type: 'subscription',
        order_by_column: 'id',
        order_by_direction: 'desc',
        page: 1,
        limit: 10,
    },
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```json
{
    "data":[
        {
            "type":"subscription",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "type":"subscription",
                "is_active":false,
                "start_date":"2019-05-01 15:21:37",
                "paid_until":"2020-05-01 15:21:37",
                "canceled_on":null,
                "note":null,
                "total_price":599,
                "tax":123,
                "currency":"CAD",
                "interval_type":"year",
                "interval_count":883379,
                "total_cycles_due":149169717,
                "total_cycles_paid":3,
                "deleted_at":null,
                "created_at":"2019-05-01 15:21:37",
                "updated_at":null
            },
            "relationships":{
                "product":{
                    "data":{
                        "type":"product",
                        "id":"1"
                    }
                },
                "user":{
                    "data":{
                        "type":"user",
                        "id":"1"
                    }
                },
                "order":{
                    "data":{
                        "type":"order",
                        "id":"1"
                    }
                },
                "paymentMethod":{
                    "data":{
                        "type":"paymentMethod",
                        "id":"1"
                    }
                }
            }
        }
    ],
    "included":[
        {
            "type":"product",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"user",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"order",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"paymentMethod",
            "id":"1",
            "attributes":[

            ]
        }
    ],
    "meta":{
        "pagination":{
            "total":1,
            "count":1,
            "per_page":10,
            "current_page":1,
            "total_pages":1
        }
    },
    "links":{
        "self":"http:\/\/localhost\/failed-billing?page=1&limit=10&order_by_column=id&order_by_direction=asc&type=subscription",
        "first":"http:\/\/localhost\/failed-billing?page=1&limit=10&order_by_column=id&order_by_direction=asc&type=subscription",
        "last":"http:\/\/localhost\/failed-billing?page=1&limit=10&order_by_column=id&order_by_direction=asc&type=subscription"
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->
