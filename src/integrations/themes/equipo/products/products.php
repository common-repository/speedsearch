<?php
/**
 * Products.
 *
 * @package SpeedSearch
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

use SpeedSearch\Dynamic_Scripts_Patching;

/**
 * Adds the list of script patches.
 */
Dynamic_Scripts_Patching::$patches = array_merge(
    Dynamic_Scripts_Patching::$patches,
    [
        'controller' => [
            'function afterProductsFetch' => 'let afterProductsFetch = window.speedsearch.themeIntegration_afterProductsFetch = function',
        ],
    ]
);

/**
 * Posts HTML integration.
 */
add_filter(
    'speedsearch_opportunity_to_insert_custom_html_for_ids',
    /**
     * Adds posts HTML to data object.
     *
     * @param array  $data      Data object, to "html" field of which products' HTML will be added.
     * @param int[]  $posts_ids Posts IDs.
     * @param string $endpoint  Endpoint name.
     *
     * @return array Data.
     *
     * @throws Exception Exception.
     */
    function ( $data, $posts_ids, $endpoint ) {
        if ( ! defined( 'ENOVATHEMES_ADDONS' ) ) {
            return $data;
        }

        $product_gap       = ( isset( $GLOBALS['equipo_enovathemes']['product-gap'] ) && 1 == $GLOBALS['equipo_enovathemes']['product-gap'] ) ? 'true' : 'false';
        $product_post_size = ( isset( $GLOBALS['equipo_enovathemes']['product-post-size'] ) && $GLOBALS['equipo_enovathemes']['product-post-size'] ) ? $GLOBALS['equipo_enovathemes']['product-post-size'] : 'medium';

        ob_start();

        do_action( 'woocommerce_before_shop_loop' );

        woocommerce_product_loop_start();

        $args     = [
            'post__in'            => $posts_ids,
            'post_type'           => 'product',
            'numberposts'         => - 1,
            'posts_per_page'      => - 1,
            'orderby'             => 'post__in',
            'ignore_sticky_posts' => true,
        ];
        $products = new WP_Query( $args );

        while ( $products->have_posts() ) {
            $products->the_post();

            include ENOVATHEMES_ADDONS . 'woocommerce/content-product.php';
        }

        woocommerce_product_loop_end();

        do_action( 'woocommerce_after_shop_loop' );

        wp_reset_postdata();

        $data['html'] = ob_get_clean();

        return $data;
    },
    10,
    3
);
