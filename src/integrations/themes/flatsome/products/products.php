<?php
/**
 * Products.
 *
 * @package SpeedSearch
 */

/**
 * Removes ordering for main shop page (because it's added by SpeedSearch plugin so the default ones are redundant).
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

use SpeedSearch\SpeedSearch;

add_action(
    'after_setup_theme',
    function () {
        remove_action( 'flatsome_category_title_alt', 'woocommerce_catalog_ordering', 30 );
    }
);

add_action(
    'wp_enqueue_scripts',
    function() {
        wp_enqueue_script( 'wc-add-to-cart-variation' );

        if ( function_exists( 'YITH_WCWL_Frontend' ) ) { // Loads YITH Wishlist scripts.
            YITH_WCWL_Frontend()->enqueue_scripts();
        }
    }
);

add_filter(
    'speedsearch_before_public_settings_ajax_output',
    function( $data ) {
        $breadcrumb_args = apply_filters(
            'woocommerce_breadcrumb_defaults',
            [
                'delimiter'   => '&nbsp;&#47;&nbsp;',
                'wrap_before' => '<nav class="woocommerce-breadcrumb">',
                'wrap_after'  => '</nav>',
                'before'      => '',
                'after'       => '',
                'home'        => _x( 'Home', 'breadcrumb', 'speedsearch' ),
            ]
        );

        $data['delimiter'] = isset( $breadcrumb_args['delimiter'] ) ? $breadcrumb_args['delimiter'] : '&nbsp;&#47;&nbsp;';

        $data['breadcrumbsSeparator'] = '<span class="divider">' . $breadcrumb_args['delimiter'] . '</span> ';
        return $data;
    }
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
        if ( 'recently_viewed_products' === $endpoint && function_exists( 'ux_products' ) ) { // For recently viewed products a special view is used.
            $args = [
                'ids'                 => implode( ',', $posts_ids ),
                'col_spacing'         => 'large',
                'slider_nav_style'    => 'circle',
                'slider_nav_position' => 'outside',
            ];

            $current_theme_slug = SpeedSearch::$options->get( 'setting-current-theme-data' )['name'];
            if ( 'picfee' === $current_theme_slug ) { // A separate "recently_viewed_products" layout for Picfee.
                $args['columns']     = '3';
                $args['columns__sm'] = '1';
                $args['columns__md'] = '2';
            }

            $data['html'] = ux_products( $args );
        } elseif ( 'search' === $endpoint ) {
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

                /**
                 * Hook: woocommerce_shop_loop.
                 *
                 * @hooked WC_Structured_Data::generate_product_data() - 10
                 */
                do_action( 'woocommerce_shop_loop' );

                wc_get_template_part( 'content', 'product' );
            }

            woocommerce_product_loop_end();

            do_action( 'woocommerce_after_shop_loop' );

            wp_reset_postdata();

            $data['html'] = ob_get_clean();
        }

        return $data;
    },
    10,
    3
);
