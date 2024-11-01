<?php
/**
 * Public admin assets (for logged-in administrator user).
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Assets;

use Exception;

/**
 * Public_Admin class.
 */
final class Public_Admin {

    /**
     * Constructor.
     *
     * @throws Exception Exception.
     */
    public function __construct() {
        add_action(
            'wp_enqueue_scripts',
            function() {
                $this->styles();
                $this->scripts();
            }
        );
    }


    /**
     * Loads styles.
     *
     * @throws Exception Exception.
     */
    private function styles() {

        // jsPanel4 (for floating blocks).

        wp_register_style(
            'speedsearch-lib-jspanel',
            SPEEDSEARCH_URL . 'libs/jsPanel4/dist/jspanel.min.css',
            [],
            '4.16.0'
        );

        wp_enqueue_style(
            'speedsearch-public-admin-style',
            SPEEDSEARCH_URL . 'assets-build/public/admin.css',
            [ 'speedsearch-public-style', 'tmm-wp-plugins-core-lib-sweetalert2', 'speedsearch-lib-jspanel' ],
            SPEEDSEARCH_VERSION
        );
    }


    /**
     * Loads scripts.
     *
     * @throws Exception Exception.
     */
    private function scripts() {

        // jsPanel4 (for floating blocks).

        Assets::register_script(
            'speedsearch-lib-jspanel',
            SPEEDSEARCH_URL . 'libs/jsPanel4/dist/jspanel.min.js',
            [],
            '4.16.0'
        );

        Assets::enqueue_script(
            'speedsearch-public-admin-script',
            SPEEDSEARCH_URL . 'assets-build/public/admin.js',
            [ 'speedsearch-public-script', 'tmm-wp-plugins-core-lib-sweetalert2', 'speedsearch-lib-jspanel' ],
            SPEEDSEARCH_VERSION
        );
    }
}
