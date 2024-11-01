<?php
/**
 * Assets for plugins screen
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Assets\Screens;

use Exception;
use SpeedSearch\Assets\Assets;

/**
 * Assets class.
 */
final class Plugins {

    /**
     * Inits.
     */
    public static function init() {
        $class = __CLASS__;
        add_action(
            'admin_enqueue_scripts',
            function () use ( $class ) {
                new $class();
            }
        );
    }

    /**
     * Constructor.
     *
     * @throws Exception Exception.
     */
    public function __construct() {
        $this->styles();
        $this->scripts();
    }

    /**
     * Loads styles.
     */
    private function styles() {
        wp_enqueue_style(
            'speedsearch-admin-style',
            SPEEDSEARCH_URL . 'assets-build/admin/index.css',
            [],
            SPEEDSEARCH_VERSION
        );

        wp_enqueue_style(
            'speedsearch-admin-plugins-screen-style',
            SPEEDSEARCH_URL . 'assets-build/admin/screens/plugins.css',
            [ 'speedsearch-admin-style', 'tmm-wp-plugins-core-admin-style' ],
            SPEEDSEARCH_VERSION
        );
    }

    /**
     * Loads scripts.
     *
     * @throws Exception Exception.
     */
    private function scripts() {

        // Main script.

        Assets::enqueue_script(
            'speedsearch-admin-plugins-screen-script',
            SPEEDSEARCH_URL . 'assets-build/admin/screens/plugins.js',
            [ 'tmm-wp-plugins-core-admin-script' ],
            SPEEDSEARCH_VERSION
        );

        wp_localize_script(
            'speedsearch-admin-plugins-screen-script',
            'speedsearchPluginsScreen',
            [
                'txt' => [
                    'deleteTheDataFromTheServer'  => __( 'Delete the store data from the server?', 'speedsearch' ),
                    'yes'                         => __( 'Yes', 'speedsearch' ),
                    'no'                          => __( 'No', 'speedsearch' ),
                    'yes2'                        => __( 'Deactivate store and delete all data from the server', 'speedsearch' ),
                    'no2'                         => __( 'Deactivate store and keep the data', 'speedsearch' ),
                    'areYouSure'                  => __( 'Are you sure?', 'speedsearch' ),
                    'theDataWillBeDeletedForever' => __( 'The data will be deleted from the server forever.', 'speedsearch' ),
                ],
            ]
        );
    }
}
