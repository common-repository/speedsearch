<?php
/**
 * Main plugin menu assets.
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Assets\Screens;

use SpeedSearch\Assets\Assets;
use SpeedSearch\SpeedSearch;

/**
 * Assets class.
 */
final class Menu {

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

        // Intro.js.

        wp_register_style(
            'speedsearch-lib-intro.js',
            SPEEDSEARCH_URL . 'libs/intro.js/introjs.min.css',
            [],
            '6.0.0'
        );

        // Main styles.

        wp_register_style(
            'speedsearch-admin-style',
            SPEEDSEARCH_URL . 'assets-build/admin/index.css',
            [
                'speedsearch-common-filters',
                'speedsearch-lib-intro.js',
                'tmm-wp-plugins-core-admin-style',
            ],
            SPEEDSEARCH_VERSION
        );

        // Submenu style.

        wp_enqueue_style(
            'speedsearch-admin-settings-screen-style',
            SPEEDSEARCH_URL . 'assets-build/admin/screens/menu.css',
            [
                'speedsearch-admin-style',
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

        // Intro.js.

        Assets::register_script(
            'speedsearch-lib-intro.js',
            SPEEDSEARCH_URL . 'libs/intro.js/intro.min.js',
            [],
            '6.0.0'
        );

        // Main script.

        Assets::enqueue_script(
            'speedsearch-admin-analytics-script',
            SPEEDSEARCH_URL . 'assets-build/admin/screens/menu.js',
            [
                'speedsearch-lib-intro.js',
                'speedsearch-lib-sortablejs',
                'speedsearch-admin-script',
                'tmm-wp-plugins-core-admin-script',
            ],
            SPEEDSEARCH_VERSION
        );

        wp_localize_script(
            'speedsearch-admin-analytics-script',
            'speedsearch_adminData',
            [
                'introductionCompleted' => SpeedSearch::$options->get( 'introduction-completed' ),
                'shopPage'              => get_permalink( wc_get_page_id( 'shop' ) ),
            ]
        );
    }
}
