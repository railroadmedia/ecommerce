# Access Codes API

[Table Schema](../schema/table-schema.md#table-ecommerce_access_codes)

The column names should be used as the keys for requests.

# Form Endpoints
### `{ POST /*/access-codes/redeem }`

Used to claim an action code for an existing or new user.

### Permissions

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|access_code|no|||The exact access code without dashes or spaces. If not specified, will concat all code1 . code2 . code3 . code4 . code5 . code6 request params|
|body|credentials_type|yes||values should be either 'new' or 'existing'||
|body|user_email|required if credentials_type param is 'existing'||||
|body|user_password|required if credentials_type param is 'existing'||||
|body|email|required if credentials_type param is 'new'|||Email for the new user to be created.|
|body|password|required if credentials_type param is 'new'|||Raw password for the new user to be created.|
|body|password_confirmation|required if credentials_type param is 'new'|||Confirm password.|
|query/body|redirect|true|previous url||Where to redirect after the request is processed.|


### Request Example

Create new account with full 24 chars access code

```html
<form method="post" action="/ecommerce/access-codes/redeem">
    <input type="hidden" name="credentials_type" value="new">
    <input type="hidden" name="redirect" value="/members">
    <input type="text" name="access_code">
    
    <input type="text" name="email">
    <input type="password" name="password">
    <input type="password" name="password_confirmation">
</form>
```

### Response Example

Redirect back with \['success' => true\] or redirect to passed in 'redirect' parameter with same message.

### Request Example

Create new account with access code split in six inputs of 4 chars each

```html
<form method="post" action="/ecommerce/access-codes/redeem">
    <input type="hidden" name="credentials_type" value="new">
    <input type="hidden" name="redirect" value="/members">
    <input type="text" name="code1">
    <input type="text" name="code2">
    <input type="text" name="code3">
    <input type="text" name="code4">
    <input type="text" name="code5">
    <input type="text" name="code6">

    <input type="text" name="email">
    <input type="password" name="password">
    <input type="password" name="password_confirmation">
</form>
```

### Response Example

Redirect back with \['success' => true\] or redirect to passed in 'redirect' parameter with same message.

### Request Example

Use existing account with access code split in six inputs of 4 chars each

```html
<form method="post" action="/ecommerce/access-codes/redeem">
    <input type="hidden" name="credentials_type" value="new">
    <input type="hidden" name="redirect" value="/members">
    <input type="text" name="code1">
    <input type="text" name="code2">
    <input type="text" name="code3">
    <input type="text" name="code4">
    <input type="text" name="code5">
    <input type="text" name="code6">

    <input type="text" name="user_email">
    <input type="password" name="user_password">
</form>
```

### Response Example

Redirect back with \['success' => true\] or redirect to passed in 'redirect' parameter with same message.

------------------------------------------------------------------------------------------------------------------------

# JSON Endpoints

### `{ GET /*/access-codes }`

List access codes.

### Permissions

- Must be logged in
- Must have the 'pull.access_codes' permission

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md) | [Branded](request_brand_filtering_parameters.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|order_by_column|false|created_at|id, code, is_claimed, claimer_id, claimed_on, brand, note, source, created_at, updated_at|The column to order the code using.|
|query|order_by_direction|false|desc|desc, desc|Which direction to order.|
|query|page||1||Which page to load, will be {limit} long.|
|query|limit||10||How many to load per page.|
|query|brands||configured default brand|must be an array of brands|Limit results to codes that belong to specific brands.|
|query|claimer_id|false|||Only return access codes claimed by this user|


### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/access-codes',
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
         "type":"accessCode",
         "id":"1",
         "attributes":  {
            "code":"52k063okcd1495o4b7qsl4sg",
            "brand":"brand",
            "product_ids":[
               1
            ],
            "is_claimed":true,
            "source": "thomann-2022",
            "claimed_on":"2019-04-23 21:03:51",
            "created_at":"2019-04-23 21:03:51",
            "updated_at":null
         },
         "relationships":{
            "claimer":{
               "data":{
                  "type":"user",
                  "id":"1"
               }
            },
            "product":{
               "data":[
                  {
                     "type":"product",
                     "id":"1"
                  }
               ]
            }
         }
      }
   ],
   "included":[
      {
         "type":"user",
         "id":"1",
         "attributes":[]
      },
      {
         "type":"product",
         "id":"1",
         "attributes":{
            "brand":"brand",
            "name":"ut",
            "sku":"et8586899",
            "price":142,
            "type":"subscription",
            "active":false,
            "category":"esse",
            "description":"Natus ut et vero. Quia explicabo odio expedita est aut officiis. Provident nulla sed debitis exercitationem sunt ut. Delectus praesentium excepturi magnam possimus itaque.",
            "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?73955",
            "is_physical":false,
            "weight":11,
            "subscription_interval_type":"year",
            "subscription_interval_count":1,
            "stock":430,
            "created_at":"2019-04-23 21:03:51",
            "updated_at":null
         }
      }
   ],
   "meta":{
      "pagination":{
         "total":195,
         "count":1,
         "per_page":1,
         "current_page":3,
         "total_pages":195
      }
   },
   "links":{
      "self":"http:\/\/localhost\/access-codes?page=3&limit=1&order_by_column=id&order_by_direction=desc",
      "first":"http:\/\/localhost\/access-codes?page=1&limit=1&order_by_column=id&order_by_direction=desc",
      "next":"http:\/\/localhost\/access-codes?page=4&limit=1&order_by_column=id&order_by_direction=desc",
      "last":"http:\/\/localhost\/access-codes?page=195&limit=1&order_by_column=id&order_by_direction=desc"
   }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ GET /*/access-codes/search }`

Search for access codes based on any part of the code.

### Permissions

- Must be logged in
- Must have the 'pull.access_codes' permission

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md) | [Branded](request_brand_filtering_parameters.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|order_by_column|false|created_at|id, code, is_claimed, claimer_id, claimed_on, brand, note, source, created_at, updated_at|The column to order the code using.|
|query|order_by_direction|false|desc|desc, desc|Which direction to order.|
|query|page||1||Which page to load, will be {limit} long.|
|query|limit||10||How many to load per page.|
|query|brands||configured default brand|must be an array of brands|Limit results to codes that belong to specific brands.|


### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/access-codes',
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
         "type":"accessCode",
         "id":"1",
         "attributes":{
            "code":"52k063okcd1495o4b7qsl4sg",
            "brand":"brand",
            "product_ids":[
               1
            ],
            "is_claimed":true,
            "source": "thomann-2022",
            "claimed_on":"2019-04-23 21:03:51",
            "created_at":"2019-04-23 21:03:51",
            "updated_at":null
         },
         "relationships":{
            "claimer":{
               "data":{
                  "type":"user",
                  "id":"1"
               }
            },
            "product":{
               "data":[
                  {
                     "type":"product",
                     "id":"1"
                  }
               ]
            }
         }
      }
   ],
   "included":[
      {
         "type":"user",
         "id":"1",
         "attributes":[]
      },
      {
         "type":"product",
         "id":"1",
         "attributes":{
            "brand":"brand",
            "name":"ut",
            "sku":"et8586899",
            "price":142,
            "type":"subscription",
            "active":false,
            "category":"esse",
            "description":"Natus ut et vero. Quia explicabo odio expedita est aut officiis. Provident nulla sed debitis exercitationem sunt ut. Delectus praesentium excepturi magnam possimus itaque.",
            "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?73955",
            "is_physical":false,
            "weight":11,
            "subscription_interval_type":"year",
            "subscription_interval_count":1,
            "stock":430,
            "created_at":"2019-04-23 21:03:51",
            "updated_at":null
         }
      }
   ],
   "meta":{
      "pagination":{
         "total":195,
         "count":1,
         "per_page":1,
         "current_page":3,
         "total_pages":195
      }
   },
   "links":{
      "self":"http:\/\/localhost\/access-codes?page=3&limit=1&order_by_column=id&order_by_direction=desc",
      "first":"http:\/\/localhost\/access-codes?page=1&limit=1&order_by_column=id&order_by_direction=desc",
      "next":"http:\/\/localhost\/access-codes?page=4&limit=1&order_by_column=id&order_by_direction=desc",
      "last":"http:\/\/localhost\/access-codes?page=195&limit=1&order_by_column=id&order_by_direction=desc"
   }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ POST /*/access-codes/claim }`

Used to claim an action code for an existing user.

### Permissions

- Must be logged in
- Must have the 'claim.access_codes' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|access_code|yes|||The exact access code without dashes or spaces.|
|body|claim_for_user_id|yes||||
|body|context|no||||

### Validation Rules

```php
[
    'access_code' => 'required|max:24|exists:' .
        config('ecommerce.database_connection_name') .
        '.' .
        'ecommerce_access_codes' .
        ',code,is_claimed,0',
    'claim_for_user_id' => 'required|integer',
    'context' => 'string|nullable',
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/access-codes/claim',
    type: 'post',
    data: {
        access_code: 'AAB6KN5DUBRPUTR6JUDUD4U8', 
        claim_for_user_id: '128762'
    }, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```201 OK```

```json
{
  "data": {
    "type": "accessCode",
    "id": "10",
    "attributes": {
      "code": "A5Z6K88IU92PU3B6J1SSD4U8",
      "brand": "drumeo",
      "product_ids": [
        124
      ],
      "is_claimed": true,
      "note": null,
      "source": null,
      "claimed_on": "2019-12-16 13:11:12",
      "created_at": "2015-10-21 16:39:53",
      "updated_at": "2019-12-16 13:11:12"
    },
    "relationships": {
      "claimer": {
        "data": {
          "type": "user",
          "id": "342760"
        }
      }
    }
  },
  "included": [
    {
      "type": "user",
      "id": "342760",
      "attributes": {
        "email": "reilly.fahey@emard.com",
      }
    }
  ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->

### `{ POST /*/access-codes/release }`

Used to release an action code.

### Permissions

- Must be logged in
- Must have the 'release.access_codes' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|access_code_id|yes|||The access code id to be released.|

### Validation Rules

```php
[
    'access_code_id' => 'required|max:24|exists:' .
        config('ecommerce.database_connection_name') .
        '.' .
        'ecommerce_access_codes' .
        ',id,is_claimed,1'
];
```

### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/access-codes/release',
    type: 'post',
    data: {
        access_code_id: '10'
    }, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

```201 OK```

```json
{
  "data": {
    "type": "accessCode",
    "id": "10",
    "attributes": {
      "code": "AAB6KN5DUBRPUTR6JUDUD4U8",
      "brand": "drumeo",
      "product_ids": [
        124
      ],
      "is_claimed": false,
      "note": null,
      "claimed_on": null,
      "source": "thomann-2022",
      "created_at": "2015-10-21 16:39:53",
      "updated_at": "2019-12-16 13:19:34"
    }
  }
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->
