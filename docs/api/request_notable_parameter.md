# Notable Parameter

Some entities have a note column which can be changed in create and update requests. All these columns work the same.

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|body|note|||null or string||

### Request Example

```js   
$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/product/1',
    type: 'PATCH',
    data: {
        note: 'This product does some things!' 
    }, 
    success: function(response) {},
    error: function(response) {}
});
```