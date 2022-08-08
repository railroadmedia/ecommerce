# Products API

[Table Schema](../schema/table-schema.md#table-ecommerce_products)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ GET /*/products }`

List products.

### Permissions

- All active products can be pulled without any special permissions or being logged in
- Must have the 'pull.inactive.products' permission to pull in-active products (admins)

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md) | [Branded](request_brand_filtering_parameters.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/products',
    data: {
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
            "type":"product",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "name":"non",
                "sku":"ut4101194",
                "price":227,
                "type":"digital subscription",
                "active":false,
                "category":"reiciendis",
                "description":"Labore et commodi a cum ut accusamus. Et blanditiis facilis accusamus officiis aut. Voluptas sit eaque nam veritatis. Quae sed fugiat est porro distinctio sint voluptas.",
                "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?67303",
                "is_physical":true,
                "weight":5,
                "subscription_interval_type":"year",
                "subscription_interval_count":7,
                "stock":402,
                "min_stock_level": 10,
                "auto_decrement_stock":true,
                "note":"some note",
                "created_at":"2019-04-30 21:29:48",
                "updated_at":null
            }
        }
    ],
    "meta":{
        "pagination":{
            "total":10,
            "count":10,
            "per_page":30,
            "current_page":1,
            "total_pages":1
        }
    },
    "links":{
        "self":"http:\/\/localhost\/product?page=1&limit=30&order_by_column=id&order_by_direction=asc",
        "first":"http:\/\/localhost\/product?page=1&limit=30&order_by_column=id&order_by_direction=asc",
        "last":"http:\/\/localhost\/product?page=1&limit=30&order_by_column=id&order_by_direction=asc"
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PUT /*/product }`

Create a new product.

### Permissions

- Must be logged in
- Must have the 'create.product' permission to create

### Request Parameters

[Notable](request_notable_parameter.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.type|yes||must be 'product'||
|body|data.attributes.name|yes||||
|body|data.attributes.brand|yes||||
|body|data.attributes.sku|yes||||
|body|data.attributes.price|yes||||
|body|data.attributes.type|yes||'digital subscription' or 'digital one time' or 'physical one time'||
|body|data.attributes.active|yes||boolean||
|body|data.attributes.category|yes||||
|body|data.attributes.description|yes||||
|body|data.attributes.thumbnail_url|yes||||
|body|data.attributes.is_physical|yes||boolean||
|body|data.attributes.weight|yes||||
|body|data.attributes.subscription_interval_type|yes if subscription||'month', 'year'||
|body|data.attributes.subscription_interval_count|yes if subscription||||
|body|data.attributes.stock||null|||
|body|data.attributes.min_stock_level||null|||
|body|data.attributes.auto_decrement_stock||false|||

### Validation Rules

```php
[
    'data.attributes.name' => 'required|max:255',
    'data.attributes.sku' => 'required|unique:'.'ecommerce_products'.',sku|max:255',
    'data.attributes.price' => 'required|numeric|min:0',
    'data.attributes.type' => 'required|max:255|in:' .
        implode(
            ',',
            [
                Product::TYPE_PHYSICAL_ONE_TIME,
                Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        ),
    'data.attributes.active' => 'required|boolean',
    'data.attributes.is_physical' => 'required|boolean',
    'data.attributes.weight' => 'required_if:data.attributes.is_physical,true',
    'data.attributes.stock' => 'nullable|numeric',
    'data.attributes.min_stock_level' => 'nullable|numeric',
    'data.attributes.auto_decrement_stock' => 'boolean',
    'data.attributes.subscription_interval_type' => 'required_if:data.attributes.type,' . Product::TYPE_DIGITAL_SUBSCRIPTION,
    'data.attributes.subscription_interval_count' => 'required_if:data.attributes.type,' . Product::TYPE_DIGITAL_SUBSCRIPTION
];
```

### Request Example

```json
{   
    "data": {
        "type": "product",
        "attributes": {
            "name": "sit",
            "sku": "omnis6239535",
            "price": 39,
            "type": "digital one time",
            "active": 0,
            "category": "assumenda",
            "description": "Et porro error laborum labore nobis",
            "thumbnail_url": "https://lorempixel.com/640/480/?72919",
            "is_physical": 1,
            "weight": 12,
            "subscription_interval_type": "month",
            "subscription_interval_count": 8,
            "stock": 620,
            "min_stock_level": 20,
            "auto_decrement_stock":true,
            "brand": "brand",
            "note": "some note", 
            "created_at": "2019-04-30 21:37:04"
        }
    }
}
```

### Response Example

```201 OK```

```json
{
    "data":{
        "type":"product",
        "id":"1",
        "attributes":{
            "brand":"brand",
            "name":"quasi",
            "sku":"laudantium4222365",
            "price":776,
            "type":"physical one time",
            "active":false,
            "category":"voluptas",
            "description":"Qui cum ipsam velit molestiae necessitatibus. Et est libero hic corporis dolorum. Ea rerum corporis soluta magnam.",
            "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?51545",
            "is_physical":false,
            "weight":71,
            "subscription_interval_type":"year",
            "subscription_interval_count":6,
            "stock":877,
            "min_stock_level": 20,
            "auto_decrement_stock":true,
            "note":"some note",
            "created_at":"2019-04-30 21:37:58",
            "updated_at":"2019-04-30 21:37:58"
        }
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PATCH /*/product/{ID} }`

Update an existing product.

### Permissions

- Must be logged in
- Must have the 'update.product' permission to create

### Request Parameters

[Notable](request_notable_parameter.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.type|yes||must be 'product'||
|body|data.attributes.name|||||
|body|data.attributes.brand|||||
|body|data.attributes.sku|||||
|body|data.attributes.price|||||
|body|data.attributes.type|||'digital subscription' or 'digital one time' or 'physical one time'||
|body|data.attributes.active|||boolean||
|body|data.attributes.category|||||
|body|data.attributes.description|||||
|body|data.attributes.thumbnail_url|||||
|body|data.attributes.is_physical|||boolean||
|body|data.attributes.weight|||||
|body|data.attributes.subscription_interval_type|||'month', 'year'||
|body|data.attributes.subscription_interval_count|||||
|body|data.attributes.stock||null|||
|body|data.attributes.min_stock_level||null|||
|body|data.attributes.auto_decrement_stock||false|||

### Validation Rules

```php
[
    'data.attributes.name' => 'max:255',
    'data.attributes.sku' => 'unique:'.'ecommerce_products'.',sku,'.Request::route('productId').'|max:255',
    'data.attributes.price' => 'numeric|min:0',
    'data.attributes.type' => 'max:255|in:' .
        implode(
            ',',
            [
                Product::TYPE_PHYSICAL_ONE_TIME,
                Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        ),
    'data.attributes.active' => 'boolean',
    'data.attributes.is_physical' => 'boolean',
    'data.attributes.weight' => 'required_if:data.attributes.is_physical,true',
    'data.attributes.stock' => 'numeric',
    'data.attributes.auto_decrement_stock' => 'boolean',
    'data.attributes.subscription_interval_type' => 'required_if:data.attributes.type,' . Product::TYPE_DIGITAL_SUBSCRIPTION,
    'data.attributes.subscription_interval_count' => 'required_if:data.attributes.type,' . Product::TYPE_DIGITAL_SUBSCRIPTION
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/product/1',
    type: 'patch', 
    data: {
        data: {
            type: "product",
            attributes: {
                name: "new-name",
                price: 199.22,
            },
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
        "type":"product",
        "id":"1",
        "attributes":{
            "brand":"brand",
            "name":"accusantium",
            "sku":"qui8237977",
            "price":624,
            "type":"digital subscription",
            "active":true,
            "category":"voluptatem",
            "description":"Quasi magnam inventore iste inventore tempora magnam ut. Hic dolorem quia qui natus sint autem. Vel sequi nobis culpa vel. Et perspiciatis quia et ad.",
            "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?91036",
            "is_physical":false,
            "weight":92,
            "subscription_interval_type":"year",
            "subscription_interval_count":2,
            "stock":870,
            "min_stock_level": 0,
            "auto_decrement_stock":false,
            "note":"some note",
            "created_at":"2019-04-30 21:45:45",
            "updated_at":"2019-04-30 21:45:45"
        }
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/product/{ID} }`

Delete an existing product.

_Uses soft deletes._

### Permissions

- Must be logged in
- Must have the 'delete.product' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|product id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/product/1',
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