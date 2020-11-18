# Cart API

# Form/Get Endpoints

### `{ GET /*/add-to-cart }`

Add a new item to the cart via a static href link (get request).

### Permissions

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|products|yes||must be an array, see notes below|
|query|locked||false|true/false|if true the quantities are locked, previous cart items are removed, and when a new item is added it clears the current cart|
|query|promo-code|||||

'products' parameter MUST be an object with the product sku as the key and the quantity to add as the value. Quantities are added if the item already exists in the cart.

### Request Examples

```html
<a href="/ecommerce/add-to-cart?products[MY-SKU]=5&locked=true&promo-code=my-code&redirect=/shop">Add To Cart x5</a>
<a href="/ecommerce/add-to-cart?products[MY-SKU]=5&products[MY-OTHER-SKU]=1">Add Products To Cart</a>
```

### Response Example

Redirects to path send in 'redirect' parameter, or back by default. 

# JSON Endpoints

### `{ GET /*/json/cart }`

Returns the current content of the cart.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|

### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/json/cart',
    type: 'get',
    data: {}, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

Returns the entire cart.

```200 OK```

```json
{
    "data":null,
    "meta":{
        "cart":{
            "items":[
                {
                    "sku":"vel168022",
                    "name":"unde",
                    "quantity":2,
                    "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?29311",
                    "sales_page_url": "http:\\/\\/www.baumbach.com\\/laudantium-fuga-esse-optio-non-ad-velit",
                    "description":"Omnis id consequuntur sit et reprehenderit. Quod dolores quod saepe accusantium nisi at. Vero nihil aperiam voluptas vel. Voluptates officia eius quo voluptatem hic.",
                    "stock":23,
                    "subscription_interval_type":"day",
                    "subscription_interval_count":9,
                    "price_before_discounts":865,
                    "price_after_discounts":865,
                    "is_digital": false
                }
            ],
            "recommendedProducts": [
                {
                    "sku": "DLM-Trial-1-month",
                    "name": "Drumeo Edge 7-Day Trial",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?89656",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description": "Atque optio dolor at sint. Soluta facere doloremque amet consectetur rerum quo suscipit. Commodi illum sed qui voluptas consequuntur.",
                    "stock": 181,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 604,
                    "price_after_discounts": 604,
                    "requires_shipping": false,
                    "is_digital": false,
                    "add_directly_to_cart": true,
                    "cta": "7 Days Free, Then $29\\/mo"
                },
                {
                    "sku": "quietpad",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?97191",
                    "sales_page_url": "http:\\/\\/www.robel.com\\/voluptates-neque-ut-quis-eveniet-sapiente-non-odit",
                    "description": "Et dicta neque voluptatem magnam dolor. Tempore ab qui placeat. Non ex aliquam assumenda corporis. Voluptatem veritatis eos dolorum.",
                    "stock": 484,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 967,
                    "price_after_discounts": 967,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                },
                {
                    "sku": "Drumeo-VaterSticks",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?69658",
                    "sales_page_url": "https:\\/\\/stark.com\\/dolores-quia-sed-pariatur-facere-similique-beatae-exercitationem.html",
                    "description": "Enim quidem dolores asperiores vel. Similique dignissimos adipisci facere. Voluptatem dolor ut animi illo hic.",
                    "stock": 694,
                    "subscription_interval_type": "year",
                    "subscription_interval_count": 11,
                    "subscription_renewal_price": 362,
                    "price_before_discounts": 362,
                    "price_after_discounts": 362,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                }
            ],
            "discounts":[

            ],
            "shipping_address":null,
            "billing_address":null,
            "number_of_payments":1,
            "locked":0,
            "totals":{
                "shipping":0,
                "tax":0,
                "due":1730
            }
        }
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/json/clear-cart }`

Clears the cart of all items and data cache.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|

### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/json/clear-cart',
    type: 'delete',
    data: {}, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

Returns the empty cart.

```200 OK```

```json
{
    "data":null,
    "meta":{
        "cart":{
            "items":[

            ],
            "recommendedProducts": [
                {
                    "sku": "DLM-Trial-1-month",
                    "name": "Drumeo Edge 7-Day Trial",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?89656",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description": "Atque optio dolor at sint. Soluta facere doloremque amet consectetur rerum quo suscipit. Commodi illum sed qui voluptas consequuntur.",
                    "stock": 181,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 604,
                    "price_after_discounts": 604,
                    "requires_shipping": false,
                    "is_digital": false,
                    "add_directly_to_cart": true,
                    "cta": "7 Days Free, Then $29\\/mo"
                },
                {
                    "sku": "quietpad",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?97191",
                    "sales_page_url": "http:\\/\\/www.robel.com\\/voluptates-neque-ut-quis-eveniet-sapiente-non-odit",
                    "description": "Et dicta neque voluptatem magnam dolor. Tempore ab qui placeat. Non ex aliquam assumenda corporis. Voluptatem veritatis eos dolorum.",
                    "stock": 484,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 967,
                    "price_after_discounts": 967,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                },
                {
                    "sku": "Drumeo-VaterSticks",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?69658",
                    "sales_page_url": "https:\\/\\/stark.com\\/dolores-quia-sed-pariatur-facere-similique-beatae-exercitationem.html",
                    "description": "Enim quidem dolores asperiores vel. Similique dignissimos adipisci facere. Voluptatem dolor ut animi illo hic.",
                    "stock": 694,
                    "subscription_interval_type": "year",
                    "subscription_interval_count": 11,
                    "subscription_renewal_price": 362,
                    "price_before_discounts": 362,
                    "price_after_discounts": 362,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                }
            ],
            "discounts":[

            ],
            "shipping_address":null,
            "billing_address":null,
            "number_of_payments":1,
            "locked":0,
            "totals":{
                "shipping":0,
                "tax":0,
                "due":0
            }
        }
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PUT /*/json/add-to-cart }`

Add new item to the cart.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|products|yes||must be an array, see notes below|
|query|locked||false|true/false|if true the quantities are locked, when a new item is added it clears the cart|
|query|promo-code|||||

'products' parameter MUST be an object with the product sku as the key and the quantity to add as the value. Quantities are added if the item already exists in the cart.


### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/json/add-to-cart',
    type: 'put',
    data: {
        products: {
            "my-sku": 1,
            "my-other-sku": 3,
        }
    }, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

Returns the entire cart.

```200 OK```

```json
{
    "data":null,
    "meta":{
        "cart":{
            "items":[
                {
                    "sku":"vel168022",
                    "name":"unde",
                    "quantity":2,
                    "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?29311",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description":"Omnis id consequuntur sit et reprehenderit. Quod dolores quod saepe accusantium nisi at. Vero nihil aperiam voluptas vel. Voluptates officia eius quo voluptatem hic.",
                    "stock":23,
                    "subscription_interval_type":"day",
                    "subscription_interval_count":9,
                    "price_before_discounts":865,
                    "price_after_discounts":865,
                    "is_digital": true
                }
            ],
            "recommendedProducts": [
                {
                    "sku": "DLM-Trial-1-month",
                    "name": "Drumeo Edge 7-Day Trial",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?89656",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description": "Atque optio dolor at sint. Soluta facere doloremque amet consectetur rerum quo suscipit. Commodi illum sed qui voluptas consequuntur.",
                    "stock": 181,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 604,
                    "price_after_discounts": 604,
                    "requires_shipping": false,
                    "is_digital": false,
                    "add_directly_to_cart": true,
                    "cta": "7 Days Free, Then $29\\/mo"
                },
                {
                    "sku": "quietpad",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?97191",
                    "sales_page_url": "http:\\/\\/www.robel.com\\/voluptates-neque-ut-quis-eveniet-sapiente-non-odit",
                    "description": "Et dicta neque voluptatem magnam dolor. Tempore ab qui placeat. Non ex aliquam assumenda corporis. Voluptatem veritatis eos dolorum.",
                    "stock": 484,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 967,
                    "price_after_discounts": 967,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                },
                {
                    "sku": "Drumeo-VaterSticks",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?69658",
                    "sales_page_url": "https:\\/\\/stark.com\\/dolores-quia-sed-pariatur-facere-similique-beatae-exercitationem.html",
                    "description": "Enim quidem dolores asperiores vel. Similique dignissimos adipisci facere. Voluptatem dolor ut animi illo hic.",
                    "stock": 694,
                    "subscription_interval_type": "year",
                    "subscription_interval_count": 11,
                    "subscription_renewal_price": 362,
                    "price_before_discounts": 362,
                    "price_after_discounts": 362,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                }
            ],
            "discounts":[

            ],
            "shipping_address":null,
            "billing_address":null,
            "number_of_payments":1,
            "locked":0,
            "locked":0,
            "totals":{
                "shipping":0,
                "tax":0,
                "due":1730
            }
        }
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/json/remove-from-cart/{PRODUCT SKU} }`

Remove item from the cart.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|product_sku|yes||sku of the product to remove|

### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/json/remove-from-cart/my-sku',
    type: 'delete',
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

Returns the entire cart.

```200 OK```

```json
{
    "data":null,
    "meta":{
        "cart":{
            "items":[
                {
                    "sku":"vel168022",
                    "name":"unde",
                    "quantity":2,
                    "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?29311",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description":"Omnis id consequuntur sit et reprehenderit. Quod dolores quod saepe accusantium nisi at. Vero nihil aperiam voluptas vel. Voluptates officia eius quo voluptatem hic.",
                    "stock":23,
                    "subscription_interval_type":"day",
                    "subscription_interval_count":9,
                    "price_before_discounts":865,
                    "price_after_discounts":865,
                    "is_digital": false
                }
            ],
            "recommendedProducts": [
                {
                    "sku": "DLM-Trial-1-month",
                    "name": "Drumeo Edge 7-Day Trial",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?89656",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description": "Atque optio dolor at sint. Soluta facere doloremque amet consectetur rerum quo suscipit. Commodi illum sed qui voluptas consequuntur.",
                    "stock": 181,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 604,
                    "price_after_discounts": 604,
                    "requires_shipping": false,
                    "is_digital": false,
                    "add_directly_to_cart": true,
                    "cta": "7 Days Free, Then $29\\/mo"
                },
                {
                    "sku": "quietpad",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?97191",
                    "sales_page_url": "http:\\/\\/www.robel.com\\/voluptates-neque-ut-quis-eveniet-sapiente-non-odit",
                    "description": "Et dicta neque voluptatem magnam dolor. Tempore ab qui placeat. Non ex aliquam assumenda corporis. Voluptatem veritatis eos dolorum.",
                    "stock": 484,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 967,
                    "price_after_discounts": 967,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                },
                {
                    "sku": "Drumeo-VaterSticks",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?69658",
                    "sales_page_url": "https:\\/\\/stark.com\\/dolores-quia-sed-pariatur-facere-similique-beatae-exercitationem.html",
                    "description": "Enim quidem dolores asperiores vel. Similique dignissimos adipisci facere. Voluptatem dolor ut animi illo hic.",
                    "stock": 694,
                    "subscription_interval_type": "year",
                    "subscription_interval_count": 11,
                    "subscription_renewal_price": 362,
                    "price_before_discounts": 362,
                    "price_after_discounts": 362,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                }
            ],
            "discounts":[

            ],
            "shipping_address":null,
            "billing_address":null,
            "number_of_payments":1,
            "locked":0,
            "totals":{
                "shipping":0,
                "tax":0,
                "due":1730
            }
        }
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PATCH /*/json/update-product-quantity/{PRODUCT SKU}/{NEW QUANTITY} }`

Update item quantity in the cart.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|product_sku|yes||sku of the product to remove|
|path|quantity|yes||new quantity|

### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/json/update-product-quantity/my-sku/3',
    type: 'patch',
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

Returns the entire cart.

```200 OK```

```json
{
    "data":null,
    "meta":{
        "cart":{
            "items":[
                {
                    "sku":"vel168022",
                    "name":"unde",
                    "quantity":2,
                    "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?29311",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description":"Omnis id consequuntur sit et reprehenderit. Quod dolores quod saepe accusantium nisi at. Vero nihil aperiam voluptas vel. Voluptates officia eius quo voluptatem hic.",
                    "stock":23,
                    "subscription_interval_type":"day",
                    "subscription_interval_count":9,
                    "price_before_discounts":865,
                    "price_after_discounts":865
                }
            ],
            "recommendedProducts": [
                {
                    "sku": "DLM-Trial-1-month",
                    "name": "Drumeo Edge 7-Day Trial",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?89656",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description": "Atque optio dolor at sint. Soluta facere doloremque amet consectetur rerum quo suscipit. Commodi illum sed qui voluptas consequuntur.",
                    "stock": 181,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 604,
                    "price_after_discounts": 604,
                    "requires_shipping": false,
                    "is_digital": false,
                    "add_directly_to_cart": true,
                    "cta": "7 Days Free, Then $29\\/mo"
                },
                {
                    "sku": "quietpad",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?97191",
                    "sales_page_url": "http:\\/\\/www.robel.com\\/voluptates-neque-ut-quis-eveniet-sapiente-non-odit",
                    "description": "Et dicta neque voluptatem magnam dolor. Tempore ab qui placeat. Non ex aliquam assumenda corporis. Voluptatem veritatis eos dolorum.",
                    "stock": 484,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 967,
                    "price_after_discounts": 967,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                },
                {
                    "sku": "Drumeo-VaterSticks",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?69658",
                    "sales_page_url": "https:\\/\\/stark.com\\/dolores-quia-sed-pariatur-facere-similique-beatae-exercitationem.html",
                    "description": "Enim quidem dolores asperiores vel. Similique dignissimos adipisci facere. Voluptatem dolor ut animi illo hic.",
                    "stock": 694,
                    "subscription_interval_type": "year",
                    "subscription_interval_count": 11,
                    "subscription_renewal_price": 362,
                    "price_before_discounts": 362,
                    "price_after_discounts": 362,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                }
            ],
            "discounts":[

            ],
            "shipping_address":null,
            "billing_address":null,
            "number_of_payments":1,
            "locked":0,
            "totals":{
                "shipping":0,
                "tax":0,
                "due":1730
            }
        }
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PATCH /*/json/update-total-overrides }`

Update total overrides in the cart.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|product_taxes_due_override| | | |'place-orders-for-other-users' permission is required to override amounts|
|body|shipping_taxes_due_override| | | |'place-orders-for-other-users' permission is required to override amounts|
|body|shipping_due_override| | | |'place-orders-for-other-users' permission is required to override amounts|
|body|order_items_due_overrides| | |array of arrays: ['sku' => 'MYSKU', 'amount' => 100]|'place-orders-for-other-users' permission is required to override amounts|

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/json/update-total-overrides',
    data: {
        product_taxes_due_override: 16,
        shipping_taxes_due_override: 8,
        shipping_due_override: 31,
        order_items_due_overrides: [
            {
                'sku': 'MY-SKU',   
                'amount': 13,   
            },
            {
                'sku': 'MY-SKU-2',   
                'amount': 51,   
            },
        ]
    },
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

Returns the entire cart with the new totals.

```200 OK```

```json
{
    "data":null,
    "meta":{
        "cart":{
            "items":[
                {
                    "sku":"vel168022",
                    "name":"unde",
                    "quantity":2,
                    "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?29311",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description":"Omnis id consequuntur sit et reprehenderit. Quod dolores quod saepe accusantium nisi at. Vero nihil aperiam voluptas vel. Voluptates officia eius quo voluptatem hic.",
                    "stock":23,
                    "subscription_interval_type":"day",
                    "subscription_interval_count":9,
                    "price_before_discounts":865,
                    "price_after_discounts":865
                }
            ],
            "recommendedProducts": [
                {
                    "sku": "DLM-Trial-1-month",
                    "name": "Drumeo Edge 7-Day Trial",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?89656",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description": "Atque optio dolor at sint. Soluta facere doloremque amet consectetur rerum quo suscipit. Commodi illum sed qui voluptas consequuntur.",
                    "stock": 181,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 604,
                    "price_after_discounts": 604,
                    "requires_shipping": false,
                    "is_digital": false,
                    "add_directly_to_cart": true,
                    "cta": "7 Days Free, Then $29\\/mo"
                },
                {
                    "sku": "quietpad",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?97191",
                    "sales_page_url": "http:\\/\\/www.robel.com\\/voluptates-neque-ut-quis-eveniet-sapiente-non-odit",
                    "description": "Et dicta neque voluptatem magnam dolor. Tempore ab qui placeat. Non ex aliquam assumenda corporis. Voluptatem veritatis eos dolorum.",
                    "stock": 484,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 967,
                    "price_after_discounts": 967,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                },
                {
                    "sku": "Drumeo-VaterSticks",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?69658",
                    "sales_page_url": "https:\\/\\/stark.com\\/dolores-quia-sed-pariatur-facere-similique-beatae-exercitationem.html",
                    "description": "Enim quidem dolores asperiores vel. Similique dignissimos adipisci facere. Voluptatem dolor ut animi illo hic.",
                    "stock": 694,
                    "subscription_interval_type": "year",
                    "subscription_interval_count": 11,
                    "subscription_renewal_price": 362,
                    "price_before_discounts": 362,
                    "price_after_discounts": 362,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                }
            ],
            "discounts":[

            ],
            "shipping_address":null,
            "billing_address":null,
            "number_of_payments":1,
            "locked":0,
            "totals":{
                "shipping":11,
                "tax":24,
                "due":1730
            }
        }
    }
}
```

### `{ PUT /*/json/session-address }`

Updates the session addresses for the cart.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|billing_address_id||||
|body|billing_email||||
|body|billing_country||||
|body|billing_region||||
|body|billing_zip_or_postal_code||||
|body|shipping_address_id||||
|body|shipping_address_line_1||||
|body|shipping_city||||
|body|shipping_country||||
|body|shipping_first_name||||
|body|shipping_last_name||||
|body|shipping_region||||
|body|shipping_zip||||

### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/json/session-address',
    type: 'put',
    data: {
        billing_country: 'Canada',
        billing_region: 'Alberta'
    },
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

Returns the entire cart.

```200 OK```

```json
{
    "data":null,
    "meta":{
        "cart":{
            "items":[
                {
                    "sku":"vel168022",
                    "name":"unde",
                    "quantity":2,
                    "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?29311",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description":"Omnis id consequuntur sit et reprehenderit. Quod dolores quod saepe accusantium nisi at. Vero nihil aperiam voluptas vel. Voluptates officia eius quo voluptatem hic.",
                    "stock":23,
                    "subscription_interval_type":"day",
                    "subscription_interval_count":9,
                    "price_before_discounts":865,
                    "price_after_discounts":865
                }
            ],
            "recommendedProducts": [
                {
                    "sku": "DLM-Trial-1-month",
                    "name": "Drumeo Edge 7-Day Trial",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?89656",
                    "sales_page_url": "http:\\/\\/www.hoppe.com\\/ea-sed-enim-aut-sit-saepe",
                    "description": "Atque optio dolor at sint. Soluta facere doloremque amet consectetur rerum quo suscipit. Commodi illum sed qui voluptas consequuntur.",
                    "stock": 181,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 604,
                    "price_after_discounts": 604,
                    "requires_shipping": false,
                    "is_digital": false,
                    "add_directly_to_cart": true,
                    "cta": "7 Days Free, Then $29\\/mo"
                },
                {
                    "sku": "quietpad",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?97191",
                    "sales_page_url": "http:\\/\\/www.robel.com\\/voluptates-neque-ut-quis-eveniet-sapiente-non-odit",
                    "description": "Et dicta neque voluptatem magnam dolor. Tempore ab qui placeat. Non ex aliquam assumenda corporis. Voluptatem veritatis eos dolorum.",
                    "stock": 484,
                    "subscription_interval_type": null,
                    "subscription_interval_count": null,
                    "subscription_renewal_price": null,
                    "price_before_discounts": 967,
                    "price_after_discounts": 967,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                },
                {
                    "sku": "Drumeo-VaterSticks",
                    "name": "sit",
                    "quantity": 1,
                    "thumbnail_url": "https:\\/\\/lorempixel.com\\/640\\/480\\/?69658",
                    "sales_page_url": "https:\\/\\/stark.com\\/dolores-quia-sed-pariatur-facere-similique-beatae-exercitationem.html",
                    "description": "Enim quidem dolores asperiores vel. Similique dignissimos adipisci facere. Voluptatem dolor ut animi illo hic.",
                    "stock": 694,
                    "subscription_interval_type": "year",
                    "subscription_interval_count": 11,
                    "subscription_renewal_price": 362,
                    "price_before_discounts": 362,
                    "price_after_discounts": 362,
                    "requires_shipping": false,
                    "is_digital": true,
                    "add_directly_to_cart": true
                }
            ],
            "discounts":[

            ],
            "shipping_address":null,
            "billing_address": {
                "zip_or_postal_code": null,
                "street_line_two": null,
                "street_line_one": null,
                "last_name": null,
                "first_name": null,
                "region": "Alberta",
                "country": "Canada",
                "city": null,
            },
            "number_of_payments":1,
            "locked":0,
            "totals":{
                "shipping":0,
                "tax":0,
                "due":1730
            }
        }
    }
}
```
