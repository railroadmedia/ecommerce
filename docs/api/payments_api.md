# Payments API

[Table Schema](../schema/table-schema.md#table-ecommerce_payments)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ GET /*/payments }`

List payments.

### Permissions

- Must be logged in
- Must have the 'list.payments' permission

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|order_id|||pull payments for the given order id only||
|query|subscription_id|||pull payments for the given subscription id only||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/address',
    data: {
        order_id: 1,
        subscription_id: 92,
        order_by_column: 'id', 
        order_by_direction: 'desc', 
        page: 3, 
        limit: 1
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
            "type":"payment",
            "id":"1",
            "attributes":{
                "total_due":99003225,
                "total_paid":706,
                "total_refunded":null,
                "conversion_rate":85,
                "type":"renewal",
                "external_id":"deserunt",
                "external_provider":"eveniet",
                "status":"1",
                "message":null,
                "currency":"MDL",
                "deleted_at":null,
                "created_at":"2019-04-30 20:15:28",
                "updated_at":null
            },
            "relationships":{
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
            "type":"paymentMethod",
            "id":"1",
            "attributes":[

            ]
        }
    ],
    "meta":{
        "pagination":{
            "total":5,
            "count":5,
            "per_page":10,
            "current_page":1,
            "total_pages":1
        }
    },
    "links":{
        "self":"http:\/\/localhost\/payment?order_by_column=id&order_by_direction=asc&limit=10&order_id=1&page=1",
        "first":"http:\/\/localhost\/payment?order_by_column=id&order_by_direction=asc&limit=10&order_id=1&page=1",
        "last":"http:\/\/localhost\/payment?order_by_column=id&order_by_direction=asc&limit=10&order_id=1&page=1"
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PUT /*/payment }`

Create a new payment. This DOES actually attempt to charge the user.

- If saved to a subscription, it will automatically renew the subscription.
- If saved to an order, the order totals will be updated accordingly.
- You can save to both and order and a subscription.

### Permissions

- Must be logged in
- Users can make payments with their own payment method, otherwise they must have the 'create.payment' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.attributes.due|yes||how much to charge in their currency||
|body|data.attributes.currency||based on IP address|||
|body|data.attributes.payment_gateway|yes||brand to charge under, should match order/sub||
|body|data.relationships.paymentMethod.data.id|yes||||
|body|data.relationships.subscription.data.id|||subscription this payment is for||
|body|data.relationships.order.data.id|||order this payment is for||

### Validation Rules

```php
[
    'data.type' => 'in:payment',
    'data.attributes.due' => 'required|numeric',
    'data.relationships.paymentMethod.data.id' =>
        'numeric|nullable|exists:'.ConfigService::$tablePaymentMethod.',id',
    'data.relationships.order.data.id' => 'numeric|exists:'.ConfigService::$tableOrder.',id',
    'data.relationships.subscription.data.id' => 'numeric|exists:'.ConfigService::$tableSubscription.',id',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/payment',
    type: 'put',
    data: {
        type: "payment",
        attributes: {
            due: 10.02,
            currency: 'CAD',
            payment_gateway: 'drumeo'
        },
        relationships: {
            paymentMethod: {
                data: {
                    type: 'paymentMethod',
                    id: 12
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
        "type":"payment",
        "id":"1",
        "attributes":{
            "total_due":114,
            "total_paid":114,
            "total_refunded":null,
            "conversion_rate":1.32,
            "type":"order",
            "external_id":null,
            "external_provider":"stripe",
            "status":"1",
            "message":"",
            "currency":"CAD",
            "deleted_at":null,
            "created_at":"2019-04-30 20:29:46",
            "updated_at":"2019-04-30 20:29:46"
        },
        "relationships":{
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
            "type":"paymentMethod",
            "id":"1",
            "attributes":[

            ]
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/payment/{ID} }`

Delete an existing payment.

_Uses soft deletes._

### Permissions

- Must be logged in
- Must have the 'delete.payment' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|payment id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/payment/1',
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