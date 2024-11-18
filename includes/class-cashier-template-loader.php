<?php // cashier/includes/class-cashier-template-loader.php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Cashier_Template_Loader {

    /**
     * Get template path
     *
     * @param string $template_name
     * @return string
     */
    public function get_template($template_name) {
        $template = '';

        // Look within passed path within the theme - this is priority
        $template = locate_template(
            array(
                'cashier/' . $template_name,
                $template_name
            )
        );

        // Get default template
        if (!$template) {
            $template = CASHIER_DIR_PATH . 'templates/' . $template_name;
        }

        // Return what we found
        return apply_filters('cashier_locate_template', $template, $template_name);
    }

    /**
     * Get and include template file
     *
     * @param string $template_name
     * @param array  $args
     * @return string
     */
    public function get_template_part($template_name, $args = array()) {
        $template = $this->get_template($template_name);

        ob_start();

        // Extract args if they exist
        if ($args && is_array($args)) {
            extract($args);
        }

        // Include file if it exists
        if (file_exists($template)) {
            include $template;
        }

        return ob_get_clean();
    }
}