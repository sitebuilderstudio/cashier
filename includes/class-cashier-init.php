<?php

namespace Cashier;

class Cashier_Init
{
    public function __construct() {
        add_action('init', array($this, 'setup_roles'));
    }

    private function setup_roles(): void
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