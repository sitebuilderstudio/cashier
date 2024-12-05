# Register / Subscribe Form

The shortcode [cashier_register_subscribe_form] renders the shortcode template located here;

templates/shortcode/partials/cashier-register-subscribe-form.php

Which can be customized with template override.

All the backend handling is here;

includes/public/class-cashier-public-shortcodes.php

## Existing Customers who are logged in

If they already have an active subscription, they're redirected to the billing portal instead of the register / subscribe form. This is because the billing portal has upgrade, downgrade, change payment method etc. So no need to go to the form if they're already logged in and active customers.


---------

# Magic Link / Subscribe Form

The shortcode [cashier_magic_link_subscribe_form] renders the shortcode template located here;

templates/shortcode/partials/cashier-magic-link-subscribe-form.php