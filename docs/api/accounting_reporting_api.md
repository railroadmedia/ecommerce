# Accounting Reporting API

Period accounting reporting, per product, and totals.

# JSON Endpoints

### `{ GET /*/product-totals }`

### Permissions

- Must be logged in
- Must have the 'pull.accounting' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|small_date_time||1 month before today|||
|query|big_date_time||end of yesterday|||
|query|brand|||||

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/product-totals',
    data: {
        small_date_time: '2019-12-01 00:00:00',
        big_date_time: '2019-12-21 00:00:00',
        brand: 'drumeo', 
    }, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

**
NOTES:
- the accountingProductsTotals id is returned for JSON-API compliance, created as a string for specified interval
- the accountingProduct id is the actual product id for which the stats/sums are displayed
**

```200 OK```

```json
{
  "data": {
    "type": "accountingProductsTotals",
    "id": "2019-12-01 - 2019-12-21",
    "attributes": {
      "tax_paid": 2321.89,
      "shipping_paid": 4299.5,
      "finance_paid": 16,
      "refunded": 17587.16,
      "net_product": 262991.78,
      "net_paid": 253881.97
    },
    "relationships": {
      "accountingProduct": {
        "data": [
          {
            "type": "accountingProduct",
            "id": "125"
          },
          {
            "type": "accountingProduct",
            "id": "86"
          }
        ]
      }
    }
  },
  "included": [
  	{
      "type": "accountingProduct",
      "id": "125",
      "attributes": {
        "name": "Drumeo Edge Membership - Annual",
        "sku": "DLM-1-year",
        "tax_paid": 1114.59,
        "shipping_paid": 0,
        "finance_paid": 0,
        "less_refunded": 5610.46,
        "total_quantity": 649,
        "refunded_quantity": 32,
        "free_quantity": 7,
        "net_product": 132386.92,
        "net_paid": 133501.51
      }
    },
    {
      "type": "accountingProduct",
      "id": "86",
      "attributes": {
        "name": "Successful Drumming (Online Edition)",
        "sku": "SD-DIGI",
        "tax_paid": 39.51,
        "shipping_paid": 0,
        "finance_paid": 0,
        "less_refunded": 76,
        "total_quantity": 591,
        "refunded_quantity": 8,
        "free_quantity": 452,
        "net_product": 6909,
        "net_paid": 6948.51
      }
    }
  ]
}
```
