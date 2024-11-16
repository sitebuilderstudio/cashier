<?php

use Stripe\StripeClient;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException;

class Cashier_Stripe {

    private $stripe;

    public function __construct() {
        $apiKey = $this->getStripeSecretKey();
        $this->stripe = new \Stripe\StripeClient($apiKey);
    }

    private function getStripeSecretKey(){
        $cashier_options       = get_option( 'cashier_settings' );
        return $cashier_options['options_secret_key'];
    }

    public function createSetupIntent() {
        // Ensure that our function can only be called via AJAX
        if (!wp_doing_ajax()) {
            wp_die('Invalid request');
        }

        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vitality-nonce')) {
            $this->logger->error('Invalid nonce in createSetupIntent');
            wp_send_json_error(array('message' => 'Invalid nonce'), 403);
            wp_die();
        }

        // Get and sanitize input
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $fname = isset($_POST['fname']) ? sanitize_text_field($_POST['fname']) : '';
        $lname = isset($_POST['lname']) ? sanitize_text_field($_POST['lname']) : '';

        // Validate input
        if (empty($email) || empty($fname) || empty($lname)) {
            $this->logger->error('Invalid input in createSetupIntent', [
                'email' => $email,
                'fname' => $fname,
                'lname' => $lname
            ]);
            wp_send_json_error(array('message' => 'Invalid input'), 400);
            wp_die();
        }

        try {
            // Create a SetupIntent without creating a customer
            $setup_intent = $this->stripe->setupIntents->create(['payment_method_types' => ['card']]);

            $this->logger->info('SetupIntent created:', ['setup_intent_id' => $setup_intent->id]);

            // Send the client secret and setup intent ID to the client
            $response_data = array(
                'clientSecret' => $setup_intent->client_secret,
                'setupIntentId' => $setup_intent->id
            );

            $this->logger->info('Sending response to client:', $response_data);

            wp_send_json_success($response_data);
        } catch (Exception $e) {
            $this->logger->error('Error in createSetupIntent: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred. Please try again.'], 500);
        }

        wp_die();
    }

    public function updateDefaultPaymentMethod(): void
    {
        // Ensure that our function can only be called via AJAX
        if (!wp_doing_ajax()) {
            wp_die('Invalid request');
        }

        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vitality-nonce')) {
            $this->logger->error('Invalid nonce in updateDefaultPaymentMethod');
            wp_send_json_error(array('message' => 'Invalid nonce'), 403);
            wp_die();
        }

        // Get and sanitize input
        $user_id = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $payment_method_id = isset($_POST['paymentMethodId']) ? sanitize_text_field($_POST['paymentMethodId']) : '';
        $customer_id = isset($_POST['customerId']) ? sanitize_text_field($_POST['customerId']) : '';

        $this->logger->info('Received data in updateDefaultPaymentMethod:', [
            'user_id' => $user_id,
            'payment_method_id' => $payment_method_id,
            'customer_id' => $customer_id
        ]);

        if (empty($user_id) || $user_id === 0) {
            $this->logger->error('Invalid user ID in updateDefaultPaymentMethod');
            wp_send_json_error(array('message' => 'Invalid user ID'), 400);
            wp_die();
        }

        if (empty($payment_method_id)) {
            $this->logger->error('Invalid payment method ID in updateDefaultPaymentMethod');
            wp_send_json_error(array('message' => 'Invalid payment method ID'), 400);
            wp_die();
        }

        if (empty($customer_id)) {
            $this->logger->error('Invalid customer ID in updateDefaultPaymentMethod');
            wp_send_json_error(array('message' => 'Invalid customer ID'), 400);
            wp_die();
        }

        try {
            // Attach the payment method to the customer
            $this->stripe->paymentMethods->attach(
                $payment_method_id,
                ['customer' => $customer_id]
            );

            // Set the payment method as the default for the customer
            $this->stripe->customers->update(
                $customer_id,
                ['invoice_settings' => ['default_payment_method' => $payment_method_id]]
            );

            // Update WordPress user meta
            update_user_meta($user_id, 'vitality_stripe_payment_method', $payment_method_id);

            $this->logger->info('Default payment method updated successfully', [
                'user_id' => $user_id,
                'payment_method_id' => $payment_method_id,
                'customer_id' => $customer_id
            ]);

            wp_send_json_success(array('message' => 'Default payment method updated successfully'));
        } catch (Exception $e) {
            $this->logger->error('Error updating default payment method: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()), 400);
        }

        wp_die(); // Terminate AJAX request
    }

    public function createOrUpdateWPUser($fname, $lname, $email, $customer_id, $payment_method): WP_Error|bool|int
    {
        $this->logger->info('createOrUpdateWPUser called with:', [
            'email' => $email,
            'customer_id' => $customer_id
        ]);

        $user = get_user_by('email', $email);
        if ($user) {
            $user_id = $user->ID;
            $this->logger->info('Existing user found:', ['user_id' => $user_id]);
        } else {
            $user_id = $this->createUser($fname, $lname, $email);
            $this->logger->info('New user created:', ['user_id' => $user_id]);
        }

        $update_result = $this->updateUserMetaAndRole($user_id, $customer_id, $payment_method);
        $this->logger->info('User meta and role update result:', ['result' => $update_result, 'user_id' => $user_id]);

        return $user_id;
    }

    private function createUser($fname, $lname, $email): WP_Error|bool|int
    {
        $this->logger->info('Creating new user:', ['email' => $email]);

        $password = wp_generate_password(12, false);
        $user_data = array(
            'user_login' => $email,
            'user_pass'  => $password,
            'user_email' => $email,
            'first_name' => $fname,
            'last_name'  => $lname,
            'show_admin_bar_front' => 'false',
        );

        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) {
            $this->logger->error('Failed to create user:', ['error' => $user_id->get_error_message()]);
            return false;
        }

        // send email to user with password
        wp_new_user_notification($user_id, null, 'user');
        $this->logger->info('New user created successfully:', ['user_id' => $user_id]);

        return $user_id;
    }

    private function updateUserMetaAndRole($user_id, $customer_id, $payment_method): bool
    {
        $this->logger->info('Updating user meta and role:', ['user_id' => $user_id, 'customer_id' => $customer_id]);

        update_user_meta($user_id, 'vitality_stripe_customer_id', $customer_id);
        if ($payment_method) {
            update_user_meta($user_id, 'vitality_stripe_payment_method', $payment_method);
        }
        $user = get_user_by('ID', $user_id);
        $user->set_role('freebie');
        $result = wp_update_user($user);

        if (is_wp_error($result)) {
            $this->logger->error('Failed to update user:', ['error' => $result->get_error_message()]);
            return false;
        }

        $this->logger->info('User meta and role updated successfully');
        return true;
    }

    private function createCustomerAndAttachPaymentMethod($email, $fname, $lname, $payment_method) {
        try {
            // Create a new Stripe customer
            $customer = $this->stripe->customers->create([
                'email' => $email,
                'name' => $fname . ' ' . $lname,
            ]);

            $this->logger->info('Customer created', ['customer_id' => $customer->id]);

            // Attach the payment method to the customer
            $this->stripe->paymentMethods->attach(
                $payment_method,
                ['customer' => $customer->id]
            );

            $this->logger->info('Payment method attached', ['customer_id' => $customer->id, 'payment_method' => $payment_method]);

            // Set the payment method as the default for the customer
            $this->stripe->customers->update(
                $customer->id,
                ['invoice_settings' => ['default_payment_method' => $payment_method]]
            );

            $this->logger->info('Default payment method set', ['customer_id' => $customer->id, 'payment_method' => $payment_method]);

            return ['success' => true, 'customer_id' => $customer->id];
        } catch (Exception $e) {
            $this->logger->error('Error creating customer or attaching payment method: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function processSubscription($email, $fname, $lname, $payment_method, $subscription_price_id) {
        $customer_id = null;
        try {
            // Create customer and attach payment method
            $customer_result = $this->createCustomerAndAttachPaymentMethod($email, $fname, $lname, $payment_method);
            if (!$customer_result['success']) {
                return $customer_result;
            }

            $customer_id = $customer_result['customer_id'];

            // Check if it's the 30DAY promo
            if ($subscription_price_id === VITALITY_30DAY_SUBSCRIPTION_PRICE_ID) {
                // Charge $1.00 immediately for 30DAY promo
                $charge_result = $this->chargeOneDollarFor30DayPromo($customer_id, $payment_method);
                if (!$charge_result['success']) {
                    throw new Exception($charge_result['message']);
                }
            }

            $subscription = $this->stripe->subscriptions->create([
                'customer' => $customer_id,
                'description' => 'VitalityVille Ltd.',
                'items' => [
                    [
                        'price' => $subscription_price_id,
                    ],
                ],
                'collection_method' => 'charge_automatically',
                'trial_period_days' => 30,
                'cancel_at' => time() + (60 * 24 * 60 * 60),
                'proration_behavior' => 'none',
                'default_payment_method' => $payment_method,
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            if ($subscription->status === 'active' || $subscription->status === 'trialing') {
                $this->logger->info('Subscription created successfully', ['subscription_id' => $subscription->id]);
                return ['success' => true, 'customer_id' => $customer_id];
            } else {
                $this->logger->error('Subscription creation failed', ['subscription_id' => $subscription->id, 'status' => $subscription->status]);
                throw new Exception('Subscription creation failed');
            }
        } catch (Exception $e) {
            $this->logger->error('Error in subscription process: ' . $e->getMessage());
            if ($customer_id) {
                $this->deleteCustomer($customer_id);
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function deleteCustomer($customer_id) {
        try {
            $this->stripe->customers->delete($customer_id);
            $this->logger->info('Customer deleted', ['customer_id' => $customer_id]);
            return true;
        } catch (Exception $e) {
            $this->logger->error('Failed to delete customer: ' . $e->getMessage(), ['customer_id' => $customer_id]);
            return false;
        }
    }
}

