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
            "id":"2019-05-05",
            "attributes":{
                "total_sales":80.02,
                "total_sales_from_renewals":80.02,
                "total_refunded":0,
                "total_number_of_orders_placed":0,
                "total_number_of_successful_subscription_renewal_payments":1,
                "total_number_of_failed_subscription_renewal_payments":0,
                "day":"2019-05-05"
            },
            "relationships":{
                "productStatistic":{
                    "data":[
                        {
                            "type":"productStatistic",
                            "id":"2019-05-05:1"
                        }
                    ]
                }
            }
        },
        {
            "type":"dailyStatistic",
            "id":"2019-05-10",
            "attributes":{
                "total_sales":135.78,
                "total_sales_from_renewals":0,
                "total_refunded":0,
                "total_number_of_orders_placed":2,
                "total_number_of_successful_subscription_renewal_payments":0,
                "total_number_of_failed_subscription_renewal_payments":0,
                "day":"2019-05-10"
            },
            "relationships":{
                "productStatistic":{
                    "data":[
                        {
                            "type":"productStatistic",
                            "id":"2019-05-10:2"
                        }
                    ]
                }
            }
        },
        {
            "type":"dailyStatistic",
            "id":"2019-05-15",
            "attributes":{
                "total_sales":120.83,
                "total_sales_from_renewals":0,
                "total_refunded":0,
                "total_number_of_orders_placed":1,
                "total_number_of_successful_subscription_renewal_payments":1,
                "total_number_of_failed_subscription_renewal_payments":0,
                "day":"2019-05-15"
            },
            "relationships":{
                "productStatistic":{
                    "data":[
                        {
                            "type":"productStatistic",
                            "id":"2019-05-15:3"
                        }
                    ]
                }
            }
        },
        {
            "type":"dailyStatistic",
            "id":"2019-05-20",
            "attributes":{
                "total_sales":82.42,
                "total_sales_from_renewals":0,
                "total_refunded":0,
                "total_number_of_orders_placed":0,
                "total_number_of_successful_subscription_renewal_payments":1,
                "total_number_of_failed_subscription_renewal_payments":0,
                "day":"2019-05-20"
            }
        },
        {
            "type":"dailyStatistic",
            "id":"2019-05-22",
            "attributes":{
                "total_sales":0,
                "total_sales_from_renewals":0,
                "total_refunded":0,
                "total_number_of_orders_placed":0,
                "total_number_of_successful_subscription_renewal_payments":0,
                "total_number_of_failed_subscription_renewal_payments":1,
                "day":"2019-05-22"
            }
        },
        {
            "type":"dailyStatistic",
            "id":"2019-05-25",
            "attributes":{
                "total_sales":0,
                "total_sales_from_renewals":0,
                "total_refunded":70.06,
                "total_number_of_orders_placed":0,
                "total_number_of_successful_subscription_renewal_payments":0,
                "total_number_of_failed_subscription_renewal_payments":0,
                "day":"2019-05-25"
            }
        }
    ],
    "included":[
        {
            "type":"productStatistic",
            "id":"2019-05-05:1",
            "attributes":{
                "sku":"explicabo441701",
                "total_quantity_sold":0,
                "total_sales":0,
                "total_renewal_sales":80.02
            }
        },
        {
            "type":"productStatistic",
            "id":"2019-05-10:2",
            "attributes":{
                "sku":"enim7176916",
                "total_quantity_sold":2,
                "total_sales":135.78,
                "total_renewal_sales":0
            }
        },
        {
            "type":"productStatistic",
            "id":"2019-05-15:3",
            "attributes":{
                "sku":"alias3863776",
                "total_quantity_sold":1,
                "total_sales":69.95,
                "total_renewal_sales":0
            }
        }
    ]
}
```
    