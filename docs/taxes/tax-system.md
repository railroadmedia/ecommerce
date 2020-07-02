# Tax System

## Overview

The tax system in this package is designed for use in Canada.

## How Taxes Are Calculated

Either the shipping or billing address is used to determine if taxes should be charged and what percent should be
charged.

- If the shipping address is present for an order (physical order items), then always use the shipping address.
- If there is no shipping address for the order (and for subscriptions), then use the billing address.

The system uses the country, and the region (state/province) to determine the tax rate.

Product costs and shipping costs are taxed differently in Canada depending on the province. For example in BC, 
shipping costs are only taxed 5% but product costs are taxes 12%. These can be configured separately 
in the main config file. Finance charges are not taxed.

**Example for an order in BC Canada:**

Product costs: $10.00  
Cost to ship: $5.00

Product taxes due: 10 x 0.12 = $1.20  
Shipping taxes due: 5 x 0.05 = $0.25  
Total taxes due: $1.55  

**Grand total due: $16.55**

## Recurring Subscription Taxes

Taxes for recurring payments are always calculated on the fly at the time of renewal. Tax rates can change and all 
active subscriptions must bill at the new rates. All recurring payment taxes are billed based on the 
attached payment method billing address.

## Payment Plans

For payment plans, the total taxes due is split up among the payments just like the product cost. By contrast the 
shipping costs are always paid in the first payment.

## Displaying Tax Info On Invoices

For charging users, the system only cares about the total tax rate per amount to be taxed. It does not care 
about different types of taxes such as PST, GST, QST, etc. On customer invoices however, we must display these 
different tax values properly depending on current laws.

In general there are 2 types of tax, GST (federal) which must be charged for all Canadian customers (5%), and
provincial. We only need to charge provincial sales tax if we have more than a specified amount of sales for that 
given province and brand.

Here is a breakdown of current Canadian tax laws per province and how they should be displayed on customer 
invoices:

---
**Alberta, Northwest Territories, Yukon**
- Federal Sales Tax (GST) - 5%

There is no provincial sales tax for these provinces/territories. Shipping and product costs are both taxed 5%. 
5% GST should be shown on customers invoices.

---
**British Columbia**
- Provincial Sales Tax (PST) - 7% (applies to all brands)
- Federal Sales Tax (GST) - 5%

Shipping costs are NOT charged PST, only GST. Shipping costs are charged 5% while product costs are charged 12%.  
PST and GST must be shown as separate totals on customer invoices.

---
**Manitoba**
- Provincial Sales Tax (PST) - 7% (NOT YET APPLICABLE TO ANY BRANDS)
- Federal Sales Tax (GST) - 5%

Shipping costs are NOT charged PST, only GST. Shipping costs are charged 5% while product costs are charged 12%.  
PST and GST must be shown as separate totals on customer invoices.

---
**New Brunswick, Newfoundland, Nova Scotia, Prince Edward Island**
- Federal & Provincial Sales Tax (Harmonized Sales Tax) (HST) - 15%

Federal and provincial sales tax is combined in to 1 name for these provinces (HST). It's too cold 
to handle separate sales tax amounts in these provinces. Product and shipping costs  
are both taxed the same amount, the HST.
HST must be shown as a separate total on customer invoices.

---
**Ontario**
- Federal & Provincial Sales Tax (Harmonized Sales Tax) (HST) - 13%

Federal and provincial sales tax is combined in to 1 name for this province (HST). Product and shipping costs  
are both taxed the same amount, the HST.
HST must be shown as a separate total on customer invoices.

---
**Saskatchewan**
- Provincial Sales Tax (PST) - 6% (NOT YET APPLICABLE TO ANY BRANDS)
- Federal Sales Tax (GST) - 5%

Shipping costs are NOT charged PST, only GST. Shipping costs are charged 5% while product costs are charged 11%.  
PST and GST must be shown as separate totals on customer invoices.

---
**Quebec**
- Quebec Sales Tax (QST) - 9.975% (ONLY APPLIES TO DRUMEO)
- Federal Sales Tax (GST) - 5%

Shipping costs are charged QST along with GST.
QST and GST must be shown as separate totals on customer invoices.