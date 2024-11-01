<?php
/**
 * Yoast SEO plugin integration.
 *
 * @package SpeedSearch
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

use SpeedSearch\Misc;

/**
 * Fix canonical (when shop page, then use the direct URL as a canonical), as pretty often URL params are filters.
 */
$speedsearch_canonical = function( $canonical ) {
    if (
        $canonical &&
        isset( $_SERVER['HTTP_HOST'] ) &&
        isset( $_SERVER['REQUEST_URI'] ) &&
        ( is_shop() || is_product_taxonomy() )
    ) {
        $canonical_parts = explode( '/page/', untrailingslashit( $canonical ) );

        $page_num = false;
        if ( count( $canonical_parts ) > 1 ) {
            $potential_page_num = $canonical_parts[ array_key_last( $canonical_parts ) ];
            if ( is_numeric( $potential_page_num ) ) {
                $page_num = $potential_page_num;
            }
        } else {
            $url_query = wp_parse_url( $canonical, PHP_URL_QUERY );
            if ( $url_query ) {
                parse_str( $url_query, $query_parts );
                if ( isset( $query_parts['page'] ) ) {
                    $page_num = $query_parts['page'];
                }
            }
        }

        $current_url = Misc::get_current_url();

        if ( $page_num ) {
            $current_url = add_query_arg( 'page', $page_num, $current_url );
        }

        if ( $current_url ) {
            return $current_url;
        }
    }

    return $canonical;
};

add_filter( 'wpseo_canonical', $speedsearch_canonical, 11 );
add_filter( 'wpseo_adjacent_rel_url', $speedsearch_canonical, 11 );
add_filter( 'wpseo_adjacent_rel_url', $speedsearch_canonical, 11 );
