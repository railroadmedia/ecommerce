# Access Codes API

# Form Endpoints
### `{ POST /*/access-codes/redeem }`

Used to claim an action code for an existing or new user.

### Permissions

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|access_code|true|||The exact access code without dashes or spaces.|
|body|email|if not logged in|||Email for the new user to be created.|
|body|password|if not logged in|||Raw password for the new user to be created.|
|body|password_confirmation|if not logged in|||Confirm password.|
|query/body|redirect|true|previous url||Where to redirect after the request is processed.|


### Request Example

```html
<form method="post" action="/ecommerce/access-codes/redeem">
    <input type="text" name="access_code">
    
    <input type="text" name="email">
    <input type="password" name="password">
    <input type="password" name="password_confirmation">
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

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|order_by_column|false|created_at|id, code, is_claimed, claimer_id, claimed_on, created_at, updated_at|The column to order the code using.|
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

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|order_by_column|false|created_at|id, code, is_claimed, claimer_id, claimed_on, created_at, updated_at|The column to order the code using.|
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
