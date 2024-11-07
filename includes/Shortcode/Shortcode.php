<?php

namespace Cashier\Shortcode;

use PHPMailer\PHPMailer\Exception;

class Shortcode {

    /**
     * Create only one instance so that it may not Repeat
     *
     * @since 1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

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
        wp_localize_script( 'cashier', 'cashier_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 ) );
    }

    public function cashier_register_subscribe_form() {
        include_once CASHIER_DIR_PATH . "templates/shortcode/partials/cashier-register-subscribe-form.php";
    }

    public function check_username() {
        if ( isset( $_POST['username'] ) ) {
            $username = $_POST['username'];

            // check if the username is taken
            $user_id = username_exists( $username );
            if ( ! username_exists( $username ) ) {
                $response = "<span style='color: green;'>Username Available.</span>";
            } else {
                $response = "<span style='color: red;'>Not Available.</span>";
            }

            echo $response;
            wp_die();
        }
    }

    public function check_email() {

        if ( isset( $_POST['email'] ) ) {

            $email = $_POST['email'];

            if ( ! email_exists( $email ) ) {
                $response = "<span style='color: green;'>Email Available.</span>";
            } else {
                $response = "<span style='color: red;'>Address is already in use.</span>";
            }

            echo $response;
            wp_die();
        }
    }

    public function check_coupon() {

        $cashier_options       = get_option( 'cashier_settings' );
        $stripe_api_secret_key = $cashier_options['options_secret_key'];

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


        $arr   = explode( "?", $_POST['subscription'], 2 );
        $price = $arr[0];

        // create wp user
        $userdata = array(
            'user_login' => $_POST['username'],
            'user_email' => $_POST['email'],
            'user_pass'  => $_POST['password'] // When creating an user, `user_pass` is expected.
        );

        $user_id = wp_insert_user( $userdata );

        // set user role to customer
        $user = new \WP_User( $user_id );
        $user->set_role( 'customer' );

        // On success.
        if ( ! is_wp_error( $user_id ) ) {
            //echo "<hr />WP User created : ". $user_id."<hr />";
        } else {
            echo "<p>Error creating new user.";
            exit;
        }

        //get keys from plugin options
        $cashier_options = get_option( 'cashier_settings' );

        $stripe = new \Stripe\StripeClient( $cashier_options['options_secret_key'] );

//		\Stripe\Stripe::setApiKey( $cashier_options['options_secret_key'] );

        // create stripe customer
        $customer = $stripe->customers->create( [
            'name'        => $_POST['name'],
            'email'       => $_POST['email'],
            'description' => '',
        ] );

        //attach payment method to customer
        $attach = $stripe->paymentMethods->attach(
            $_POST['payment_method'],
            [ 'customer' => $customer->id ]
        );

        //set payment method as default
        $stripe->customers->update(
            $customer->id,
            [ "invoice_settings" => [ "default_payment_method" => $_POST['payment_method'] ] ]
        );

        // add the customer stripe info to usermenta
        update_user_meta( $user_id, 'cashier_stripe_id', $customer->id );
        update_user_meta( $user_id, 'cashier_stripe_email', $customer->email );

        // if there's a coupon
        if ( isset( $_POST['coupon'] ) && $_POST['coupon'] != "" ) {

            //check the coupon
            try {

                // Use Stripe's library to make requests
                $response = $stripe->coupons->retrieve( $_POST['coupon'], [] );

                if ( $response->valid ) {

                    //run the subscription with the coupon
                    $subscription = $stripe->subscriptions->create( [
                        'trial_from_plan' => true,
                        'customer'        => $customer->id,
                        'items'           => [
                            [ 'price' => 'price_1JFFSLLJrATzBsWhnuMlYZUh' ],
                        ],
                        'coupon'          => $_POST['coupon'],
                    ] );

                }

            } catch ( Exception $e ) {
                var_dump($e);
            }

        } else {

            try {
                $subscription = $stripe->subscriptions->create( [
                    'trial_from_plan' => true,
                    'customer'        => $customer->id,
                    'items'           => [
                        [ 'price' => 'price_1JFFSLLJrATzBsWhnuMlYZUh' ],
                    ]
                ] );

                var_dump( $subscription );

            } catch ( Exception $e ) {
                var_dump( $e );
                die( 'disappinted.' );
            }

        }

        $url = strtok( $_POST['_wp_http_referer'], '?' );

        //redirect user to referrer with get var for do complete signup
        $url = $url . "?do=complete_registration&plan=" . $_POST['plan'];

        nocache_headers();
        if ( wp_safe_redirect( $url ) ) {
            exit;
        }
    }
}