<?php // cashier/includes/admin/class-cashier-public-shortcodes.php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;

class Cashier_Shortcodes {

    public function __construct() {

        // create shortcodes
        add_shortcode( 'cashier_register_subscribe_form', [ $this, 'cashier_register_subscribe_form' ] );

        // load scripts
        add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ] );

        // handle ajax calls
        add_action( 'wp_ajax_check_username', [ $this, 'check_username' ] );
        add_action( 'wp_ajax_nopriv_check_username', [ $this, 'check_username' ] );
        add_action( 'wp_ajax_check_email', [ $this, 'check_email' ] );
        add_action( 'wp_ajax_nopriv_check_email', [ $this, 'check_email' ] );
        add_action( 'wp_ajax_check_coupon', [ $this, 'check_coupon' ] );
        add_action( 'wp_ajax_nopriv_check_coupon', [ $this, 'check_coupon' ] );

        // handle post submits
        add_action( 'admin_post_cashier_register_subscribe_form_handler', [
            $this,
            'cashier_register_subscribe_form_handler'
        ] );
        add_action( 'admin_post_nopriv_cashier_register_subscribe_form_handler', [
            $this,
            'cashier_register_subscribe_form_handler'
        ] );
    }

    public function load_scripts() {

        // external
        wp_enqueue_script( 'stripe-js-library', 'https://js.stripe.com/v3/' );

        // internal
        wp_enqueue_style( 'cashier', CASHIER_DIR_URL . 'assets/css/cashier-style.css' );
        wp_enqueue_script( 'cashier', CASHIER_DIR_URL . 'assets/js/cashier-script.js', [ 'jquery' ], '', true );
        wp_localize_script('cashier', 'cashier_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'stripe_public_key' => $this->get_stripe_public_key(),
            'nonce' => wp_create_nonce('cashier_ajax_nonce')
        ]);
    }

    private function get_stripe_public_key(){
        $cashier_options       = get_option( 'cashier_settings' );
        return $cashier_options['cashier_publishable_key'];
    }

    public function cashier_register_subscribe_form() {

        // Redirect to billing portal if user is logged in and has an active subscription
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $stripe_customer_id = get_user_meta($user->ID, 'cashier_stripe_id', true);

            if ($stripe_customer_id) {
                $portal_handler = new Cashier_Portal_Handler();
                $session = $portal_handler->create_portal_session($stripe_customer_id);

                if ($session) {
                    wp_redirect($session->url);
                    exit;
                }
            }
        }

        // Else load the register-subscribe form
        $template_loader = new Cashier_Template_Loader;

        $args = array(
            'price_id' => sanitize_text_field($_GET['price_id']),
            'is_logged_in' => is_user_logged_in()
        );

        return $template_loader->get_template_part(
            'shortcode/partials/cashier-register-subscribe-form.php',
            $args
        );
    }

    public function check_username() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cashier_ajax_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token']);
        }

        if (isset($_POST['username'])) {
            $username = sanitize_user($_POST['username']);

            if (strlen($username) < 3) {
                wp_send_json_error([
                    'message' => 'Username must be at least 3 characters long'
                ]);
            }

            if (!username_exists($username)) {
                wp_send_json_success([
                    'message' => 'Username is available'
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Username is not available'
                ]);
            }
        }
        wp_send_json_error(['message' => 'Username is required']);
    }

    public function check_email() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cashier_ajax_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token']);
        }

        if (isset($_POST['email'])) {
            $email = sanitize_email($_POST['email']);

            if (!is_email($email)) {
                wp_send_json_error([
                    'message' => 'Please enter a valid email address'
                ]);
            }

            if (!email_exists($email)) {
                wp_send_json_success([
                    'message' => 'Email is available'
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Email address is already in use'
                ]);
            }
        }
        wp_send_json_error(['message' => 'Email is required']);
    }

    public function check_coupon() {

        $cashier_options       = get_option( 'cashier_settings' );
        $stripe_api_secret_key = $cashier_options['cashier_secret_key'];

        $coupon = $_POST['coupon'];

        //check coupon via stripe api

        try {
            // Use Stripe's library to make requests...
            $stripe   = new \Stripe\StripeClient( $stripe_api_secret_key );
            $response = $stripe->coupons->retrieve( $coupon, [] );

            if ( $response->valid ) {
                $response = "<span style='color: green;'>Coupon is valid</span>";
            }

        } catch ( Exception $e ) {

            // Since it's a decline, \Stripe\Exception\CardException will be caught
            //echo 'Status is:' . $e->getHttpStatus() . '\n';
            //echo 'Type is:' . $e->getError()->type . '\n';
            //echo 'Code is:' . $e->getError()->code . '\n';
            // param is '' in this case
            //echo 'Param is:' . $e->getError()->param . '\n';
            //echo 'Message is:' . $e->getError()->message . '\n';
            $response = "<span style='color: red;'>Coupon is not valid</span>";

        } catch ( \Stripe\Exception\RateLimitException $e ) {
            // Too many requests made to the API too quickly
        } catch ( \Stripe\Exception\InvalidRequestException $e ) {
            // Invalid parameters were supplied to Stripe's API
            $response = "<span style='color: red;'>Coupon is not valid</span>";

        } catch ( \Stripe\Exception\AuthenticationException $e ) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            $response = "<span style='color: red;'>Coupon is not valid</span>";

        } catch ( \Stripe\Exception\ApiConnectionException $e ) {
            // Network communication with Stripe failed
            $response = "<span style='color: red;'>Coupon is not valid</span>";
        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            $response = "<span style='color: red;'>Coupon is not valid</span>";
        } catch ( Exception $e ) {
            // Something else happened, completely unrelated to Stripe
            $response = "<span style='color: red;'>Coupon is not valid</span>";
        }
        echo $response;
        wp_die();
    }

    public function cashier_register_subscribe_form_handler() {
        try {
            // Create WP user first but don't set role yet
            $userdata = array(
                'user_login' => sanitize_user($_POST['username']),
                'user_email' => sanitize_email($_POST['email']),
                'user_pass'  => $_POST['password']
            );

            $user_id = wp_insert_user($userdata);

            if (is_wp_error($user_id)) {
                throw new Exception($user_id->get_error_message());
            }

            $cashier_options = get_option('cashier_settings');
            $stripe = new \Stripe\StripeClient($cashier_options['cashier_secret_key']);

            // Create stripe customer
            try {
                $customer = $stripe->customers->create([
                    'name'        => sanitize_text_field($_POST['name']),
                    'email'       => sanitize_email($_POST['email']),
                    'description' => '',
                ]);
            } catch (CardException $e) {
                // Delete the WP user since payment failed
                wp_delete_user($user_id);
                throw new Exception($this->get_payment_error_message($e));
            }

            try {
                // Attach payment method to customer
                $stripe->paymentMethods->attach(
                    $_POST['payment_method'],
                    ['customer' => $customer->id]
                );

                // Set as default payment method
                $stripe->customers->update(
                    $customer->id,
                    ["invoice_settings" => ["default_payment_method" => $_POST['payment_method']]]
                );
            } catch (CardException $e) {
                // Clean up: delete customer and WP user
                $stripe->customers->delete($customer->id);
                wp_delete_user($user_id);

                // Return JSON response for AJAX handling
                wp_send_json_error([
                    'message' => $this->get_payment_error_message($e),
                    'code' => $e->getError()->code
                ]);
                exit;
            }

            // Create subscription
            try {
                $subscription_data = [
                    'trial_from_plan' => true,
                    'customer'        => $customer->id,
                    'items'           => [
                        ['price' => $_POST['price_id']],
                    ]
                ];

                if (!empty($_POST['coupon'])) {
                    $coupon = $stripe->coupons->retrieve($_POST['coupon']);
                    if ($coupon->valid) {
                        $subscription_data['coupon'] = $_POST['coupon'];
                    }
                }

                $subscription = $stripe->subscriptions->create($subscription_data);

                // Only set role and metadata after successful subscription
                $user = new \WP_User($user_id);
                $user->set_role('subscriber');
                update_user_meta($user_id, 'cashier_stripe_id', $customer->id);
                update_user_meta($user_id, 'cashier_stripe_email', $customer->email);

                // Get thank you page URL from settings
                $cashier_options = get_option('cashier_settings');
                $thank_you_page = $cashier_options['cashier_thank_you_page'] ?? home_url();

                // Add query parameters to thank you page URL
                $redirect_url = add_query_arg(
                    array(
                        'registration' => 'complete',
                        'status' => 'success'
                    ),
                    get_permalink($thank_you_page)
                );

                // Return success response with redirect URL
                wp_send_json_success([
                    'redirect_url' => $redirect_url,
                    'message' => 'Registration successful!'
                ]);
                exit;

            } catch (CardException $e) {
                // Clean up: delete customer and WP user
                $stripe->customers->delete($customer->id);
                wp_delete_user($user_id);
                throw new Exception($this->get_payment_error_message($e));
            } catch (ApiErrorException $e) {
                // Handle any other Stripe API errors
                $stripe->customers->delete($customer->id);
                wp_delete_user($user_id);
                throw new Exception('An error occurred while processing your payment. Please try again later.');
            }

        } catch (Exception $e) {
            // Return JSON response for AJAX handling
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => isset($e->getError) ? $e->getError()->code : 'general_error'
            ]);
            exit;
        }
    }

    private function get_payment_error_message($e): string
    {
        $error_code = $e->getError()->code;

        $error_messages = [
            'card_declined' => 'Your card was declined. Please try a different card.',
            'insufficient_funds' => 'Your card has insufficient funds. Please try a different card.',
            'expired_card' => 'Your card has expired. Please try a different card.',
            'incorrect_cvc' => 'The security code (CVC) was incorrect. Please check and try again.',
            'processing_error' => 'An error occurred while processing your card. Please try again.',
            'incorrect_number' => 'The card number is incorrect. Please check and try again.'
        ];

        return $error_messages[$error_code] ?? 'An error occurred while processing your payment. Please try again.';
    }
}