# Addresses API

[Table Schema](../schema/table-schema.md#table-ecommerce_customers)

The column names should be used as the keys for requests.

# JSON Endpoints

### `{ GET /*/customers }`

List addresses.

### Permissions

- Must be logged in
- Must have the 'pull.customers' permission to pull customers

### Request Parameters

[Paginated](request_pagination_parameters.md) | [Ordered](request_ordering_parameters.md) | [Branded](request_brand_filtering_parameters.md)
<br>

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|term|no|||match any customer having specified string in email address|

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/customers',
    data: {
        term: 'mckenna.donnelly',
        order_by_column: 'id', 
        order_by_direction: 'desc', 
        page: 1, 
        limit: 10, 
        brands: ['drumeo', 'pianote']
    }, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

All included entities have values under attributes key, omitted here

```200 OK```

```json
{
  "data": [
    {
      "type": "customer",
      "id": "19484",
      "attributes": {
        "brand": "drumeo",
        "phone": null,
        "email": "mckenna.donnelly@cole.com",
        "note": null,
        "created_at": "2019-12-03 13:59:39",
        "updated_at": "2019-12-03 13:59:39"
      },
      "relationships": {
        "order": {
          "data": {
            "type": "order",
            "id": "119820"
          }
        }
      }
    }
  ],
  "included": [
    {
      "type": "address",
      "id": "180165",
      "attributes": {

      },
      "relationships": {
        "customer": {
          "data": {
            "type": "customer",
            "id": "19484"
          }
        }
      }
    },
    {
      "type": "address",
      "id": "180166",
      "attributes": {

      },
      "relationships": {
        "customer": {
          "data": {
            "type": "customer",
            "id": "19484"
          }
        }
      }
    },
    {
      "type": "order",
      "id": "119820",
      "attributes": {

      },
      "relationships": {
        "customer": {
          "data": {
            "type": "customer",
            "id": "19484"
          }
        },
        "billingAddress": {
          "data": {
            "type": "address",
            "id": "180165"
          }
        },
        "shippingAddress": {
          "data": {
            "type": "address",
            "id": "180166"
          }
        }
      }
    },
    {
      "type": "product",
      "id": "235",
      "attributes": {

      }
    },
    {
      "type": "product",
      "id": "237",
      "attributes": {

      }
    },
    {
      "type": "product",
      "id": "139",
      "attributes": {

      }
    },
    {
      "type": "product",
      "id": "96",
      "attributes": {

      }
    },
    {
      "type": "orderItem",
      "id": "192172",
      "attributes": {

      },
      "relationships": {
        "order": {
          "data": {
            "type": "order",
            "id": "119820"
          }
        },
        "product": {
          "data": {
            "type": "product",
            "id": "235"
          }
        }
      }
    },
    {
      "type": "orderItem",
      "id": "192173",
      "attributes": {

      },
      "relationships": {
        "order": {
          "data": {
            "type": "order",
            "id": "119820"
          }
        },
        "product": {
          "data": {
            "type": "product",
            "id": "237"
          }
        }
      }
    },
    {
      "type": "orderItem",
      "id": "192174",
      "attributes": {

      },
      "relationships": {
        "order": {
          "data": {
            "type": "order",
            "id": "119820"
          }
        },
        "product": {
          "data": {
            "type": "product",
            "id": "139"
          }
        }
      }
    },
    {
      "type": "orderItem",
      "id": "192175",
      "attributes": {

      },
      "relationships": {
        "order": {
          "data": {
            "type": "order",
            "id": "119820"
          }
        },
        "product": {
          "data": {
            "type": "product",
            "id": "96"
          }
        }
      }
    }
  ],
  "meta": {
    "pagination": {
      "total": 1,
      "count": 1,
      "per_page": 10,
      "current_page": 1,
      "total_pages": 1
    }
  },
  "links": {
    "self": "https://dev.drumeo.com/laravel/public/ecommerce/customers?lf=0&term=mckenna.donnelly&page=1",
    "first": "https://dev.drumeo.com/laravel/public/ecommerce/customers?lf=0&term=mckenna.donnelly&page=1",
    "last": "https://dev.drumeo.com/laravel/public/ecommerce/customers?lf=0&term=mckenna.donnelly&page=1"
  }
}
```