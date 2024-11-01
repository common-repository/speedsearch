<?php
/**
 * Integrates the plugin filters into the WC shop page.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;
use SpeedSearch\Demo_Mode as Demo;
use SpeedSearch\Initial_Elements_Rendering\Parse_Url_For_Request_Params;

/**
 * WC Integrations.
 */
final class WC_Integration {

    /**
     * Ensure that the page contains SpeedSearch posts container, and if so, inserts the scripts the page header the script.
     *
     * @param string $content                   Page content, before out output.
     * @param bool   $assume_container_is_found Assume that the container is found.
     *
     * @return string Page content, but maybe already formatted (with header script injected).
     */
    public static function output_header_script_for_speedsearch_posts_block( $content, $assume_container_is_found = false ) {
        if ( $assume_container_is_found || str_contains( $content, 'class="speedsearch-posts-container' ) ) {
            $search  = "id='speedsearch-common-functions-js'></script>";
            $replace = $search .
            '<script' . " type='text/javascript'>
            /* <![CDATA[ */
            speedsearch.updatePosts( false, false, 'pageTop' );
			speedsearch.startRequestsEnqueue();			
            /* ]]> */
            </script>
";

            // Replace only the first occurrence. {@see https://stackoverflow.com/a/1252710}.
            $pos = strpos( $content, $search );
            if ( false !== $pos ) {
                $content = substr_replace( $content, ( new \MatthiasMullie\Minify\JS( $replace ) )->minify(), $pos, strlen( $search ) );
            }
        }

        return $content;
    }

    /**
     * Init.
     *
     * @throws Exception Exception.
     */
    public function __construct() {
        if (
            Demo::is_in_demo_mode() ||
            Initial_Setup::is_store_synced()
        ) {
            add_filter( 'template_include', [ $this, 'custom_wc_shop_page' ], 100 );

            add_filter(
                'template_redirect',
                function() {
                    if ( is_shop() || is_product_taxonomy() ) { // Output on shop page without checks.
                        // No need to close the buffers, WP will do that at the end.
                        ob_start(
                            function( $content ) {
                                return self::output_header_script_for_speedsearch_posts_block( $content, true );
                            }
                        );
                    } elseif ( ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() && ! defined( 'REST_REQUEST' ) ) {
                        ob_start( // Or output optionally if SpeedSearch posts container is present on the page.
                            function( $content ) {
                                return self::output_header_script_for_speedsearch_posts_block( $content );
                            }
                        );
                    }
                }
            );

            /**
             * Filters the array of parsed query variables.
             *
             * No 404 page when no posts found (e.g. applying a wrong filter: http://example.com/shop/?pa_brand=not_existing_brand).
             *
             * @param array $query_vars The array of requested query variables.
             */
            add_filter(
                'request',
                function( $query_vars ) {
                    if (
                        ! is_admin() &&
                        (
                            isset( $query_vars['post_type'] ) && 'product' === $query_vars['post_type'] ||
                            isset( $query_vars['page_id'] ) && wc_get_page_id( 'shop' ) === (int) $query_vars['page_id'] ||
                            // 404 when opening fancy URL. E.g. https://example.com/shop/pa_color/Red
                            (
                                isset( $query_vars['error'] ) && '404' === $query_vars['error'] &&
                                isset( $_SERVER['REQUEST_URI'] ) &&
                                str_starts_with( home_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ), get_permalink( wc_get_page_id( 'shop' ) ) )
                            )
                        ) &&
                        ! isset( $query_vars['product'] ) && // Not a single product view.
                        ( // Not a single product view (also).
                            ! isset( $query_vars['post_type'] ) ||
                            'product' !== $query_vars['post_type']
                        )
                    ) {
                        $query_vars = [
                            'post_type' => 'product',
                        ];
                    }

                    return $query_vars;
                },
                100
            );

            /**
             * Add 'speedsearch-filters-selected' body class when some of the filters are selected.
             *
             * - Check for product taxonomy (archive, tags).
             * - Check for applied URL params (HTML cache) on non taxonomy archive pages.
             */
            add_action(
                'wp',
                function() {
                    if (
                        is_product_taxonomy() ||
                        count(
                            array_filter(
                                array_keys( Parse_Url_For_Request_Params::get() ),
                                function( $key ) {
                                    return ! in_array( $key, [ 'page', 'sortBy' ], true );
                                }
                            )
                        )
                    ) {
                        add_filter(
                            'body_class',
                            function( $classes ) {
                                $classes[] = 'speedsearch-filters-selected';
                                return $classes;
                            }
                        );
                    }
                }
            );
        }

        add_filter(
            'woocommerce_api_permissions_in_scope',
            [
                $this,
                'modify_read_scope_for_speedsearch_plugin',
            ],
            10,
            2
        );
    }

    /**
     * Adds the plugin filters to WC shop page.
     *
     * @param string $template Template file path.
     *
     * @return string
     */
    public function custom_wc_shop_page( $template ) {
        if ( is_shop() || is_product_taxonomy() ) {
            return HTML::locate_template( 'woocommerce/archive-product.php' );
        }
        return $template;
    }

    /**
     * Modifies a read scope permissions for SpeedSearch plugin.
     *
     * @param array  $permissions Permissions.
     * @param string $scope       Name of the scope.
     *
     * @return array Scope permissions.
     */
    public function modify_read_scope_for_speedsearch_plugin( array $permissions, $scope ) {
        if ( 'read' === $scope && array_key_exists( 'app_name', $_GET ) && 'SpeedSearch' === $_GET['app_name'] ) { // @codingStandardsIgnoreLine
            $permissions = [
                __( 'View products', 'speedsearch' ),
            ];
        }
        return $permissions;
    }
}
