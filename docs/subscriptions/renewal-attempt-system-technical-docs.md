# Subscriptions renewal command

Subscriptions renewal command performs three major actions:

- it pulls subscriptions to be renewed from the database, using a method of subscription repository
- foreach subscription to be renewed, it calls subscription service method to renew it
- based on the renewal action result, performed by subscription service renew method, it triggers one of the two events: CommandSubscriptionRenewed or CommandSubscriptionRenewFailed, with the old/new subscription states and the payment object.

## getSubscriptionsDueToRenew method of SubscriptionRepository

The method pulls subscriptions from the database that are due to be renewed.

Fixed conditions for a subscription to be renewed:
- 'brand' equal to config('ecommerce.brand')
- 'canceled_on' is null
- 'stopped' equals false/0
- 'type' in [Subscription::TYPE_SUBSCRIPTION, Subscription::TYPE_PAYMENT_PLAN]
- 'total_cycles_due' is null, or 'total_cycles_due' equals zero, or 'total_cycles_paid' is less than 'total_cycles_due'
- ('is_active' equals true/1 and 'renewal_attempt' equals 0 and 'paid_until' is less than Carbon::now()) or ('is_active' equals false/0 and one dynamic condition is true)

Dynamic conditions are created based on config('ecommerce.subscriptions_renew_cycles')\
Foreach {index}/{value} of the config array, 'renewal_attempt' must be equal to the {config array index} and 'paid_until' must be less than Carbon::now()->subHours({config array value})

Example of config('ecommerce.subscriptions_renew_cycles'):\
'subscriptions_renew_cycles' => [\
    1 => 8,\
    2 => 24 * 3,\
    3 => 24 * 7,\
    4 => 24 * 14,\
    5 => 24 * 30,\
],

The above example will produce exactly five dynamic condition blocks, as following:
- ('renewal_attempt' equals 1 and 'paid_until' less than Carbon::now()->subHours(8))
- or ('renewal_attempt' equals 2 and 'paid_until' less than Carbon::now()->subHours(72))
- or ('renewal_attempt' equals 3 and 'paid_until' less than Carbon::now()->subHours(168))
- or ('renewal_attempt' equals 4 and 'paid_until' less than Carbon::now()->subHours(336))
- or ('renewal_attempt' equals 5 and 'paid_until' less than Carbon::now()->subHours(720))

Using simpler terms, the subscription will renew if one of the conditions group is true:
- it's active and renewal_attempt is 0 and 'paid_until' is less than current date/time
- it's inactive and renewal_attempt is 1 and 'paid_until' is less than 8 hours ago
- it's inactive and renewal_attempt is 2 and 'paid_until' is less than 3 days ago
- it's inactive and renewal_attempt is 3 and 'paid_until' is less than 7 day ago
- it's inactive and renewal_attempt is 4 and 'paid_until' is less than 2 weeks ago
- it's inactive and renewal_attempt is 5 and 'paid_until' is less than 30 days ago

If now is April 9, 2020 09:30 AM, "'paid_until' is less than 8 hours ago" means 'paid_until' can be any date in the past before April 9, 2020 01:30 AM.

For a better understaning, if a subscription has 'paid_until' April 5, 2020, 'is_active' false, renewal_attempt' equals 3 and current date is April 9, 2020, the subscription will not renew today, it's next renewal schedule is 7 days after 'paid_until', meaning April 12, 2020.

## renew method of SubscriptionService

The method attempts to create a charge and renew a subscription

From the existing subscription record, it calculates due as 'total_price' minus 'tax'.\
It calculates taxes for due value, using associated payment method billing address.\
Based on associated payment method type, either PaymentMethod::TYPE_CREDIT_CARD or PaymentMethod::TYPE_PAYPAL, it tries to create a new charge or transaction.\
A new payment object is created and populated with charge/transaction details, including the case where charge/transaction fails.\
A subscription payment object is created and linked with the payment and subscriptions objects.\
If the payment 'total_paid' is greater than 0 the subscription next bill date is calculated and stored in 'paid_until'. 'is_active' is set to true/1, 'canceled_on' is set to null, 'total_cycles_paid' value is incremented by 1, 'renewal_attempt' is set to 0, 'failed_payment' is set to null, 'updated_at' is set to Carbon::now().\
A payment taxes object is created and populated.\
A SubscriptionEvent is triggered.\
The 'updateSubscriptionProducts' method of userProductService is called.\
If the charge/transaction failed, the subscription 'renewal_attempt' value is incremented by 1, 'is_active' is set to false, 'updated_at' is set to Carbon::now() and 'note' is set to SubscriptionService::DEACTIVATION_MESSAGE and SubscriptionUpdated and SubscriptionEvent events are triggered and a PaymentFailedException is thrown.

