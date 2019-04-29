# Access Codes API

[Table Schema](../schema/table-schema.md#anchor)

The column names should be used as the keys for requests.

# Form Endpoints
### `{ METHOD /*/-url- }`

Description.

### Permissions

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|

### Request Example

```html
<form method="post" action="/url">
    <input type="text" name="input">
</form>
```

### Response Example

------------------------------------------------------------------------------------------------------------------------

# JSON Endpoints

### `{ METHOD /*/-url- }`

Description.

### Permissions

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md) | [Branded](request_brand_filtering_parameters.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/url',
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

   ]
}
```

<!--- -------------------------------------------------------------------------------------------------------------- -->