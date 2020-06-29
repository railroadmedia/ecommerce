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
in the main config file.

**Example for an order in BC Canada:**

Product costs: $10.00  
Cost to ship: $5.00

Product taxes due: 10 x 0.12 = $1.20  
Shipping taxes due: 5 x 0.05 = $0.25  
Total taxes due: $1.55  

**Grand total due: $16.55**