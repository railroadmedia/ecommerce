Ecommerce
========================================================================================================================

- [Ecommerce](#ecommerce)
  * [Install](#install)
  * [API Reference](#api-reference)
    + [Add item to cart - forms controller](#add-item-to-cart---forms-controller)
      - [Request Example](#request-example)
      - [Request Parameters](#request-parameters)

<!-- ecotrust-canada.github.io/markdown-toc -->

E-commerce system

## Install
With composer command
``` composer require railroad/ecommerce:1.0.19 ```

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



