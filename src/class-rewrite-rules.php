<?php
/**
 * Rewrite rules.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;

/**
 * Class Rewrite Rules.
 */
final class Rewrite_Rules {

    /**
     * Init.
     */
    public function __construct() {
        // Adds rewrite rules (if necessary).
        add_action( 'woocommerce_after_register_post_type', [ $this, 'add_rewrite_rules' ] );

        // Flushes rewrite rules on plugin deactivation.
        register_deactivation_hook( SPEEDSEARCH_FILE, [ $this, 'deactivation_handler' ] );
    }

    /**
     * Rewrites URLs: redirect from category page to main page url.
     *
     * Otherwise, it will be considered by WP as another page and return 404 error.
     *
     * @throws Exception Exception.
     *
     * @global $wp_query
     */
    public function add_rewrite_rules() {
        // If permalink structure is not plain (?page_id=3465).
        // In this case categories are encoded as url params.
        $permalink_structure = get_option( 'permalink_structure' );
        if ( $permalink_structure ) {
            $categories_structure = SpeedSearch::$options->get( 'setting-categories-structure' );

            $shop_page_id  = wc_get_page_id( 'shop' );
            $front_page_id = (int) get_option( 'page_on_front' );

            if (
                $shop_page_id !== $front_page_id &&
                -1 !== $shop_page_id
            ) {
                $main_page_relative_url = str_replace( home_url(), '', get_permalink( $shop_page_id ) );
                $categories_type        = $categories_structure['type'];

                if ( strpos( $categories_type, 'with-shop-page' ) ) { // Categories url structure with SpeedSearch page.
                    $main_page_relative_url_with_no_left_slash = ltrim( $main_page_relative_url, '/' );
                    add_rewrite_rule( "$main_page_relative_url_with_no_left_slash(([^/]+)[/])*([^/]+)[/]?$", "index.php?page_id=$shop_page_id", 'top' );

                    if (
                        SpeedSearch::$options->get( 'post-id-last-rewrite-rules-save' ) !== $shop_page_id ||
                        SpeedSearch::$options->get( 'categories-type-last-rewrite-rules-save' ) !== $categories_type
                    ) {
                        SpeedSearch::$options->set( 'post-id-last-rewrite-rules-save', $shop_page_id );
                        SpeedSearch::$options->set( 'categories-type-last-rewrite-rules-save', $categories_type );
                        flush_rewrite_rules();
                    }

                    // Removes trailing slash for categories for main page url.

                    if (
                        isset( $_SERVER['REQUEST_URI'] ) &&
                        preg_match( '@^' . $main_page_relative_url . '[^?]+@', sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) )
                    ) {
                        global $wp_rewrite;
                        $wp_rewrite->use_trailing_slashes = false;
                    }
                } else { // Categories url structure without SpeedSearch page.
                    $categories_prefix = $categories_structure['categories-prefix'];
                    if ( $categories_prefix ) {
                        add_rewrite_rule( "^$categories_prefix/(([^/]+)[/])*([^/]+)[/]?$", 'index.php?product_cat=$matches[3]', 'top' );

                        if (
                            SpeedSearch::$options->get( 'categories-prefix-last-rewrite-rules-save' ) !== $categories_prefix ||
                            SpeedSearch::$options->get( 'categories-type-last-rewrite-rules-save' ) !== $categories_type
                        ) {
                            SpeedSearch::$options->set( 'categories-prefix-last-rewrite-rules-save', $categories_prefix );
                            SpeedSearch::$options->set( 'categories-type-last-rewrite-rules-save', $categories_type );
                            flush_rewrite_rules();
                        }

                        // Removes trailing slash for categories for URL that start from categories-prefix.

                        if (
                            isset( $_SERVER['REQUEST_URI'] ) &&
                            preg_match( '@^/' . $categories_prefix . '[^?]+@', sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) )
                        ) {
                            global $wp_rewrite;
                            $wp_rewrite->use_trailing_slashes = false;
                        }
                    }
                }
            }
        }
    }

    /**
     * Plugin deactivation handler.
     *
     * Flush rewrite rules on plugin deactivation.
     *
     * @throws Exception Exception.
     */
    public function deactivation_handler() {
        /**
         * Rewrite Rules flush.
         */
        SpeedSearch::$options->delete( 'post-id-last-rewrite-rules-save' );
        SpeedSearch::$options->delete( 'categories-prefix-last-rewrite-rules-save' );
        SpeedSearch::$options->delete( 'categories-type-last-rewrite-rules-save' );
        flush_rewrite_rules();
    }
}
