# Discount Criteria API

[Table Schema](../schema/table-schema.md#table-ecommerce_discount_criteria)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ PUT /*/discount-criteria/{DISCOUNT ID} }`

Create a new discount criteria for a discount.

### Permissions

- Must be logged in
- Must have the 'create.discount.criteria' permission.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.type|yes||must be 'discountCriteria'||
|body|data.attributes.name|yes||||
|body|data.attributes.type|yes||'product quantity requirement', 'date requirement', 'order total requirement', 'shipping total requirement', 'shipping country requirement', 'promo code requirement', 'product own requirement'||
|body|data.attributes.products_relation_type|yes||'any of products', 'all of products'||
|body|data.relationships.products.data.*.id|yes| |an existing product id | |
|body|data.relationships.products.data.*.type|yes| |'product'| |
|body|data.attributes.min|yes|||to be set depending on the type|
|body|data.attributes.max|yes|||to be set depending on the type|

### Validation Rules

```php
[
    'data.type' => 'in:discountCriteria',
    'data.attributes.name' => 'required|max:255',
    'data.attributes.type' => 'required|max:255',
    'data.attributes.min' => 'required',
    'data.attributes.max' => 'required',
    'data.attributes.products_relation_type' => 'required|in:' . implode(
            ',',
            [
                DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY,
                DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            ]
        ),
    'data.relationships.products.data.*.type' => 'required|in:product',
    'data.relationships.products.data.*.id' => 'required|numeric|exists:ecommerce_products,id',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/discount-criteria',
    type: 'put',
    data: {
        data: {
            type: 'discountCriteria',
            attributes: {
                name: "aut",
                type: "autem",
                min: 5,
                max: 81,
                discount_id: 6,
                created_at: "2019-04-30 16:19:01",
            },
            relationships: {
                product: {
                    data: [
                        {
                            type: 'product',
                            id: 1
                        }
                    ]
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
        "type":"discountCriteria",
        "id":"1",
        "attributes":{
            "name":"necessitatibus",
            "type":"explicabo",
            "min":"6",
            "max":"62",
            "created_at":"2019-04-30 16:21:06",
            "updated_at":"2019-04-30 16:21:06"
        },
        "relationships":{
            "discount":{
                "data":{
                    "type":"discount",
                    "id":"1"
                }
            },
            "product":{
                "data":[
                    {
                        "type":"product",
                        "id":"2"
                    }
                ]
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
            "type":"discount",
            "id":"1",
            "attributes":{
                "name":"eos",
                "description":"Ut quam molestias esse. Ea hic id doloribus blanditiis. Ab perferendis ratione occaecati dignissimos. Hic asperiores quidem vero aperiam officia. Omnis aut dolorum consequatur dolor quo et aut.",
                "type":"hic",
                "amount":37,
                "product_category":null,
                "active":false,
                "visible":true,
                "created_at":"2019-04-30 16:21:06",
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
            "type":"product",
            "id":"2",
            "attributes":[

            ]
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PATCH /*/discount-criteria/{DISCOUNT CRITERIA ID} }`

Update an existing discount criteria.

### Permissions

- Must be logged in
- Must have the 'update.discount.criteria' permission.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|discount criteria id|yes||||
|body|data.type|yes||must be 'discountCriteria'||
|body|data.attributes.name|yes||||
|body|data.attributes.type|yes||'product quantity requirement', 'date requirement', 'order total requirement', 'shipping total requirement', 'shipping country requirement', 'promo code requirement', 'product own requirement'||
|body|data.attributes.products_relation_type|||'any of products', 'all of products'||
|body|data.relationships.products.data.*.id|yes| |an existing product id | |
|body|data.relationships.products.data.*.type|yes| |'product'| |
|body|data.attributes.min|yes|||to be set depending on the type|
|body|data.attributes.max|yes|||to be set depending on the type|

**NOTE: the associations with discount criteria products are rewritten on each update request, only with the ones specified in the request. In other words, if previous associated discount criteria products are not specified in the update request, the associations will be removed.**

### Validation Rules

```php
[
    'data.type' => 'in:discountCriteria',
    'data.attributes.name' => 'max:255',
    'data.attributes.type' => 'max:255',
    'data.attributes.min' => '',
    'data.attributes.max' => '',
    'data.attributes.products_relation_type' => 'in:' . implode(
            ',',
            [
                DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY,
                DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
            ]
        ),
    'data.relationships.products.data.*.type' => 'in:product',
    'data.relationships.products.data.*.id' => 'numeric|exists:ecommerce_products,id',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/discount-criteria/3',
    type: 'patch',
    data: {
        data: {
            type: 'discountCriteria',
            attributes: {
                min: 5,
                max: 81,
            },
            relationships: {
                product: {
                    data: [
                        {
                            "type":"product",
                            "id":"2"
                        },
                        {
                            "type":"product",
                            "id":"5"
                        }
                    ]
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
    "data":{
        "type":"discountCriteria",
        "id":"1",
        "attributes":{
            "name":"necessitatibus",
            "type":"explicabo",
            "min":"6",
            "max":"62",
            "created_at":"2019-04-30 16:21:06",
            "updated_at":"2019-04-30 16:21:06"
        },
        "relationships":{
            "discount":{
                "data":{
                    "type":"discount",
                    "id":"1"
                }
            },
            "product":{
                "data":[
                    {
                        "type":"product",
                        "id":"2"
                    },
                    {
                        "type":"product",
                        "id":"5"
                    }
                ]
            }
        }
    },
    "included":[
        {
            "type":"discount",
            "id":"1",
            "attributes":{
                "name":"eos",
                "description":"Ut quam molestias esse. Ea hic id doloribus blanditiis. Ab perferendis ratione occaecati dignissimos. Hic asperiores quidem vero aperiam officia. Omnis aut dolorum consequatur dolor quo et aut.",
                "type":"hic",
                "amount":37,
                "product_category":null,
                "active":false,
                "visible":true,
                "created_at":"2019-04-30 16:21:06",
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
            "type":"product",
            "id":"2",
            "attributes":[

            ]
        },
        {
            "type":"product",
            "id":"5",
            "attributes":[

            ]
        }
    ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/discount-criteria/{DISCOUNT CRITERIA ID} }`

Delete an existing discount criteria.

### Permissions

- Must be logged in
- Must have the 'delete.discount.criteria' permission.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|discount criteria id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/discount-criteria/1',
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