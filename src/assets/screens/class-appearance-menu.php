<?php
/**
 * Appearance plugin menu assets.
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Assets\Screens;

use SpeedSearch\Assets\Assets;

/**
 * Appearance menu assets.
 */
final class Appearance_Menu {

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

        // Pickr.

        wp_register_style(
            'speedsearch-lib-pickr',
            SPEEDSEARCH_URL . 'libs/pickr/themes/classic.min.css',
            [],
            '1.8.2'
        );

        // Main styles.

        wp_enqueue_style(
            'speedsearch-admin-style',
            SPEEDSEARCH_URL . 'assets-build/admin/index.css',
            [
                'speedsearch-common-filters',
                'speedsearch-lib-pickr',
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

        // Core media assets (for image upload).

        wp_enqueue_media();

        // Pickr.

        Assets::register_script(
            'speedsearch-lib-pickr',
            SPEEDSEARCH_URL . 'libs/pickr/pickr.min.js',
            [],
            '1.8.2'
        );

        // Main script.

        Assets::enqueue_script(
            'speedsearch-admin-appearance-menu-script',
            SPEEDSEARCH_URL . 'assets-build/admin/screens/appearance-menu.js',
            [
                'speedsearch-common-filters',
                'speedsearch-lib-sortablejs',
                'speedsearch-lib-pickr',
                'speedsearch-admin-script',
                'tmm-wp-plugins-core-admin-script',
            ],
            SPEEDSEARCH_VERSION
        );
    }
}
