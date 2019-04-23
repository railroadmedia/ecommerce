# Access Codes API

## Form Endpoints
### { POST /*/access-codes/redeem }

Used to claim an action code for an existing or new user.

### Request Parameters

|Type|Key|Required|Default|Notes|
|----|---|--------|-------|-----|
|body|access_code|true||The exact access code without dashes or spaces.|
|body|email|true|if not logged in|Email for the new user to be created.|
|body|password|true|if not logged in|Raw password for the new user to be created.|
|body|password_confirmation|true|if not logged in|Confirm password|
|query/body|redirect|true|back with session message: \['success' => true\]|Where to redirect after the request is processed.|


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