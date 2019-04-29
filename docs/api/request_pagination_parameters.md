# Pagination Parameters

Many endpoints have the same standard pagination parameters which are outlined below.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|page||1||Which page to load, will be {limit} long.|
|query|limit||10||How many to load per page.|

### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/example',
    data: {
        page: 3, 
        limit: 1 
    }, 
    success: function(response) {},
    error: function(response) {}
});
```