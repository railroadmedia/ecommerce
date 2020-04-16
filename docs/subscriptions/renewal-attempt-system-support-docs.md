# Renewal System

## Determining If A Subscription Should Be Renewed

The subscription must follow these rules to be triggered for an automatic renewal:

- Paid until date is in the past
- Cancelled on is empty/null
- (Is active is TRUE) OR (is active is FALSE, and the renewal attempt number is less than 5)
- Stopped is FALSE

## Renewal & Renew Attempt Schedule

The system processes renewals every 8 hours, relative to the 7th hour of every day depending on our 
daylight savings changes. Example:

- 7:00 AM
- 3:00 PM
- 11:00 PM  

<br>
When the first renewal payment for an active subscription fails, the system will try to renew the subscription 
4 more times following the initial failed payment date. Here is that schedule, relative to the subscriptions 
paid until date:

- 8 Hours after
- 3 days after
- 7 days after
- 14 days after

Once all 5 payments have failed, the renewal attempt number will be at 5 (increasing once per failure), 
and the system will no longer try to put the payment through.  

If any of the payments succeed, the subscription will be reactivated normally, 
and the renewal attempts number will be set back to 0.

## Determining If The System Should Reattempt A Renewal Payment

The system will only attempt to renew a subscription with a failed payment if:  

- Paid until date is in the past
- Is active is FALSE
- The renewal attempt number for the subscription is less than 5
- Cancelled on is empty/null
- Stopped is FALSE
- In Musora Center the state will show 'suspended'

You can get the system to go through the renewal attempt procedure for a given subscription by 
setting the above values within those rules and setting the paid until date to when you want it to start.

## Subscription States

There are 4 states a subscription can be in:

**active**
The subscription is scheduled to renew in the future. The subscription is only considered active if the 
following criteria are met:

- Paid until date is in the future
- Cancelled on is empty/null
- Is active is TRUE
- Stopped is FALSE

**suspended**
The subscriptions initial renewal payment failed. This subscription is either going to start going through 
the renewal reattempt schedule, or if the system already tried to renew the subscription 5 times, 
it will be ignored. The subscription is only considered active if the following criteria are met:

- Paid until date is in the past
- Cancelled on is empty/null
- Is active is FALSE
- Stopped is FALSE

**cancelled**
The subscription has been cancelled by the user or by support. It will no longer be renewed in any scenario. 
The subscription is only considered cancelled if the following criteria are met:

- Cancelled on is set to any date

This overrides all other values. The subscription will be considered cancelled regardless of what any of 
the other values are set to.

**stopped**
The subscription was suspended but the system will ignore it and no longer attempt to renew it under 
any scenario.

- Stopped is TRUE

This overrides all other values. The subscription will be considered stopped regardless of what any of 
the other values are set to.  

This is used when support would like to handle renewing a users subscription manually. It tells 
the automated system to completely ignore this subscription.