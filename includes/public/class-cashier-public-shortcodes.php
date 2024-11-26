<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;

class Cashier_Shortcodes {
    private $stripe;
    private $cashier_options;

    public function __construct() {
        $this->init_hooks();
        $this->cashier_options = get_option('cashier_settings');
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Shortcodes
        add_shortcode('cashier_register_subscribe_form', [$this, 'cashier_register_subscribe_form']);

        // Scripts
        add_action('wp_enqueue_scripts', [$this, 'load_scripts']);

        // Ajax handlers
        $this->init_ajax_handlers();

        // Form handlers
        $this->init_form_handlers();
    }

    /**
     * Initialize Ajax handlers
     */
    private function init_ajax_handlers() {
        $ajax_actions = [
            'check_username',
            'check_email',
            'check_coupon'
        ];

        foreach ($ajax_actions as $action) {
            add_action("wp_ajax_{$action}", [$this, $action]);
            add_action("wp_ajax_nopriv_{$action}", [$this, $action]);
        }
    }

    /**
     * Initialize form handlers
     */
    private function init_form_handlers() {
        $handler = 'cashier_register_subscribe_form_handler';
        add_action("admin_post_{$handler}", [$this, $handler]);
        add_action("admin_post_nopriv_{$handler}", [$this, $handler]);
    }

    /**
     * Load required scripts and styles
     */
    public function load_scripts() {
        // External scripts
        wp_enqueue_script('stripe-js-library', 'https://js.stripe.com/v3/');

        // Internal assets
        wp_enqueue_style('cashier', CASHIER_DIR_URL . 'assets/css/cashier-style.css');
        wp_enqueue_script('cashier', CASHIER_DIR_URL . 'assets/js/cashier-script.js', ['jquery'], '', true);

        wp_localize_script('cashier', 'cashier_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'stripe_public_key' => $this->get_stripe_public_key(),
            'nonce' => wp_create_nonce('cashier_ajax_nonce')
        ]);
    }

    /**
     * Get Stripe public key from settings
     */
    private function get_stripe_public_key() {
        return $this->cashier_options['cashier_publishable_key'];
    }

    /**
     * Handle the register/subscribe form shortcode
     */
    public function cashier_register_subscribe_form() {
        if ($portal_url = $this->check_existing_subscription()) {
            wp_redirect($portal_url);
            exit;
        }

        return $this->render_registration_form();
    }

    /**
     * Check if user has existing subscription
     */
    private function check_existing_subscription() {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        $stripe_customer_id = get_user_meta($user->ID, 'cashier_stripe_id', true);

        if ($stripe_customer_id) {
            $portal_handler = new Cashier_Portal_Handler();
            $session = $portal_handler->create_portal_session($stripe_customer_id);
            return $session ? $session->url : false;
        }

        return false;
    }

    /**
     * Render the registration form
     */
    private function render_registration_form() {
        $template_loader = new Cashier_Template_Loader;
        $user_data = $this->get_user_data();

        $args = [
            'price_id' => sanitize_text_field($_GET['price_id']),
            'is_logged_in' => is_user_logged_in(),
            'user_data' => $user_data
        ];

        return $template_loader->get_template_part(
            'shortcode/partials/cashier-register-subscribe-form.php',
            $args
        );
    }

    /**
     * Get current user data if logged in
     */
    private function get_user_data() {
        if (!is_user_logged_in()) {
            return [];
        }

        $user = wp_get_current_user();
        return [
            'user_login' => $user->user_login,
            'user_email' => $user->user_email
        ];
    }

    /**
     * Ajax handler for username validation
     */
    public function check_username() {
        $this->verify_nonce();

        if (!isset($_POST['username'])) {
            wp_send_json_error(['message' => 'Username is required']);
        }

        $username = sanitize_user($_POST['username']);

        if (strlen($username) < 3) {
            wp_send_json_error(['message' => 'Username must be at least 3 characters long']);
        }

        wp_send_json_success([
            'message' => username_exists($username) ? 'Username is not available' : 'Username is available'
        ]);
    }

    /**
     * Ajax handler for email validation
     */
    public function check_email() {
        $this->verify_nonce();

        if (!isset($_POST['email'])) {
            wp_send_json_error(['message' => 'Email is required']);
        }

        $email = sanitize_email($_POST['email']);

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address']);
        }

        wp_send_json_success([
            'message' => email_exists($email) ? 'Email address is already in use' : 'Email is available'
        ]);
    }

    /**
     * Ajax handler for coupon validation
     */
    public function check_coupon() {
        try {
            $stripe = new \Stripe\StripeClient($this->cashier_options['cashier_secret_key']);
            $response = $stripe->coupons->retrieve($_POST['coupon'], []);

            echo $response->valid ?
                "<span style='color: green;'>Coupon is valid</span>" :
                "<span style='color: red;'>Coupon is not valid</span>";
        } catch (Exception $e) {
            echo "<span style='color: red;'>Coupon is not valid</span>";
        }
        wp_die();
    }

    /**
     * Handle form submission
     */
    public function cashier_register_subscribe_form_handler() {
        ray($_POST);

        try {
            $user_id = $this->handle_user_creation();
            $stripe_customer_id = $this->handle_stripe_customer($user_id);
            $this->handle_payment_method($stripe_customer_id);
            $this->create_subscription($stripe_customer_id, $user_id);

            $this->send_success_response();
        } catch (Exception $e) {
            $this->handle_error($e);
        }
    }

    /**
     * Handle user creation or retrieval
     */
    private function handle_user_creation() {
        if (is_user_logged_in()) {
            return get_current_user_id();
        }

        $userdata = [
            'display_name' => sanitize_text_field($_POST['name']),
            'user_login' => sanitize_user($_POST['username']),
            'user_email' => sanitize_email($_POST['email']),
            'user_pass' => sanitize_text_field($_POST['password'])
        ];

        $user_id = wp_insert_user($userdata);
        if (is_wp_error($user_id)) {
            throw new Exception($user_id->get_error_message());
        }

        return $user_id;
    }

    /**
     * Handle Stripe customer creation or retrieval
     */
    private function handle_stripe_customer($user_id) {
        $stripe = new \Stripe\StripeClient($this->cashier_options['cashier_secret_key']);
        $stripe_customer_id = get_user_meta($user_id, 'cashier_stripe_id', true);

        if (!$stripe_customer_id) {
            $user = get_userdata($user_id);
            $customer = $stripe->customers->create([
                'name' => trim($user->first_name . ' ' . $user->last_name),
                'email' => $user->user_email,
                'description' => ''
            ]);
            $stripe_customer_id = $customer->id;
            update_user_meta($user_id, 'cashier_stripe_id', $stripe_customer_id);
        }

        return $stripe_customer_id;
    }

    /**
     * Handle payment method attachment
     */
    private function handle_payment_method($stripe_customer_id) {
        $stripe = new \Stripe\StripeClient($this->cashier_options['cashier_secret_key']);
        $payment_method = sanitize_text_field($_POST['payment_method']);

        $stripe->paymentMethods->attach($payment_method, ['customer' => $stripe_customer_id]);
        $stripe->customers->update($stripe_customer_id, [
            "invoice_settings" => ["default_payment_method" => $payment_method]
        ]);
    }

    /**
     * Create subscription
     */
    private function create_subscription($stripe_customer_id, $user_id) {
        $stripe = new \Stripe\StripeClient($this->cashier_options['cashier_secret_key']);

        $subscription_data = [
            'trial_from_plan' => true,
            'customer' => $stripe_customer_id,
            'items' => [['price' => sanitize_text_field($_POST['price_id'])]]
        ];

        if (!empty($_POST['coupon'])) {
            $coupon = $stripe->coupons->retrieve(sanitize_text_field($_POST['coupon']));
            if ($coupon->valid) {
                $subscription_data['coupon'] = $coupon;
            }
        }

        $stripe->subscriptions->create($subscription_data);

        $user = new \WP_User($user_id);
        $user->set_role('subscriber');
        update_user_meta($user_id, 'cashier_stripe_email', $user->user_email);
    }

    /**
     * Send success response
     */
    private function send_success_response() {
        $thank_you_page = $this->cashier_options['cashier_thank_you_page'] ?? home_url();
        $redirect_url = add_query_arg(
            ['registration' => 'complete', 'status' => 'success'],
            get_permalink($thank_you_page)
        );

        wp_send_json_success([
            'redirect_url' => $redirect_url,
            'message' => 'Registration successful!'
        ]);
    }

    /**
     * Handle errors
     */
    private function handle_error(Exception $e) {
        wp_send_json_error([
            'message' => $this->get_payment_error_message($e),
            'code' => isset($e->getError) ? $e->getError()->code : 'general_error'
        ]);
    }

    /**
     * Verify nonce
     */
    private function verify_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cashier_ajax_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token']);
        }
    }

    /**
     * Get payment error message
     */
    private function get_payment_error_message($e): string {
        if (!($e instanceof CardException)) {
            return 'An error occurred while processing your payment. Please try again.';
        }

        $error_messages = [
            'card_declined' => 'Your card was declined. Please try a different card.',
            'insufficient_funds' => 'Your card has insufficient funds. Please try a different card.',
            'expired_card' => 'Your card has expired. Please try a different card.',
            'incorrect_cvc' => 'The security code (CVC) was incorrect. Please check and try again.',
            'processing_error' => 'An error occurred while processing your card. Please try again.',
            'incorrect_number' => 'The card number is incorrect. Please check and try again.'
        ];

        return $error_messages[$e->getError()->code] ?? 'An error occurred while processing your payment. Please try again.';
    }
}