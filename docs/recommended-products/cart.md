# Cart serialization contains a list of recommended products

The list and the number of recommended products are defined in ecommerce config.
The algorithm of picking recommended products selects first recommended products that pass the exclusion rules.
The recommended products serialization starts with normal product serialization and adds additional fields. It is possible to override product name, specify a product call-to-action label and specify if the call-to-action should redirect the user to product page or add the product in cart with an AJAX request.

## Exclusion rules:
#### * if the customer is logged in, and already owns the recommended product, it will not be displayed
#### * if the cart contains the recommended product, it will not be displayed
#### * if the recommended product has stock 0
#### * for custom configured recommended products, if the user added a product in the cart, defined in the recommended product exclude-list, the recommended product will not be displayed

Example:
##### ** if the recommended product 'trial membership', has in the exclude-list all the other membership types, when a customer adds a membership product in the cart, the 'trial membership' will not be displayed in recommended products

##### ** if a product with variants, such as clothing, has in the exclude-list all the product variants, when a customer adds any product variant to cart, the product will not be displayed in recommended products

## Config

### Number of recommended products displayed
```php
'recommended_products_count' => 3,
```

### Recommended product config
| Key                    | Type             | Default      | Description
|------------------------|------------------|--------------|---------------
| `sku`                  | String           | N/A          | Required, no existing default.
| `product_page_url`     | String           | N/A          | Required, no existing default.
| `name_override`        | String           | N/A          | Optional. Replacement for serialized product name
| `excluded_skus`        | Array of Strings | N/A          | Optional. If any product, with sku specified here, is in cart, it will exclude current recommended product
| `cta`                  | String           | N/A          | Optional. Frontent UI should display it, instead of normal CTA or product price
| `add_directly_to_cart` | Boolean          | true         | Optional. If false, the frontent UI should should redirect customer to product page instead of adding product in cart

Minimal product config:
```php
'recommended_products' => [
    'drumeo' => [
        [
            'sku' => 'quietpad',
            'product_page_url' => '/drumshop/quietpad/',
        ],
    ],
],
```
Membership trial example:
```php
'recommended_products' => [
    'drumeo' => [
        [
            'sku' => 'DLM-Trial-1-month',
            'product_page_url' => '/trial/',
            'name_override' => 'Drumeo Edge 7-Day Trial',
            'excluded_skus' => [
                'DLM-1-month',
                'DLM-1-year',
                'DLM-Trial-1-month',
                'DLM-6-month',
                'DLM-teachers-1-year',
                'DLM-teachers-upgrade-1-month',
                'DLM-teachers-upgrade-1-year',
                'DLM-3-month',
                'DLM-UPSELL-2-month',
                'DLM-Trial-Best-Book-1-month',
                'edge-membership-6-months',
                'DLM-Trial-Drummers-Toolbox-1-month',
                'DLM-Lifetime',
                'drumeo_edge_30_days_access',
                'DLM-Trial-30-Day',
            ],
            'cta' => '7 Days Free, Then $29/mo',
        ],
    ],
],
```
Product with variants example:
```php
'recommended_products' => [
    'pianote' => [
        [
            'sku' => 'Sweatshirt-Hooded-Black-S',
            'product_page_url' => '/shop/hoodie-iconic',
            'name_override' => 'Iconic Pianote Hoodie',
            'excluded_skus' => [
                'Sweatshirt-Hooded-Black-S',
                'Sweatshirt-Hooded-Black-M',
                'Sweatshirt-Hooded-Black-L',
                'Sweatshirt-Hooded-Black-XL',
                'Sweatshirt-Hooded-Black-XXL',
                'Sweatshirt-Hooded-Black-XXXL',
            ],
            'cta' => 'See Details',
            'add_directly_to_cart' => false,
        ],
    ],
],
```
## Serialization

##### The extra fields the recommended product has, than normal cart item product:
| Key                    | Type             | Default      | Description
|------------------------|------------------|--------------|---------------
| `product_page_url`     | String           | N/A          | Always included. Product page url.
| `add_directly_to_cart` | Boolean          | true         | Always included. If false, the frontent UI should should redirect customer to product page instead of adding product in cart
| `cta`                  | String           | N/A          | Optional. Frontent UI should display it, instead of normal CTA or product price

##### Serialization example:
```json
{
  "data": null,
  "meta": {
    "cart": {
      "items": [],
      "recommendedProducts": [
        {
          "sku": "PIANOTE-MEMBERSHIP-TRIAL",
          "name": "Pianote 7-Day Trial",
          "quantity": 1,
          "thumbnail_url": "https://d1923uyy6spedc.cloudfront.net/0-product-thumb--1574878049.jpg",
          "description": "7 days free, then $29 / month. Easy to cancel anytime.",
          "stock": 0,
          "subscription_interval_type": "month",
          "subscription_interval_count": 1,
          "subscription_renewal_price": 29,
          "price_before_discounts": 29,
          "price_after_discounts": 29,
          "requires_shipping": false,
          "is_digital": true,
          "product_page_url": "/trial/",
          "add_directly_to_cart": true,
          "cta": "7 Days Free, Then $29/mo"
        },
        {
          "sku": "pianote-foundation",
          "name": "Pianote Foundation Books",
          "quantity": 1,
          "thumbnail_url": "https://pianote.s3.amazonaws.com/sales/foundations-books.png",
          "description": "The perfect companion to your Pianote membership. Add to your video lessons with stunning guides to complement and reinforce your skills.",
          "stock": 100,
          "subscription_interval_type": null,
          "subscription_interval_count": null,
          "subscription_renewal_price": null,
          "price_before_discounts": 149,
          "price_after_discounts": 149,
          "requires_shipping": true,
          "is_digital": false,
          "product_page_url": "/foundations",
          "add_directly_to_cart": true
        },
        {
          "sku": "Sweatshirt-Hooded-Black-S",
          "name": "Iconic Pianote Hoodie",
          "quantity": 1,
          "thumbnail_url": "https://d1923uyy6spedc.cloudfront.net/0-product-thumb--1574879595.jpg",
          "description": "This ultra-soft, super warm fleeced hoodie will keep you cozy on those chilly days.",
          "stock": 100,
          "subscription_interval_type": null,
          "subscription_interval_count": null,
          "subscription_renewal_price": null,
          "price_before_discounts": 59,
          "price_after_discounts": 59,
          "requires_shipping": true,
          "is_digital": false,
          "product_page_url": "/shop/hoodie-iconic",
          "add_directly_to_cart": false,
          "cta": "See Details"
        }
      ],
      "discounts": [],
      "shipping_address": null,
      "billing_address": {
        "zip_or_postal_code": null,
        "street_line_two": null,
        "street_line_one": null,
        "last_name": null,
        "first_name": null,
        "region": "British Columbia",
        "country": "Canada",
        "city": null
      },
      "number_of_payments": 1,
      "payment_plan_options": [],
      "locked": false,
      "totals": {
        "shipping": 0,
        "shipping_before_override": 0,
        "tax": 0,
        "due": 0,
        "product_taxes": 0,
        "shipping_taxes": 0
      }
    }
  }
}
```