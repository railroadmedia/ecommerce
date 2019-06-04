# Orders API

[Table Schema](../schema/table-schema.md#table-ecommerce_orders)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ GET /*/orders }`

List orders.

### Permissions

- Must be logged in
- Must have the 'pull.orders' permission 

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md) | [Branded](request_brand_filtering_parameters.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|start-date|no||any parseable date/time string|only pull orders after this time|
|query|end-date|no||any parseable date/time string|only pull orders before this time|
|query|user_id|no||||only pull orders for a specific user|

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/orders',
    data: {
        order_by_column: 'id', 
        order_by_direction: 'desc', 
        page: 3, 
        limit: 1,
        brands: ['drumeo', 'pianote'],
        'start-date': '2018-10-02 00:00:00',
        'end-date': '2018-12-18 00:00:00',
        'user_id': 92
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
            "type":"order",
            "id":"1",
            "attributes":{
                "total_due":2686157,
                "product_due":204,
                "taxes_due":55586,
                "shipping_due":2,
                "finance_due":88,
                "total_paid":4971861,
                "brand":"brand",
                "deleted_at":null,
                "note":"some note",
                "created_at":"2019-04-30 18:27:36",
                "updated_at":null
            },
            "relationships":{
                "orderItem":{
                    "data":[
                        {
                            "type":"orderItem",
                            "id":"1"
                        }
                    ]
                },
                "user":{
                    "data":{
                        "type":"user",
                        "id":"1"
                    }
                },
                "shippingAddress":{
                    "data":{
                        "type":"address",
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
            "type":"orderItem",
            "id":"1",
            "attributes":{
                "quantity":906,
                "weight":8,
                "initial_price":594,
                "total_discounted":25148031,
                "final_price":975,
                "created_at":"2019-04-30 18:27:36",
                "updated_at":null
            },
            "relationships":{
                "product":{
                    "data":{
                        "type":"product",
                        "id":"1"
                    }
                }
            }
        },
        {
            "type":"address",
            "id":"1",
            "attributes":{
                "type":"shipping",
                "brand":"brand",
                "first_name":"Sherwood",
                "last_name":"Raynor",
                "street_line_1":"9905 Metz Courts Apt. 149",
                "street_line_2":null,
                "city":"Myrtisview",
                "zip":"50951-7893",
                "region":"ea",
                "country":"Ireland",
                "created_at":"2019-04-30 18:27:36",
                "updated_at":"2019-04-30 18:27:36"
            },
            "relationships":{
                "user":{
                    "data":{
                        "type":"user",
                        "id":"1"
                    }
                }
            }
        }
    ],
    "meta":{
        "pagination":{
            "total":10,
            "count":10,
            "per_page":10,
            "current_page":1,
            "total_pages":1
        }
    },
    "links":{
        "self":"http:\/\/localhost\/orders?page=1&limit=10",
        "first":"http:\/\/localhost\/orders?page=1&limit=10",
        "last":"http:\/\/localhost\/orders?page=1&limit=10"
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ GET /*/order/{ORDER ID} }`

Get an order.

### Permissions

- Must be logged in
- Must have the 'pull.orders' permission 

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|order id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/orders/1',
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```200 OK```

```json
{
    "data":
    {
        "type":"order",
        "id":"1",
        "attributes":{
            "total_due":2686157,
            "product_due":204,
            "taxes_due":55586,
            "shipping_due":2,
            "finance_due":88,
            "total_paid":4971861,
            "brand":"brand",
            "deleted_at":null,
            "note":"some note",
            "created_at":"2019-04-30 18:27:36",
            "updated_at":null
        },
        "relationships":{
            "orderItem":{
                "data":[
                    {
                        "type":"orderItem",
                        "id":"1"
                    }
                ]
            },
            "user":{
                "data":{
                    "type":"user",
                    "id":"1"
                }
            },
            "shippingAddress":{
                "data":{
                    "type":"address",
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
            "type":"orderItem",
            "id":"1",
            "attributes":{
                "quantity":906,
                "weight":8,
                "initial_price":594,
                "total_discounted":25148031,
                "final_price":975,
                "created_at":"2019-04-30 18:27:36",
                "updated_at":null
            },
            "relationships":{
                "product":{
                    "data":{
                        "type":"product",
                        "id":"1"
                    }
                }
            }
        },
        {
            "type":"address",
            "id":"1",
            "attributes":{
                "type":"shipping",
                "brand":"brand",
                "first_name":"Sherwood",
                "last_name":"Raynor",
                "street_line_1":"9905 Metz Courts Apt. 149",
                "street_line_2":null,
                "city":"Myrtisview",
                "zip":"50951-7893",
                "region":"ea",
                "country":"Ireland",
                "created_at":"2019-04-30 18:27:36",
                "updated_at":"2019-04-30 18:27:36"
            },
            "relationships":{
                "user":{
                    "data":{
                        "type":"user",
                        "id":"1"
                    }
                }
            }
        }
    ],
    "meta":{
        "pagination":{
            "total":10,
            "count":10,
            "per_page":10,
            "current_page":1,
            "total_pages":1
        }
    },
    "links":{
        "self":"http:\/\/localhost\/orders?page=1&limit=10",
        "first":"http:\/\/localhost\/orders?page=1&limit=10",
        "last":"http:\/\/localhost\/orders?page=1&limit=10"
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PATCH /*/order/{ORDER ID} }`

Update an existing order.

### Permissions

- Must be logged in
- Must have the 'update.order' permission.

### Request Parameters

[Notable](request_notable_parameter.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.type|yes||must be 'order'||
|body|data.attributes.note|||||
|body|data.attributes.total_due|||||
|body|data.attributes.taxes_due|||||
|body|data.attributes.shipping_due|||||
|body|data.attributes.total_paid|||||

### Validation Rules

```php
[
    'data.type' => 'in:order',
    'data.attributes.total_due' => 'numeric|min:0',
    'data.attributes.taxes_due' => 'numeric|min:0',
    'data.attributes.shipping_due' => 'numeric|min:0',
    'data.attributes.total_paid' => 'numeric|min:0',
    'data.attributes.note' => 'nullable|string',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/order/3',
    type: 'patch',
    data: {
        data: {
            type: 'order',
            attributes: {
                total_due: 199.99
            },
        }
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
        "type":"order",
        "id":"1",
        "attributes":{
            "total_due":1530,
            "product_due":2799,
            "taxes_due":1784.11,
            "shipping_due":1377.03,
            "finance_due":67,
            "total_paid":65,
            "brand":"brand",
            "note":"some note",
            "deleted_at":null,
            "created_at":"2019-04-30 18:37:18",
            "updated_at":"2019-04-30 18:37:18"
        }
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/order/{ORDER ID} }`

Delete an existing order.

_Uses soft deletes._

### Permissions

- Must be logged in
- Must have the 'delete.order' permission.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|order id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/order/1',
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