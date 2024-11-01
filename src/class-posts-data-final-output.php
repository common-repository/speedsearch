<?php
/**
 * A class for posts data final output.
 *
 * 1. A try_to_inject_html, which is a wrapper for "speedsearch_opportunity_to_insert_custom_html_for_ids" filter.
 * 2. A get_posts_data,     which is a wrapper for @see Posts::get_post_data.
 *
 * A good place to use plugins' filters to fix incompatibility bugs the speedsearch products html generation.
 *
 * Should be extended as rare as possible, only when there is no other way to fix the bug.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;

/**
 * Class Posts_Data_Final_Output.
 */
final class Posts_Data_Final_Output {

    /**
     * The list of filters to use, with their handlers.
     *
     * 0 - filter
     * 1 - callback
     * 3 - (optional) priority
     * 3 - (optional) accepted arguments
     */
    const FILTERS_TO_USE = [
        [
            'jetpack_lazy_images_new_attributes',
            [ __CLASS__, 'jetpack_disable_products_images_lazyloading' ],
        ],
        [
            'wp_get_attachment_image_attributes',
            [ __CLASS__, 'when_no_image_alt_use_product_title' ],
        ],
        [
            'wp_get_attachment_image_attributes',
            [ __CLASS__, 'add_onerror_to_image' ],
        ],
        [
            'woocommerce_post_class',
            [ __CLASS__, 'product_classes' ],
        ],
    ];

    /**
     * Adds 'speedsearch-single-post' class to each product.
     *
     * @param array $classes Classes list.
     *
     * @return array
     */
    public static function product_classes( $classes ) {
        $classes[] = 'speedsearch-single-post';

        // TODO: Add 'first' and 'last' classes to products in the columns dynamically, the same way as wc_get_loop_class does it, to have more classes parity.
        // TODO: But without saving it to HTML cache for that. Ideally, during the very late-stage render time.

        return array_filter(
            $classes,
            function( $class ) {
                /**
                 * Delete "last" class because it adds too much randomness to the products HTML cache,
                 * And makes cache validation to stop working (because some products have 'last' class, some do not).
                 */
                return ! in_array( $class, [ 'first', 'last' ], true );
            }
        );
    }

    /**
     * Disables Jetpack products lazy-loading due to the bug.
     *
     * When it keeps this srcset (srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7")
     * even when the image finished lazy-loading.
     *
     * I'm not sure about the deep reasons of this bug, maybe there are much elegant ways to solve it, but I decided to simply
     * disable srcset for products, as for now.
     *
     * @param array $attributes Attributes.
     *
     * @return array Attributes.
     */
    public static function jetpack_disable_products_images_lazyloading( $attributes ) {
        $attributes['srcset'] = '';
        return $attributes;
    }

    /**
     * When the product image has no alt, use product title as the alt.
     *
     * @param array $image_attributes Image HTML.
     *
     * @global $product
     *
     * @return array Image attributes.
     *
     * @throws Exception Exception.
     */
    public static function when_no_image_alt_use_product_title( array $image_attributes ) {
        if ( '1' === SpeedSearch::$options->get( 'setting-when-no-image-alt-use-product-title' ) ) {
            global $product;

            if ( ! $product ) {
                return $image_attributes;
            }

            if ( ! property_exists( $product, 'speedsearch_image_count' ) ) {
                $product->speedsearch_image_count = 0;
            }

            $product->speedsearch_image_count ++;

            if ( '' === $image_attributes['alt'] ) {
                $image_attributes['alt'] = $product->get_title() .
                    ( $product->speedsearch_image_count > 1 ? ' ' . $product->speedsearch_image_count : '' );
            }
        }

        return $image_attributes;
    }

    /**
     * Add onerror attribute to image to reset image thumbnail to null when no available.
     *
     * @param array $image_attributes Image HTML.
     *
     * @global $product
     *
     * @return array Image attributes.
     *
     * @throws Exception Exception.
     */
    public static function add_onerror_to_image( array $image_attributes ) {
        if ( ! isset( $image_attributes['onerror'] ) ) {
            $image_attributes['onerror'] = "this.onerror=null; this.src=speedsearch.imageUrls.placeholderImage; this.removeAttribute('srcset');";
        }

        return $image_attributes;
    }

    /**
     * Adds posts HTML to data object.
     *
     * @param array  $data      Data object, to "html" field of which products' HTML potentially can be added.
     * @param int[]  $posts_ids Posts IDs.
     * @param string $endpoint  Endpoint name.
     *
     * @throws Exception Exception.
     */
    public static function try_to_inject_html( array $data, array $posts_ids, $endpoint = 'search' ) {
        self::before();

        $data = apply_filters( 'speedsearch_opportunity_to_insert_custom_html_for_ids', $data, $posts_ids, $endpoint );

        self::after();

        return $data;
    }

    /**
     * Returns posts data.
     *
     * @param int[] $posts_ids IDs of the posts.
     *
     * @return array Posts data.
     *
     * @throws Exception Exception.
     */
    public static function get_posts_data( $posts_ids ) {
        self::before();

        $data = [];
        foreach ( $posts_ids as $post_id ) {
            $post_data = Posts::get_post_data( $post_id );
            if ( $post_data ) {
                $data[] = $post_data;
            }
        }

        self::after();

        return $data;
    }

    /**
     * Before the call.
     */
    public static function before() {
        foreach ( self::FILTERS_TO_USE as $filter_data ) {
            add_filter(
                $filter_data[0],
                $filter_data[1],
                isset( $filter_data[2] ) ? $filter_data[2] : 10,
                isset( $filter_data[3] ) ? $filter_data[3] : 1
            );
        }
    }

    /**
     * After the call.
     */
    public static function after() {
        foreach ( self::FILTERS_TO_USE as $filter_data ) {
            remove_filter(
                $filter_data[0],
                $filter_data[1],
                isset( $filter_data[2] ) ? $filter_data[2] : 10
            );
        }
    }
}
