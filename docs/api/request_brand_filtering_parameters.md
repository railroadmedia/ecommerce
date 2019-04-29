# Brand Filtering Parameters

Many endpoints can be restricted by an array of brands, that parameter is outlined below.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|brands||configured default brand|must be an array of brands|Limit results to codes that belong to specific brands.|

### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/example',
    data: {
        brands: ['drumeo', 'pianote']
    }, 
    success: function(response) {},
    error: function(response) {}
});
```
