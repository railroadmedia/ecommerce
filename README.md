Ecommerce
========================================================================================================================
- [Ecommerce](#ecommerce)
  * [Install](#install)
  * [Required abilities](#required-abilities)
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
    + [Pull product - JSON controller](#pull-product---json-controller)
      - [Request Example](#request-example-5)
      - [Request Parameters](#request-parameters-5)
      - [Response Example](#response-example-5)
    + [Add a new product - JSON controller](#add-a-new-product---json-controller)
      - [Request Example](#request-example-6)
      - [Request Parameters](#request-parameters-6)
      - [Response Example](#response-example-6)
    + [Update product - JSON controller](#update-product---json-controller)
      - [Request Example](#request-example-7)
      - [Request Parameters](#request-parameters-7)
      - [Response Example](#response-example-7)
    + [Delete product - JSON controller](#delete-product---json-controller)
      - [Request Example](#request-example-8)
      - [Request Parameters](#request-parameters-8)
      - [Response Example](#response-example-8)
    + [Upload product thumbnail - JSON controller](#upload-product-thumbnail---json-controller)
      - [Request Example](#request-example-9)
      - [Request Parameters](#request-parameters-9)
      - [Response Example](#response-example-9)
    + [Get all shipping options - JSON controller](#get-all-shipping-options---json-controller)
      - [Request Example](#request-example-10)
      - [Request Parameters](#request-parameters-10)
      - [Response Example](#response-example-10)
    + [Add a new shipping option - JSON controller](#add-a-new-shipping-option---json-controller)
      - [Request Example](#request-example-11)
      - [Request Parameters](#request-parameters-11)
      - [Response Example](#response-example-11)
    + [Update shipping option - JSON controller](#update-shipping-option---json-controller)
      - [Request Example](#request-example-12)
      - [Request Parameters](#request-parameters-12)
      - [Response Example](#response-example-12)
    + [Delete shipping option - JSON controller](#delete-shipping-option---json-controller)
      - [Request Example](#request-example-13)
      - [Request Parameters](#request-parameters-13)
      - [Response Example](#response-example-13)
    + [Add shipping costs weight range - JSON controller](#add-shipping-costs-weight-range---json-controller)
      - [Request Example](#request-example-14)
      - [Request Parameters](#request-parameters-14)
      - [Response Example](#response-example-14)
    + [Update a shipping costs weight range - JSON controller](#update-a-shipping-costs-weight-range---json-controller)
      - [Request Example](#request-example-15)
      - [Request Parameters](#request-parameters-15)
      - [Response Example](#response-example-15)
    + [Delete a shipping costs weight range - JSON controller](#delete-a-shipping-costs-weight-range---json-controller)
      - [Request Example](#request-example-16)
      - [Request Parameters](#request-parameters-16)
      - [Response Example](#response-example-16)
    + [Get all discounts - JSON controller](#get-all-discounts---json-controller)
      - [Request Example](#request-example-17)
      - [Request Parameters](#request-parameters-17)
      - [Response Example](#response-example-17)
    + [Add a new discount - JSON controller](#add-a-new-discount---json-controller)
      - [Request Example](#request-example-18)
      - [Request Parameters](#request-parameters-18)
      - [Response Example](#response-example-18)
    + [Update discount - JSON controller](#update-discount---json-controller)
      - [Request Example](#request-example-19)
      - [Request Parameters](#request-parameters-19)
      - [Response Example](#response-example-19)
    + [Delete discount - JSON controller](#delete-discount---json-controller)
      - [Request Example](#request-example-20)
      - [Request Parameters](#request-parameters-20)
      - [Response Example](#response-example-20)
    + [Define a discount criteria for discount - JSON controller](#define-a-discount-criteria-for-discount---json-controller)
      - [Request Example](#request-example-21)
      - [Request Parameters](#request-parameters-21)
      - [Response Example](#response-example-21)
    + [Update discount criteria - JSON controller](#update-discount-criteria---json-controller)
      - [Request Example](#request-example-22)
      - [Request Parameters](#request-parameters-22)
      - [Response Example](#response-example-22)
    + [Delete discount criteria - JSON controller](#delete-discount-criteria---json-controller)
      - [Request Example](#request-example-23)
      - [Request Parameters](#request-parameters-23)
      - [Response Example](#response-example-23)
    + [Prepare order form - JSON controller](#prepare-order-form---json-controller)
      - [Request Example](#request-example-24)
      - [Response Example](#response-example-24)
    + [Get payments - JSON controller](#get-payments---json-controller)
      - [Request Example](#request-example-25)
      - [Request Parameters](#request-parameters-24)
      - [Response Example](#response-example-25)
    + [Add a new payment - JSON controller](#add-a-new-payment---json-controller)
      - [Request Example](#request-example-26)
      - [Request Parameters](#request-parameters-25)
      - [Response Example](#response-example-26)
    + [Delete a payment - JSON controller](#delete-a-payment---json-controller)
      - [Request Example](#request-example-27)
      - [Request Parameters](#request-parameters-26)
      - [Response Example](#response-example-27)
    + [Refund a payment - JSON controller](#refund-a-payment---json-controller)
      - [Request Example](#request-example-28)
      - [Request Parameters](#request-parameters-27)
      - [Response Example](#response-example-28)
    + [Create a new payment method for user/customer - JSON controller](#create-a-new-payment-method-for-user-customer---json-controller)
      - [Request Example](#request-example-29)
      - [Request Parameters](#request-parameters-28)
      - [Response Example](#response-example-29)
    + [Set default payment method for user/customer - JSON controller](#set-default-payment-method-for-user-customer---json-controller)
      - [Request Example](#request-example-30)
      - [Request Parameters](#request-parameters-29)
      - [Response Example](#response-example-30)
    + [Get PayPal billing agreement express checkout url - JSON controller](#get-paypal-billing-agreement-express-checkout-url---json-controller)
      - [Request Example](#request-example-31)
      - [Request Parameters](#request-parameters-30)
      - [Response Example](#response-example-31)
    + [Create PayPal billing agreement - JSON controller](#create-paypal-billing-agreement---json-controller)
      - [Request Example](#request-example-32)
      - [Request Parameters](#request-parameters-31)
      - [Response Example](#response-example-32)
    + [Update Credit card payment method - JSON controller](#update-credit-card-payment-method---json-controller)
      - [Request Example](#request-example-33)
      - [Request Parameters](#request-parameters-32)
      - [Response Example](#response-example-33)
    + [Delete payment method - JSON controller](#delete-payment-method---json-controller)
      - [Request Example](#request-example-34)
      - [Request Parameters](#request-parameters-33)
      - [Response Example](#response-example-34)
    + [Get all user's payment methods - JSON controller](#get-all-user-s-payment-methods---json-controller)
      - [Request Example](#request-example-35)
      - [Request Parameters](#request-parameters-34)
      - [Response Example](#response-example-35)
    + [Get user addresses - JSON controller](#get-user-addresses---json-controller)
      - [Request Example](#request-example-36)
      - [Request Parameters](#request-parameters-35)
      - [Response Example](#response-example-36)
    + [Create a new address for user/customer - JSON controller](#create-a-new-address-for-user-customer---json-controller)
      - [Request Example](#request-example-37)
      - [Request Parameters](#request-parameters-36)
      - [Response Example](#response-example-37)
    + [Update address - JSON controller](#update-address---json-controller)
      - [Request Example](#request-example-38)
      - [Request Parameters](#request-parameters-37)
      - [Response Example](#response-example-38)
    + [Delete address - JSON controller](#delete-address---json-controller)
      - [Request Example](#request-example-39)
      - [Request Parameters](#request-parameters-38)
      - [Response Example](#response-example-39)
    + [Pull subscriptions paginated - JSON controller](#pull-subscriptions-paginated---json-controller)
      - [Request Example](#request-example-40)
      - [Request Parameters](#request-parameters-39)
      - [Response Example](#response-example-40)
    + [Create a new subscription - JSON controller](#create-a-new-subscription---json-controller)
      - [Request Example](#request-example-41)
      - [Request Parameters](#request-parameters-40)
      - [Response Example](#response-example-41)
    + [Update a subscription - JSON controller](#update-a-subscription---json-controller)
      - [Request Example](#request-example-42)
      - [Request Parameters](#request-parameters-41)
      - [Response Example](#response-example-42)
    + [Delete a subscription - JSON controller](#delete-a-subscription---json-controller)
      - [Request Example](#request-example-43)
      - [Request Parameters](#request-parameters-42)
      - [Response Example](#response-example-43)
    + [Renew a subscription - JSON controller](#renew-a-subscription---json-controller)
      - [Request Example](#request-example-44)
      - [Request Parameters](#request-parameters-43)
      - [Response Example](#response-example-44)
    + [Pull orders - JSON controller](#pull-orders---json-controller)
      - [Request Example](#request-example-45)
      - [Request Parameters](#request-parameters-44)
      - [Response Example](#response-example-45)
    + [Update order - JSON controller](#update-order---json-controller)
      - [Request Example](#request-example-46)
      - [Request Parameters](#request-parameters-45)
      - [Response Example](#response-example-46)
    + [Delete order - JSON controller](#delete-order---json-controller)
      - [Request Example](#request-example-47)
      - [Request Parameters](#request-parameters-46)
      - [Response Example](#response-example-47)
    + [Pull shipping fulfillments - JSON controller](#pull-shipping-fulfillments---json-controller)
      - [Request Example](#request-example-48)
      - [Request Parameters](#request-parameters-47)
      - [Response Example](#response-example-48)
    + [Fulfilled order or order item - JSON controller](#fulfilled-order-or-order-item---json-controller)
      - [Request Example](#request-example-49)
      - [Request Parameters](#request-parameters-48)
      - [Response Example](#response-example-49)
    + [Delete shipping fulfillment  - JSON controller](#delete-shipping-fulfillment----json-controller)
      - [Request Example](#request-example-50)
      - [Request Parameters](#request-parameters-49)
      - [Response Example](#response-example-50)
    + [Get products statistics  - JSON controller](#get-products-statistics----json-controller)
      - [Request Example](#request-example-51)
      - [Request Parameters](#request-parameters-50)
      - [Response Example](#response-example-51)
    + [Get orders statistics  - JSON controller](#get-orders-statistics----json-controller)
      - [Request Example](#request-example-52)
      - [Request Parameters](#request-parameters-51)
      - [Response Example](#response-example-52)
    + [Get all access codes](#get-all-access-codes)
      - [Request Example](#request-example-53)
      - [Request Parameters](#request-parameters-52)
      - [Response Example](#response-example-53)
    + [Search access codes](#search-access-codes)
      - [Request Example](#request-example-54)
      - [Request Parameters](#request-parameters-53)
      - [Response Example](#response-example-54)
    + [Claim access code - JSON controller](#claim-access-code---json-controller)
      - [Request Example](#request-example-55)
      - [Request Parameters](#request-parameters-54)
      - [Response Example](#response-example-55)
    + [Release access code - JSON controller](#release-access-code---json-controller)
      - [Request Example](#request-example-56)
      - [Request Parameters](#request-parameters-55)
      - [Response Example](#response-example-56)
    + [Claim access code - FORM controller](#claim-access-code---form-controller)
      - [Request Example](#request-example-57)
      - [Request Parameters](#request-parameters-56)
      - [Response Example](#response-example-57)

<!-- ecotrust-canada.github.io/markdown-toc -->


## Install
With composer command
``` composer require railroad/ecommerce:1.0.43 ```
## Required abilities 

| Endpoint                                      |  Route                                                    |  Required ability           |  Observation                                                              | 
|-----------------------------------------------|-----------------------------------------------------------|-----------------------------|---------------------------------------------------------------------------| 
| Get all products                              |  GET /ecommerce/product                                   |                             |  'pull.inactive.products' - to pull inactive products also                | 
| Pull product                                  |  GET /ecommerce/product/{productId}                       |                             |  'pull.inactive.products' - to pull an inactive product                   | 
| Add a new product                             |  PUT /ecommerce/product                                   |  'create.product'           |                                                                           | 
| Update product                                |  PATCH /ecommerce/product/{productId}                     |  'update.product'           |                                                                           | 
| Delete product                                |  DELETE /ecommerce/product/{productId}                    |  'delete.product'           |                                                                           | 
| Get all shipping options                      |  GET /ecommerce/shipping-options                          |  'pull.shipping.options'    |                                                                           | 
| Add a new shipping option                     |  PUT /ecommerce/shipping-options                          | 'create.shipping.option'    |                                                                           | 
| Update shipping option                        |  PATCH /ecommerce/shipping-options/{shippingOptionId}     |  'edit.shipping.option'     |                                                                           | 
| Delete shipping option                        |  DELETE /ecommerce/shipping-options/{shippingOptionId}    |  'delete.shipping.option'   |                                                                           | 
| Add shipping costs weight range               |  PUT /ecommerce/shipping-cost                             |  'create.shipping_cost'     |                                                                           | 
| Update a shipping costs weight range          |  PATCH /ecommerce/shipping-cost/{shippingCostId}          |  'edit.shipping_cost'       |                                                                           | 
| Delete a shipping costs weight range          |  DELETE /ecommerce/shipping-cost/{shippingCostId}         |  'delete.shipping_cost'     |                                                                           | 
| Get all discounts                             |  GET /ecommerce/discounts                                 |  'pull.discounts'           |                                                                           | 
| Add a new discount                            |  PUT /ecommerce/discount                                  |  'create.discount'          |                                                                           | 
| Update discount                               |  PATCH /ecommerce/discount/{discountId}                   |  'update.discount'          |                                                                           | 
| Delete discount                               |  DELETE /ecommerce/discount/{discountId}                  |  'delete.discount'          |                                                                           | 
| Define a discount criteria for discount       |  PUT /ecommerce/discount-criteria/{discountId}            |  'create.discount.criteria' |                                                                           | 
| Update discount criteria                      |  PATCH /ecommerce/discount-criteria/{discountCriteriaId}  |  'update.discount.criteria' |                                                                           | 
| Delete discount criteria                      |  DELETE /ecommerce/discount-criteria/{discountCriteriaId} |  'delete.discount.criteria' |                                                                           | 
| Get payments                                  |  GET /ecommerce/payment                                   |  'list.payment'             |                                                                           | 
| Add a new payment                             |  PUT /ecommerce/payment                                   |  'create.payment'           |                                                                           | 
| Delete a payment                              |  DELETE /ecommerce/payment/{paymentId}                    |  'delete.payment'           |                                                                           | 
| Refund a payment                              |  PUT /ecommerce/refund                                    |  'store.refund'             |                                                                           | 
| Create a new payment method for user/customer |  PUT /ecommerce/payment-method                            |  'create.payment.method'    |                                                                           | 
| Set default payment method for user/customer  |  PATCH /ecommerce/payment-method/set-default              |                             |  'update.payment.method' ability to set as default a method you don't own | 
| Update Credit card payment method             |  PATCH /ecommerce/payment-method/{paymentMethodId}        |  'update.payment.method'    |                                                                           | 
| Delete payment method                         |  DELETE /ecommerce/payment-method/{paymentMethodId}       |                             |  'delete.payment.method' - to delete methods you don't own                | 
| Get all user's payment methods                |  GET /ecommerce/user-payment-method/{userId}              |  'pull.user.payment.method' |                                                                           | 
| Get user addresses                            |  GET /ecommerce/address                                   |                             |  'pull.user.payment.method' to pull address for other users               | 
| Update user's address                         |  PATCH /ecommerce/address/{addressId}                     |                             |  'update.address' - to update other user address                          | 
| Delete address                                |  DELETE /ecommerce/address/{addressId}                    |                             | 'delete.address' - to delete other user's address                         | 
| Pull subscriptions paginated                  |  GET /ecommerce/subscriptions                             |  'pull.subscriptions'       |                                                                           | 
| Create a new subscription                     |  PUT /ecommerce/subscription                              |  'create.subscription'      |                                                                           | 
| Update a subscription                         |  PATCH /ecommerce/subscription/{subscriptionId}           |  'edit.subscription'        |                                                                           | 
| Delete a subscription                         |  DELETE /ecommerce/subscription/{subscriptionId}          |  'delete.subscription'      |                                                                           | 
| Renew a subscription                          |  POST /ecommerce/subscription-renew/{subscriptionId}      |  'renew.subscription'       |                                                                           | 
| Pull orders                                   |  GET /ecommerce/orders                                    |  'pull.orders'              |                                                                           | 
| Update order                                  |  PATCH /ecommerce/order/{orderId}                         |  'edit.order'               |                                                                           | 
| Delete order                                  |  DELETE /ecommerce/order/{orderId}                        |  'delete.order'             |                                                                           | 
| Pull shipping fulfillments                    |  GET /ecommerce/fulfillment                               |  'pull.fulfillments'        |                                                                           | 
| Fulfilled order or order item                 |  PATCH /ecommerce/fulfillment                             |  'fulfilled.fulfillment'    |                                                                           | 
| Delete shipping fulfillment                   |  DELETE /ecommerce/fulfillment                            |  'delete.fulfillment'       |                                                                           | 
| Get products statistics                       |  GET /ecommerce/stats/products                            |  'pull.stats'               |                                                                           | 
| Get orders statistics                         |  GET /ecommerce/stats/orders                              |  'pull.stats'               |                                                                           | 
| Get all access codes                          |  GET /access-codes                                        |  'pull.access_codes'        |                                                                           | 
| Search access codes                           |  GET /access-codes/search                                 |  'pull.access_codes'        |                                                                           |
| Claim access code                             |  POST /access-codes/claim                                 |  'claim.access_codes'       | Endpoint for admins to claim access code for user                         |
| Release access code                           |  POST /access-codes/release                               |  'release.access_codes'     |                                                                           |
| Redeem access code                            |  POST /access-codes/redeem                                |                             | Endpoint for users to claim access code                                   |

## API Reference


### Add item to cart - forms controller

```
GET /ecommerce/add-to-cart
```

Add products to cart; if the products are active and available(the product stock > requested quantity).

The success field from response it's set to false if at least one product it's not active or available.
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

Remove product from cart.
     
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

 Update the cart item quantity.
 
 If the product it's not active or it's not available(the product stock it's smaller that the quantity)
 an error message it's returned in notAvailableProducts, success = false and the cart item quantity it's not
     modified.
     
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

```201 OK```

```json
{
	"success":true,
	"addedProducts":[
		{
		"id":"83e531fbbe7b8bcbe25c67426d86c98fe791215c546f74a02b0f1b2cc24c1d7c",
		"name":"quae",
		"description":"Dicta tenetur odio quisquam nostrum velit. Vel nesciunt ducimus sed. Cumque officiis quis debitis est id et maxime ut. Maxime porro neque beatae optio.",
		"quantity":"7",
		"price":"149",
		"totalPrice":1043,
		"requiresShippingAddress":"1",
		"requiresBillinggAddress":"1",
		"subscriptionIntervalType":"qui",
		"subscriptionIntervalCount":1820561976,
		"weight":0,
		"options":{
			"product-id":"1",
			"product":{
				"id":"1",
				"brand":"drumeo",
				"name":"quae",
				"sku":"deleniti",
				"price":"149",
				"type":"subscription",
				"active":"1",
				"description":"Dicta tenetur odio quisquam nostrum velit. Vel nesciunt ducimus sed. Cumque officiis quis debitis est id et maxime ut. Maxime porro neque beatae optio.",
				"thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?25629",
				"is_physical":"1",
				"weight":"42",
				"subscription_interval_type":"day",
				"subscription_interval_count":"6",
				"stock":"1834",
				"created_on":"2018-10-18 06:13:53",
				"updated_on":null,
				"discounts":[]
			}
		}
	}],
	"cartNumberOfItems":1,
	"notAvailableProducts":[],
	"tax":125.16,
	"total":1168.16,
	"cartItems":[{
		"id":"83e531fbbe7b8bcbe25c67426d86c98fe791215c546f74a02b0f1b2cc24c1d7c",
		"name":"quae",
		"description":"Dicta tenetur odio quisquam nostrum velit. Vel nesciunt ducimus sed. Cumque officiis quis debitis est id et maxime ut. Maxime porro neque beatae optio.",
		"quantity":"7",
		"price":"149",
		"totalPrice":1043,
		"requiresShippingAddress":"1",
		"requiresBillinggAddress":"1",
		"subscriptionIntervalType":"qui",
		"subscriptionIntervalCount":1820561976,
		"weight":0,
		"options":{
			"product-id":"1",
			"product":{
				"id":"1",
				"brand":"drumeo",
				"name":"quae",
				"sku":"deleniti",
				"price":"149",
				"type":"subscription",
				"active":"1",
				"description":"Dicta tenetur odio quisquam nostrum velit. Vel nesciunt ducimus sed. Cumque officiis quis debitis est id et maxime ut. Maxime porro neque beatae optio.",
				"thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?25629",
				"is_physical":"1",
				"weight":"42",
				"subscription_interval_type":"day",
				"subscription_interval_count":"6",
				"stock":"1834",
				"created_on":"2018-10-18 06:13:53",
				"updated_on":null,
				"discounts":[]
			}
		},
		"itemTax":125.16,
		"itemShippingCosts":0
		}],
	"isPaymentPlanEligible":false,
	"paymentPlanPricing":[]
	}]
}
   
```

```201 Insuficient stock```

```json
[
   {
      "success":false,
      "addedProducts":[
         {
            "id":"75be891df24aecfd3bebdee8656493eec5ca13ca783e8154506f6c345ff5ffda",
            "name":"delectus",
            "description":"Enim asperiores cum velit est et. Accusamus quia eius qui numquam enim consequatur minima eius. Et enim quae autem eum ut. Non perferendis quia maxime ea est assumenda cupiditate.",
            "quantity":1,
            "price":"170",
            "totalPrice":170,
            "requiresShippingAddress":"0",
            "requiresBillinggAddress":"0",
            "subscriptionIntervalType":"et",
            "subscriptionIntervalCount":0,
            "weight":1667156608,
            "options":{
               "product-id":"1",
               "product":{
                  "id":"1",
                  "brand":"drumeo",
                  "name":"delectus",
                  "sku":"impedit",
                  "price":"170",
                  "type":"product",
                  "active":"1",
                  "description":"Enim asperiores cum velit est et. Accusamus quia eius qui numquam enim consequatur minima eius. Et enim quae autem eum ut. Non perferendis quia maxime ea est assumenda cupiditate.",
                  "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?59203",
                  "is_physical":"0",
                  "weight":"64",
                  "subscription_interval_type":"day",
                  "subscription_interval_count":"3",
                  "stock":"2",
                  "created_on":"2018-10-18 06:20:39",
                  "updated_on":null,
                  "discounts":[

                  ]
               }
            }
         }
      ],
      "cartNumberOfItems":1,
      "notAvailableProducts":[
         "The quantity can not be updated. The product stock(2) is smaller than the quantity you've selected(9)"
      ],
      "tax":20.4,
      "total":190.4,
      "cartItems":[
         {
            "id":"75be891df24aecfd3bebdee8656493eec5ca13ca783e8154506f6c345ff5ffda",
            "name":"delectus",
            "description":"Enim asperiores cum velit est et. Accusamus quia eius qui numquam enim consequatur minima eius. Et enim quae autem eum ut. Non perferendis quia maxime ea est assumenda cupiditate.",
            "quantity":1,
            "price":"170",
            "totalPrice":170,
            "requiresShippingAddress":"0",
            "requiresBillinggAddress":"0",
            "subscriptionIntervalType":"et",
            "subscriptionIntervalCount":0,
            "weight":1667156608,
            "options":{
               "product-id":"1",
               "product":{
                  "id":"1",
                  "brand":"drumeo",
                  "name":"delectus",
                  "sku":"impedit",
                  "price":"170",
                  "type":"product",
                  "active":"1",
                  "description":"Enim asperiores cum velit est et. Accusamus quia eius qui numquam enim consequatur minima eius. Et enim quae autem eum ut. Non perferendis quia maxime ea est assumenda cupiditate.",
                  "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?59203",
                  "is_physical":"0",
                  "weight":"64",
                  "subscription_interval_type":"day",
                  "subscription_interval_count":"3",
                  "stock":"2",
                  "created_on":"2018-10-18 06:20:39",
                  "updated_on":null,
                  "discounts":[

                  ]
               }
            },
            "itemTax":20.4,
            "itemShippingCosts":0
         }
      ],
      "isPaymentPlanEligible":true,
      "paymentPlanPricing":{
         "1":190.4,
         "2":95.7,
         "5":38.28
      }
   }
]
```

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

```201 OK```

```json
{
   "shipping":{
      "streetLineOne":"70066 Sigmund Cliff Apt. 635\nGerholdview, KY 65090-9411",
      "city":"Mariamton",
      "firstName":"et"
   },
   "billing":{
      "country":"Canada",
      "region":"British Columbia"
   }
}
```

### Get all products - JSON controller

```
GET /ecommerce/product
```

Pull paginated products. 

For user with 'pull.inactive.products' ability all products(active/inactive) will be pulled. 

#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/product?page=3&limit=25&brands[]=drumeo&order_by_column=created_on&order_by_direction=desc',
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
| path\|query\|body |  key                |  required |  default                   |  description\|notes                                  | 
|-------------------|---------------------|-----------|----------------------------|------------------------------------------------------| 
| body              |  brands             |  no       | [value set in config file] |  Only products from specified brands will be pulled. | 
| query             |  page               |  no       |  1                         |  Pagination page.                                    | 
| query             |  limit              |  no       |  10                        |  Amount of products to pull per page.                | 
| query             |  order_by_column    |  no       |  created_on                |  Sort column name.                                   | 
| query             |  order_by_direction |  no       |  desc                      |  Sort column direction                               | 


#### Response Example

```200 OK```
```json
{
   "id":"1",
   "brand":"drumeo",
   "name":"commodi",
   "sku":"tempora",
   "price":"288",
   "type":"product",
   "active":"0",
   "description":"Qui omnis rerum dignissimos qui soluta. Sunt dolores at et quam eos. Ut ea cupiditate et rerum doloremque.",
   "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?80575",
   "is_physical":"1",
   "weight":"88",
   "subscription_interval_type":"day",
   "subscription_interval_count":"1",
   "stock":"16",
   "created_on":"2018-10-18 06:26:11",
   "updated_on":null,
   "discounts":[

   ]
},
...
{
   "id":"10",
   "brand":"drumeo",
   "name":"vero",
   "sku":"eveniet",
   "price":"877",
   "type":"product",
   "active":"0",
   "description":"Suscipit non nulla nulla aut. In provident dolores distinctio quae ut. Quis dolor ut dolorem nobis qui. Alias ut aut sit ea dicta.",
   "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?98967",
   "is_physical":"1",
   "weight":"64",
   "subscription_interval_type":"day",
   "subscription_interval_count":"9",
   "stock":"886",
   "created_on":"2018-10-18 06:26:11",
   "updated_on":null,
   "discounts":[

   ]
}

```
### Pull product - JSON controller
```
GET /ecommerce/product/{productId}
```

Pull specific product. 

Only users with 'pull.inactive.products' ability  can pull inactive product. 

#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/product/1',
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
| path              |  productId          |  yes      |                           |  The product id you want to pull.          |


#### Response Example

```200 OK```
```json
{
   "id":"1",
   "brand":"drumeo",
   "name":"commodi",
   "sku":"tempora",
   "price":"288",
   "type":"product",
   "active":"0",
   "description":"Qui omnis rerum dignissimos qui soluta. Sunt dolores at et quam eos. Ut ea cupiditate et rerum doloremque.",
   "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?80575",
   "is_physical":"1",
   "weight":"88",
   "subscription_interval_type":"day",
   "subscription_interval_count":"1",
   "stock":"16",
   "created_on":"2018-10-18 06:26:11",
   "updated_on":null,
   "discounts":[
   ]
}

```

```404 Not Found```
```json
{
"data":{},
"meta":{
  "errors":{  
      "title":"Not found.",
      "detail":"Pull failed, product not found with id: 1"
      }
  }
}

```


### Add a new product - JSON controller

```
PUT /ecommerce/product
```

Users with 'create.product' ability can create a new product. 

The method return new created product in JSON format.

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

```200 OK```
```json
{
   "id":"1",
   "brand":"drumeo",
   "name":"et",
   "sku":"non",
   "price":"712",
   "type":"subscription",
   "active":"0",
   "description":"Dolorum reiciendis sunt et rerum aut ut molestiae. Est sunt aspernatur deserunt ab non est at. Expedita qui qui nisi. Omnis assumenda necessitatibus dolor et asperiores impedit.",
   "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?96853",
   "is_physical":"0",
   "weight":"61",
   "subscription_interval_type":"year",
   "subscription_interval_count":"6",
   "stock":"484",
   "created_on":"2018-10-18 06:28:44",
   "updated_on":null,
   "discounts":[

   ]
}
```
```403 Not allowed```
```422 Unprocessable Entity```
```json
"data":[
],
"meta":{
   "totalResults":0,
   "page":1,
   "limit":10,
   "errors":[
      {
         "source":"weight",
         "detail":"The weight field is required when is physical is 1."
      }
   ]
}
```

### Update product - JSON controller

```
PATCH /ecommerce/product/{productId}
```

Users with 'update.product' ability can update a product based on product id. Will return updated product in JSON format.

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

```201 OK```
```json
"data":[
   {
      "id":"1",
      "brand":"drumeo",
      "name":"facilis",
      "sku":"impedit",
      "price":"178",
      "type":"subscription",
      "active":"1",
      "description":"Magnam velit molestiae minima excepturi numquam. Ex explicabo architecto dolorem. Quia fugit ea sunt qui. Atque nostrum voluptatibus quia et soluta.",
      "thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?70810",
      "is_physical":"0",
      "weight":"57",
      "subscription_interval_type":"day",
      "subscription_interval_count":"9",
      "stock":"352",
      "created_on":"2018-10-18 06:34:20",
      "updated_on":"2018-10-18 06:34:20",
      "discounts":[

      ]
   }
]
```
```422 Unprocessable Entity```
```json
{
   "data":[

   ],
   "meta":{
      "totalResults":0,
      "page":1,
      "limit":10,
      "errors":[
         {
            "source":"subscription_interval_type",
            "detail":"The subscription interval type field is required when type is subscription."
         },
         {
            "source":"subscription_interval_count",
            "detail":"The subscription interval count field is required when type is subscription."
         }
      ]
   }
}
```
```404 Not Found```
```json
"data":[
],
"meta":{
   "errors":{
      "title":"Not found.",
      "detail":"Update failed, product not found with id: 366148449"
   }
}
```

### Delete product - JSON controller

```
DELETE /ecommerce/product/{productId}
```

Delete a product that it's not connected to orders or discounts and return a JsonResponse.

Only users with 'delete.product' ability can delete products.

Throw  
- NotFoundException if the product not exist or the user have not rights to delete the product
- NotAllowedException if the product it's connected to orders or discounts
     
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

```204 No Content```

```404 Not Found```
```json
{
   "data":[

   ],
   "meta":{
      "errors":{
         "title":"Not found.",
         "detail":"Delete failed, product not found with id: 1676493580"
      }
   }
}
```
### Upload product thumbnail - JSON controller

```
PUT /ecommerce/product/upload/
```

Upload product thumbnail on remote storage using remotestorage package.
       
Throw an error JSON response if the upload failed or return the uploaded thumbnail url.
     
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

```201 OK```

```400 Upload failed```


### Get all shipping options - JSON controller

```
GET /ecommerce/shipping-options
```

Pull shipping options. 

Only users with 'pull.shipping.options' ability can pull shipping options.

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
```200 OK```

### Add a new shipping option - JSON controller

```
PUT /ecommerce/shipping-options
```

Create a new shipping option and return it in JSON format.

Only users with 'create.shipping.option' ability can create shipping options.


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
```200 OK```


### Update shipping option - JSON controller

```
PATCH /ecommerce/shipping-options/{shippingOptionId}
```

Update a shipping option based on id and return it in JSON format or proper exception if the shipping option not exist.

Only users with 'edit.shipping.option' ability can update shipping options.

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

```201 OK```

```404 Not Found```

### Delete shipping option - JSON controller

```
DELETE /ecommerce/shipping-options/{shippingOptionId}
```

Delete a shipping option if exist in the database.
 
Only users with 'delete.shipping.option' ability can delete shipping option.
  
Throw proper exception if the shipping option not exist in the database or a json response with status 204.
     
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

```204 No Content```

```404 Not Found```


### Add shipping costs weight range - JSON controller

```
PUT /ecommerce/shipping-cost
```

Store a shipping cost weight range in the database and return it in JSON format if the shipping option exist.

Only users with 'create.shipping_cost' ability can create shipping costs weight range.

Return a JSON response with the shopping cost weight range or throw the proper exception.
     
     
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
```200 OK```

### Update a shipping costs weight range - JSON controller

```
PATCH /ecommerce/shipping-cost/{shippingCostId}
```

Update a shipping cost weight range based on id and return it in JSON format or proper exception if the shipping cost weight range not exist

Only users with 'edit.shipping_cost' ability can update shipping costs weight range.

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
```201 OK```

```404 Not Found```


### Delete a shipping costs weight range - JSON controller

```
DELETE /ecommerce/shipping-cost/{shippingCostId}
```

Delete a shipping cost weight range if exist in the database.

Only users with 'delete.shipping_cost' ability can delete shipping costs weight range.

Throw proper exception if the shipping cost weight range not exist in the database or a json response with status 204.
     
     
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

```204 No Content```

```404 No Found```


### Get all discounts - JSON controller

```
GET /ecommerce/discounts
```

Pull discounts.

Only users with 'pull.discounts' ability can pull discounts.


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

```200 OK```

### Add a new discount - JSON controller

```
PUT /ecommerce/discount
```

Create a new discount.

Only users with 'create.discount' ability can create discounts. 

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

```200 OK```


### Update discount - JSON controller

```
PATCH /ecommerce/discount/{discountId}
```

Update discount with the data sent on the request. 

Only users with 'update.discount' ability can update discounts.

Throw 404 Not Found Exception if the discount not exists in the database.

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

```201 OK```

```404 Not Found```

### Delete discount - JSON controller

```
DELETE /ecommerce/discount/{discountId}
```

Delete selected discount. 

Only users with 'delete.discount' ability can delete discounts.

Throw 404 Not Found Exception if the discount not exists in the database.

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

```204 No Content```

```404 No Found```


### Define a discount criteria for discount - JSON controller

```
PUT /ecommerce/discount-criteria/{discountId}
```

Create a discount criteria for selected discount. 

Only users with 'create.discount.criteria' ability can create discount criteria.

Throw 404 Not Found Exception if the discount not exists in the database.

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

```200 OK```

```404 Not Found```

### Update discount criteria - JSON controller

```
PATCH /ecommerce/discount-criteria/{discountCriteriaId}
```

Update a discount criteria with the data sent on the request. 

Only users with 'update.discount.criteria' ability can update discount criteria. 

Throw 404 Not Found Exception if the discount criteria not exists in the database.

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

```201 OK```

```404 Not Found```

### Delete discount criteria - JSON controller

```
DELETE /ecommerce/discount-criteria/{discountCriteriaId}
```


Delete a discount criteria. 

Only users with 'delete.discount.criteria' ability can delete discount criteria. 

Throw 404 Not Found Exception if the discount criteria not exists in the database.


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

```204 No Content```

```404 Not Found```


### Prepare order form - JSON controller

```
GET /ecommerce/order
```
Prepare order form based on authenticated user and items from cart. 

Calculate taxes and shipping costs for each item and total, apply discounts and calculate payment plan options and initial price per payment.

Return:
- shippingAddress
- billingAddress
- paymentPlanOptions
- cartItemsSubTotal
- cartItems
- totalDue
- totalTax
- shippingCosts
- pricePerPayment
- initialPricePerPayment

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

#### Response Example

```404 Not Found``` - cart it's empty

```
	{
	"data":[],
	"meta":{
		"errors":{
			"title":"Not found.",
			"detail":"The cart it's empty"
			}
		}
	}
```

```200 OK```
```
	{
	"shippingAddress":null,
	"billingAddress":{
		"country":"Canada",
		"region":"British Columbia"
	},
	"paymentPlanOptions":{
		"1":253.12,
		"2":127.06,
		"5":50.82
	},
	"cartItemsSubTotal":226,
	"cartItems":[
	{
		"id":"224cd3d7e5ece096cb2e7d7c7dae008929ba219d802f678d6de228f3b50d550f",
		"name":"dolorum",
		"description":"alias",
		"quantity":1,
		"price":"147",
		"totalPrice":147,
		"requiresShippingAddress":"0",
		"requiresBillinggAddress":"0",
		"subscriptionIntervalType":"",
		"subscriptionIntervalCount":"",
		"weight":0,
		"options":{
			"product-id":"1",
			"product":{
				"id":"1",
				"brand":"drumeo",
				"name":"dolorum",
				"sku":"expedita",
				"price":"147",
				"type":"product",
				"active":"1",
				"description":"alias",
				"thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?92381",
				"is_physical":"0",
				"weight":"0",
				"subscription_interval_type":"",
				"subscription_interval_count":"",
				"stock":"10",
				"created_on":"2018-10-18 05:41:02",
				"updated_on":null,
				"discounts":[]
			}
		},
		"itemTax":17.64,
		"itemShippingCosts":0
	},{
		"id":"0a720528eb3816a3a67f72b44103077e890b1c1032684ff61996aa167cba3d26",
		"name":"ut",
		"description":"quis",
		"quantity":1,
		"price":"79",
		"totalPrice":79,
		"requiresShippingAddress":"1",
		"requiresBillinggAddress":"1",
		"subscriptionIntervalType":"",
		"subscriptionIntervalCount":"",
		"weight":5.1,
		"options":{
			"product-id":"2",
			"product":{
				"id":"2",
				"brand":"drumeo",
				"name":"ut",
				"sku":"id",
				"price":"79",
				"type":"product",
				"active":"1",
				"description":"quis",
				"thumbnail_url":"https:\/\/lorempixel.com\/640\/480\/?49161",
				"is_physical":"1",
				"weight":"5.1",
				"subscription_interval_type":"",
				"subscription_interval_count":"",
				"stock":"237",
				"created_on":"2018-10-18 05:41:02",
				"updated_on":null,
				"discounts":[]
			}
		},
		"itemTax":9.48,
		"itemShippingCosts":0
	}		
	],
	"totalDue":253.12,
	"totalTax":27.12,
	"shippingCosts":0,
	"pricePerPayment":253.12,
	"initialPricePerPayment":253.12
	}
```

### Get payments - JSON controller

```
GET /ecommerce/payment
```

Users with 'list.payment' ability can pull paginated payments. 

If order_id it's set on the request only the payments for selected order are pulled.

If subscription_id it's set on the request only the payments for selected subscription are pulled.

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
Users with 'create.payment' ability can create new payments. If the user add a new payment using a payment method he does not own 'create.payment.method' ability it's required.

Call the method that save a new payment and create the links with subscription or order if it's necessary.

Return a JsonResponse with the new created payment record, in JSON format.
     
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

```200 OK```

```403 Not allowed```

### Delete a payment - JSON controller

```
DELETE /ecommerce/payment/{paymentId}
```

Soft delete a payment.

Only users with 'delete.payment' ability can delete payments.

Throw 404 Not Found Exception if the payment not exists in the database.

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

```204 No Content```

```404 Not Found```

### Refund a payment - JSON controller

```
PUT /ecommerce/refund
```

Call the refund method from the external payment helper and the method that save the refund in the database.

Only users with 'store.refund' ability can refund payments.

Return the new created refund in JSON format
     
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

```200 OK```

### Create a new payment method for user/customer - JSON controller

```
PUT /ecommerce/payment-method
```

Call the service method to create a new payment method based on request parameters.

Only users with 'create.payment.method' ability can create new payment methods.

Return:
- NotFoundException if the request method type parameter it's not defined (paypal or credit card)
- JsonResponse with the new created payment method
     
     
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

```403 Not Allowed```

```200 OK```

### Set default payment method for user/customer - JSON controller

```
PATCH /ecommerce/payment-method/set-default
```

Set selected payment method as default for authenticated user.

If the users have not the 'update.payment.method' ability, can set default only the payment method they own.

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

```200 OK```

```404 Not Found```

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

Update a credit card payment method based on request data and payment method id.

If the users have not the 'update.payment.method' ability, can update only the payment method they own.

Return 
- NotFoundException if the payment method doesn't exist or the user have not rights to access it
- JsonResponse with the updated payment method
     
     
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

```200 OK```

```404 Not Found```

### Delete payment method - JSON controller

```
DELETE /ecommerce/payment-method/{paymentMethodId}
```

Delete a payment method and return a JsonResponse.

If the users have not the 'delete.payment.method' ability, can delete only the payment method they own.

Throw  - NotFoundException if the payment method not exist

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

```204 No Content```

```404 Not Found```

```403 Not Allowed```

### Get all user's payment methods - JSON controller

```
GET /ecommerce/user-payment-method/{userId}
```

Get all user's payment methods with all the method details: credit card or paypal billing agreement

Only users with 'pull.user.payment.method' ability can pull user's payment methods.

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

```200 OK```

### Get user addresses - JSON controller

```
GET /ecommerce/address
```

Pull user address. If the user_id it's not set on the request the method pull authenticated user addresses.

Only users with 'pull.user.payment.method' can pull address for other users. Each user can pull his own address.

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

```200 OK```

### Create a new address for user/customer - JSON controller

```
PUT /ecommerce/address
```

Call the method to store a new address based on request parameters.

Return a JsonResponse with the new created address.
     
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

```200 OK```

### Update address - JSON controller

```
PATCH /ecommerce/address/{addressId}
```

Update an address based on address id and requests parameters.

Each user can update his own address. In other to update other user's address the authenticated user should have the 'update.address' ability.

Return 
- NotFoundException if the address not exists
- NotAllowedException if the user have not rights to access it
- JsonResponse with the updated address

     
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

```201 OK```

```404 Not Found```

```403 Not Allowed```

### Delete address - JSON controller

```
DELETE /ecommerce/address/{addressId}
```

Delete an address based on the id.

Each user can delete his own address. In order to delete other user's address, the authenticated user should have the 'delete.address' ability.

Return 
- NotFoundException if the address not exists
- NotAllowedException if the address it's in used (exists orders defined for the selected address)  or the user have not rights to access it
- JsonResponse with code 204 otherwise
     
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

```204 No Content```

```404 Not Found```

```403 Not Allowed```

### Pull subscriptions paginated - JSON controller

```
GET /ecommerce/subscriptions
```

Pull subscriptions paginated. 

Only users with 'pull.subscriptions' ability can pull subscriptions.

If the user_id it's set on the request only the user's subscriptions are pulled.


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
| path\|query\|body |  key                |  required |  default                 |  description\|notes                                           | 
|-------------------|---------------------|-----------|--------------------------|---------------------------------------------------------------| 
| query             |  user_id            |  no       |                          |  Pull only user's subscriptions                               | 
| query             |  page               |  no       |  1                       |  Pagination page.                                             | 
| query             |  limit              |  no       |  100                     |  Amount of subscriptions to pull per page.                    | 
| query             |  order_by_column    |  no       |  created_on              |  Sort column name.                                            | 
| query             |  order_by_direction |  no       |  desc                    |  Sort column direction.                                       | 
| query             |  brands             |  no       |  [brand from config file]|  Only subscriptions defined on selected brands will be pulled | 



#### Response Example

```200 OK```

### Create a new subscription - JSON controller

```
PUT /ecommerce/subscription
```

Only users with 'create.subscription' ability can create subscriptions.

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

```201 OK```

### Update a subscription - JSON controller

```
PATCH /ecommerce/subscription/{subscriptionId}
```

Update a subscription and returned updated data in JSON format.

Only users with 'edit.subscription' ability can update subscriptions.


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

```201 OK```

```404 Not Found```


### Delete a subscription - JSON controller

```
DELETE /ecommerce/subscription/{subscriptionId}
```

Soft delete a subscription if exists in the database.

Only users with 'delete.subscription' ability can delete subscriptions.


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

```204 No Content```

```404 Not Found```

### Renew a subscription - JSON controller

```
POST /ecommerce/subscription-renew/{subscriptionId}
```

Renew selected subscription.

Only users with 'renew.subscription' ability can renew subscriptions.

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

```201 OK```

```422 ```

### Pull orders - JSON controller

```
GET /ecommerce/orders
```

Pull paginated orders. 

Only users with 'pull.orders' ability can pull orders.

If start-date and end-date are set on the request are pulled only the orders created in specified period. 

If user_id it's set on the request pull only the user's orders.

#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/orders?page=3&limit=25&brands[]=drumeo&order_by_column=created_on&order_by_direction=desc&user_id=12',
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
| path\|query\|body |  key                |  required |  default                  |  description\|notes                                                  | 
|-------------------|---------------------|-----------|---------------------------|----------------------------------------------------------------------| 
| query             |  brands             |  no       | [Value set in config file]|  Order defined on brands                                             | 
| query             |  start_date         |  no       |                           |  If it's defined will be pulled only orders created after start_date | 
| query             |  end_date           |  no       |                           |  f it's defined will be pulled only orders created before end_date   | 
| query             |  user_id            |  no       |                           |  If it's defined only user's orders will be pulled                   | 
| query             |  page               |  no       |  1                        |  Pagination page.                                                    | 
| query             |  limit              |  no       |  100                      |  Amount of orders to pull per page.                                  | 
| query             |  order_by_column    |  no       |  created_on               |  Sort column name.                                                   | 
| query             |  order_by_direction |  no       |  desc                     |  Sort column direction.                                              | 


#### Response Example

```200 OK```

### Update order - JSON controller

```
PATCH /ecommerce/order/{orderId}
```

Update order if exists in db and the user have rights to update it.

Only users with 'edit.order' ability can update order.

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
| path\|query\|body |  key            |  required |  default |  description\|notes        | 
|-------------------|-----------------|-----------|----------|----------------------------| 
| path              |  id             |  yes      |          |  Order id you want to edit | 
| body              |  due            |  no       |          |  Order due                 | 
| body              |  tax            |  no       |          |  Order tax                 | 
| body              |  shipping_costs |  no       |          |  Order shipping costs      | 
| body              |  paid           |  no       |          |  Order paid value          | 


#### Response Example

```201 OK```

```404 Not Found```

### Delete order - JSON controller

```
DELETE /ecommerce/order/{orderId}
```

Soft delete order. Only users with 'delete.order' ability can delete orders.


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

| path\|query\|body |  key     |  required |  default               |  description\|notes                 | 
|-------------------|----------|-----------|------------------------|-------------------------------------| 
| path              |  id      |  yes      |                        |  Order id you want to delete        | 

#### Response Example

```204 No Content```

```404 Not Found```


### Pull shipping fulfillments - JSON controller

```
GET /ecommerce/fulfillment
```

Pull paginated shipping fulfillments. 

If the status it's set on the requests the results are filtered by status.

Only users with 'pull.fulfillments' ability can pull shipping fulfillments.

#### Request Example
```js   

$.ajax({
    url: 'https://www.musora.com' +
        '/ecommerce/fulfillment?page=3&limit=25&order_by_column=created_on&order_by_direction=desc&status=pending',
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
| path\|query\|body |  key                |  required |  default                 |  description\|notes                                            | 
|-------------------|---------------------|-----------|--------------------------|----------------------------------------------------------------| 
| query             |  status             |  no       |  ['pending' 'fulfilled'] |  Only shipping fulfillment with selected status will be pulled | 
| query             |  page               |  no       |  1                       |  Pagination page.                                              | 
| query             |  limit              |  no       |  100                     |   Amount of shipping fulfillments to pull per page.            | 
| query             |  order_by_column    |  no       |  created_on              |  Sort column name.                                             | 
| query             |  order_by_direction |  no       |  desc                    |  Sort column direction.                                        | 


#### Response Example

```200 OK```

### Fulfilled order or order item - JSON controller

```
PATCH /ecommerce/fulfillment
```

Fulfilled order or order item. 

If the order_item_id it's set on the request only the order item it's fulfilled, otherwise entire order it's fulfilled.

Only users with 'fulfilled.fulfillment' ability can fulfilled order or order items.
     
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
| path\|query\|body |  key               |  required |  default |  description\|notes                     | 
|-------------------|--------------------|-----------|----------|-----------------------------------------| 
| body              |  tracking_number   |  yes      |          |  Shipping fulfillment tracking number   | 
| body              |  shipping_company  |  yes      |          |  Company name                           | 
| body              |  order_id          |  yes      |          |  Mark fulfilled order with specified id | 
| body              |  order_item_id     |  no       |          |  Mark fulfilled only an item from order | 


#### Response Example

```201 OK```

```404 Not Found```

### Delete shipping fulfillment  - JSON controller

```
DELETE /ecommerce/fulfillment
```

Delete order or order item fulfillment. 

Only users with 'delete.fulfillment' ability can delete fulfillments.


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

| path\|query\|body |  key           |  required |  default |  description\|notes                                  | 
|-------------------|----------------|-----------|----------|------------------------------------------------------| 
| body              |  order_id      |  yes      |          |  Delete fulfillment for order with specified id      | 
| body              |  order_item_id |  no       |          |  Delete fulfillment for order item with specified id | 


#### Response Example

```204 No Content```

### Get products statistics  - JSON controller

```
GET /ecommerce/stats/products
```

Pull products statistics.

If start-date and end-date are set on the request only the stats from specified period are pulled.

Only users with 'pull.stats' ability can pull statistics.
 
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

| path\|query\|body |  key        |  required |  default                   |  description\|notes        | 
|-------------------|-------------|-----------|----------------------------|----------------------------| 
| body              |  brands     |  no       | [Value set in config file] |  Brands                    | 
| body              |  start_date |  no       |  today                     |  Start of period for stats | 
| body              |  end_date   |  no       |  today                     |  End of period for stats   | 


#### Response Example

```200 OK```


### Get orders statistics  - JSON controller

```
GET /ecommerce/stats/orders
```

Pull orders statistics.

If start-date and end-date are set on the request only the stats from specified period are pulled.

Only users with 'pull.stats' ability can pull statistics.
 
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

| path\|query\|body |  key        |  required |  default                   |  description\|notes        | 
|-------------------|-------------|-----------|----------------------------|----------------------------| 
| body              |  brands     |  no       | [Value set in config file] |  Brands                    | 
| body              |  start_date |  no       |  today                     |  Start of period for stats | 
| body              |  end_date   |  no       |  today                     |  End of period for stats   | 


#### Response Example

```200 OK```


### Get all access codes

```
GET /access-codes
```

Pull paginated access codes.

The products associated with the access codes are included in meta.products key of the response
#### Request Example

```js
$.ajax({
    url: 'https://www.musora.com' +
        '/access-codes?page=3&limit=25&brands[]=drumeo&order_by_column=created_on&order_by_direction=desc',
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
| path\|query\|body |  key                |  required |  default                   |  description\|notes                                  | 
|-------------------|---------------------|-----------|----------------------------|------------------------------------------------------| 
| query             |  brands             |  no       | [value set in config file] |  Only access codes from specified brands will be pulled. | 
| query             |  page               |  no       |  1                         |  Pagination page.                                    | 
| query             |  limit              |  no       |  10                        |  Amount of access codes to pull per page.            | 
| query             |  order_by_column    |  no       |  created_on                |  Sort column name.                                   | 
| query             |  order_by_direction |  no       |  desc                      |  Sort column direction                               |

#### Response Example

```200 OK```
```json
{
  "data": [
    {
      "id": 8,
      "code": "aaaabbbbccccddddeeeeffff",
      "product_ids": [
        1,
        2
      ],
      "is_claimed": 1,
      "claimer_id": 12345,
      "claimed_on": "2018-11-09 13:49:54",
      "brand": "recordeo",
      "created_on": "2018-11-09 13:49:54",
      "updated_on": "2018-11-09 13:49:54",
      "claimer": "bob@example.com"
    },
    ...
    {
      "id": 9,
      "code": "aa12bbbbccccddddeeeeffff",
      "product_ids": [
        9,
        12,
        14
      ],
      "is_claimed": 0,
      "claimer_id": null,
      "claimed_on": null,
      "brand": "recordeo",
      "created_on": "2018-11-09 13:49:54",
      "updated_on": null,
      "claimer": null
    }
  ],
  "meta": {
    "products": [
      {
        "id": 1,
        "brand": "recordeo",
        "name": "Recordeo Membership - Annual",
        "sku": "MEMBERSHIP-1-YEAR",
        "price": "97.00",
        "type": "product",
        "active": 1,
        "description": "The Ultimate Guide To Recording Drums",
        "thumbnail_url": "https://d1y4o0cjx5s9r3.cloudfront.net/assets/recordeo-checkout-image.svg",
        "is_physical": 0,
        "weight": "0.00",
        "subscription_interval_type": "yearly",
        "subscription_interval_count": 1,
        "stock": null,
        "created_on": "2018-05-30 18:08:30",
        "updated_on": null,
        "discounts": []
      },
      ...
      {
        "id": 2,
        "brand": "recordeo",
        "name": "Recordeo Membership - Monthly",
        "sku": "MEMBERSHIP-1-MONTH",
        "price": "12.00",
        "type": "product",
        "active": 1,
        "description": "The Ultimate Guide To Recording Drums",
        "thumbnail_url": "https://d1y4o0cjx5s9r3.cloudfront.net/assets/recordeo-checkout-image.svg",
        "is_physical": 0,
        "weight": "0.00",
        "subscription_interval_type": "monthly",
        "subscription_interval_count": 1,
        "stock": null,
        "created_on": "2018-05-30 18:08:30",
        "updated_on": null,
        "discounts": []
      }
    ],
    "totalResults": 6,
    "page": "1",
    "limit": "20"
  }
}

```

### Search access codes

```
GET /access-codes/search
```

Search access codes.

The products associated with the access codes are included in meta.products key of the response
#### Request Example

```js
$.ajax({
    url: 'https://www.musora.com' +
        '/access-codes/search?brands[]=drumeo&term=aa1',
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
| path\|query\|body |  key                |  required |  default                   |  description\|notes                                  | 
|-------------------|---------------------|-----------|----------------------------|------------------------------------------------------| 
| query             |  brands             |  no       | [value set in config file] |  Only access codes from specified brands will be pulled. | 
| query             |  term               |  yes      |                            |  The search term                                     |

#### Response Example

```200 OK```
```json
{
  "data": [
    {
      "id": 9,
      "code": "aa12bbbbccccddddeeeeffff",
      "product_ids": [
        9,
        12,
        14
      ],
      "is_claimed": 0,
      "claimer_id": null,
      "claimed_on": null,
      "brand": "recordeo",
      "created_on": "2018-11-09 13:49:54",
      "updated_on": null,
      "claimer": null
    },
    ...
    {
      "id": 10,
      "code": "aa14bbbbccccddddeeeeffff",
      "product_ids": [
        9,
        15,
        16
      ],
      "is_claimed": 1,
      "claimer_id": 102905,
      "claimed_on": "2018-11-13 15:03:50",
      "brand": "recordeo",
      "created_on": "2018-11-09 13:49:54",
      "updated_on": "2018-11-13 15:03:50",
      "claimer": "reuben@drumeo.com"
    }
  ],
  "meta": {
    "products": [
      {
        "id": 9,
        "brand": "drumeo",
        "name": "Drum Play-Along System (Online Edition)",
        "sku": "DPAS-DIGI",
        "price": "77.00",
        "type": "product",
        "active": 1,
        "description": "Instant Online Access",
        "thumbnail_url": "/laravel/public/assets/order-form/images/product-images/DPAS-DIGI.png",
        "is_physical": 0,
        "weight": "0.00",
        "subscription_interval_type": null,
        "subscription_interval_count": null,
        "stock": 50,
        "created_on": "2015-10-21 02:51:00",
        "updated_on": "2016-08-02 17:03:45",
        "discounts": []
      },
      ...
      {
        "id": 12,
        "brand": "drumeo",
        "name": "Drum Rudiment System (Online Edition)",
        "sku": "DRUDSYS-DIGI",
        "price": "147.00",
        "type": "product",
        "active": 1,
        "description": "Instant Online Access",
        "thumbnail_url": "/laravel/public/assets/order-form/images/product-images/DRUDSYS-DIGI.png",
        "is_physical": 0,
        "weight": "0.00",
        "subscription_interval_type": null,
        "subscription_interval_count": null,
        "stock": 50,
        "created_on": "2015-10-21 02:51:00",
        "updated_on": "2016-08-02 17:03:45",
        "discounts": []
      }
    ]
  }
}

```

### Claim access code - JSON controller

```
POST /access-codes/claim
```

Admin action to claim an access code for an existing user.

#### Request Example
```js

$.ajax({
    url: 'https://www.musora.com' +
        '/access-codes/claim',
    type: 'post'
    data: {'access_code': 'aa14bbbbccccddddeeeeffff', 'claim_for_user_email': 'bob@example.com'}
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

| path\|query\|body |  key                   |  required |  default |  description\|notes                            | 
|-------------------|------------------------|-----------|----------|------------------------------------------------| 
| body              |  access_code           |  yes      |          |  The access code to be claimed                 | 
| body              |  claim_for_user_email  |  yes      |          |  The user email the access code is claimed for | 


#### Response Example

```200 OK```
```json
{
  "data": [
    {
      "id": 10,
      "code": "aa14bbbbccccddddeeeeffff",
      "product_ids": [
        9,
        15,
        16
      ],
      "is_claimed": 1,
      "claimer_id": 102905,
      "claimed_on": "2018-11-14 09:47:27",
      "brand": "recordeo",
      "created_on": "2018-11-09 13:49:54",
      "updated_on": "2018-11-14 09:47:27"
    }
  ]
}
```

```422 Unprocessable Entity```
```json
{
  "data": [],
  "meta": {
    "totalResults": 0,
    "page": 1,
    "limit": 10,
    "errors": [
      {
        "source": "access_code",
        "detail": "The selected access code is invalid."
      },
      {
        "source": "claim_for_user_email",
        "detail": "The claim for user email must be a valid email address."
      }
    ]
  }
}
```


### Release access code - JSON controller

```
POST /access-codes/release
```

Admin action to release an access code.

#### Request Example
```js

$.ajax({
    url: 'https://www.musora.com' +
        '/access-codes/release',
    type: 'post'
    data: {'access_code_id': 123}
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

| path\|query\|body |  key            | required | default | description\|notes                | 
|-------------------|-----------------|----------|---------|-----------------------------------| 
| body              |  access_code_id | yes      |         | The access code id to be released | 


#### Response Example

```200 OK```
```json
{
  "data": [
    {
      "id": 123,
      "code": "aa14bbbbccccddddeeeeffff",
      "product_ids": [
        9,
        15,
        16
      ],
      "is_claimed": 0,
      "claimer_id": null,
      "claimed_on": null,
      "brand": "recordeo",
      "created_on": "2018-11-09 13:49:54",
      "updated_on": "2018-11-14 10:09:59"
    }
  ]
}
```

```422 Unprocessable Entity```
```json
{
  "data": [],
  "meta": {
    "totalResults": 0,
    "page": 1,
    "limit": 10,
    "errors": [
      {
        "source": "access_code_id",
        "detail": "The selected access code id is invalid."
      }
    ]
  }
}
```

### Claim access code - FORM controller

```
POST /access-codes/redeem
```

User action to claim an access code.

#### Request Example
```
<form method="POST" action="/access-codes/redeem">
    <input type="text" name="access_code" value="aa14bbbbccccddddeeeeffff">

    <button type="submit">Submit</button>
</form>
```
or
```
<form method="POST" action="/access-codes/redeem">
    <input type="text" name="access_code" value="aa14bbbbccccddddeeeeffff">
    <input type="text" name="email" value="new_user@example.com">
    <input type="text" name="password" value="asdafdfasdfsdf">
    <input type="text" name="password_confirmation" value="asdafdfasdfsdf">

    <button type="submit">Submit</button>
</form>
```

#### Request Parameters

| path\|query\|body |  key                      | required | default            |  description\|notes                | 
|-------------------|---------------------------|----------|--------------------|------------------------------------| 
| body              |  access_code              | yes      |                    |  The access code to be claimed     | 
| body              |  redirect                 | no       | redirect()->back() |  If this is set the request will redirect to this url; otherwise will be redirect back | 
| body              |  email                    | no       |                    |  The email for the new account     | 
| body              |  password                 | no       |                    |  When email field is specified, this field is required. Password for new account | 
| body              |  password_confirmation    | no       |                    |  When email field is specified, this field is required. Password confirmation |

#### Response Example

``` 302 ```
Redirects to previous url or to path passed in with redirect param.

On the session are flashed the following data:\
    * `success` - boolean value\
    * `access_code` - boolean value, set to true on success, for displaying specific success message to user\

