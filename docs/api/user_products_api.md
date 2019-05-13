# User Products API (previously known as 'user levels')

[Table Schema](../schema/table-schema.md#table-ecommerce_user_products)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ GET /*/user-product }`

List user products.

### Permissions

- Must be logged in
- Must have the 'pull.user-products' permission

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|user_id||logged in user id|||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/user-product',
    data: {
        user_id: 1,
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
            "type":"userProduct",
            "id":"1",
            "attributes":{
                "quantity":4,
                "expiration_date":"2019-05-13 20:36:41",
                "created_at":"2019-05-13 20:36:41",
                "updated_at":null
            },
            "relationships":{
                "user":{
                    "data":{
                        "type":"user",
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
        }
    ],
    "included":[
        {
            "type":"user",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"product",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "name":"ea",
                "sku":"itaque3414632",
                "price":857,
                "type":"product",
                "active":false,
                "category":"enim",
                "description":"Voluptas est necessitatibus.",
                "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?56734",
                "is_physical":true,
                "weight":50,
                "subscription_interval_type":"month",
                "subscription_interval_count":5,
                "stock":247,
                "created_at":"2019-05-13 20:36:41",
                "updated_at":null
            }
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
        "self":"http:\/\/localhost\/user-product?page=1",
        "first":"http:\/\/localhost\/user-product?page=1",
        "last":"http:\/\/localhost\/user-product?page=1"
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PUT /*/user-product }`

Create a new user product row.

### Permissions

- Must be logged in
- Must have the 'create.user-products' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.type|yes||should be 'userProduct'||
|body|data.attributes.quantity|yes||usually 1||
|body|data.attributes.expiration_date||null (meaning never expires)|datetime||
|body|data.relationships.product.data.type|yes||should be 'product'||
|body|data.relationships.product.data.id|yes||||
|body|data.relationships.user.data.type|yes||should be 'user'||
|body|data.relationships.user.data.id|yes||||

### Validation Rules

```php
[
    'data.type' => 'in:userProduct',
    'data.attributes.quantity' => 'required|numeric',
    'data.attributes.expiration_date' => 'date|nullable',
    'data.relationships.user.data.id' => 'required|integer',
    'data.relationships.product.data.id' => 'required|numeric|exists:' . ConfigService::$tableProduct . ',id',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/user-product',
    data: {
        type: "userProduct",
        attributes: {
            quantity: 3,
            expiration_date: "2019-05-13 20:36:41",
        },
        relationships: {
            user: {
                data: {
                    type: "user",
                    id: 1
                }
            },
            product: {
                data: {
                    type: "product",
                    id: 1
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
    "data":[
        {
            "type":"userProduct",
            "id":"1",
            "attributes":{
                "quantity":4,
                "expiration_date":"2019-05-13 20:36:41",
                "created_at":"2019-05-13 20:36:41",
                "updated_at":null
            },
            "relationships":{
                "user":{
                    "data":{
                        "type":"user",
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
        }
    ],
    "included":[
        {
            "type":"user",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"product",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "name":"ea",
                "sku":"itaque3414632",
                "price":857,
                "type":"product",
                "active":false,
                "category":"enim",
                "description":"Voluptas est necessitatibus.",
                "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?56734",
                "is_physical":true,
                "weight":50,
                "subscription_interval_type":"month",
                "subscription_interval_count":5,
                "stock":247,
                "created_at":"2019-05-13 20:36:41",
                "updated_at":null
            }
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PATCH /*/address/{ID} }`

Update an existing user product.

### Permissions

- Must be logged in
- Users can update addresses for themselves without any special permissions
- Must have the 'update.address' permission to update addresses for others users or customers

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.type|yes||should be 'userProduct'||
|body|data.attributes.quantity|||usually 1||
|body|data.attributes.expiration_date||null (meaning never expires)|datetime||
|body|data.relationships.product.data.type|||should be 'product'||
|body|data.relationships.product.data.id|||||
|body|data.relationships.user.data.type|||should be 'user'||
|body|data.relationships.user.data.id|||||

### Validation Rules

```php
[
    'data.type' => 'in:userProduct',
    'data.attributes.quantity' => 'numeric',
    'data.attributes.expiration_date' => 'date|nullable',
    'data.relationships.user.data.id' => 'integer',
    'data.relationships.product.data.id' => 'numeric|exists:' . ConfigService::$tableProduct . ',id',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/user-product/1',
    type: 'patch',
    data: {
        type: "userProduct",
        attributes: {
            quantity: 3,
        },
        relationships: {
            product: {
                data: {
                    type: "product",
                    id: 1
                }
            }
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
    "data":[
        {
            "type":"userProduct",
            "id":"1",
            "attributes":{
                "quantity":4,
                "expiration_date":"2019-05-13 20:36:41",
                "created_at":"2019-05-13 20:36:41",
                "updated_at":null
            },
            "relationships":{
                "user":{
                    "data":{
                        "type":"user",
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
        }
    ],
    "included":[
        {
            "type":"user",
            "id":"1",
            "attributes":[

            ]
        },
        {
            "type":"product",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "name":"ea",
                "sku":"itaque3414632",
                "price":857,
                "type":"product",
                "active":false,
                "category":"enim",
                "description":"Voluptas est necessitatibus.",
                "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?56734",
                "is_physical":true,
                "weight":50,
                "subscription_interval_type":"month",
                "subscription_interval_count":5,
                "stock":247,
                "created_at":"2019-05-13 20:36:41",
                "updated_at":null
            }
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/user-product/{ID} }`

Delete an existing user product.

### Permissions

- Must be logged in
- Must have the 'delete.user-products' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/user-product/1',
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