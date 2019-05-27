# Daily Statistics API

Per day reporting for various statistics.

# JSON Endpoints

### `{ GET /*/daily-statistics }`

List daily products.

### Permissions

- Must be logged in
- Must have the 'pull.daily-statistics' permission

### Request Parameters

|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
|query|small_date_time||1 month before today|||
|query|big_date_time||today|||
|query|brand||||if this is not passed, all brands will be considered|

### Request Example

```js   
$.ajax({
    url: 'https://www.domain.com' +
        '/ecommerce/daily-statistics',
    data: {
        small_date_time: '2019-04-01 00:00:00',
        big_date_time: '2019-05-01 00:00:00',
        brand: 'pianote', 
    }, 
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example

**NOTE: the productStatistic id for referencing the included data should be the day and the product id combined**

```200 OK```

```json
{
    "data":[
        {
            "type":"dailyStatistic",
            "id":"2019-04-01",
            "attributes":{
                "total_sales":13825.32,
                "total_refunded":824.91,
                "total_number_of_orders_placed":513,
                "total_number_of_successful_subscription_renewal_payments":87,
                "total_number_of_failed_subscription_renewal_payments":21,
                "day":"2019-04-01"
            },
            "relationships":{
                "productStatistic":{
                    "data":{
                        "type":"productStatistic",
                        "id":"2019-04-01:1"
                    }
                },
                "productStatistic":{
                    "data":{
                        "type":"productStatistic",
                        "id":"2019-04-01:2"
                    }
                }
            }
        },
        {
            "type":"dailyStatistic",
            "id":"2019-04-02",
            "attributes":{
                "total_sales":13825.32,
                "total_refunded":824.91,
                "total_number_of_orders_placed":513,
                "total_number_of_successful_subscription_renewal_payments":87,
                "total_number_of_failed_subscription_renewal_payments":21,
                "day":"2019-04-01"
            },
            "relationships":{
                "productStatistic":{
                    "data":{
                        "type":"productStatistic",
                        "id":"2019-04-02:1"
                    }
                },
                "productStatistic":{
                    "data":{
                        "type":"productStatistic",
                        "id":"2019-04-02:2"
                    }
                }
            }
        }, ...
    ],
    "included":[
        {
            "type":"productStatistic",
            "id":"2019-04-01:1",
            "attributes":{
                "sku":"some-sku",
                "total_quantity_sold":42,
                "total_sales":34875.22
            }
        },
        {
            "type":"productStatistic",
            "id":"2019-04-01:2",
            "attributes":{
                "sku":"other-sku",
                "total_quantity_sold":4,
                "total_sales":842.31
            }
        },
        {
            "type":"productStatistic",
            "id":"2019-04-02:1",
            "attributes":{
                "sku":"some-sku-2",
                "total_quantity_sold":42,
                "total_sales":34875.22
            }
        },
        {
            "type":"productStatistic",
            "id":"2019-04-02:2",
            "attributes":{
                "sku":"other-sku-2",
                "total_quantity_sold":0,
                "total_sales":0
            }
        }
    ]
}
```
    