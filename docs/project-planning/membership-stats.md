# Membership Statistics API & Dashboard

## Stats List Per Day

- Total Users With Active Membership Subscription (is_active = true && paid_until > now)
- Total Users With Suspended Membership Subscription (is_active = false && paid_until < now && canceled_on is null)
- Total Users With Cancelled Membership Subscription (canceled_on is not null)

- Total Membership Subscriptions Started Today (start_date)
- Total Membership Subscriptions Expired/Suspended Today (paid_until)
- Total Membership Subscriptions Cancelled Today (canceled_on)

## Total Users Stats

For the 'Total Users' stats we only want to track the stats per user, not per subscription. We need to make sure a single user is only 
represented once in the statistics for a given day. For example a user may have multiple subscriptions, one canceled from 2 years ago, and 1 active started a week ago. 
This user should only add 1 to the 'Total Users With Active Membership Subscription' stat. This user should not add 1 to the Cancelled total.

The 3 'Total Users' stats for the current day should add up to the total amount of users who have ever had a subscription. Currently,
these are calculated by looking at the subscriptions table and totalling up the subscription counts. This leads to inflated numbers 
because users can have more than 1 subscription row. Instead it should count how many unique users have a subscription in the given state.

Even though a single user can have multiple subscriptions, we should only look at 1 and use it to represent the user in the stats.
Here is the priority:

1. If a user has any active membership subscription, it should be used. If they have multiple active, the one with the paid_until furthest in the future
should be used.
2. If the user has no active membership subscriptions, we should compare all their suspended subscriptions
and choose the one with the paid_until date furthest in the future.
3. If the user doesn't have any suspended or active subscriptions, use the subscription with the furthest cancellation date in the future.

You can see roughly how this logic works here: src/Listeners/DuplicateSubscriptionHandler.php 

Here is an example. A user has 2 monthly subscription rows:
1. Started 2018-01-01, Paid Until 2018-04-01, Cancelled 2018-05-01
2. Started 2020-02-01, Paid Until 2020-04-15, currently active

- If you were to calculate the past stats anytime from for 2018-01-01 to 2018-04-01, it should add +1 to the active total for those days.
- If you were to calculate the past stats anytime from for 2018-04-01 to 2018-05-01, it should add +1 to the suspended/expired total for those days. No other states should get +1 for those days. 
- If you were to calculate the past stats anytime from for 2018-05-01 to 2020-02-01, it should add +1 to the canceled total for those days. No other states should get +1 for those days.
- If you were to calculate the past stats for 2020-02-01 to current time, it should add +1 to the active total. No other states should get +1 for those days.

In summary, a user cannot be represented in a given days stats more than once even if they have multiple subscription rows.

## Total Subscriptions With State Stats

The logic for these looks good.