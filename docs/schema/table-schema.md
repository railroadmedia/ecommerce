# Schema Documentation

`Generated using: https://github.com/UniSharp/doc-us`

## Table: `ecommerce_access_codes`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `code` | VARCHAR(255) | Not null |  |  |
| `product_ids` | VARCHAR(255) | Not null |  |  |
| `is_claimed` | TINYINT(1) | Not null |  |  |
| `claimer_id` | INT(11) |  | NULL |  |
| `claimed_on` | DATETIME |  | NULL |  |
| `brand` | VARCHAR(255) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_access_code_code_index` | `code` | INDEX |  |
| `ecommerce_access_code_product_ids_index` | `product_ids` | INDEX |  |
| `ecommerce_access_code_is_claimed_index` | `is_claimed` | INDEX |  |
| `ecommerce_access_code_claimer_id_index` | `claimer_id` | INDEX |  |
| `ecommerce_access_code_claimed_on_index` | `claimed_on` | INDEX |  |
| `ecommerce_access_code_brand_index` | `brand` | INDEX |  |
| `ecommerce_access_code_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_access_code_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_addresses`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `type` | VARCHAR(255) | Not null |  |  |
| `brand` | VARCHAR(255) | Not null |  |  |
| `user_id` | INT(11) |  | NULL |  |
| `customer_id` | INT(11) |  | NULL |  |
| `first_name` | VARCHAR(255) |  | NULL |  |
| `last_name` | VARCHAR(255) |  | NULL |  |
| `street_line_1` | VARCHAR(255) |  | NULL |  |
| `street_line_2` | VARCHAR(255) |  | NULL |  |
| `city` | VARCHAR(255) |  | NULL |  |
| `zip` | VARCHAR(255) |  | NULL |  |
| `region` | VARCHAR(255) |  | NULL |  |
| `country` | VARCHAR(255) |  | NULL |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_address_type_index` | `type` | INDEX |  |
| `ecommerce_address_brand_index` | `brand` | INDEX |  |
| `ecommerce_address_user_id_index` | `user_id` | INDEX |  |
| `ecommerce_address_customer_id_index` | `customer_id` | INDEX |  |
| `ecommerce_address_first_name_index` | `first_name` | INDEX |  |
| `ecommerce_address_last_name_index` | `last_name` | INDEX |  |
| `ecommerce_address_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_address_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_apple_receipts`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `receipt` | TEXT | Not null |  |  |
| `request_type` | VARCHAR(255) | Not null |  |  |
| `notification_type` | VARCHAR(255) |  | NULL |  |
| `email` | VARCHAR(255) |  | NULL |  |
| `brand` | VARCHAR(255) | Not null |  |  |
| `valid` | TINYINT(1) | Not null |  |  |
| `validation_error` | VARCHAR(255) |  | NULL |  |
| `payment_id` | INT(11) |  | NULL |  |
| `subscription_id` | INT(11) |  | NULL |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |

## Table: `ecommerce_credit_cards`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `fingerprint` | VARCHAR(255) | Not null |  |  |
| `last_four_digits` | INT(11) | Not null |  |  |
| `cardholder_name` | VARCHAR(255) |  | NULL |  |
| `company_name` | VARCHAR(255) | Not null |  |  |
| `expiration_date` | DATETIME | Not null |  |  |
| `external_id` | VARCHAR(64) | Not null |  |  |
| `external_customer_id` | VARCHAR(64) |  | NULL |  |
| `payment_gateway_name` | VARCHAR(64) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_credit_card_company_name_index` | `company_name` | INDEX |  |
| `ecommerce_credit_card_external_id_index` | `external_id` | INDEX |  |
| `ecommerce_credit_card_external_customer_id_index` | `external_customer_id` | INDEX |  |
| `ecommerce_credit_card_payment_gateway_name_index` | `payment_gateway_name` | INDEX |  |
| `ecommerce_credit_card_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_credit_card_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_customer_payment_methods`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `customer_id` | INT(11) | Not null |  |  |
| `payment_method_id` | INT(11) | Not null |  |  |
| `is_primary` | TINYINT(1) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_customer_payment_methods_customer_id_index` | `customer_id` | INDEX |  |
| `ecommerce_customer_payment_methods_payment_method_id_index` | `payment_method_id` | INDEX |  |
| `ecommerce_customer_payment_methods_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_customer_payment_methods_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_customers`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `phone` | VARCHAR(255) |  | NULL |  |
| `email` | VARCHAR(255) |  | NULL |  |
| `brand` | VARCHAR(255) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_customer_brand_index` | `brand` | INDEX |  |
| `ecommerce_customer_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_customer_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_discount_criteria`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `name` | VARCHAR(255) | Not null |  |  |
| `type` | VARCHAR(255) | Not null |  |  |
| `product_id` | INT(11) |  | NULL |  |
| `min` | VARCHAR(255) | Not null |  |  |
| `max` | VARCHAR(255) | Not null |  |  |
| `discount_id` | INT(11) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_discount_criteria_name_index` | `name` | INDEX |  |
| `ecommerce_discount_criteria_type_index` | `type` | INDEX |  |
| `ecommerce_discount_criteria_product_id_index` | `product_id` | INDEX |  |
| `ecommerce_discount_criteria_discount_id_index` | `discount_id` | INDEX |  |
| `ecommerce_discount_criteria_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_discount_criteria_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_discounts`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `name` | VARCHAR(255) | Not null |  |  |
| `description` | TEXT | Not null |  |  |
| `type` | VARCHAR(255) | Not null |  |  |
| `amount` | DECIMAL(8,2) | Not null |  |  |
| `product_id` | INT(11) |  | NULL |  |
| `product_category` | VARCHAR(255) |  | NULL |  |
| `active` | TINYINT(1) | Not null |  |  |
| `expiration_date` | DATETIME |  |  NULL |  |
| `visible` | TINYINT(1) |  | NULL |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_discount_name_index` | `name` | INDEX |  |
| `ecommerce_discount_type_index` | `type` | INDEX |  |
| `ecommerce_discount_active_index` | `active` | INDEX |  |
| `ecommerce_discount_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_discount_updated_on_index` | `updated_at` | INDEX |  |
| `ecommerce_discount_product_id_index` | `product_id` | INDEX |  |
| `ecommerce_discount_visible_index` | `visible` | INDEX |  |
| `ecommerce_discount_product_category_index` | `product_category` | INDEX |  |

## Table: `ecommerce_google_receipts`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `purchase_token` | TEXT | Not null |  |  |
| `package_name` | VARCHAR(255) | Not null |  |  |
| `product_id` | VARCHAR(255) | Not null |  |  |
| `request_type` | VARCHAR(255) | Not null |  |  |
| `notification_type` | VARCHAR(255) |  | NULL |  |
| `email` | VARCHAR(255) |  | NULL |  |
| `brand` | VARCHAR(255) | Not null |  |  |
| `valid` | TINYINT(1) | Not null |  |  |
| `validation_error` | VARCHAR(255) |  | NULL |  |
| `payment_id` | INT(11) |  | NULL |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |

## Table: `ecommerce_order_discounts`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `order_id` | INT(11) | Not null |  |  |
| `order_item_id` | INT(11) |  | NULL |  |
| `discount_id` | INT(11) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_order_discount_order_id_index` | `order_id` | INDEX |  |
| `ecommerce_order_discount_order_item_id_index` | `order_item_id` | INDEX |  |
| `ecommerce_order_discount_discount_id_index` | `discount_id` | INDEX |  |
| `ecommerce_order_discount_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_order_discount_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_order_item_fulfillment`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `order_id` | INT(11) | Not null |  |  |
| `order_item_id` | INT(11) | Not null |  |  |
| `status` | VARCHAR(64) | Not null |  |  |
| `company` | VARCHAR(255) |  | NULL |  |
| `tracking_number` | VARCHAR(255) |  | NULL |  |
| `fulfilled_on` | DATETIME |  | NULL |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_order_item_fulfillment_order_id_index` | `order_id` | INDEX |  |
| `ecommerce_order_item_fulfillment_order_item_id_index` | `order_item_id` | INDEX |  |
| `ecommerce_order_item_fulfillment_status_index` | `status` | INDEX |  |
| `ecommerce_order_item_fulfillment_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_order_item_fulfillment_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_order_items`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `order_id` | INT(11) | Not null |  |  |
| `product_id` | INT(11) | Not null |  |  |
| `quantity` | INT(11) | Not null |  |  |
| `weight` | DECIMAL(8,2) |  | NULL |  |
| `initial_price` | DECIMAL(8,2) | Not null |  |  |
| `total_discounted` | DECIMAL(8,2) | Not null |  |  |
| `final_price` | DECIMAL(8,2) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_order_item_order_id_index` | `order_id` | INDEX |  |
| `ecommerce_order_item_product_id_index` | `product_id` | INDEX |  |
| `ecommerce_order_item_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_order_item_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_order_payments`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `order_id` | INT(11) | Not null |  |  |
| `payment_id` | INT(11) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_order_payment_order_id_index` | `order_id` | INDEX |  |
| `ecommerce_order_payment_payment_id_index` | `payment_id` | INDEX |  |
| `ecommerce_order_payment_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_order_payment_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_orders`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `total_due` | DECIMAL(8,2) | Not null |  |  |
| `product_due` | DECIMAL(8,2) |  | NULL |  |
| `taxes_due` | DECIMAL(8,2) | Not null |  |  |
| `shipping_due` | DECIMAL(8,2) | Not null |  |  |
| `finance_due` | DECIMAL(8,2) |  | NULL |  |
| `total_paid` | DECIMAL(8,2) | Not null |  |  |
| `user_id` | INT(11) |  | NULL |  |
| `customer_id` | INT(11) |  | NULL |  |
| `brand` | VARCHAR(255) | Not null |  |  |
| `shipping_address_id` | INT(11) |  | NULL |  |
| `billing_address_id` | INT(11) |  | NULL |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |
| `deleted_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_order_user_id_index` | `user_id` | INDEX |  |
| `ecommerce_order_customer_id_index` | `customer_id` | INDEX |  |
| `ecommerce_order_brand_index` | `brand` | INDEX |  |
| `ecommerce_order_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_order_updated_on_index` | `updated_at` | INDEX |  |
| `ecommerce_order_deleted_on_index` | `deleted_at` | INDEX |  |
| `ecommerce_order_product_due_index` | `product_due` | INDEX |  |
| `ecommerce_order_finance_due_index` | `finance_due` | INDEX |  |

## Table: `ecommerce_payment_methods`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `method_id` | INT(11) | Not null |  |  |
| `method_type` | VARCHAR(255) | Not null |  |  |
| `currency` | VARCHAR(3) | Not null |  |  |
| `billing_address_id` | INT(11) |  | NULL |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |
| `deleted_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_payment_method_method_id_index` | `method_id` | INDEX |  |
| `ecommerce_payment_method_method_type_index` | `method_type` | INDEX |  |
| `ecommerce_payment_method_currency_index` | `currency` | INDEX |  |
| `ecommerce_payment_method_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_payment_method_updated_on_index` | `updated_at` | INDEX |  |
| `ecommerce_payment_method_deleted_on_index` | `deleted_at` | INDEX |  |

## Table: `ecommerce_payments`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `total_due` | DECIMAL(8,2) | Not null |  |  |
| `total_paid` | DECIMAL(8,2) |  | NULL |  |
| `total_refunded` | DECIMAL(8,2) |  | NULL |  |
| `conversion_rate` | DECIMAL(8,2) |  | NULL |  |
| `type` | VARCHAR(255) | Not null |  |  |
| `external_id` | VARCHAR(64) |  | NULL |  |
| `external_provider` | VARCHAR(64) |  | NULL |  |
| `status` | VARCHAR(64) | Not null |  |  |
| `message` | TEXT |  | NULL |  |
| `payment_method_id` | INT(11) |  | NULL |  |
| `currency` | VARCHAR(3) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |
| `deleted_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_payment_type_index` | `type` | INDEX |  |
| `ecommerce_payment_external_id_index` | `external_id` | INDEX |  |
| `ecommerce_payment_external_provider_index` | `external_provider` | INDEX |  |
| `ecommerce_payment_status_index` | `status` | INDEX |  |
| `ecommerce_payment_payment_method_id_index` | `payment_method_id` | INDEX |  |
| `ecommerce_payment_currency_index` | `currency` | INDEX |  |
| `ecommerce_payment_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_payment_updated_on_index` | `updated_at` | INDEX |  |
| `ecommerce_payment_deleted_on_index` | `deleted_at` | INDEX |  |

## Table: `ecommerce_paypal_billing_agreements`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `external_id` | VARCHAR(64) | Not null |  |  |
| `payment_gateway_name` | VARCHAR(64) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_paypal_billing_agreement_external_id_index` | `external_id` | INDEX |  |
| `ecommerce_paypal_billing_agreement_payment_gateway_name_index` | `payment_gateway_name` | INDEX |  |
| `ecommerce_paypal_billing_agreement_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_paypal_billing_agreement_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_products`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `brand` | VARCHAR(255) | Not null |  |  |
| `name` | VARCHAR(255) | Not null |  |  |
| `sku` | VARCHAR(255) | Not null |  |  |
| `price` | DECIMAL(8,2) | Not null |  |  |
| `type` | VARCHAR(255) | Not null |  |  |
| `active` | TINYINT(1) | Not null |  |  |
| `category` | VARCHAR(255) |  | NULL |  |
| `description` | TEXT |  | NULL |  |
| `thumbnail_url` | TEXT |  | NULL |  |
| `is_physical` | TINYINT(1) | Not null |  |  |
| `weight` | DECIMAL(8,2) |  | NULL |  |
| `subscription_interval_type` | VARCHAR(255) |  | NULL |  |
| `subscription_interval_count` | INT(11) |  | NULL |  |
| `stock` | INT(11) |  | NULL |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_products_sku_unique` | `sku` | UNIQUE |  |
| `ecommerce_product_brand_index` | `brand` | INDEX |  |
| `ecommerce_product_name_index` | `name` | INDEX |  |
| `ecommerce_product_sku_index` | `sku` | INDEX |  |
| `ecommerce_product_type_index` | `type` | INDEX |  |
| `ecommerce_product_active_index` | `active` | INDEX |  |
| `ecommerce_product_subscription_interval_type_index` | `subscription_interval_type` | INDEX |  |
| `ecommerce_product_subscription_interval_count_index` | `subscription_interval_count` | INDEX |  |
| `ecommerce_product_stock_index` | `stock` | INDEX |  |
| `ecommerce_product_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_product_updated_on_index` | `updated_at` | INDEX |  |
| `ecommerce_product_category_index` | `category` | INDEX |  |

## Table: `ecommerce_refunds`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `payment_id` | INT(11) | Not null |  |  |
| `payment_amount` | DECIMAL(8,2) | Not null |  |  |
| `refunded_amount` | DECIMAL(8,2) | Not null |  |  |
| `note` | TEXT |  | NULL |  |
| `external_provider` | VARCHAR(255) |  | NULL |  |
| `external_id` | VARCHAR(255) |  | NULL |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_refund_payment_id_index` | `payment_id` | INDEX |  |
| `ecommerce_refund_external_provider_index` | `external_provider` | INDEX |  |
| `ecommerce_refund_external_id_index` | `external_id` | INDEX |  |
| `ecommerce_refund_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_refund_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_shipping_costs_weight_ranges`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `shipping_option_id` | INT(11) | Not null |  |  |
| `min` | DECIMAL(8,2) | Not null |  |  |
| `max` | DECIMAL(8,2) | Not null |  |  |
| `price` | DECIMAL(8,2) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_shipping_costs_weight_range_shipping_option_id_index` | `shipping_option_id` | INDEX |  |
| `ecommerce_shipping_costs_weight_range_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_shipping_costs_weight_range_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_shipping_options`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `country` | VARCHAR(255) | Not null |  |  |
| `active` | TINYINT(1) | Not null |  |  |
| `priority` | INT(11) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_shipping_option_country_index` | `country` | INDEX |  |
| `ecommerce_shipping_option_active_index` | `active` | INDEX |  |
| `ecommerce_shipping_option_priority_index` | `priority` | INDEX |  |
| `ecommerce_shipping_option_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_shipping_option_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_subscription_access_codes`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `subscription_id` | INT(11) | Not null |  |  |
| `access_code_id` | INT(11) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_subscription_access_code_subscription_id_index` | `subscription_id` | INDEX |  |
| `ecommerce_subscription_access_code_access_code_id_index` | `access_code_id` | INDEX |  |
| `ecommerce_subscription_access_code_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_subscription_access_code_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_subscription_payments`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `subscription_id` | INT(11) | Not null |  |  |
| `payment_id` | INT(11) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_subscription_payment_subscription_id_index` | `subscription_id` | INDEX |  |
| `ecommerce_subscription_payment_payment_id_index` | `payment_id` | INDEX |  |
| `ecommerce_subscription_payment_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_subscription_payment_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_subscriptions`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `brand` | VARCHAR(255) | Not null |  |  |
| `type` | VARCHAR(255) | Not null |  |  |
| `user_id` | INT(11) |  | NULL |  |
| `customer_id` | INT(11) |  | NULL |  |
| `order_id` | INT(11) |  | NULL |  |
| `product_id` | INT(11) |  | NULL |  |
| `is_active` | TINYINT(1) | Not null |  |  |
| `start_date` | DATETIME | Not null |  |  |
| `paid_until` | DATETIME | Not null |  |  |
| `canceled_on` | DATETIME |  | NULL |  |
| `note` | TEXT |  | NULL |  |
| `total_price` | DECIMAL(8,2) | Not null |  |  |
| `currency` | VARCHAR(3) | Not null |  |  |
| `interval_type` | VARCHAR(255) | Not null |  |  |
| `interval_count` | INT(11) | Not null |  |  |
| `total_cycles_due` | INT(11) |  | NULL |  |
| `total_cycles_paid` | INT(11) | Not null |  |  |
| `payment_method_id` | INT(11) |  | NULL |  |
| `apple_expiration_date` | DATETIME |  | NULL | |
| `external_app_store_id` | TEXT |  | NULL | |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |
| `deleted_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_subscription_brand_index` | `brand` | INDEX |  |
| `ecommerce_subscription_type_index` | `type` | INDEX |  |
| `ecommerce_subscription_user_id_index` | `user_id` | INDEX |  |
| `ecommerce_subscription_customer_id_index` | `customer_id` | INDEX |  |
| `ecommerce_subscription_order_id_index` | `order_id` | INDEX |  |
| `ecommerce_subscription_product_id_index` | `product_id` | INDEX |  |
| `ecommerce_subscription_is_active_index` | `is_active` | INDEX |  |
| `ecommerce_subscription_start_date_index` | `start_date` | INDEX |  |
| `ecommerce_subscription_paid_until_index` | `paid_until` | INDEX |  |
| `ecommerce_subscription_currency_index` | `currency` | INDEX |  |
| `ecommerce_subscription_interval_type_index` | `interval_type` | INDEX |  |
| `ecommerce_subscription_payment_method_id_index` | `payment_method_id` | INDEX |  |
| `ecommerce_subscription_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_subscription_updated_on_index` | `updated_at` | INDEX |  |
| `ecommerce_subscription_deleted_on_index` | `deleted_at` | INDEX |  |

## Table: `ecommerce_user_payment_methods`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `user_id` | INT(11) | Not null |  |  |
| `payment_method_id` | INT(11) | Not null |  |  |
| `is_primary` | TINYINT(1) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_user_payment_methods_user_id_index` | `user_id` | INDEX |  |
| `ecommerce_user_payment_methods_payment_method_id_index` | `payment_method_id` | INDEX |  |
| `ecommerce_user_payment_methods_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_user_payment_methods_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_user_products`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `user_id` | INT(11) | Not null |  |  |
| `product_id` | INT(11) | Not null |  |  |
| `quantity` | INT(11) | Not null |  |  |
| `expiration_date` | DATETIME |  | NULL |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_user_product_user_id_index` | `user_id` | INDEX |  |
| `ecommerce_user_product_product_id_index` | `product_id` | INDEX |  |
| `ecommerce_user_product_quantity_index` | `quantity` | INDEX |  |
| `ecommerce_user_product_expiration_date_index` | `expiration_date` | INDEX |  |
| `ecommerce_user_product_created_on_index` | `created_at` | INDEX |  |
| `ecommerce_user_product_updated_on_index` | `updated_at` | INDEX |  |

## Table: `ecommerce_user_stripe_customer_ids`

### Description:



### Columns:

| Column | Data Type | Attributes | Default | Description |
| --- | --- | --- | --- | --- |
| `id` | INT(10) UNSIGNED | Primary, Auto increment, Not null |  |  |
| `user_id` | INT(11) | Not null |  |  |
| `stripe_customer_id` | INT(11) | Not null |  |  |
| `created_at` | DATETIME | Not null |  |  |
| `updated_at` | DATETIME |  | NULL |  |

### Indices:

| Name | Columns | Type | Description |
| --- | --- | --- | --- |
| `PRIMARY` | `id` | PRIMARY |  |
| `ecommerce_user_stripe_customer_ids_user_id_index` | `user_id` | INDEX |  |
| `ecommerce_user_stripe_customer_ids_stripe_customer_id_index` | `stripe_customer_id` | INDEX |  |
| `ecommerce_user_stripe_customer_ids_created_at_index` | `created_at` | INDEX |  |
| `ecommerce_user_stripe_customer_ids_updated_at_index` | `updated_at` | INDEX |  |