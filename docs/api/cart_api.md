# Cart API

# Form/Get Endpoints

### `{ GET /*/add-to-cart }`

Add a new item to the cart via a static href link (get request).

### Permissions

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|products|yes||must be an array, see notes below|
|query|locked||false|true/false|if true the quantities are locked, when a new item is added it clears the cart|
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
                    "description":"Omnis id consequuntur sit et reprehenderit. Quod dolores quod saepe accusantium nisi at. Vero nihil aperiam voluptas vel. Voluptates officia eius quo voluptatem hic.",
                    "stock":23,
                    "subscription_interval_type":"day",
                    "subscription_interval_count":9,
                    "price_before_discounts":865,
                    "price_after_discounts":865
                }
            ],
            "discounts":[

            ],
            "shipping_address":null,
            "billing_address":null,
            "number_of_payments":1,
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
                    "description":"Omnis id consequuntur sit et reprehenderit. Quod dolores quod saepe accusantium nisi at. Vero nihil aperiam voluptas vel. Voluptates officia eius quo voluptatem hic.",
                    "stock":23,
                    "subscription_interval_type":"day",
                    "subscription_interval_count":9,
                    "price_before_discounts":865,
                    "price_after_discounts":865
                }
            ],
            "discounts":[

            ],
            "shipping_address":null,
            "billing_address":null,
            "number_of_payments":1,
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
                    "description":"Omnis id consequuntur sit et reprehenderit. Quod dolores quod saepe accusantium nisi at. Vero nihil aperiam voluptas vel. Voluptates officia eius quo voluptatem hic.",
                    "stock":23,
                    "subscription_interval_type":"day",
                    "subscription_interval_count":9,
                    "price_before_discounts":865,
                    "price_after_discounts":865
                }
            ],
            "discounts":[

            ],
            "shipping_address":null,
            "billing_address":null,
            "number_of_payments":1,
            "totals":{
                "shipping":0,
                "tax":0,
                "due":1730
            }
        }
    }
}
```