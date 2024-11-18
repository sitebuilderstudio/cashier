<?php // cashier/includes/class-cashier-init.php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Cashier_Init
{
    public function __construct() {
        add_action('init', array($this, 'setup_roles'));
    }

    public function setup_roles(): void
    {
        if (!get_role('subscriber')) {
            add_role(
                'subscriber',
                __('Subscriber', 'cashier'),
                array(
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                )
            );
        }
    }
}