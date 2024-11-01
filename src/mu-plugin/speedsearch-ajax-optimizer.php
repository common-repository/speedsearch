<?php
/**
 * Plugin Name: SpeedSearch AJAX Optimizer
 * Description: A plugin that helps SpeedSearch AJAX requests to work faster by deloading all plugins except for the SpeedSearch and WooCommerce for SpeedSearch AJAX requests. It was installed automatically by SpeedSearch plugin. To delete it, deactivate SpeedSearch plugin.
 * Version:     0.1.12
 * Text Domain: speedsearch
 *
 * @package SpeedSearch AJAX Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

add_filter(
    'option_active_plugins',
    function( $active_plugins ) {
        $speedsearch_plugin_file = 'speedsearch/speedsearch.php';
        $wc_plugin_file          = 'woocommerce/woocommerce.php';

        if (
            // If SpeedSearch plugin is not active (e.g. when it was removed without deactivation), delete this MU plugin.
            // The same for WC (SpeedSearch dependency).
            (
                ! in_array( $speedsearch_plugin_file, $active_plugins, true ) ||
                ! in_array( $wc_plugin_file, $active_plugins, true )
            ) &&
            // This is for case when other AJAX optimizers are used, which could lead to the false-negative self-destruction of this file.
            ! isset( $_REQUEST['action'] ) // @codingStandardsIgnoreLine
        ) {
            if ( is_file( __FILE__ ) ) {
                @unlink( __FILE__ ); // @codingStandardsIgnoreLine
            }
            return $active_plugins;
        }

        $actions_used_to_retrieve_posts = [
            'search',
            'get_public_settings',
            'get_recently_viewed_products_data',
        ];

        // Deloads all plugins except for the SpeedSearch and WC for posts-retrieval SpeedSearch AJAX requests.
        if (
            array_key_exists( 'plugin', $_REQUEST ) && 'speedsearch' === $_REQUEST['plugin'] && // @codingStandardsIgnoreLine
            ! in_array( $_REQUEST['action'], $actions_used_to_retrieve_posts, true ) && // @codingStandardsIgnoreLine
            // For wp_get_active_and_valid_plugins() to get the list of plugins for loading, but not for the subsequent get_option() calls.
            did_filter( 'option_active_plugins' ) < 2
        ) {
            return [
                $wc_plugin_file,
                $speedsearch_plugin_file,
            ];
        }

        return $active_plugins;
    }
);
