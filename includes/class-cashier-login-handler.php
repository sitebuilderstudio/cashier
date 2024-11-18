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
                try {
                    $cashier_options = get_option('cashier_settings');
                    $stripe = new \Stripe\StripeClient($cashier_options['options_secret_key']);

                    $subscriptions = $stripe->subscriptions->all([
                        'customer' => $stripe_customer_id,
                        'status' => 'active',
                        'limit' => 1
                    ]);

                    $has_active_subscription = !empty($subscriptions->data);

                    $configuration_id = $this->create_portal_configuration($stripe, $has_active_subscription);

                    if (!$configuration_id) {
                        throw new \Exception('Failed to create portal configuration');
                    }

                    $session = $stripe->billingPortal->sessions->create([
                        'customer' => $stripe_customer_id,
                        'return_url' => home_url(),
                        'configuration' => $configuration_id,
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

    private function create_portal_configuration($stripe, $has_active_subscription) {
        $products = $this->get_products_configuration();

        if (empty($products)) {
            error_log('No products found for portal configuration');
            return null;
        }

        try {
            $configuration = $stripe->billingPortal->configurations->create([
                'features' => [
                    'subscription_update' => [
                        'enabled' => true,
                        'default_allowed_updates' => ['price', 'quantity'],
                        'proration_behavior' => $has_active_subscription ? 'create_prorations' : 'none',
                        'products' => $products,
                    ],
                    'payment_method_update' => [
                        'enabled' => true,
                    ],
                    'customer_update' => [
                        'enabled' => true,
                        'allowed_updates' => ['email', 'address'],
                    ],
                    'invoice_history' => [
                        'enabled' => true,
                    ],
                    'subscription_cancel' => [
                        'enabled' => true,
                        'mode' => 'at_period_end',
                    ],
                ],
                'business_profile' => [
                    'headline' => 'Manage your subscription',
                ],
            ]);

            return $configuration->id;
        } catch (\Exception $e) {
            error_log('Failed to create portal configuration: ' . $e->getMessage());
            return null;
        }
    }

    private function get_products_configuration() {
        $table_name = $this->wpdb->prefix . 'cash_products';

        $items = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT stripe_product_id, stripe_price_id FROM {$table_name} 
                 WHERE subscription = %d 
                 ORDER BY display_order ASC",
                1
            )
        );

        $grouped_products = [];
        foreach ($items as $item) {
            if ($item->stripe_product_id && $item->stripe_price_id) {
                if (!isset($grouped_products[$item->stripe_product_id])) {
                    $grouped_products[$item->stripe_product_id] = [
                        'product' => $item->stripe_product_id,
                        'prices' => []
                    ];
                }
                $grouped_products[$item->stripe_product_id]['prices'][] = $item->stripe_price_id;
            }
        }

        return array_values($grouped_products);
    }
}