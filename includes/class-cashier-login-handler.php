<?php // cashier/includes/class-cashier-login-handler.php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Cashier_Login_Handler
{
    public function __construct() {
        add_action('wp_login', array($this, 'redirect_to_stripe_portal'), 10, 2);
    }

    public function redirect_to_stripe_portal($user_login, $user)
    {

        if (in_array('subscriber', (array)$user->roles)) {

            $stripe_customer_id = get_user_meta($user->ID, 'cashier_stripe_id', true);

            if ($stripe_customer_id) {
                try {

                    \Stripe\Stripe::setApiKey(get_option('cashier_stripe_secret_key'));

                    $session = \Stripe\BillingPortal\Session::create([
                        'customer' => $stripe_customer_id,
                        'return_url' => home_url(),
                    ]);

                    wp_redirect($session->url);
                    exit;
                } catch (\Exception $e) {

                    error_log('Stripe portal redirect failed: ' . $e->getMessage());
                    wp_redirect(home_url());
                    exit;
                }
            }
        }
    }
}