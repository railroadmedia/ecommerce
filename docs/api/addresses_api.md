# Addresses API

[Table Schema](../schema/table-schema.md#table-ecommerce_addresses)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ GET /*/address }`

List addresses.

### Permissions

- Must be logged in
- Users can pull their own addresses without any special permissions
- Must have the 'pull.addresses' permission to pull others users or customers addresses

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md) | [Branded](request_brand_filtering_parameters.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|user_id||logged in user id|||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/address',
    data: {
        user_id: 1,
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
         "type":"address",
         "id":"1",
         "attributes":{
            "type":"billing",
            "brand":"brand",
            "first_name":"Sean",
            "last_name":"Welch",
            "street_line_1":"6284 Brandy Crest",
            "street_line_2":null,
            "city":"Lake Adanchester",
            "zip":"26707-9218",
            "state":"sunt",
            "country":"The Gambia",
            "created_at":"2019-04-29 18:26:45",
            "updated_at":"2019-04-29 18:26:45"
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
   ],
   "included":[
      {
         "type":"user",
         "id":"1",
         "attributes":[

         ]
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
      "self":"http:\/\/localhost\/address?page=1",
      "first":"http:\/\/localhost\/address?page=1",
      "last":"http:\/\/localhost\/address?page=1"
   }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PUT /*/address }`

Create a new address.

### Permissions

- Must be logged in
- Users can create new addresses for themselves without any special permissions
- Must have the 'store.address' permission to create addresses for others users or customers

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.type|yes||should be 'address'||
|body|data.attributes.brand|yes||||
|body|data.attributes.type|yes||'billing' or 'shipping'||
|body|data.attributes.first_name|||||
|body|data.attributes.last_name|||||
|body|data.attributes.street_line_1|||||
|body|data.attributes.street_line_2|||||
|body|data.attributes.city|||||
|body|data.attributes.zip|||||
|body|data.attributes.state|yes||||
|body|data.attributes.country|yes||any country name||
|body|data.relationships.customer.data.type|yes if no user specified||should be 'customer'||
|body|data.relationships.customer.data.id|yes if no user specified||||
|body|data.relationships.user.data.type|yes if no customer specified||should be 'user'||
|body|data.relationships.user.data.id|yes if no customer specified||||

### Validation Rules

```php
[
    'data.type' => 'in:address',
    'data.attributes.type' => 'required|max:255|in:' . implode(
            ',',
            [
                'billing',
                'shipping',
            ]
        ),
    'data.attributes.first_name' => 'nullable|max:255',
    'data.attributes.last_name' => 'nullable|max:255',
    'data.attributes.street_line_1' => 'nullable|max:255',
    'data.attributes.street_line_2' => 'nullable|max:255',
    'data.attributes.city' => 'nullable|max:255',
    'data.attributes.zip' => 'nullable|max:255',
    'data.attributes.state' => 'nullable|max:255',
    'data.attributes.country' => 'required|max:255|in:' . implode(',', LocationService::countries()),
    'data.relationships.customer.data.type' => 'nullable|in:customer',
    'data.relationships.customer.data.id' => 'integer|nullable|exists:' . 'ecommerce_customers' . ',id',
    'data.relationships.user.data.type' => 'nullable|in:user',
    'data.relationships.user.data.id' => 'integer|nullable',
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
            type: "shipping",
            brand: "brand",
            first_name: "Silas",
            last_name: "Luettgen",
            street_line_1: "635 Hagenes Prairie Apt. 664",
            street_line_2: null,
            city: "South Houston",
            zip: "33348-7377",
            state: "sed",
            country: "Ireland",
            created_at: "2019-04-29 18:46:48",
            updated_at: "2019-04-29 18:46:48"
        },
        relationships: {
            user: {
                data: {
                    type: "user",
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
   "data":{
      "type":"address",
      "id":"1",
      "attributes":{
         "type":"billing",
         "brand":"brand",
         "first_name":"Carmine",
         "last_name":"Harvey",
         "street_line_1":"386 Joannie Row",
         "street_line_2":null,
         "city":"New Lilyburgh",
         "zip":"14241",
         "state":"rerum",
         "country":"Czech Republic",
         "created_at":"2019-04-29 18:49:41",
         "updated_at":"2019-04-29 18:49:41"
      },
      "relationships":{
         "user":{
            "data":{
               "type":"user",
               "id":"1"
            }
         }
      }
   },
   "included":[
      {
         "type":"user",
         "id":"1",
         "attributes":[

         ]
      }
   ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ PATCH /*/address/{ID} }`

Update an existing address.

### Permissions

- Must be logged in
- Users can update addresses for themselves without any special permissions
- Must have the 'update.address' permission to update addresses for others users or customers

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|data.attributes.brand|||||
|body|data.attributes.type|yes||'billing' or 'shipping'||
|body|data.attributes.first_name|||||
|body|data.attributes.last_name|||||
|body|data.attributes.street_line_1|||||
|body|data.attributes.street_line_2|||||
|body|data.attributes.city|||||
|body|data.attributes.zip|||||
|body|data.attributes.state|yes||||
|body|data.attributes.country|yes||any country name||
|body|data.relationships.customer.data.type|||should be 'customer'||
|body|data.relationships.customer.data.id|||||
|body|data.relationships.user.data.type|||should be 'user'||
|body|data.relationships.user.data.id|||||

### Validation Rules

```php
[
    'data.id' => 'exists:' . 'ecommerce_addresses' . ',id',
    'data.type' => 'in:address',
    'data.attributes.type' => 'max:255|in:' . implode(
            ',',
            [
                'billing',
                'shipping',
            ]
        ),
    'data.attributes.first_name' => 'nullable|max:255',
    'data.attributes.last_name' => 'nullable|max:255',
    'data.attributes.street_line_1' => 'nullable|max:255',
    'data.attributes.street_line_2' => 'nullable|max:255',
    'data.attributes.city' => 'nullable|max:255',
    'data.attributes.zip' => 'nullable|max:255',
    'data.attributes.state' => 'nullable|max:255',
    'data.attributes.country' => 'max:255|in:' . implode(',', LocationService::countries()),
    'data.relationships.customer.data.type' => 'nullable|in:customer',
    'data.relationships.customer.data.id' => 'integer|nullable|exists:' . 'ecommerce_customers' . ',id',
    'data.relationships.user.data.type' => 'nullable|in:user',
    'data.relationships.user.data.id' => 'integer|nullable',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/address/1',
    type: 'patch', 
    data: {
        type: "address",
        attributes: {
            type: "shipping",
            brand: "brand",
            first_name: "Silas",
            last_name: "Luettgen",
            street_line_1: "635 Hagenes Prairie Apt. 664",
            street_line_2: null,
            city: "South Houston",
            zip: "33348-7377",
            state: "sed",
            country: "Ireland",
            created_at: "2019-04-29 18:46:48",
            updated_at: "2019-04-29 18:46:48"
        },
        relationships: {
            user: {
                data: {
                    type: "user",
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
   "data":{
      "type":"address",
      "id":"1",
      "attributes":{
         "type":"billing",
         "brand":"brand",
         "first_name":"Carmine",
         "last_name":"Harvey",
         "street_line_1":"386 Joannie Row",
         "street_line_2":null,
         "city":"New Lilyburgh",
         "zip":"14241",
         "state":"rerum",
         "country":"Czech Republic",
         "created_at":"2019-04-29 18:49:41",
         "updated_at":"2019-04-29 18:49:41"
      },
      "relationships":{
         "user":{
            "data":{
               "type":"user",
               "id":"1"
            }
         }
      }
   },
   "included":[
      {
         "type":"user",
         "id":"1",
         "attributes":[

         ]
      }
   ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ DELETE /*/address/{ID} }`

Delete an existing address.

_Uses soft deletes._

### Permissions

- Must be logged in
- Users can delete addresses for themselves without any special permissions
- Must have the 'delete.address' permission to update addresses for others users or customers

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|path|id|yes||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/address/1',
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