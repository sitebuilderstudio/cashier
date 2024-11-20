<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Cashier_Login_Handler
{
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        add_action('wp_login', array($this, 'redirect_to_stripe_portal'), 10, 2);
    }

    public function redirect_to_stripe_portal($user_login, $user) {
        if (in_array('subscriber', (array)$user->roles)) {
            $stripe_customer_id = get_user_meta($user->ID, 'cashier_stripe_id', true);

            if ($stripe_customer_id) {
                $portal_handler = new Cashier_Portal_Handler();
                $session = $portal_handler->create_portal_session($stripe_customer_id);

                if ($session) {
                    wp_redirect($session->url);
                    exit;
                }

                wp_redirect(home_url());
                exit;
            }
        }
    }
}