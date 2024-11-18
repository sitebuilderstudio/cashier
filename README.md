# Cashier
This is the scaffold plugin we use to build out custom Stripe integrations for WordPress.

More info on wpcashier.com

## Stripe Billing Portal Setup

By default, cashier will redirect any user with role of 'subscriber' to the Stripe Billing Portal immediatly after they login. 

To set up the portal to work properly, go to your Stripe dashboard;

1 > Go to Settings > Billing > Customer Portal
2 > Enable "Cancel subscription" and "Switch to different plan/price"
3 > Configure which prices should be visible in the portal ( these can be controlled from that list in the session create method in class-cashier-login-handler.php )
Save the configuration

After that, the products and prices from your database should appear as upgrade/downgrade options in the portal.