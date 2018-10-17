Ecommerce
========================================================================================================================

- [Ecommerce](#ecommerce)
  * [Install](#install)
  * [API Reference](#api-reference)
    + [Add item to cart - forms controller](#add-item-to-cart---forms-controller)
      - [Request Example](#request-example)
      - [Request Parameters](#request-parameters)
      - [Response Example](#response-example)
    + [Remove a product from cart - JSON](#remove-a-product-from-cart---json)
      - [Request Example](#request-example-1)
      - [Request Parameters](#request-parameters-1)
      - [Response Example](#response-example-1)
    + [Update product quantity on cart - JSON controller](#update-product-quantity-on-cart---json-controller)
      - [Request Example](#request-example-2)
      - [Request Parameters](#request-parameters-2)
      - [Response Example](#response-example-2)
    + [Save shipping and billing addresses on the session - JSON controller](#save-shipping-and-billing-addresses-on-the-session---json-controller)
      - [Request Example](#request-example-3)
      - [Request Parameters](#request-parameters-3)
      - [Response Example](#response-example-3)
    + [Get all products - JSON controller](#get-all-products---json-controller)
      - [Request Example](#request-example-4)
      - [Request Parameters](#request-parameters-4)
      - [Response Example](#response-example-4)
    + [Add a new product - JSON controller](#add-a-new-product---json-controller)
      - [Request Example](#request-example-5)
      - [Request Parameters](#request-parameters-5)
      - [Response Example](#response-example-5)
    + [Update product - JSON controller](#update-product---json-controller)
      - [Request Example](#request-example-6)
      - [Request Parameters](#request-parameters-6)
      - [Response Example](#response-example-6)
    + [Delete product - JSON controller](#delete-product---json-controller)
      - [Request Example](#request-example-7)
      - [Request Parameters](#request-parameters-7)
      - [Response Example](#response-example-7)
    + [Upload product thumbnail - JSON controller](#upload-product-thumbnail---json-controller)
      - [Request Example](#request-example-8)
      - [Request Parameters](#request-parameters-8)
      - [Response Example](#response-example-8)
    + [Get all shipping options - JSON controller](#get-all-shipping-options---json-controller)
      - [Request Example](#request-example-9)
      - [Request Parameters](#request-parameters-9)
      - [Response Example](#response-example-9)
    + [Add a new shipping option - JSON controller](#add-a-new-shipping-option---json-controller)
      - [Request Example](#request-example-10)
      - [Request Parameters](#request-parameters-10)
      - [Response Example](#response-example-10)
    + [Update shipping option - JSON controller](#update-shipping-option---json-controller)
      - [Request Example](#request-example-11)
      - [Request Parameters](#request-parameters-11)
      - [Response Example](#response-example-11)
    + [Delete shipping option - JSON controller](#delete-shipping-option---json-controller)
      - [Request Example](#request-example-12)
      - [Request Parameters](#request-parameters-12)
      - [Response Example](#response-example-12)
    + [Add shipping costs weight range - JSON controller](#add-shipping-costs-weight-range---json-controller)
      - [Request Example](#request-example-13)
      - [Request Parameters](#request-parameters-13)
      - [Response Example](#response-example-13)
    + [Update a shipping costs weight range - JSON controller](#update-a-shipping-costs-weight-range---json-controller)
      - [Request Example](#request-example-14)
      - [Request Parameters](#request-parameters-14)
      - [Response Example](#response-example-14)
    + [Delete a shipping costs weight range - JSON controller](#delete-a-shipping-costs-weight-range---json-controller)
      - [Request Example](#request-example-15)
      - [Request Parameters](#request-parameters-15)
      - [Response Example](#response-example-15)
    + [Get all discounts - JSON controller](#get-all-discounts---json-controller)
      - [Request Example](#request-example-16)
      - [Request Parameters](#request-parameters-16)
      - [Response Example](#response-example-16)
    + [Add a new discount - JSON controller](#add-a-new-discount---json-controller)
      - [Request Example](#request-example-17)
      - [Request Parameters](#request-parameters-17)
      - [Response Example](#response-example-17)
    + [Update discount - JSON controller](#update-discount---json-controller)
      - [Request Example](#request-example-18)
      - [Request Parameters](#request-parameters-18)
      - [Response Example](#response-example-18)
    + [Delete discount - JSON controller](#delete-discount---json-controller)
      - [Request Example](#request-example-19)
      - [Request Parameters](#request-parameters-19)
      - [Response Example](#response-example-19)
    + [Define a discount criteria for discount - JSON controller](#define-a-discount-criteria-for-discount---json-controller)
      - [Request Example](#request-example-20)
      - [Request Parameters](#request-parameters-20)
      - [Response Example](#response-example-20)
    + [Update discount criteria - JSON controller](#update-discount-criteria---json-controller)
      - [Request Example](#request-example-21)
      - [Request Parameters](#request-parameters-21)
      - [Response Example](#response-example-21)
    + [Delete discount criteria - JSON controller](#delete-discount-criteria---json-controller)
      - [Request Example](#request-example-22)
      - [Request Parameters](#request-parameters-22)
      - [Response Example](#response-example-22)
    + [Prepare order form - JSON controller](#prepare-order-form---json-controller)
      - [Request Example](#request-example-23)
      - [Request Parameters](#request-parameters-23)
      - [Response Example](#response-example-23)
    + [Get payments - JSON controller](#get-payments---json-controller)
      - [Request Example](#request-example-24)
      - [Request Parameters](#request-parameters-24)
      - [Response Example](#response-example-24)
    + [Add a new payment - JSON controller](#add-a-new-payment---json-controller)
      - [Request Example](#request-example-25)
      - [Request Parameters](#request-parameters-25)
      - [Response Example](#response-example-25)
    + [Delete a payment - JSON controller](#delete-a-payment---json-controller)
      - [Request Example](#request-example-26)
      - [Request Parameters](#request-parameters-26)
      - [Response Example](#response-example-26)
    + [Refund a payment - JSON controller](#refund-a-payment---json-controller)
      - [Request Example](#request-example-27)
      - [Request Parameters](#request-parameters-27)
      - [Response Example](#response-example-27)
    + [Create a new payment method for user/customer - JSON controller](#create-a-new-payment-method-for-user-customer---json-controller)
      - [Request Example](#request-example-28)
      - [Request Parameters](#request-parameters-28)
      - [Response Example](#response-example-28)
    + [Set default payment method for user/customer - JSON controller](#set-default-payment-method-for-user-customer---json-controller)
      - [Request Example](#request-example-29)
      - [Request Parameters](#request-parameters-29)
      - [Response Example](#response-example-29)
    + [Get PayPal billing agreement express checkout url - JSON controller](#get-paypal-billing-agreement-express-checkout-url---json-controller)
      - [Request Example](#request-example-30)
      - [Request Parameters](#request-parameters-30)
      - [Response Example](#response-example-30)
    + [Create PayPal billing agreement - JSON controller](#create-paypal-billing-agreement---json-controller)
      - [Request Example](#request-example-31)
      - [Request Parameters](#request-parameters-31)
      - [Response Example](#response-example-31)
    + [Update payment method - JSON controller](#update-payment-method---json-controller)
      - [Request Example](#request-example-32)
      - [Request Parameters](#request-parameters-32)
      - [Response Example](#response-example-32)
    + [Delete payment method - JSON controller](#delete-payment-method---json-controller)
      - [Request Example](#request-example-33)
      - [Request Parameters](#request-parameters-33)
      - [Response Example](#response-example-33)
    + [Get all user's payment methods - JSON controller](#get-all-user-s-payment-methods---json-controller)
      - [Request Example](#request-example-34)
      - [Request Parameters](#request-parameters-34)
      - [Response Example](#response-example-34)
    + [Get user addresses - JSON controller](#get-user-addresses---json-controller)
      - [Request Example](#request-example-35)
      - [Request Parameters](#request-parameters-35)
      - [Response Example](#response-example-35)
    + [Create a new address for user/customer - JSON controller](#create-a-new-address-for-user-customer---json-controller)
      - [Request Example](#request-example-36)
      - [Request Parameters](#request-parameters-36)
      - [Response Example](#response-example-36)
    + [Update address - JSON controller](#update-address---json-controller)
      - [Request Example](#request-example-37)
      - [Request Parameters](#request-parameters-37)
      - [Response Example](#response-example-37)
    + [Delete address - JSON controller](#delete-address---json-controller)
      - [Request Example](#request-example-38)
      - [Request Parameters](#request-parameters-38)
      - [Response Example](#response-example-38)
    + [Pull subscriptions paginated - JSON controller](#pull-subscriptions-paginated---json-controller)
      - [Request Example](#request-example-39)
      - [Request Parameters](#request-parameters-39)
      - [Response Example](#response-example-39)
    + [Create a new subscription - JSON controller](#create-a-new-subscription---json-controller)
      - [Request Example](#request-example-40)
      - [Request Parameters](#request-parameters-40)
      - [Response Example](#response-example-40)
    + [Update a subscription - JSON controller](#update-a-subscription---json-controller)
      - [Request Example](#request-example-41)
      - [Request Parameters](#request-parameters-41)
      - [Response Example](#response-example-41)
    + [Delete a subscription - JSON controller](#delete-a-subscription---json-controller)
      - [Request Example](#request-example-42)
      - [Request Parameters](#request-parameters-42)
      - [Response Example](#response-example-42)
    + [Renew a subscription - JSON controller](#renew-a-subscription---json-controller)
      - [Request Example](#request-example-43)
      - [Request Parameters](#request-parameters-43)
      - [Response Example](#response-example-43)
    + [Pull orders - JSON controller](#pull-orders---json-controller)
      - [Request Example](#request-example-44)
      - [Request Parameters](#request-parameters-44)
      - [Response Example](#response-example-44)
    + [Update order - JSON controller](#update-order---json-controller)
      - [Request Example](#request-example-45)
      - [Request Parameters](#request-parameters-45)
      - [Response Example](#response-example-45)
    + [Delete order - JSON controller](#delete-order---json-controller)
      - [Request Example](#request-example-46)
      - [Request Parameters](#request-parameters-46)
      - [Response Example](#response-example-46)
    + [Pull shipping fulfillments - JSON controller](#pull-shipping-fulfillments---json-controller)
      - [Request Example](#request-example-47)
      - [Request Parameters](#request-parameters-47)
      - [Response Example](#response-example-47)
    + [Fulfilled order or order item - JSON controller](#fulfilled-order-or-order-item---json-controller)
      - [Request Example](#request-example-48)
      - [Request Parameters](#request-parameters-48)
      - [Response Example](#response-example-48)
    + [Delete shipping fulfillment  - JSON controller](#delete-shipping-fulfillment----json-controller)
      - [Request Example](#request-example-49)
      - [Request Parameters](#request-parameters-49)
      - [Response Example](#response-example-49)
    + [Get products statistics  - JSON controller](#get-products-statistics----json-controller)
      - [Request Example](#request-example-50)
      - [Request Parameters](#request-parameters-50)
      - [Response Example](#response-example-50)
    + [Get orders statistics  - JSON controller](#get-orders-statistics----json-controller)
      - [Request Example](#request-example-51)
      - [Request Parameters](#request-parameters-51)
      - [Response Example](#response-example-51)


<!-- ecotrust-canada.github.io/markdown-toc -->


## Install
With composer command
``` composer require railroad/ecommerce:1.0.43 ```

## API Reference

### Add item to cart - forms controller

```
GET /ecommerce/add-to-cart
```
#### Request Example

```
<form method="GET" action="/ecommerce/add-to-cart">
    <input type="text" name="products[SKU-abc]" value="2">
    <input type="text" name="products[SKU-aaa]" value="1">
    <input type="text" name="products[SKU-bbb]" value="1">

    <button type="submit">Submit</button>
</form>
```

#### Request Parameters
| path\|query\|body |  key         |  required |  default            |  description\|notes                                                                               | 
|-------------------|--------------|-----------|---------------------|---------------------------------------------------------------------------------------------------| 
| query             |  products    |  yes      |                     |  Products array. Array keys will be matched to product SKU and array values to product quantity.  | 
| query             |  redirect    |  no       |  redirect()->back() |  If this is set the request will redirect to this url; otherwise will be redirect back            | 
| query             |  locked      |  no       |                     |  If it's true 'lock' the cart so when other items are added it clears the cart first              | 
| query             |  promo-code  |  no       |                     |  Promo code                                                                                       | 


<!-- donatstudios.com/CsvToMarkdownTable
path\|query\|body, key, required, default, description\|notes
query , products , yes ,  , Products array. Array keys will be matched to product SKU and array values to product quantity. 
query , redirect , no , redirect()->back(), If this is set the request will redirect to this url; otherwise will be redirect back
query , locked , no , , If it's true 'lock' the cart so when other items are added it clears the cart first
query , promo-code , no , , Promo code 
-->

#### Response Example

``` 302 ```
Redirects to previous url or to path passed in with redirect param.

On the session are flashed the following data:\
    * `success` - boolean value\
    * `addedProducts` - array with added product info\
    * `cartSubTotal` - cart items subtotal price\
    * `cartNumberOfItems` - the number of cart items\
    * `notAvailableProducts` - array with the error messages for the products that could not be added to cart


### Remove a product from cart - JSON 

```
PUT /ecommerce/remove-from-cart/{productId}
```
#### Request Example

```
$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/remove-from-cart/1',
    type: 'put',
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters
| path\|query\|body |  key        |  required |  default |  description\|notes                           | 
|-----------------|-------------|-----------|----------|-----------------------------------------------| 
| path            |  productId  |  yes      |          |  The product id you want to remove from cart. | 




#### Response Example

```201 OK```

```json
{
 	"tax":"0",
        "total":"0",
        "cartItems":[]
}

```

### Update product quantity on cart - JSON controller

```
PUT /ecommerce/update-product-quantity/{productId}/{newQuantity}
```
#### Request Example
```
$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/update-product-quantity/1/2',
    type: 'put',
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters
| path\|query\|body |  key          |  required |  default |  description\|notes                                  | 
|-------------------|---------------|-----------|----------|------------------------------------------------------| 
| path              |  productId    |  yes      |          |  The product id you want to modify quantity on cart. | 
| path              |  newQuantity  |  yes      |          |  The new quantity for the product.                   | 


#### Response Example


### Save shipping and billing addresses on the session - JSON controller

```
PUT /ecommerce/session/address
```
#### Request Example

```
$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/session/address',
    type: 'put',
    data: {billing-email: 'test@drumeo.com', billing-country: '', billing-region:'', billing-zip-or-postal-code:'', bipping-address-line-1:''} 
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters
| path\|query\|body |  key                         |  required |  default |  description\|notes                   | 
|-------------------|------------------------------|-----------|----------|---------------------------------------| 
| body              |  billing-email               |  no       |          |  The billing email address.           | 
| body              |  billing-country             |  no       |          |  The billing country.                 | 
| body              |  billing-region              |  no       |          |  The billing region.                  | 
| body              |  billing-zip-or-postal-code  |  no       |          |  The billing zip code.                | 
| body              |  shipping-address-line-1     |  no       |          |  The shipping address line 1.         | 
| body              |  shipping-city               |  no       |          |  The shipping city.                   | 
| body              |  shipping-country            |  no       |          |  The shipping country.                | 
| body              |  shipping-first-name         |  no       |          |  The first name for shipping address. | 
| body              |  shipping-last-name          |  no       |          |  The last name for shipping address.  | 
| body              |  shipping-region             |  no       |          |  The region for shipping address.     | 
| body              |  shipping-zip                |  no       |          |  The zip code for shipping address.   | 


#### Response Example



### Get all products - JSON controller

```
GET /ecommerce/product
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/product?page=3&limit=25&brand=drumeo&order_by_column=created_on&order_by_direction=desc',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters
| path\|query\|body |  key                |  required |  default                  |  description\|notes                        | 
|-------------------|---------------------|-----------|---------------------------|--------------------------------------------| 
| body              |  brand              |  no       |  value set in config file |  The brand where the product it's defined. | 
| query             |  page               |  no       |  1                        |  Pagination page.                          | 
| query             |  limit              |  no       |  10                       |  Amount of products to pull per page.      | 
| query             |  order_by_column    |  no       |  created_on               |  Sort column name.                         | 
| query             |  order_by_direction |  no       |  desc                     |  Sort column direction                     | 


#### Response Example




### Add a new product - JSON controller

```
PUT /ecommerce/product
```
#### Request Example

```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/product',
    type: 'put'
  	data: {name: 'product name', sku: 'product sku' price: 127, type: 'product', active:1, brand:'drumeo'} 
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key                         |  required                   |  default |  description\|notes                                            | 
|-------------------|------------------------------|-----------------------------|----------|----------------------------------------------------------------| 
| body              |  name                        |  yes                        |          |  Product name.                                                 | 
| body              |  sku                         |  yes                        |          |  Product sku.                                                  | 
| body              |  price                       |  yes                        |          |  Product price.                                                | 
| body              |  type                        |  yes                        |          |  Product type; available options:product and subscription.     | 
| body              |  active                      |  yes                        |          |  Product it's active or inactive                               | 
| body              |  is_physical                 |  yes                        |          |  Flag that determines whether the Product it's physical or not | 
| body              |  weight                      |  yes if is_physical = true  |          |  Product weight                                                | 
| body              |  stock                       |  yes                        |          |  Product stock quantity                                        | 
| body              |  subscription_interval_type  |  yes if type = subscription |          | Subscription interval type                                     | 
| body              |  subscription_interval_count |  yes if type = subscription |          | Subscription interval period                                   | 


#### Response Example



### Update product - JSON controller

```
PATCH /ecommerce/product/{productId}
```
#### Request Example

```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/product/1',
    type: 'patch'
  	data: {description: 'new description for product'}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key                         |  required                   |  default |  description\|notes                                            | 
|-------------------|------------------------------|-----------------------------|----------|----------------------------------------------------------------| 
| path              |  productId                   |  yes                        |          |  Id of the product you want to edit.                           | 
| body              |  name                        |  no                         |          |  New product name.                                             | 
| body              |  sku                         |  no                         |          |  New product sku.                                              | 
| body              |  price                       |  no                         |          |  New product price.                                            | 
| body              |  type                        |  no                         |          |  New product type; available options:product and subscription. | 
| body              |  active                      |  no                         |          |  Product it's active or inactive                               | 
| body              |  is_physical                 |  no                         |          |  Flag that determines whether the Product it's physical or not | 
| body              |  weight                      |  yes if is_physical = true  |          |  Product weight                                                | 
| body              |  stock                       |  no                         |          |  New product stock quantity                                    | 
| body              |  subscription_interval_type  |  yes if type = subscription |          | Subscription interval type                                     | 
| body              |  subscription_interval_count |  yes if type = subscription |          | Subscription interval period                                   | 


#### Response Example



### Delete product - JSON controller

```
DELETE /ecommerce/product/{productId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/product/1',
    type: 'delete'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key        |  required |  default |  description\|notes                    | 
|-------------------|-------------|-----------|----------|----------------------------------------| 
| path              |  productId  |  yes      |          |  Id of the product you want to delete. | 


#### Response Example



### Upload product thumbnail - JSON controller

```
PUT /ecommerce/product/upload/
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/product/upload',
    type: 'put'
  	data: {target: '', file: ''} 
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters


#### Response Example




### Get all shipping options - JSON controller

```
GET /ecommerce/shipping-options
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/shipping-options?page=3&limit=25&order_by_column=created_on&order_by_direction=desc',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters

| path\|query\|body |  key                |  required |  default    |  description\|notes                            | 
|-------------------|---------------------|-----------|-------------|------------------------------------------------| 
| query             |  page               |  no       |  1          |  Pagination page.                              | 
| query             |  limit              |  no       |  100        |   Amount of shipping options to pull per page. | 
| query             |  order_by_column    |  no       |  created_on |  Sort column name.                             | 
| query             |  order_by_direction |  no       |  desc       |  Sort column direction.                        | 


#### Response Example


### Add a new shipping option - JSON controller

```
PUT /ecommerce/shipping-options
```
#### Request Example

```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/shipping-options',
    type: 'put'
  	data: {country: 'En', priority: '1' active: '1'} 
		// language, brand, will be set to internal defaults
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key       |  required |  default |  description\|notes                       | 
|-------------------|------------|-----------|----------|-------------------------------------------| 
| body              |  country   |  yes      |          |  Country for shipping option.             | 
| body              |  priority  |  yes      |          |   Shipping option priority.               | 
| body              |  active    |  yes      |          |  Shipping option it's active or inactive. | 


#### Response Example



### Update shipping option - JSON controller

```
PATCH /ecommerce/shipping-options/{shippingOptionId}
```
#### Request Example

```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/shipping-options/1',
    type: 'patch'
  	data: {active: '0'}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters

| path\|query\|body |  key       |  required |  default |  description\|notes                          | 
|-------------------|------------|-----------|----------|----------------------------------------------| 
| path              |  id        |  yes      |          |  Id of the shipping option you want to edit. | 
| body              |  country   |  yes      |          |  New country for shipping option.            | 
| body              |  priority  |  yes      |          |   New shipping option priority.              | 
| body              |  active    |  yes      |          |  Shipping option it's active or inactive.    | 


#### Response Example


### Delete shipping option - JSON controller

```
DELETE /ecommerce/shipping-options/{shippingOptionId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/shipping-option/1',
    type: 'delete'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters

| path\|query\|body |  key |  required |  default |  description\|notes                            | 
|-------------------|------|-----------|----------|------------------------------------------------| 
| path              |  id  |  yes      |          |  Id of the shipping option you want to delete. | 


#### Response Example




### Add shipping costs weight range - JSON controller

```
PUT /ecommerce/shipping-cost
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/shipping-cost',
    type: 'put'
  	data: {shipping_option_id: '1', min: '0' max: '100', price: '15'} 
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key                |  required |  default |  description\|notes                            | 
|-------------------|---------------------|-----------|----------|------------------------------------------------| 
| body              |  shipping_option_id |  yes      |          |  Id of the shipping option for shipping costs. | 
| body              |  min                |  yes      |          |  Min weight to apply the cost                  | 
| body              |  max                |  yes      |          |  Max  weight to apply the cost                 | 
| body              |  price              |  yes      |          |  Price                                         | 


#### Response Example


### Update a shipping costs weight range - JSON controller

```
PATCH /ecommerce/shipping-cost/{shippingCostId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/shipping-cost/1',
    type: 'patch'
  	data: {min: '3'}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key                |  required |  default |  description\|notes                            | 
|-------------------|---------------------|-----------|----------|------------------------------------------------| 
| path              |  shippingCostId     |  yes      |          |  Id of the shipping costs you want to update.  | 
| body              |  shipping_option_id |  no       |          |  Id of the shipping option for shipping costs. | 
| body              |  min                |  no       |          |  Min weight to apply the cost                  | 
| body              |  max                |  no       |          |  Max  weight to apply the cost                 | 
| body              |  price              |  no       |          |  Price                                         | 


#### Response Example


### Delete a shipping costs weight range - JSON controller

```
DELETE /ecommerce/shipping-cost/{shippingCostId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/shipping-cost/1',
    type: 'delete'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key |  required |  default |  description\|notes                            | 
|-------------------|------|-----------|----------|------------------------------------------------| 
| path              |  id  |  yes      |          |  Id of the shipping cost you want to delete.   | 


#### Response Example



### Get all discounts - JSON controller

```
GET /ecommerce/discounts
```
#### Request Example

```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/discounts?page=3&limit=25&order_by_column=created_on&order_by_direction=desc',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters
| path\|query\|body |  key                |  required |  default    |  description\|notes                    | 
|-------------------|---------------------|-----------|-------------|----------------------------------------| 
| query             |  page               |  no       |  1          |  Pagination page.                      | 
| query             |  limit              |  no       |  100        |  Amount of discounts to pull per page. | 
| query             |  order_by_column    |  no       |  created_on |  Sort column name.                     | 
| query             |  order_by_direction |  no       |  desc       |  Sort column direction.                | 


#### Response Example

### Add a new discount - JSON controller

```
PUT /ecommerce/discount
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/discount',
    type: 'put'
  	data: {name: 'test', description: 'discount description' type: 'product amount off', amount: '10', product_id:'1', active:'1'} 
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key         |  required |  default |  description\|notes               | 
|-------------------|--------------|-----------|----------|-----------------------------------| 
| body              |  name        |  yes      |          |  Discount name.                   | 
| body              |  description |  yes      |          |  Discount description             | 
| body              |  type        |  yes      |          |  Discount type                    | 
| body              |  amount      |  yes      |          |  Discount amount                  | 
| body              |  active      |  yes      |          |  Discount it's active or inactive | 


#### Response Example

### Update discount - JSON controller

```
PATCH /ecommerce/discount/{discountId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/discount/1',
    type: 'patch'
  	data: {amount: '30'}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key         |  required |  default |  description\|notes               | 
|-------------------|--------------|-----------|----------|-----------------------------------| 
| payh              |  id          |  yes      |          |  Discount id you want to edit     | 
| body              |  name        |  no       |          |  Discount name.                   | 
| body              |  description |  no       |          |  Discount description             | 
| body              |  type        |  no       |          |  Discount type                    | 
| body              |  amount      |  no       |          |  Discount amount                  | 
| body              |  active      |  no       |          |  Discount it's active or inactive | 


#### Response Example

### Delete discount - JSON controller

```
DELETE /ecommerce/discount/{discountId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/discount/1',
    type: 'delete'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key |  required |  default |  description\|notes                            | 
|-------------------|------|-----------|----------|------------------------------------------------| 
| path              |  id  |  yes      |          |  Id of the discount you want to delete.        | 

#### Response Example

### Define a discount criteria for discount - JSON controller

```
PUT /ecommerce/discount-criteria/{discountId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/discount-criteria/1',
    type: 'put'
  	data: {name: 'discount criteria name', product_id:'1', type:'product quantity requirement',min:2, max:5} 
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key        |  required |  default |  description\|notes                           | 
|-------------------|-------------|-----------|----------|-----------------------------------------------| 
| path              |  id         |  yes      |          |  Id of discounts where criteria it's defined  | 
| body              |  name       |  yes      |          |  Discount criteria name.                      | 
| body              |  type       |  yes      |          |  Discount criteria type.                      | 
| body              |  product_id |  yes      |          |  Product id that should met the criteria      | 
| body              |  min        |  no       |          |  Min                                          | 
| body              |  max        |  no       |          |  Max                                          | 


#### Response Example

### Update discount criteria - JSON controller

```
PATCH /ecommerce/discount-criteria/{discountCriteriaId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/discount-criteria/1',
    type: 'patch'
  	data: {max: '30'}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters

| path\|query\|body |  key        |  required |  default |  description\|notes                         | 
|-------------------|-------------|-----------|----------|---------------------------------------------| 
| path              |  id         |  yes      |          |  Id of discount criteria you want to update | 
| body              |  name       |  no       |          |  Discount criteria name.                    | 
| body              |  type       |  no       |          |  Discount criteria type.                    | 
| body              |  product_id |  no       |          |  Product id that should met the criteria    | 
| body              |  min        |  no       |          |  Min                                        | 
| body              |  max        |  no       |          |  Max                                        | 


#### Response Example

### Delete discount criteria - JSON controller

```
DELETE /ecommerce/discount-criteria/{discountCriteriaId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/discount-criteria/1',
    type: 'delete'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key |  required |  default |  description\|notes                            | 
|-------------------|------|-----------|----------|------------------------------------------------| 
| path              |  id  |  yes      |          |  Id of the discount criteria you want to delete.| 

#### Response Example


### Prepare order form - JSON controller

```
GET /ecommerce/order
```
#### Request Example

```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/order',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```
#### Request Parameters


#### Response Example


### Get payments - JSON controller

```
GET /ecommerce/payment
```
#### Request Example

```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/payment?page=3&limit=25&order_by_column=created_on&order_by_direction=desc&order_id=1',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters

| path\|query\|body |  key                |  required                          |  default    |  description\|notes                    | 
|-------------------|---------------------|------------------------------------|-------------|----------------------------------------| 
| query             |  order_id           |  yes if subscription_id not exists |             |  Pull payments for order_id            | 
| query             |  subscription_id    |  yes if order_id not exists        |             |  Pull payments for subscription_id     | 
| query             |  page               |  no                                |  1          |  Pagination page.                      | 
| query             |  limit              |  no                                |  100        |   Amount of payments to pull per page. | 
| query             |  order_by_column    |  no                                |  created_on |  Sort column name.                     | 
| query             |  order_by_direction |  no                                |  desc       |  Sort column direction.                | 


#### Response Example

### Add a new payment - JSON controller

```
PUT /ecommerce/payment
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/payment',
    type: 'put'
  	data: {payment_method_id: '1', due:100, paid:100, order_id:2} 
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key               |  required |  default |  description\|notes                         | 
|-------------------|--------------------|-----------|----------|---------------------------------------------| 
| body              |  due               |  yes      |          |  Payment due.                               | 
| body              |  paid              |  no       |          |  Paid Amount                                | 
| body              |  refunded          |  no       |          |  Refunded amount                            | 
| body              |  payment_method_id |  no       |          |  Associated payment method                  | 
| body              |  currency          |  no       |          |  Currency                                   | 
| body              |  order_id          |  no       |          |  Payment for order with specified id        | 
| body              |  subscription_id   |  no       |          |  Payment for subscription with specified id | 


#### Response Example

### Delete a payment - JSON controller

```
DELETE /ecommerce/payment/{paymentId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/payment/1',
    type: 'delete'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key |  required |  default |  description\|notes                            | 
|-------------------|------|-----------|----------|------------------------------------------------| 
| path              |  id  |  yes      |          |  Id of the payment you want to delete.| 

#### Response Example

### Refund a payment - JSON controller

```
PUT /ecommerce/refund
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/refund',
    type: 'put'
  	data: {payment_id: '1', refund_amount:100, note:'Requested by customer', gateway_name:'drumeo'} 
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters

| path\|query\|body |  key           |  required |  default |  description\|notes          | 
|-------------------|----------------|-----------|----------|------------------------------| 
| body              |  refund_amount |  yes      |          |  Amount that will be refund. | 
| body              |  note          |  no       |          |  Refund optional description | 
| body              |  payment_id    |  yes      |          |  Payment that it's refund.   | 
| body              |  gateway_name  |  yes      |          |  Gateway name                | 


#### Response Example

### Create a new payment method for user/customer - JSON controller

```
PUT /ecommerce/payment-method
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/payment-method',
    type: 'put'
  	data: {method_type: 'paypal', gateway:'drumeo', token:'SDcd54345', address_id:1, user_id:1, user_email:'test@drumeo.com'} 
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key         |  required                            |  default |  description\|notes                          | 
|-------------------|--------------|--------------------------------------|----------|----------------------------------------------| 
| body              |  method_type |  yes                                 |          |  Payment method type: paypal or credit-card  | 
| body              |  card_token  |  yes for credit-card type            |          |  Card token                                  | 
| body              |  gateway     |  yes                                 |          |  Gateway name.                               | 
| body              |  token       |  yes for paypal type                 |          |  Paypal token                                | 
| body              |  address_id  |  yes for paypal type                 |          |  Address id                                  | 
| body              |  user_id     |  yes if customer_id it's not defined |          |  User id                                     | 
| body              |  user_email  |  yes if customer_id it's not defined |          |  User email address                          | 
| body              |  customer_id |  yes if user_id it's not defined     |          |  Customer id                                 | 


#### Response Example

### Set default payment method for user/customer - JSON controller

```
PATCH /ecommerce/payment-method/set-default
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/payment-method/set-default',
    type: 'patch'
  	data: {id: '30'}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters

| path\|query\|body |  key |  required |  default |  description\|notes                           | 
|-------------------|------|-----------|----------|-----------------------------------------------| 
| body              |  id  |  yes      |          |  Payment method id you want to set as default | 


#### Response Example


### Get PayPal billing agreement express checkout url - JSON controller

```
GET /ecommerce/payment-method/paypal-url
```
#### Request Example


#### Request Parameters


#### Response Example

### Create PayPal billing agreement - JSON controller

```
GET /ecommerce/payment-method/paypal-agreement
```
#### Request Example


#### Request Parameters


#### Response Example





### Update Credit card payment method - JSON controller

```
PATCH /ecommerce/payment-method/{paymentMethodId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/payment-method/1',
    type: 'patch'
  	data: {year: '2022', month:'12'}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key         |  required |  default |  description\|notes                    | 
|-------------------|--------------|-----------|----------|----------------------------------------| 
| path              |  id          |  yes      |          |  Payment method id you want to edit    | 
| body              |  gateway     |  yes      |          |  Gateway name                          | 
| body              |  year        |  yes      |          |  Credit card expiration year           | 
| body              |  month       |  yes      |          |  Credit card expiration month          | 
| body              |  country     |  no       |          |  Country                               | 
| body              |  state       |  no       |          |  State                                 | 
| body              |  set_default |  no       |          |  Flag to set payment method as default | 


#### Response Example

### Delete payment method - JSON controller

```
DELETE /ecommerce/payment-method/{paymentMethodId}
```
#### Request Example

```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/payment-method/1',
    type: 'delete'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key |  required |  default |  description\|notes                           | 
|-------------------|------|-----------|----------|-----------------------------------------------| 
| body              |  id  |  yes      |          |  Payment method id you want to delete         | 

#### Response Example


### Get all user's payment methods - JSON controller

```
GET /ecommerce/user-payment-method/{userId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/user-payment-method/1',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters
| path\|query\|body |  key |  required |  default |  description\|notes                       | 
|-------------------|------|-----------|----------|-------------------------------------------| 
| path              |  id  |  yes      |          |  User id you want to pull payment methods | 



#### Response Example


### Get user addresses - JSON controller

```
GET /ecommerce/address
```
#### Request Example

```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/address',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters

| path\|query\|body |  key     |  required |  default               |  description\|notes                 | 
|-------------------|----------|-----------|------------------------|-------------------------------------| 
| body              |  user_id |  no       |  authenticated user id |  User id you want to pull addresses | 



#### Response Example

### Create a new address for user/customer - JSON controller

```
PUT /ecommerce/address
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/address',
    type: 'put'
  	data: {type: 'billing', first_name:'First', last_name:'Naama', street_line_1:'asa dsd', street_line_2:'dsds 4', city:'BlaTest', zip:'545400',country:'Canada',user_id:1} 
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key           |  required |  default                |  description\|notes                | 
|-------------------|----------------|-----------|-------------------------|------------------------------------| 
| body              |  type          |  yes      |                         |  Address type: billing or shipping | 
| body              |  first_name    |  no       |                         |  First name                        | 
| body              |  last_name     |  no       |                         |  Last name                         | 
| body              |  street_line_1 |  no       |                         |  Street line 1                     | 
| body              |  street_line_2 |  no       |                         |  Street line 2                     | 
| body              |  city          |  no       |                         |  City                              | 
| body              |  zip           |  no       |                         |  Zip code                          | 
| body              |  state         |  no       |                         |  State                             | 
| body              |  country       |  yes      |                         |  Country                           | 
| body              |  user_id       |  no       |                         |  User id                           | 
| body              |  customer_id   |  no       |                         |  Customer id                       | 
| body              |  brand         |  no       |  brand from config file |  Brand                             | 


#### Response Example

### Update address - JSON controller

```
PATCH /ecommerce/address/{addressId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/address/1',
    type: 'patch'
  	data: {street_line_2: 'Lorem ipsum'}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key           |  required |  default                |  description\|notes                | 
|-------------------|----------------|-----------|-------------------------|------------------------------------| 
| path              |  id            |  yes      |                         |  Address id you want to edit       | 
| body              |  type          |  no       |                         |  Address type: billing or shipping | 
| body              |  first_name    |  no       |                         |  First name                        | 
| body              |  last_name     |  no       |                         |  Last name                         | 
| body              |  street_line_1 |  no       |                         |  Street line 1                     | 
| body              |  street_line_2 |  no       |                         |  Street line 2                     | 
| body              |  city          |  no       |                         |  City                              | 
| body              |  zip           |  no       |                         |  Zip code                          | 
| body              |  state         |  no       |                         |  State                             | 
| body              |  country       |  no       |                         |  Country                           | 
| body              |  user_id       |  no       |                         |  User id                           | 
| body              |  customer_id   |  no       |                         |  Customer id                       | 
| body              |  brand         |  no       |  brand from config file |  Brand                             | 


#### Response Example

### Delete address - JSON controller

```
DELETE /ecommerce/address/{addressId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/address/1',
    type: 'delete'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key         |  required |  default |  description\|notes            | 
|-------------------|--------------|-----------|----------|--------------------------------| 
| path              |  id          |  yes      |          |  Address id you want to delete | 
| body              |  customer_id |  no       |          |  Customer id                   | 


#### Response Example

### Pull subscriptions paginated - JSON controller

```
GET /ecommerce/subscriptions
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/subscriptions?limit=10&page=1&user_id=52',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters
| path\|query\|body |  key                |  required |  default    |  description\|notes                        | 
|-------------------|---------------------|-----------|-------------|--------------------------------------------| 
| query             |  user_id            |  no       |             |  Pull only user's subscriptions            | 
| query             |  page               |  no       |  1          |  Pagination page.                          | 
| query             |  limit              |  no       |  100        |  Amount of subscriptions to pull per page. | 
| query             |  order_by_column    |  no       |  created_on |  Sort column name.                         | 
| query             |  order_by_direction |  no       |  desc       |  Sort column direction.                    | 
| query             |  brand              |  no       |             |  Brand                                     | 



#### Response Example

### Create a new subscription - JSON controller

```
PUT /ecommerce/subscription
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/subscription',
    type: 'put'
  	data: {type: 'subscription', order_id:'1', product_id:'12', is_active:'true',
  	 start_date:'2016-11-28 22:06:43', paid_until:'2018-12-01 00:00:00', total_price_per_payment:120,
  	 interval_type:'month', interval_count:'1',payment_method_id:1212
  	 } 
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key                     |  required |  default                  |  description\|notes                               | 
|-------------------|--------------------------|-----------|---------------------------|---------------------------------------------------| 
| body              |  brand                   |  no       |  Value set in config file |  Brand                                            | 
| body              |  currency                |  no       |                           |  Currency                                         | 
| body              |  type                    |  yes      |                           |  Subscription type: payment plan or subscription. | 
| body              |  order_id                |  no       |                           |  Associated order id                              | 
| body              |  product_id              |  no       |                           |  Associated product id                            | 
| body              |  is_active               |  no       |                           |  Subscription it's active or inactive             | 
| body              |  start_date              |  no       |                           |  Membership start date                            | 
| body              |  paid_until              |  no       |                           |  End of membership                                | 
| body              |  canceled_on             |  no       |                           |  Date when the subscription was canceled          | 
| body              |  note                    |  no       |                           |  Optional note                                    | 
| body              |  total_price_per_payment |  no       |                           |  Amount that should be paid                       | 
| body              |  interval_type           |  no       |                           |  Subscription interval type                       | 
| body              |  interval_count          |  no       |                           |  Subscription interval count                      | 
| body              |  total_cycles_due        |  no       |                           |  Total cycles due for payment plan                | 
| body              |  total_cycles_paid       |  no       |                           |  Total cycles paid for payment plan               | 
| body              |  payment_method_id       |  no       |                           |  Associated payment method                        | 
| body              |  user_id                 |  no       |                           |  User id                                          | 
| body              |  customer_id             |  no       |                           |  Customer id                                      | 


#### Response Example

### Update a subscription - JSON controller

```
PATCH /ecommerce/subscription/{subscriptionId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/subscription/1',
    type: 'patch'
  	data: {paid_until: '2019-04-09 00:00:00'}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key                     |  required |  default                  |  description\|notes                               | 
|-------------------|--------------------------|-----------|---------------------------|---------------------------------------------------| 
| path              |  id                      |  yes      |                           |  Subscription id you want to edit                 | 
| body              |  brand                   |  no       |  Value set in config file |  Brand                                            | 
| body              |  currency                |  no       |                           |  Currency                                         | 
| body              |  type                    |  no       |                           |  Subscription type: payment plan or subscription. | 
| body              |  order_id                |  no       |                           |  Associated order id                              | 
| body              |  product_id              |  no       |                           |  Associated product id                            | 
| body              |  is_active               |  no       |                           |  Subscription it's active or inactive             | 
| body              |  start_date              |  no       |                           |  Membership start date                            | 
| body              |  paid_until              |  no       |                           |  End of membership                                | 
| body              |  canceled_on             |  no       |                           |  Date when the subscription was canceled          | 
| body              |  note                    |  no       |                           |  Optional note                                    | 
| body              |  total_price_per_payment |  no       |                           |  Amount that should be paid                       | 
| body              |  interval_type           |  no       |                           |  Subscription interval type                       | 
| body              |  interval_count          |  no       |                           |  Subscription interval count                      | 
| body              |  total_cycles_due        |  no       |                           |  Total cycles due for payment plan                | 
| body              |  total_cycles_paid       |  no       |                           |  Total cycles paid for payment plan               | 
| body              |  payment_method_id       |  no       |                           |  Associated payment method                        | 
| body              |  user_id                 |  no       |                           |  User id                                          | 
| body              |  customer_id             |  no       |                           |  Customer id                                      | 


#### Response Example

### Delete a subscription - JSON controller

```
DELETE /ecommerce/subscription/{subscriptionId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/subscription/1',
    type: 'delete'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key     |  required |  default               |  description\|notes                 | 
|-------------------|----------|-----------|------------------------|-------------------------------------| 
| path              |  id      |  yes      |                        |  Subscription id you want to delete | 

#### Response Example

### Renew a subscription - JSON controller

```
POST /ecommerce/subscription-renew/{subscriptionId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/subscription-renew/1',
    type: 'put'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters
| path\|query\|body |  key     |  required |  default               |  description\|notes                 | 
|-------------------|----------|-----------|------------------------|-------------------------------------| 
| path              |  id      |  yes      |                        |  Subscription id you want to renew  | 

#### Response Example

### Pull orders - JSON controller

```
GET /ecommerce/orders
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/orders?page=3&limit=25&brand=drumeo&order_by_column=created_on&order_by_direction=desc&user_id=12',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters


#### Response Example

### Update order - JSON controller

```
PATCH /ecommerce/order/{orderId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/order/1',
    type: 'patch'
  	data: {due: 100}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters


#### Response Example

### Delete order - JSON controller

```
DELETE /ecommerce/order/{orderId}
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/order/1',
    type: 'delete'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters


#### Response Example


### Pull shipping fulfillments - JSON controller

```
GET /ecommerce/fulfillment
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/fulfillment?page=3&limit=25&brand=drumeo&order_by_column=created_on&order_by_direction=desc&status=pending',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters


#### Response Example

### Fulfilled order or order item - JSON controller

```
PATCH /ecommerce/fulfillment
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/fulfillment',
    type: 'patch'
  	data: {tracking_number: '43100', shipping_company:'Lorem ipsum', order_id:2}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters


#### Response Example

### Delete shipping fulfillment  - JSON controller

```
DELETE /ecommerce/fulfillment
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/fulfillment',
    type: 'delete',
    data: {order_id: 2}
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});

```

#### Request Parameters


#### Response Example

### Get products statistics  - JSON controller

```
GET /ecommerce/stats/products
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/stats/products',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters


#### Response Example


### Get orders statistics  - JSON controller

```
GET /ecommerce/stats/orders
```
#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/stats/orders',
    type: 'get'
    dataType: 'json',
    success: function(response) {
        // handle success
    },
    error: function(response) {
        // handle error
    }
});
```

#### Request Parameters


#### Response Example

