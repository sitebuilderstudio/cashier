<?php
// cashier/includes/class-cashier-portal-handler.php

class Cashier_Portal_Handler {
    private $wpdb;
    private $stripe;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        $cashier_options = get_option('cashier_settings');
        $this->stripe = new \Stripe\StripeClient($cashier_options['cashier_secret_key']);
    }

    public function create_portal_session($stripe_customer_id) {
        try {
            $subscriptions = $this->stripe->subscriptions->all([
                'customer' => $stripe_customer_id,
                'status' => 'active',
                'limit' => 1
            ]);

            $has_active_subscription = !empty($subscriptions->data);
            $configuration_id = $this->create_portal_configuration($has_active_subscription);

            if (!$configuration_id) {
                throw new \Exception('Failed to create portal configuration');
            }

            return $this->stripe->billingPortal->sessions->create([
                'customer' => $stripe_customer_id,
                'return_url' => home_url(),
                'configuration' => $configuration_id,
            ]);

        } catch (\Exception $e) {
            error_log('Portal session creation failed: ' . $e->getMessage());
            return null;
        }
    }

    private function create_portal_configuration($has_active_subscription) {
        $products = $this->get_products_configuration();

        if (empty($products)) {
            return null;
        }

        try {
            $configuration = $this->stripe->billingPortal->configurations->create([
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
            error_log('Portal configuration creation failed: ' . $e->getMessage());
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