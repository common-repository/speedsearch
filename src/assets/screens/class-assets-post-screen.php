<?php
/**
 * Assets for post screen
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Assets\Screens;

use SpeedSearch\Assets\Assets;

/**
 * Assets class.
 */
final class Post {

    /**
     * Constructor.
     */
    public function __construct() {
        if (
            isset( $_SERVER['REQUEST_URI'] ) &&
            ! str_starts_with(
                sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
                wp_parse_url( admin_url( 'post-new.php' ), PHP_URL_PATH )
            ) &&
            isset( $_GET['post'] ) && // @codingStandardsIgnoreLine
            'product' === get_post_type( (int) $_GET['post'] ) // @codingStandardsIgnoreLine
        ) {
            $this->styles();
            $this->scripts();
        }
    }

    /**
     * Loads styles.
     */
    private function styles() {
        wp_enqueue_style(
            'speedsearch-admin-post-screen-style',
            SPEEDSEARCH_URL . 'assets-build/admin/screens/post.css',
            [
                'tmm-wp-plugins-core-admin-style',
                'speedsearch-lib-datatables',
            ],
            SPEEDSEARCH_VERSION
        );
    }

    /**
     * Loads scripts.
     */
    private function scripts() {

        // Main script.

        Assets::enqueue_script(
            'speedsearch-admin-post-screen-script',
            SPEEDSEARCH_URL . 'assets-build/admin/screens/post.js',
            [
                'tmm-wp-plugins-core-admin-script',
                'speedsearch-lib-datatables',
            ],
            SPEEDSEARCH_VERSION
        );
    }
}
