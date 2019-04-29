# Addresses API

[Table Schema](../schema/table-schema.md#table-ecommerce_addresses)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ GET /*/address }`

List access codes.

### Permissions

- Must be logged in
- Users can always pull their own addresses
- Must have the 'pull.user.address' permission to pull others users addresses

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
        user_id: 2,
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
            "first_name":"Antonette",
            "last_name":"Hill",
            "street_line_1":"42295 Nicolas Island",
            "street_line_2":null,
            "city":"Armstrongmouth",
            "zip":"95885",
            "state":"voluptate",
            "country":"Equatorial Guinea",
            "created_at":"2019-04-29 17:53:40",
            "updated_at":"2019-04-29 17:53:40"
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
   ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->