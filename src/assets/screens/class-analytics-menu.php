<?php
/**
 * Analytics plugin menu assets.
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Assets\Screens;

use SpeedSearch\Assets\Assets;

/**
 * Analytics menu assets.
 */
final class Analytics_Menu {

    /**
     * Inits.
     */
    public static function init() {
        $class = __CLASS__;
        add_action(
            'admin_enqueue_scripts',
            function() use ( $class ) {
                new $class();
            }
        );
    }


    /**
     * Constructor.
     */
    public function __construct() {
        $this->styles();
        $this->scripts();
    }


    /**
     * Loads styles.
     */
    private function styles() {

        // Main styles.

        wp_enqueue_style(
            'speedsearch-admin-style',
            SPEEDSEARCH_URL . 'assets-build/admin/index.css',
            [
                'speedsearch-lib-datatables',
                'tmm-wp-plugins-core-admin-style',
            ],
            SPEEDSEARCH_VERSION
        );
    }


    /**
     * Loads scripts.
     */
    private function scripts() {

        // Top-prio scripts (functions.js, url.js, posts.js).

        add_action(
            'wp_print_scripts',
            function() {
                Assets::print_top_prio_scripts();
            },
            0
        );

        // Main script.

        Assets::enqueue_script(
            'speedsearch-admin-analytics-menu-script',
            SPEEDSEARCH_URL . 'assets-build/admin/screens/analytics-menu.js',
            [
                'speedsearch-lib-datatables',
                'speedsearch-admin-script',
                'tmm-wp-plugins-core-admin-script',
            ],
            SPEEDSEARCH_VERSION
        );
    }
}
