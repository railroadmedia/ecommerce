## On Order

1. Subscription is created with the renewal date set exactly to whatever comes back from the receipt
2. If necessary a payment is created
3. User product is created with an expiration date of the subscription paid until date plus a 3 day buffer

## On Renewal Payment

1. Payment is created on the relevant subscription row
2. Subscription renewal date set exactly to whatever comes back from the receipt
3. User product expiration date is set to the subscription paid until date plus a 3 day buffer

## On Cancel/Expire Notification

1. Update the status and cancellation date of the receipt in question

## Notes

- Orders are never created