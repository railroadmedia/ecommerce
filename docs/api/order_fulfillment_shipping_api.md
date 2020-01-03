# Shipping/Order Fulfillment API

[Table Schema](../schema/table-schema.md#ecommerce_order_item_fulfillment)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ GET /*/fulfillment }`

Get all orders and items that need to be fulfilled between the specified dates.

### Permissions

- Must be logged in
- Must have the 'pull.fulfillments' permission

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md)
<br>


|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|small_date_time|||||
|query|big_date_time|||||
|query|search_term||||This searches the users or customers email address.|
|query|csv||false|if set to true, will return CSV file download||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/fulfillment',
    data: {
        search_term: "bob@email.com"
    }, 
    success: function(response) {},
    error: function(response) {}
});

$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/fulfillment',
    data: {
        order_by_column: 'created_at', 
        order_by_direction: 'desc', 
        small_date_time: '2019-04-01 00:00:00',
        big_date_time: '2019-05-01 00:00:00',
    }, 
    success: function(response) {},
    error: function(response) {}
});
```

To get the CSV file download:
```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/fulfillment',
    data: {
        order_by_column: 'created_at', 
        order_by_direction: 'desc', 
        small_date_time: '2019-04-01 00:00:00',
        big_date_time: '2019-05-01 00:00:00',
        csv: true,
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
            "type":"fulfillment",
            "id":"1",
            "attributes":{
                "status":"pending",
                "company":null,
                "tracking_number":null,
                "fulfilled_on":null,
                "note":"Atque dolorem ut explicabo quos aut. Ut esse delectus quis voluptatem dolores beatae. Neque illum provident non quas iste. Accusamus necessitatibus placeat sint dolores ut facilis id.",
                "created_at":"2019-06-05 21:03:35",
                "updated_at":null
            },
            "relationships":{
                "order":{
                    "data":{
                        "type":"order",
                        "id":"1"
                    }
                },
                "orderItem":{
                    "data":{
                        "type":"orderItem",
                        "id":"1"
                    }
                }
            }
        },
        {
            "type":"fulfillment",
            "id":"2",
            "attributes":{
                "status":"pending",
                "company":null,
                "tracking_number":null,
                "fulfilled_on":null,
                "note":"Sed asperiores nisi reiciendis tempore officiis. Ut architecto nulla voluptatem. Placeat illo quos ducimus.",
                "created_at":"2019-06-05 20:03:35",
                "updated_at":null
            },
            "relationships":{
                "order":{
                    "data":{
                        "type":"order",
                        "id":"2"
                    }
                },
                "orderItem":{
                    "data":{
                        "type":"orderItem",
                        "id":"2"
                    }
                }
            }
        }
    ],
    "included":[
        {
            "type":"address",
            "id":"64542691",
            "attributes":{

            }
        },
        {
            "type":"address",
            "id":"1",
            "attributes":{

            }
        },
        {
            "type":"order",
            "id":"1",
            "attributes":{
                "total_due":456061,
                "product_due":5998328,
                "taxes_due":165240,
                "shipping_due":327,
                "finance_due":2,
                "total_paid":317809367,
                "brand":"brand",
                "note":"Cum quasi doloremque mollitia ut cumque. Et aut doloremque repellendus nemo et repellendus. Veniam rerum ut explicabo illo ut.",
                "deleted_at":null,
                "created_at":"2019-06-05 21:03:35",
                "updated_at":null
            },
            "relationships":{
                "billingAddress":{
                    "data":{
                        "type":"address",
                        "id":"64542691"
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
        {
            "type":"product",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "name":"omnis",
                "sku":"aut119474",
                "price":946,
                "type":"physical one time",
                "active":false,
                "category":"deserunt",
                "description":"Molestiae voluptatem dolorem ducimus alias et veniam omnis. Quaerat voluptatem qui aut id quis reiciendis sed. A aliquid impedit libero sint odio.",
                "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?47109",
                "is_physical":true,
                "weight":6,
                "subscription_interval_type":"year",
                "subscription_interval_count":9,
                "stock":805,
                "note":"Enim natus impedit sed unde fugiat vel. Est dolor assumenda quaerat sunt non. Aut modi consectetur molestias nihil doloribus velit.",
                "created_at":"2019-06-05 21:03:35",
                "updated_at":null
            }
        },
        {
            "type":"orderItem",
            "id":"1",
            "attributes":{
                "quantity":2,
                "weight":358,
                "initial_price":34097,
                "total_discounted":2677151,
                "final_price":2103,
                "created_at":"2019-06-05 21:03:35",
                "updated_at":null
            },
            "relationships":{
                "order":{
                    "data":{
                        "type":"order",
                        "id":"1"
                    }
                },
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
            "id":"95293581",
            "attributes":{

            }
        },
        {
            "type":"address",
            "id":"2",
            "attributes":{

            }
        },
        {
            "type":"order",
            "id":"2",
            "attributes":{
                "total_due":8300379,
                "product_due":192773099,
                "taxes_due":1269854,
                "shipping_due":92,
                "finance_due":9,
                "total_paid":51491028,
                "brand":"brand",
                "note":"Veniam aut et voluptatum et. Modi magnam earum incidunt. Aut nobis aliquam unde laborum doloribus fugiat quidem.",
                "deleted_at":null,
                "created_at":"2019-06-05 21:03:35",
                "updated_at":null
            },
            "relationships":{
                "billingAddress":{
                    "data":{
                        "type":"address",
                        "id":"95293581"
                    }
                },
                "shippingAddress":{
                    "data":{
                        "type":"address",
                        "id":"2"
                    }
                }
            }
        },
        {
            "type":"product",
            "id":"2",
            "attributes":{
                "brand":"brand",
                "name":"dolor",
                "sku":"praesentium3707729",
                "price":81,
                "type":"digital subscription",
                "active":true,
                "category":"ipsum",
                "description":"Molestiae hic ullam voluptatem. Enim quia similique illo magnam. Distinctio unde qui odit dignissimos qui tenetur. Et dolores autem repellat aut maiores sed.",
                "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?48770",
                "is_physical":false,
                "weight":95,
                "subscription_interval_type":"day",
                "subscription_interval_count":6,
                "stock":219,
                "note":"Rerum et et maxime ut doloribus. Ullam similique dolor voluptatem sed qui corporis. Odio dolores eum officia tempore et ut praesentium omnis. Doloribus et dolorum veniam reiciendis cum.",
                "created_at":"2019-06-05 21:03:35",
                "updated_at":null
            }
        },
        {
            "type":"orderItem",
            "id":"2",
            "attributes":{
                "quantity":737283,
                "weight":26344596,
                "initial_price":55159,
                "total_discounted":869,
                "final_price":910986,
                "created_at":"2019-06-05 21:03:35",
                "updated_at":null
            },
            "relationships":{
                "order":{
                    "data":{
                        "type":"order",
                        "id":"2"
                    }
                },
                "product":{
                    "data":{
                        "type":"product",
                        "id":"2"
                    }
                }
            }
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PATCH /*/fulfillment }`

Update an existing fulfillment or all existing fulfillments for an order.

### Permissions

- Must be logged in
- Must have the 'fulfilled.fulfillment' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|tracking_number|yes|1 month before today|||
|body|shipping_company|yes|today|||
|body|fulfilled_on||today|datetime||
|body|order_item_id|||if this is not set, all order item fulfilments for the order will be updated||
|body|order_id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/fulfillment',
    data: {
        tracking_number: '2385729374782934', 
        shipping_company: 'Fedex', 
        fulfilled_on: '2019-04-01 00:00:00',
        order_item_id: 4152,
        order_id: 8373,
    }, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

Returns an empty response if there were no errors.

```201 OK```

```json
{}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/fulfillment/{orderId}/{orderItemId?} }`

Delete an existing fulfillment or all fulfillments for an order.

### Permissions

- Must be logged in
- Must have the 'delete.fulfillment' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|orderId|||if this is not set, all order item fulfilments for the order_id will be deleted||
|path|orderItemId|yes||||


### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/fulfillment/21',
    type: 'delete', 
    success: function(response) {},
    error: function(response) {}
});
```

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/fulfillment/1613/972',
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

### `{ POST /*/fulfillment/mark-fulfilled-csv-upload-shipstation }`

Upload a CSV file which is parsed and used to update existing fulfillments. This usually comes straight from the shippers software.

### Permissions

- Must be logged in
- Must have the 'upload.fulfillments' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|csv_file|||the csv file, comma seperated||

### Request Example

I am unsure how to send files via ajax. -Caleb

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/fulfillment/mark-fulfilled-csv-upload-shipstation',
    type: 'post', 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

NOTE: success will always be true.

Processed without errors:

```201 OK```

```json
{
    "success":true,
    "errors":[

    ]
}
```

Processed with errors:

```201 OK```

```json
{
    "success":true,
    "errors":[
        {
            "title":"Data Error",
            "source":"csv_file",
            "detail":"Missing \"Shipment - Service\" name at row: 2 column: 5. Please review."
        },
        {
            "title":"Data Error",
            "source":"csv_file",
            "detail":"Missing \"Shipment - Service\" name at row: 3 column: 5. Please review."
        }
    ]
}
```
