Ecommerce
========================================================================================================================

- [Ecommerce](#ecommerce)
  * [Install](#install)
  * [API Reference](#api-reference)
    + [Add item to cart - forms controller](#add-item-to-cart---forms-controller)
      - [Request Example](#request-example)
      - [Request Parameters](#request-parameters)
      - [Response Example](#response-example)
    + [Remove a product from cart - forms controller](#remove-a-product-from-cart---forms-controller)
      - [Request Example](#request-example-1)
      - [Request Parameters](#request-parameters-1)
      - [Response Example](#response-example-1)
    + [Update product quantity on cart - forms controller](#update-product-quantity-on-cart---forms-controller)
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
    + [Submit order - JSON controller](#submit-order---json-controller)
      - [Request Example](#request-example-24)
      - [Request Parameters](#request-parameters-24)
      - [Response Example](#response-example-24)
    + [Get payments - JSON controller](#get-payments---json-controller)
      - [Request Example](#request-example-25)
      - [Request Parameters](#request-parameters-25)
      - [Response Example](#response-example-25)
    + [Add a new payment - JSON controller](#add-a-new-payment---json-controller)
      - [Request Example](#request-example-26)
      - [Request Parameters](#request-parameters-26)
      - [Response Example](#response-example-26)
    + [Delete a payment - JSON controller](#delete-a-payment---json-controller)
      - [Request Example](#request-example-27)
      - [Request Parameters](#request-parameters-27)
      - [Response Example](#response-example-27)
    + [Refund a payment - JSON controller](#refund-a-payment---json-controller)
      - [Request Example](#request-example-28)
      - [Request Parameters](#request-parameters-28)
      - [Response Example](#response-example-28)
    + [Create a new payment method for user/customer - JSON controller](#create-a-new-payment-method-for-user-customer---json-controller)
      - [Request Example](#request-example-29)
      - [Request Parameters](#request-parameters-29)
      - [Response Example](#response-example-29)
    + [Set default payment method for user/customer - JSON controller](#set-default-payment-method-for-user-customer---json-controller)
      - [Request Example](#request-example-30)
      - [Request Parameters](#request-parameters-30)
      - [Response Example](#response-example-30)
    + [Get PayPal billing agreement express checkout url - JSON controller](#get-paypal-billing-agreement-express-checkout-url---json-controller)
      - [Request Example](#request-example-31)
      - [Request Parameters](#request-parameters-31)
      - [Response Example](#response-example-31)
    + [Create PayPal billing agreement - JSON controller](#create-paypal-billing-agreement---json-controller)
      - [Request Example](#request-example-32)
      - [Request Parameters](#request-parameters-32)
      - [Response Example](#response-example-32)
    + [Update payment method - JSON controller](#update-payment-method---json-controller)
      - [Request Example](#request-example-33)
      - [Request Parameters](#request-parameters-33)
      - [Response Example](#response-example-33)
    + [Delete payment method - JSON controller](#delete-payment-method---json-controller)
      - [Request Example](#request-example-34)
      - [Request Parameters](#request-parameters-34)
      - [Response Example](#response-example-34)
    + [Get all user's payment methods - JSON controller](#get-all-user-s-payment-methods---json-controller)
      - [Request Example](#request-example-35)
      - [Request Parameters](#request-parameters-35)
      - [Response Example](#response-example-35)
    + [Get user addresses - JSON controller](#get-user-addresses---json-controller)
      - [Request Example](#request-example-36)
      - [Request Parameters](#request-parameters-36)
      - [Response Example](#response-example-36)
    + [Create a new address for user/customer - JSON controller](#create-a-new-address-for-user-customer---json-controller)
      - [Request Example](#request-example-37)
      - [Request Parameters](#request-parameters-37)
      - [Response Example](#response-example-37)
    + [Update address - JSON controller](#update-address---json-controller)
      - [Request Example](#request-example-38)
      - [Request Parameters](#request-parameters-38)
      - [Response Example](#response-example-38)
    + [Delete address - JSON controller](#delete-address---json-controller)
      - [Request Example](#request-example-39)
      - [Request Parameters](#request-parameters-39)
      - [Response Example](#response-example-39)
    + [Pull subscriptions paginated - JSON controller](#pull-subscriptions-paginated---json-controller)
      - [Request Example](#request-example-40)
      - [Request Parameters](#request-parameters-40)
      - [Response Example](#response-example-40)
    + [Create a new subscription - JSON controller](#create-a-new-subscription---json-controller)
      - [Request Example](#request-example-41)
      - [Request Parameters](#request-parameters-41)
      - [Response Example](#response-example-41)
    + [Update a subscription - JSON controller](#update-a-subscription---json-controller)
      - [Request Example](#request-example-42)
      - [Request Parameters](#request-parameters-42)
      - [Response Example](#response-example-42)
    + [Delete a subscription - JSON controller](#delete-a-subscription---json-controller)
      - [Request Example](#request-example-43)
      - [Request Parameters](#request-parameters-43)
      - [Response Example](#response-example-43)
    + [Renew a subscription - JSON controller](#renew-a-subscription---json-controller)
      - [Request Example](#request-example-44)
      - [Request Parameters](#request-parameters-44)
      - [Response Example](#response-example-44)
    + [Pull orders - JSON controller](#pull-orders---json-controller)
      - [Request Example](#request-example-45)
      - [Request Parameters](#request-parameters-45)
      - [Response Example](#response-example-45)
    + [Update order - JSON controller](#update-order---json-controller)
      - [Request Example](#request-example-46)
      - [Request Parameters](#request-parameters-46)
      - [Response Example](#response-example-46)
    + [Delete order - JSON controller](#delete-order---json-controller)
      - [Request Example](#request-example-47)
      - [Request Parameters](#request-parameters-47)
      - [Response Example](#response-example-47)
    + [Pull shipping fulfillments - JSON controller](#pull-shipping-fulfillments---json-controller)
      - [Request Example](#request-example-48)
      - [Request Parameters](#request-parameters-48)
      - [Response Example](#response-example-48)
    + [Fulfilled order or order item - JSON controller](#fulfilled-order-or-order-item---json-controller)
      - [Request Example](#request-example-49)
      - [Request Parameters](#request-parameters-49)
      - [Response Example](#response-example-49)
    + [Delete shipping fulfillment  - JSON controller](#delete-shipping-fulfillment----json-controller)
      - [Request Example](#request-example-50)
      - [Request Parameters](#request-parameters-50)
      - [Response Example](#response-example-50)
    + [Get products statistics  - JSON controller](#get-products-statistics----json-controller)
      - [Request Example](#request-example-51)
      - [Request Parameters](#request-parameters-51)
      - [Response Example](#response-example-51)
    + [Get orders statistics  - JSON controller](#get-orders-statistics----json-controller)
      - [Request Example](#request-example-52)
      - [Request Parameters](#request-parameters-52)
      - [Response Example](#response-example-52)

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


### Remove a product from cart - forms controller

```
PUT /ecommerce/remove-from-cart/{productId}
```
#### Request Example


#### Request Parameters


#### Response Example


### Update product quantity on cart - forms controller

```
PUT /ecommerce/update-product-quantit/{productId}/{newQuantity}
```
#### Request Example


#### Request Parameters


#### Response Example


### Save shipping and billing addresses on the session - JSON controller

```
PUT /ecommerce/session/address
```
#### Request Example


#### Request Parameters


#### Response Example



### Get all products - JSON controller

```
GET /ecommerce/product
```
#### Request Example


#### Request Parameters


#### Response Example




### Add a new product - JSON controller

```
PUT /ecommerce/product
```
#### Request Example


#### Request Parameters


#### Response Example



### Update product - JSON controller

```
PATCH /ecommerce/product/{productId}
```
#### Request Example


#### Request Parameters


#### Response Example



### Delete product - JSON controller

```
DELETE /ecommerce/product/{productId}
```
#### Request Example


#### Request Parameters


#### Response Example



### Upload product thumbnail - JSON controller

```
PUT /ecommerce/product/upload/
```
#### Request Example


#### Request Parameters


#### Response Example




### Get all shipping options - JSON controller

```
GET /ecommerce/shipping-options
```
#### Request Example


#### Request Parameters


#### Response Example


### Add a new shipping option - JSON controller

```
PUT /ecommerce/shipping-options
```
#### Request Example


#### Request Parameters


#### Response Example



### Update shipping option - JSON controller

```
PATCH /ecommerce/shipping-options/{shippingOptionId}
```
#### Request Example


#### Request Parameters


#### Response Example


### Delete shipping option - JSON controller

```
DELETE /ecommerce/shipping-options/{shippingOptionId}
```
#### Request Example


#### Request Parameters


#### Response Example




### Add shipping costs weight range - JSON controller

```
PUT /ecommerce/shipping-cost
```
#### Request Example


#### Request Parameters


#### Response Example


### Update a shipping costs weight range - JSON controller

```
PATCH /ecommerce/shipping-cost/{shippingCostId}
```
#### Request Example


#### Request Parameters


#### Response Example


### Delete a shipping costs weight range - JSON controller

```
DELETE /ecommerce/shipping-cost/{shippingCostId}
```
#### Request Example


#### Request Parameters


#### Response Example



### Get all discounts - JSON controller

```
GET /ecommerce/discounts
```
#### Request Example


#### Request Parameters


#### Response Example

### Add a new discount - JSON controller

```
PUT /ecommerce/discount
```
#### Request Example


#### Request Parameters


#### Response Example

### Update discount - JSON controller

```
PATCH /ecommerce/discount/{discountId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Delete discount - JSON controller

```
DELETE /ecommerce/discount/{discountId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Define a discount criteria for discount - JSON controller

```
PUT /ecommerce/discount-criteria/{discountId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Update discount criteria - JSON controller

```
PATCH /ecommerce/discount-criteria/{discountCriteriaId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Delete discount criteria - JSON controller

```
DELETE /ecommerce/discount-criteria/{discountCriteriaId}
```
#### Request Example


#### Request Parameters


#### Response Example


### Prepare order form - JSON controller

```
GET /ecommerce/order
```
#### Request Example


#### Request Parameters


#### Response Example


### Submit order - JSON controller

```
PUT /ecommerce/order
```
#### Request Example


#### Request Parameters


#### Response Example

### Get payments - JSON controller

```
GET /ecommerce/payment
```
#### Request Example


#### Request Parameters


#### Response Example

### Add a new payment - JSON controller

```
PUT /ecommerce/payment
```
#### Request Example


#### Request Parameters


#### Response Example

### Delete a payment - JSON controller

```
DELETE /ecommerce/payment/{paymentId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Refund a payment - JSON controller

```
PUT /ecommerce/refund
```
#### Request Example


#### Request Parameters


#### Response Example

### Create a new payment method for user/customer - JSON controller

```
PUT /ecommerce/payment-method
```
#### Request Example


#### Request Parameters


#### Response Example

### Set default payment method for user/customer - JSON controller

```
PATCH /ecommerce/payment-method/set-default
```
#### Request Example


#### Request Parameters


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





### Update payment method - JSON controller

```
PATCH /ecommerce/payment-method/{paymentMethodId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Delete payment method - JSON controller

```
DELETE /ecommerce/payment-method/{paymentMethodId}
```
#### Request Example


#### Request Parameters


#### Response Example


### Get all user's payment methods - JSON controller

```
GET /ecommerce/user-payment-method/{userId}
```
#### Request Example


#### Request Parameters


#### Response Example


### Get user addresses - JSON controller

```
GET /ecommerce/address
```
#### Request Example


#### Request Parameters


#### Response Example

### Create a new address for user/customer - JSON controller

```
PUT /ecommerce/address
```
#### Request Example


#### Request Parameters


#### Response Example

### Update address - JSON controller

```
PATCH /ecommerce/address/{addressId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Delete address - JSON controller

```
DELETE /ecommerce/address/{addressId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Pull subscriptions paginated - JSON controller

```
GET /ecommerce/subscriptions
```
#### Request Example


#### Request Parameters


#### Response Example

### Create a new subscription - JSON controller

```
PUT /ecommerce/subscription
```
#### Request Example


#### Request Parameters


#### Response Example

### Update a subscription - JSON controller

```
PATCH /ecommerce/subscription/{subscriptionId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Delete a subscription - JSON controller

```
DELETE /ecommerce/subscription/{subscriptionId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Renew a subscription - JSON controller

```
POST /ecommerce/subscription-renew/{subscriptionId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Pull orders - JSON controller

```
GET /ecommerce/orders
```
#### Request Example


#### Request Parameters


#### Response Example

### Update order - JSON controller

```
PATCH /ecommerce/order/{orderId}
```
#### Request Example


#### Request Parameters


#### Response Example

### Delete order - JSON controller

```
DELETE /ecommerce/order/{orderId}
```
#### Request Example


#### Request Parameters


#### Response Example


### Pull shipping fulfillments - JSON controller

```
GET /ecommerce/fulfillment
```
#### Request Example


#### Request Parameters


#### Response Example

### Fulfilled order or order item - JSON controller

```
PATCH /ecommerce/fulfillment
```
#### Request Example


#### Request Parameters


#### Response Example

### Delete shipping fulfillment  - JSON controller

```
DELETE /ecommerce/fulfillment
```
#### Request Example


#### Request Parameters


#### Response Example

### Get products statistics  - JSON controller

```
GET /ecommerce/stats/products
```
#### Request Example


#### Request Parameters


#### Response Example


### Get orders statistics  - JSON controller

```
GET /ecommerce/stats/orders
```
#### Request Example


#### Request Parameters


#### Response Example

