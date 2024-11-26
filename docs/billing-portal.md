# Billing Portal

If user logging in has a Stripe customer ID stored as user meta-data, and if they have an active subscription, they'll be automatically redirected to the billing portal upon logging into the site.

In order for this to work, you must have the products and prices entered in admin > cashier > plans. If they aren't entered there, the billing portal won't confirgure with the appropriate products / prices.