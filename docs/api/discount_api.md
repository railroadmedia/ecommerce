# Addresses API

[Table Schema](../schema/table-schema.md#table-ecommerce_discounts)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ GET /*/discounts }`

List discounts.

### Permissions

- Must be logged in
- Must have the 'pull.discounts' permission 

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/address',
    data: {
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
            "type":"discount",
            "id":"1",
            "attributes":{
                "name":"quo",
                "description":"Soluta quisquam dolores quo repellat nihil voluptas voluptatem. Est minus qui ad ratione optio deleniti recusandae sunt.",
                "type":"iure",
                "amount":62,
                "product_category":null,
                "active":true,
                "visible":true,
                "created_at":"2019-04-30 17:32:40",
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
        }
    ],
    "included":[
        {
            "type":"product",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "name":"similique",
                "sku":"excepturi1826572",
                "price":470,
                "type":"product",
                "active":true,
                "category":"et",
                "description":"Quasi nostrum consequatur in. Et nobis sapiente voluptas consectetur. Debitis explicabo consequatur necessitatibus expedita deserunt. Repellat ducimus blanditiis odit est ut animi.",
                "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?61053",
                "is_physical":true,
                "weight":54,
                "subscription_interval_type":"month",
                "subscription_interval_count":12,
                "stock":119,
                "created_at":"2019-04-30 17:32:40",
                "updated_at":null
            }
        }
    ],
    "meta":{
        "pagination":{
            "total":25,
            "count":10,
            "per_page":10,
            "current_page":1,
            "total_pages":3
        }
    },
    "links":{
        "self":"http:\/\/localhost\/discounts?page=1&limit=10&order_by_direction=asc",
        "first":"http:\/\/localhost\/discounts?page=1&limit=10&order_by_direction=asc",
        "next":"http:\/\/localhost\/discounts?page=2&limit=10&order_by_direction=asc",
        "last":"http:\/\/localhost\/discounts?page=3&limit=10&order_by_direction=asc"
    }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ GET /*/discounts/{DISCOUNT ID} }`

Get a discount.

### Permissions

- Must be logged in
- Must have the 'pull.discounts' permission 

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|discount id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/discounts/1',
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```200 OK```

```json
{
    "data":{
        "type":"discount",
        "id":"1",
        "attributes":{
            "name":"minima",
            "description":"Aspernatur tempore similique accusantium qui sequi aut. Vitae sequi voluptatem deserunt voluptatem rerum cum molestias. Ut ut vel cumque vitae.",
            "type":"veritatis",
            "amount":18,
            "product_category":null,
            "active":false,
            "visible":false,
            "created_at":"2019-04-30 17:35:37",
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
    "included":[
        {
            "type":"product",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "name":"eveniet",
                "sku":"voluptate9758473",
                "price":894,
                "type":"product",
                "active":true,
                "category":"autem",
                "description":"Ut excepturi dicta atque. Enim corrupti voluptatem eius. Minima blanditiis perspiciatis repellendus cupiditate dolor consequatur velit qui. Recusandae reprehenderit quod dolore et quo ea assumenda.",
                "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?52176",
                "is_physical":false,
                "weight":67,
                "subscription_interval_type":"day",
                "subscription_interval_count":8,
                "stock":557,
                "created_at":"2019-04-30 17:35:37",
                "updated_at":null
            }
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PUT /*/discount }`

Create a new discount.

### Permissions

- Must be logged in
- Must have the 'create.discount' permission.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.type|yes||must be 'discount'||
|body|data.attributes.name|yes||||
|body|data.attributes.description|yes||||
|body|data.attributes.type|yes||||
|body|data.attributes.product_category|yes||||
|body|data.attributes.amount|yes||||
|body|data.attributes.active|yes||||
|body|data.attributes.visible|yes||||
|body|data.relationships.product.id|||||

### Validation Rules

```php
[
    'data.type' => 'in:discount',
    'data.attributes.name' => 'required|max:255',
    'data.attributes.description' => 'required|max:255',
    'data.attributes.type' => 'required|max:255',
    'data.attributes.amount' => 'required|numeric',
    'data.attributes.active' => 'required|boolean',
    'data.attributes.visible' => 'required|boolean',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/discount',
    type: 'put',
    data: {
        data: {
            type: 'discount',
            attributes: {
                name: "accusamus",
                description: "Fuga dolorem impedit sed. Delectus corporis et inventore.",
                type: "ipsam",
                amount: 85,
                product_id: 1,
                active: true,
                visible: false,
                created_at: "2019-04-30 17:45:11",
            },
            relationships: {
                product: {
                    data: {
                        type: 'product',
                        id: 1
                   }
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
        "type":"discount",
        "id":"1",
        "attributes":{
            "name":"minima",
            "description":"Aspernatur tempore similique accusantium qui sequi aut. Vitae sequi voluptatem deserunt voluptatem rerum cum molestias. Ut ut vel cumque vitae.",
            "type":"veritatis",
            "amount":18,
            "product_category":null,
            "active":false,
            "visible":false,
            "created_at":"2019-04-30 17:35:37",
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
    "included":[
        {
            "type":"product",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "name":"eveniet",
                "sku":"voluptate9758473",
                "price":894,
                "type":"product",
                "active":true,
                "category":"autem",
                "description":"Ut excepturi dicta atque. Enim corrupti voluptatem eius. Minima blanditiis perspiciatis repellendus cupiditate dolor consequatur velit qui. Recusandae reprehenderit quod dolore et quo ea assumenda.",
                "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?52176",
                "is_physical":false,
                "weight":67,
                "subscription_interval_type":"day",
                "subscription_interval_count":8,
                "stock":557,
                "created_at":"2019-04-30 17:35:37",
                "updated_at":null
            }
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PATCH /*/discount/{DISCOUNT ID} }`

Update an existing discount.

### Permissions

- Must be logged in
- Must have the 'update.discount' permission.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.type|yes||must be 'discount'||
|body|data.attributes.name|||||
|body|data.attributes.description|||||
|body|data.attributes.type|||||
|body|data.attributes.product_category|||||
|body|data.attributes.amount|||||
|body|data.attributes.active|||||
|body|data.attributes.visible|||||
|body|data.relationships.product.id|||||

### Validation Rules

```php
return [
    'data.type' => 'in:discount',
    'data.attributes.name' => 'max:255',
    'data.attributes.description' => 'max:255',
    'data.attributes.type' => 'max:255',
    'data.attributes.amount' => 'numeric',
    'data.attributes.active' => 'boolean',
    'data.attributes.visible' => 'boolean',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/discount/3',
    type: 'patch',
    data: {
        data: {
            type: 'discount',
            attributes: {
                name: "accusamus",
                amount: 85,
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
        "type":"discount",
        "id":"1",
        "attributes":{
            "name":"minima",
            "description":"Aspernatur tempore similique accusantium qui sequi aut. Vitae sequi voluptatem deserunt voluptatem rerum cum molestias. Ut ut vel cumque vitae.",
            "type":"veritatis",
            "amount":18,
            "product_category":null,
            "active":false,
            "visible":false,
            "created_at":"2019-04-30 17:35:37",
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
    "included":[
        {
            "type":"product",
            "id":"1",
            "attributes":{
                "brand":"brand",
                "name":"eveniet",
                "sku":"voluptate9758473",
                "price":894,
                "type":"product",
                "active":true,
                "category":"autem",
                "description":"Ut excepturi dicta atque. Enim corrupti voluptatem eius. Minima blanditiis perspiciatis repellendus cupiditate dolor consequatur velit qui. Recusandae reprehenderit quod dolore et quo ea assumenda.",
                "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?52176",
                "is_physical":false,
                "weight":67,
                "subscription_interval_type":"day",
                "subscription_interval_count":8,
                "stock":557,
                "created_at":"2019-04-30 17:35:37",
                "updated_at":null
            }
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/discount/{DISCOUNT ID} }`

Delete an existing discount.

### Permissions

- Must be logged in
- Must have the 'delete.discount' permission.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|discount id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/discount/1',
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