# Ordering Parameters

Many endpoints have the same standard ordering parameters which are outlined below.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|order_by_column|false|created_at|id, created_at, updated_at, _all other columns_|The column to order the code using.|
|query|order_by_direction|false|desc|desc, desc|Which direction to order.|

### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/example',
    data: {
        order_by_column: 'id', 
        order_by_direction: 'desc'
    }, 
    success: function(response) {},
    error: function(response) {}
});
```