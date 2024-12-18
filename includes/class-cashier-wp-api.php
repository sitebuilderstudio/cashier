<?php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Cashier_WP_API
{
    public function __construct()
    {
        //add_action( 'admin_menu', [ $this, 'cashier_register_admin_menus' ] );
        add_action('rest_api_init', [ $this, 'register_routes' ] );
        add_action('rest_api_init', [ $this, 'register_fields' ] );
    }

    public function register_routes()
    {
        // write to debug.log that we're here
        error_log('Cashier_WP_API: register_routes()');

        // Get user by email
        register_rest_route('cashier/v1', '/user-by-email', array(
            'methods' => 'GET',
            'callback' => [ $this, 'get_user_by_email'],
            'permission_callback' => function () {
                return current_user_can('edit_users');
            },
            'args' => array(
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'email',
                ),
            ),
        ));

        // Get user by Stripe Customer ID
        register_rest_route('cashier/v1', '/user-by-stripe-id', array(
            'methods' => 'GET',
            'callback' => [ $this, 'get_user_by_stripe_id' ],
            'permission_callback' => function () {
                return current_user_can('edit_users');
            },
            'args' => array(
                'stripe_id' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        // Get user metadata
        register_rest_route('cashier/v1', '/user-metadata/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => [ $this, 'get_user_metadata' ],
            'permission_callback' => function () {
                return current_user_can('edit_users');
            },
        ));

        // Update user metadata
        register_rest_route('cashier/v1', '/user-metadata/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => 'update_user_metadata',
            'permission_callback' => function () {
                return current_user_can('edit_users');
            },
        ));
    }

    public function register_fields()
    {
        // Add user email field to user object
        register_rest_field(
            'user',
            'user_email',
            [
                'get_callback' => static function (array $user): string {
                    return get_userdata($user['id'])->user_email;
                },
            ]
        );
    }

    public function get_user_by_email( WP_REST_Request $request )
    {
        $email = $request->get_param('email');
        $user = get_user_by('email', $email);

        if ($user) {
            return new WP_REST_Response(array(
                'id' => $user->ID,
                'email' => $user->user_email,
                'name' => $user->display_name,
            ), 200);
        } else {
            return new WP_REST_Response(array('message' => 'User not found'), 404);
        }
    }

    public function get_user_by_stripe_id( WP_REST_Request $request )
    {
        $stripe_id = $request->get_param('stripe_id');

        $users = get_users(array(
            'meta_key' => 'cashier_stripe_id',
            'meta_value' => $stripe_id,
            'number' => 1,
            'fields' => 'ID'
        ));

        if (empty($users)) {
            return new WP_REST_Response(array('message' => 'User not found'), 404);
        }

        $user_id = $users[0];

        return new WP_REST_Response(array(
            'id' => $user_id,
            'stripe_id' => $stripe_id
        ), 200);
    }

    public function get_user_metadata( WP_REST_Request $request )
    {
        $user_id = $request['id'];
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $metadata = get_user_meta($user_id);

        // Set which keys to fetch
        $allowed_keys = array('cashier_stripe_id', 'cashier_stripe_email');
        $filtered_metadata = array_intersect_key($metadata, array_flip($allowed_keys));

        return new WP_REST_Response($filtered_metadata, 200);
    }

    public function update_user_metadata( WP_REST_Request $request )
    {
        $user_id = $request['id'];
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $params = $request->get_json_params();
        $updated = array();

        foreach ($params as $key => $value) {
            // You might want to add additional validation here
            if (update_user_meta($user_id, $key, $value)) {
                $updated[$key] = $value;
            }
        }

        if (empty($updated)) {
            return new WP_Error('update_failed', 'No metadata was updated', array('status' => 400));
        }

        return new WP_REST_Response($updated, 200);
    }
}