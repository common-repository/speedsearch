<?php
/**
 * Assets
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Assets;

use Exception;
use SpeedSearch\SpeedSearch;
use SpeedSearch\Themes;

/**
 * Assets class.
 */
final class Publ {

    /**
     * Constructor.
     *
     * @throws Exception Exception.
     */
    public function __construct() {

        // Top-prio scripts (functions.js, url.js, posts.js).

        add_action(
            'wp_head',
            function() {
                Assets::print_top_prio_scripts();
            },
            0
        );

        // Print other assets.

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
        wp_enqueue_style(
            'speedsearch-public-style',
            SPEEDSEARCH_URL . 'assets-build/public/index.css',
            [ 'speedsearch-common-filters' ],
            SPEEDSEARCH_VERSION
        );

        if (
            function_exists( 'use_block_editor_for_post_type' ) &&
            use_block_editor_for_post_type( 'page' ) &&
            ! (
                SpeedSearch::$options->get( 'setting-posts-enable-theme-integration' ) &&
                SpeedSearch::$integrations->is_current_theme_products_integration_present
            )
        ) {
            // For Gutenberg posts styling.
            wp_enqueue_style( 'wp-block-post-template' );
        }

        // Loads theme-specific public/style.css.

        $current_theme_slug = SpeedSearch::$options->get( 'setting-current-theme-data' )['name'];
        if ( 'default' !== $current_theme_slug ) {
            $theme_path      = Themes::$theme_paths[ $current_theme_slug ];
            $asset_file_path = $theme_path . 'assets-build/public/index.css';
            if ( SpeedSearch::$fs->is_file( $asset_file_path ) ) {
                wp_enqueue_style(
                    "speedsearch-public-style-$current_theme_slug",
                    SpeedSearch::convert_path_to_url( $asset_file_path ),
                    [ 'speedsearch-public-style' ],
                    SPEEDSEARCH_VERSION
                );
            }
        }
    }


    /**
     * Loads scripts.
     *
     * @throws Exception Exception.
     */
    private function scripts() {

        // Public script.

        Assets::enqueue_script(
            'speedsearch-public-script',
            SPEEDSEARCH_URL . 'assets-build/public/index.js',
            [ 'speedsearch-common-filters' ],
            SPEEDSEARCH_VERSION
        );

        // Loads theme-specific public/script.js.

        $current_theme_slug = SpeedSearch::$options->get( 'setting-current-theme-data' )['name'];
        if ( 'default' !== $current_theme_slug ) {
            $theme_path      = Themes::$theme_paths[ $current_theme_slug ];
            $asset_file_path = $theme_path . 'assets-build/public/index.js';
            if ( SpeedSearch::$fs->is_file( $asset_file_path ) ) {
                Assets::enqueue_script(
                    "speedsearch-public-script-$current_theme_slug",
                    SpeedSearch::convert_path_to_url( $asset_file_path ),
                    [ 'speedsearch-public-script' ],
                    SPEEDSEARCH_VERSION
                );
            }
        }
    }
}
