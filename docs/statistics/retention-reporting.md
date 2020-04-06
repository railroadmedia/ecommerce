# Membership Retention Reporting

There are many methods of reporting membership retention within given a time period. This is a guide is meant to explain
why we chose the one we did and how it works. It also goes over the problems with simple retention reporting that 
is often written about online.

Here is a list of questions we are trying to answer with the reporting from this system. These questions are important 
because we will use them to make decisions on how to structure the algorithm and reporting.

- If we add a new feature or new content, does it have an impact on the odds of a user renewing their membership?
- Does adding value for our users have an impact on retention rates over time?
- How is retention rate affected by our seasonality curve? Are users who sign up in January more likely to renew 
than ones who sign up in May?
- How do new products affect a users retention rate over time?


## How Simple Retention Theory Works 

Say there are a total of 10 monthly subscribers at the start of our example data (Jan 1) and the month progresses like 
this:

- Jan 01: 1 user successfully renews
- Jan 02: 1 user fails to renew
- Jan 03: 3 new users
- Jan 05: 2 users successfully renew
- Jan 10: 1 user fails to renew
- Jan 12: 6 new users
- Jan 15: 1 user successfully renews
- Jan 20: 1 user fails to renew
- Jan 22: 1 user successfully renews
- Jan 13: 2 new users
- Jan 26: 1 user successfully renews
- Jan 30: 1 user successfully renews
- Jan 31: 4 new users

**At the end of January:**
- 7 out of the original 10 renewed successfully
- 3 out of the original 10 failed to renew
- 15 new users started subscriptions
- there are 22 total active subscriptions

If we were to calculate retention for January on a monthly basis, we would use the normal formula of:  
(total subscribers at end of period - total new subscribers during period) / total customers at start of period  
  
We would end up with: (22 - 15) / 10 = 0.70 or **70% retention rate** for January  
  
This math works out well and is easily to calculate.


## Problems With Simple Retention Theory

### The Time Frame Problem

What happens if a user signs up and cancels within the reporting 
time frame itself? For example, lets add this to the example data above: 8 additional users sign up on Jan 5, 
and all 8 of them cancel between Jan 20 and Jan 28.

We would end up with: (22 - 23) / 10 = -0.10 or **-10% retention rate** for January, which makes no sense.  
  
This problem gets exponentially worse as we increase the reporting time frame. If we want to check
the retention rate for an entire year, thousands of users may have purchased and cancelled during that time frame
which makes the original formula useless. It will always result in a negative number.  
  
We might ask, why not ignore the users who start and end their subscription during the reporting time frame? 
Doing so would significantly reduce the meaning and usefulness of the report. The reported numbers would appear 
better than reality and would not be a good representation of the health of the subscription product.


### Cancellation Use Case Problem

A user can cancel their membership anytime between ordering and their renewal date. We have 3 pieces of 
information for a given users subscription: start date, scheduled renewal date, and cancellation date.

As an example lets say a user has the following subscription for a yearly membership:

- Start date: January 1, 2020
- Scheduled renewal date: Jan 1, 2021
- Cancellation date: March 1, 2020 (the user cancelled after 2 months)

In which time frame should this user be represented in the retention reporting? For example if we pulled the yearly 
retention number for all of March 2020, should this user be subtracted from the 'customers at end' variable? Lets say  
we pulled the yearly retention number for all of January 2021, should they be subtracted from 'customers at end' for 
this report? Should the user affect the report in both of these cases?


### Subscription Renewal Length VS Reporting Length Problem

Say there is the following example data for yearly subscribers for January 2020:

- There are 100 subscribers at the beginning of the month, Jan 01
- During the month, 10 of those users subscriptions were scheduled to renew
- Of the 10 scheduled to renew in January, 8 of them successfully renewed, 2 failed to renew or cancelled
- During January, an additional 10 users signed up for a yearly membership
- At the end of January, there were 108 total active yearly subscribers

If we plug this in to the formula we get:  

(108 - 10) / 100 = 0.98 or **98% retention rate** for January for yearly subscribers.  

This number is true to the formula but we know that our yearly retention rate, and the odds of a yearly member renewing 
successfully, is generally between 50% and 70%. The issue is we are pulling the report for a time frame that is less 
than the renewal length. Only 10 out of the 100 total yearly users were scheduled to renew during January. We also know 
that users tend to cancel near their scheduled renewal date. That is what makes the reported rate oddly high.    
 
This number is nearly meaningless without a full understanding of the math behind retention. 
Reporting the retention numbers in this way would cause confusion and not be useful.  

There is also a seasonality problem related to this issue. We know that users tend to cancel or fail to renew either on, 
or shortly before their renewal date. This means that if we pull a report for a time frame which is less 
than the subscription renewal length, such as in the above example, the rate will be directly effected by sales 
and marketing. Here is some example data showing how that works.

- In January 2019: 10000 new users sign up for a yearly membership  

- In February 2019: 250 of the original 10000 who signed up in January cancel before their renewal date
- In March 2019: 300 of the original 10000 who signed up in January cancel before their renewal date
- In April 2019: 350 of the original 10000 who signed up in January cancel before their renewal date
- In May 2019: 400 of the original 10000 who signed up in January cancel before their renewal date  
  
... (note the upward trend of the cancellations)  

- In December 2019: 5000 of the original 1000 who signed up in January cancel before their renewal date

(note the overwhelming majority of cancellations happen in the last month before the renewal date)

Lets say we were to the report 2019 retention numbers per month for 2019, the rate would decline in an exponential 
downward curve as you reported further toward the renewal date. Lets say in a real world example you mixed in real 
numbers per month in the above example. In the other months it's likely that significantly less new customers will sign 
up for a new yearly membership. Since so many people signed up in January 2019, that group of users is going to have 
an overwhelming impact on the retention numbers for the time period right before next years January.

I found this a bit tricky to show with a simple number example but the core idea is: **using the original simple 
algorithm to report on time periods less than the renewal cycle length results in the rates being directly effected 
by sales and marketing metrics.**  

This happens because users tend to cancel or fail to renew right before their 
scheduled renewal date. This is a major problem because when team members review the retention numbers, they are 
going to wonder why retention is so much lower in January as opposed to another month and possibly make 
assumptions about the product or platform. In reality the change in the rate is due to sales seasonality or 
random promotions, its not related to changes made to the product, platform, or content. 
The reporting algorithm must be independent of surges in new member sign ups.


### Seasonality Problem


## Solving The Retention Reporting Problems

We need a reporting algorithm which works independently of renewal cycle length and reporting time frame. We also need 
to make sure that monthly to yearly upgrades do not negatively impact retention reporting for the monthly membership. 
Lastly we need to make sure that we are treating all users subscriptions dates consistently, whether it be cancel date 
or renewal date, or a combination of both.