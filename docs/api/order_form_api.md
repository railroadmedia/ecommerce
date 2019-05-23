# Order Form API

# JSON Endpoints

### `{ GET /*/json/order-form }`

Get all the cart and order form data required to render a checkout page.

### Request Parameters

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/json/order-form',
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```200 OK```

```json
{
    "data":null,
    "meta":{
        "cart":{
            "items":[
                {
                    "sku":"aaut",
                    "name":"aut",
                    "quantity":2,
                    "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?95232",
                    "description":"voluptatem",
                    "stock":871,
                    "subscription_interval_type":"",
                    "subscription_interval_count":0,
                    "price_before_discounts":12.95,
                    "price_after_discounts":12.95,
                    "requires_shipping":false
                },
                {
                    "sku":"bamet",
                    "name":"voluptatem",
                    "quantity":1,
                    "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?10410",
                    "description":"sunt",
                    "stock":388,
                    "subscription_interval_type":"",
                    "subscription_interval_count":0,
                    "price_before_discounts":247,
                    "price_after_discounts":247,
                    "requires_shipping":true
                }
            ],
            "discounts":[
                "est"
            ],
            "shipping_address":{
                "zip_or_postal_code":null,
                "street_line_two":null,
                "street_line_one":null,
                "last_name":null,
                "first_name":null,
                "state":"ontario",
                "country":"canada",
                "city":null
            },
            "billing_address":{
                "zip_or_postal_code":null,
                "street_line_two":null,
                "street_line_one":null,
                "last_name":null,
                "first_name":null,
                "state":"ontario",
                "country":"canada",
                "city":null
            },
            "number_of_payments":1,
            "totals":{
                "shipping":5.5,
                "tax":34.89,
                "due":303.29
            }
        }
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PUT /*/json/order-form/submit }`

Submit the order with whatever is currently in the cart.

### Permissions

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|payment_method_type|yes if 'payment_method_id' not set||||
|body|payment_method_id|yes if 'payment_method_type' not set||||
|body|billing_country|yes||||
|body|card_token|yes if 'payment_method_type' == 'credit card'||||
|body|gateway|yes||||
|body|currency||based on IP address|||
|body|payment_plan_number_of_payments||1|1,2,5|this can be configured to allow any values|
|body|billing_region|yes if 'billing_country' == 'Canada'||||
|body|billing_zip_or_postal_code||||||
|body|shipping_address_id|yes if cart has shippable items, and if all the other shipping fields are not set||||
|body|shipping_first_name|yes if cart has shippable items, and 'shipping_address_id' is not set||||
|body|shipping_last_name|yes if cart has shippable items, and 'shipping_address_id' is not set||||
|body|shipping_address_line_1|yes if cart has shippable items, and 'shipping_address_id' is not set||||
|body|shipping_city|yes if cart has shippable items, and 'shipping_address_id' is not set||||
|body|shipping_region|yes if cart has shippable items, and 'shipping_address_id' is not set||||
|body|shipping_zip_or_postal_code|yes if cart has shippable items, and 'shipping_address_id' is not set||||
|body|shipping_country|yes if cart has shippable items, and 'shipping_address_id' is not set||||
|body|billing_email|yes if 'account_creation_email' not set|||**can only use this for non-digital items**|
|body|account_creation_email|yes if the order has digital items or 'billing_email' is not set||||
|body|account_creation_password|yes if the order has digital items or 'billing_email' is not set||||
|body|account_creation_password_confirmation|yes if the order has digital items or 'billing_email' is not set||||
|body|user_id||||'place-orders-for-other-users' permission is required to place orders for other users|

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/json/order-form/submit',
    data: {
        payment_method_type: "credit_card",
        card_token: "veritatis",
        billing_region: "deleniti",
        billing_zip_or_postal_code: "27895-2195",
        billing_country: "Canada",
        gateway: "drumeo",
        shipping_first_name: "Wyman",
        shipping_last_name: "Kozey",
        shipping_address_line_1: "Blandaport, NC 39987-8605",
        shipping_city: "Amandamouth",
        shipping_region: "deleniti",
        shipping_zip_or_postal_code: "81723-8095",
        shipping_country: "Canada",
        currency: "CAD",
        account_creation_email: "reilly.fahey@emard.com",
        account_creation_password: "`riMe8x37Q{L",
        account_creation_password_confirmation: "`riMe8x37Q{L"
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
            "total_due":27.2,
            "product_due":25.9,
            "taxes_due":1.3,
            "shipping_due":0,
            "finance_due":0,
            "total_paid":27.2,
            "brand":"drumeo",
            "deleted_at":null,
            "created_at":"2019-05-02 16:52:39",
            "updated_at":"2019-05-02 16:52:39"
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
            "type":"product",
            "id":"1",
            "attributes":{
                "brand":"drumeo",
                "name":"aliquid",
                "sku":"distinctio6461867",
                "price":12.95,
                "type":"product",
                "active":true,
                "category":"voluptas",
                "description":"doloremque",
                "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?11290",
                "is_physical":false,
                "weight":0,
                "subscription_interval_type":"",
                "subscription_interval_count":0,
                "stock":248,
                "created_at":"2019-05-02 16:52:39",
                "updated_at":null
            }
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
                "quantity":2,
                "weight":0,
                "initial_price":12.95,
                "total_discounted":0,
                "final_price":25.9,
                "created_at":"2019-05-02 16:52:39",
                "updated_at":"2019-05-02 16:52:39"
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
                "type":"billing",
                "brand":"drumeo",
                "first_name":null,
                "last_name":null,
                "street_line_1":null,
                "street_line_2":null,
                "city":null,
                "zip":"32552-5376",
                "state":"illum",
                "country":"Canada",
                "created_at":"2019-05-02 16:52:39",
                "updated_at":"2019-05-02 16:52:39"
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
    ]
}
```