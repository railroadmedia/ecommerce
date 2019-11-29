# Payment Method API

[Table Schema](../schema/table-schema.md#table-ecommerce_payment_methods)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ GET /*/customer-payment-method/{CUSTOMER ID} }`

List customer payment methods.

### Permissions

- Must be logged in
- Must have the 'pull.customer.payment.method' permission
- Must have the 'show_deleted' permission to pull soft-deleted payment methods

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|customer_id|||||
|query|view_deleted|no|false|'true' or 'false'|requires 'show_deleted' permission to include soft-deleted payment methods|

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/customer-payment-method/43',
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```200 OK```

```json
{
  "data": [
    {
      "type": "paymentMethod",
      "id": "126315",
      "attributes": {
        "method_id": 88561,
        "method_type": "credit_card",
        "currency": "USD",
        "note": null,
        "deleted_at": null,
        "created_at": "2019-11-29 11:46:19",
        "updated_at": "2019-11-29 11:46:19"
      },
      "relationships": {
        "method": {
          "data": {
            "type": "creditCard",
            "id": "88561"
          }
        },
        "billingAddress": {
          "data": {
            "type": "address",
            "id": "176064"
          }
        }
      }
    }
  ],
  "included": [
    {
      "type": "customer",
      "id": "19282",
      "attributes": {
        "brand": "drumeo",
        "phone": null,
        "email": "wava.quigley@hotmail.com",
        "note": null,
        "created_at": "2019-11-29 11:46:15",
        "updated_at": "2019-11-29 11:46:15"
      }
    },
    {
      "type": "creditCard",
      "id": "88561",
      "attributes": {
        "fingerprint": "p1raZanLcARD5xTT",
        "last_four_digits": 4444,
        "cardholder_name": null,
        "company_name": "MasterCard",
        "expiration_date": "2020-11-29 11:46:18",
        "external_id": "card_1Fk7vRKoDqdTNxK1JQ7Y697D",
        "external_customer_id": "cus_GGfF5yTJO9nRi7",
        "payment_gateway_name": "drumeo",
        "created_at": "2019-11-29 11:46:18",
        "updated_at": "2019-11-29 11:46:18"
      }
    },
    {
      "type": "address",
      "id": "176064",
      "attributes": {
        "type": "billing",
        "brand": "drumeo",
        "first_name": "",
        "last_name": "",
        "street_line_1": "",
        "street_line_2": "",
        "city": "",
        "zip": "",
        "region": "",
        "country": "",
        "note": null,
        "created_at": "2019-11-29 11:46:18",
        "updated_at": "2019-11-29 11:46:18",
        "deleted_at": null
      },
      "relationships": {
        "customer": {
          "data": {
            "type": "customer",
            "id": "19282"
          }
        }
      }
    }
  ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ GET /*/user-payment-method/{USER ID} }`

List users payment methods.

### Permissions

- Must be logged in
- Must have the 'pull.user.payment.method' permission
- Must have the 'show_deleted' permission to pull soft-deleted payment methods

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|user_id|||||
|query|view_deleted|no|false|'true' or 'false'|requires 'show_deleted' permission to include soft-deleted payment methods|

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/user-payment-method/43',
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
            "type":"userPaymentMethods",
            "id":"1",
            "attributes":{
                "is_primary":true,
                "created_at":"2019-04-30 20:44:44",
                "updated_at":null
            },
            "relationships":{
                "paymentMethod":{
                    "data":{
                        "type":"paymentMethod",
                        "id":"1"
                    }
                },
                "user":{
                    "data":{
                        "type":"user",
                        "id":"1"
                    }
                },
                "method":{
                    "data":{
                        "type":"creditCard",
                        "id":"1"
                    }
                }
            }
        },
        {
            "type":"userPaymentMethods",
            "id":"3",
            "attributes":{
                "is_primary":false,
                "created_at":"2019-04-30 20:44:44",
                "updated_at":null
            },
            "relationships":{
                "paymentMethod":{
                    "data":{
                        "type":"paymentMethod",
                        "id":"3"
                    }
                },
                "user":{
                    "data":{
                        "type":"user",
                        "id":"1"
                    }
                },
                "method":{
                    "data":{
                        "type":"paypalAgreement",
                        "id":"1"
                    }
                }
            }
        }
    ],
    "included":[
        {
            "type":"address",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"paymentMethod",
            "id":"1",
            "attributes":{
                "method_id":1,
                "method_type":"credit_card",
                "currency":"LSL",
                "deleted_at":null,
                "created_at":"2019-04-30 20:44:44",
                "updated_at":null
            },
            "relationships":{
                "billingAddress":{
                    "data":{
                        "type":"address",
                        "id":"1"
                    }
                }
            }
        },
        {
            "type":"user",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"creditCard",
            "id":"1",
            "attributes":{
                "fingerprint":"4242424242424242",
                "last_four_digits":8132,
                "cardholder_name":"Mariam Stehr",
                "company_name":"Visa Retired",
                "expiration_date":"2019-10-19 00:00:00",
                "external_id":"card_1CT9rUE2yPYKc9YRHSwdADbH",
                "external_customer_id":"cus_CsviON4xYQxcwC",
                "payment_gateway_name":"recordeo",
                "created_at":"2019-04-30 20:44:44",
                "updated_at":null
            }
        },
        {
            "type":"paymentMethod",
            "id":"3",
            "attributes":{
                "method_id":1,
                "method_type":"paypal",
                "currency":"CZK",
                "deleted_at":null,
                "created_at":"2019-04-30 20:44:44",
                "updated_at":null
            },
            "relationships":{
                "billingAddress":{
                    "data":{
                        "type":"address",
                        "id":"1"
                    }
                }
            }
        },
        {
            "type":"paypalAgreement",
            "id":"1",
            "attributes":{
                "external_id":"B-5Y6562572W918445E",
                "payment_gateway_name":"recordeo",
                "created_at":"2019-04-30 20:44:44",
                "updated_at":null
            }
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PUT /*/payment-method }`

Create a new payment method. This endpoint does not adhere to the JSON api spec since it uses special logic.

### Permissions

- Must be logged in
- Users can create payment methods for themselves
- Must have the 'create.payment.method' permission to create addresses for others users

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|method_type|yes||must be 'credit_card', paypal is not supported||
|body|card_token|yes||the stripe card token from front end request||
|body|gateway|yes||brand||
|body|address_id|yes|||the billing address id|
|body|user_id|yes if 'customer_id' is not set||||
|body|customer_id|yes if 'user_id' is not set||||

### Validation Rules

```php
[
    'method_type' => 'required|max:255',
    'card_token'  => 'required',
    'gateway'     => 'required',
    'address_id'  => 'required',
    'user_id'     => 'required_without:customer_id',
    'customer_id' => 'required_without:user_id'
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
            card_token: 'tok_236235134',
            gateway: 'drumeo',
            method_type: 'credit card',
            currency: 'CAD',
            set_default: false,
            user_id: 5,
            address_id: 3,
        },
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
        "type":"paymentMethod",
        "id":"1",
        "attributes":{
            "method_id":1,
            "method_type":"credit_card",
            "currency":"ARS",
            "deleted_at":null,
            "created_at":"2019-04-30 21:07:51",
            "updated_at":"2019-04-30 21:07:51"
        },
        "relationships":{
            "billingAddress":{
                "data":{
                    "type":"address",
                    "id":"2"
                }
            }
        }
    },
    "included":[
        {
            "type":"user",
            "id":"2",
            "attributes":[

            ]
        },
        {
            "type":"address",
            "id":"2",
            "attributes":{
                "type":"billing",
                "brand":"brand",
                "first_name":null,
                "last_name":null,
                "street_line_1":null,
                "street_line_2":null,
                "city":null,
                "zip":null,
                "region":"",
                "country":"",
                "created_at":"2019-04-30 21:07:51",
                "updated_at":"2019-04-30 21:07:51"
            },
            "relationships":{
                "user":{
                    "data":{
                        "type":"user",
                        "id":"2"
                    }
                }
            }
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PATCH /*/payment-method/{ID} }`

Update an existing payment method. This endpoint does not adhere to the JSON api spec since it uses special logic.

### Permissions

- Must be logged in
- Users can update their own credit card payment methods without any special permissions
- Must have the 'update.payment.method' permission can update any credit card payment methods

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|payment method id|yes||||
|body|gateway|||the brand||
|body|month|||integer (1, 2, etc)||
|body|year|||integer||
|body|country|||any country||
|body|region|||||

### Validation Rules

```php
[
    'gateway' => 'required',
    'year'    => 'required|numeric',
    'month'   => 'required|numeric',
    'country' => 'required|string',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/payment_method/1',
    type: 'patch', 
    data: {
        gateway: "drumeo",
        year: 2020,
        month: 1,
        country: "Ireland",
        region: "texas",
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
        "type":"paymentMethod",
        "id":"1",
        "attributes":{
            "method_id":1,
            "method_type":"credit_card",
            "currency":"DOP",
            "deleted_at":null,
            "created_at":"2019-04-30 21:18:34",
            "updated_at":null
        },
        "relationships":{
            "billingAddress":{
                "data":{
                    "type":"address",
                    "id":"1"
                }
            }
        }
    },
    "included":[
        {
            "type":"address",
            "id":"1",
            "attributes":[

            ]
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/payment-method/{ID} }`

Delete an existing payment method.

_Uses soft deletes._

### Permissions

- Must be logged in
- Users can delete their own payment methods without any special permissions
- Must have the 'delete.payment.method' permission to delete payment methods belonging to others users or customers

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|payment method id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/payment-method/1',
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