<?php
/**
 * Products Hash generation.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Products_Hash;

use Exception;
use SpeedSearch\SpeedSearch;
use SpeedSearch\Sync_Data_Feed\Sync_Data_Feed;
use WC_REST_Products_Controller;

/**
 * Generates Products hash.
 */
final class Init_Generation extends WC_REST_Products_Controller {

    /**
     * Properties white-list.
     *
     * @var string[] Property.
     */
    private static $white_list = [
        'short_description',
        'name',
        'slug',
        'description',
        'price',
        'average_rating',
        'rating_count',
        'total_sales',
        'menu_order',
        'stock_status',
        'variations',
        'images',
        'catalog_visibility',
        'date_created',
        'date_modified',
        'meta_data',
        'speedsearch_variable_product_prices',
        'parent_id',
        'type',
        'featured',
        'shipping_required',
        'shipping_taxable',
        'reviews_allowed',
        'upsell_ids',
        'cross_sell_ids',
        'purchase_note',
        'default_attributes',
        'is_variation',
        'is_visible',
        'has_variations',
        'permalink',
        'date_created_gmt',
        'date_modified_gmt',
        'sku',
        'regular_price',
        'sale_price',
        'date_on_sale_from',
        'date_on_sale_from_gmt',
        'date_on_sale_to',
        'date_on_sale_to_gmt',
        'on_sale',
        'status',
        'purchasable',
        'virtual',
        'downloadable',
        'downloads',
        'download_limit',
        'download_expiry',
        'tax_status',
        'manage_stock',
        'stock_quantity',
        'backorders',
        'backorders_allowed',
        'backordered',
        'weight',
        'width',
        'height',
        'length',
        'shipping_class',
        'shipping_class_id',
        'categories',
        'tags',
        'attributes',
        'grouped_products',
    ];

    /**
     * Batch size for hash generation. Number of posts to generate the hash for.
     *
     * If your server is fast, can be increased.
     */
    const BATCH_SIZE = 25;

    /**
     * Hook name of the interval.
     */
    const INTERVAL_HOOK_NAME = 'speedsearch_generate_all_products_hash';

    /**
     * Constructor.
     *
     * @throws Exception Exception.
     */
    public function __construct() {
        parent::__construct();

        // Adds a generation action for Action Scheduler.

        add_action( self::INTERVAL_HOOK_NAME, [ $this, 'generate_hash_for_all_products' ] );

        // Generates hash for all products if it's not generated yet.

        if ( 0 === (int) SpeedSearch::$options->get( 'product-hashes-generation-status' ) ) {
            SpeedSearch::$options->set( 'product-hashes-generation-status', 1 ); // "1" for generation action is planned but not started yet.
            if ( false === as_next_scheduled_action( self::INTERVAL_HOOK_NAME, [], 'speedsearch' ) ) {
                $action_id = as_enqueue_async_action( self::INTERVAL_HOOK_NAME, [], 'speedsearch' );
                SpeedSearch::$options->set( 'product-hashes-generation-last-as-id', $action_id );
            }
        }
    }

    /**
     * Sorting (so the hash will have the same format).
     *
     * @param array $array Array to sort.
     * @param int   $flags Flags.
     */
    private static function aksort_recursive( &$array, $flags = SORT_REGULAR ) {
        foreach ( $array as &$value ) {
            if ( is_array( $value ) ) {
                self::aksort_recursive( $value, $flags );
            }
        }
        array_multisort( $array, SORT_ASC, $flags ); // We need an array sort function that retains key assoc for string keys, but not for numeric.
        ksort( $array, $flags );
    }

    /**
     * Updates the product cache prefix.
     *
     * Used to flush the cache due to WC bug (when attr term is renamed, the wc_get_product_terms() still returns the old data from the cache).
     *
     * @see _wc_get_cached_product_terms()
     * @see \WC_Cache_Helper::get_cache_prefix()
     *
     * @param int    $product_id Product ID.
     * @param string $new_prefix New prefix to set.
     */
    private function update_the_product_cache_prefix( $product_id, $new_prefix ) {
        $group = "product_$product_id";

        wp_cache_set( 'wc_' . $group . '_cache_prefix', $new_prefix, $group );
    }

    /**
     * Returns hash for the product by id.
     *
     * @param int|\WC_Product $product  Product object or ID.
     * @param bool            $get_debugging_array Returns debugging array.
     *
     * @return string|array
     */
    public function get_hash_for_product( $product, $get_debugging_array = false ) {
        if ( is_numeric( $product ) ) {
            $product = wc_get_product( $product );
        }

        if ( $product ) {
            // Cleans the product from the cache due to WC bug (when attr term is renamed, the wc_get_product_terms() still returns the old data from the cache).
            $this->update_the_product_cache_prefix( $product->get_id(), microtime() );

            $product_data = SpeedSearch::$sync_data_feed->get_the_product_data( $product );
            if ( $product_data ) {
                foreach ( $product_data['meta_data'] as $i => $meta ) { // We don't need the hash meta for the hash.
                    if ( in_array( $meta['key'], [ Base::HASH_META_KEY, Sync_Data_Feed::FEED_HANDLED_META_NAME ], true ) ) {
                        unset( $product_data['meta_data'][ $i ] );
                        break;
                    }
                }

                $data = [];
                foreach ( self::$white_list as $key ) {
                    $data[ $key ] = isset( $product_data[ $key ] ) ? $product_data[ $key ] : null;
                }
                self::aksort_recursive( $data ); // Sorting.

                if ( $get_debugging_array ) {
                    return $data;
                } else {
                    return sha1( json_encode( $data ) ); // @codingStandardsIgnoreLine
                }
            }
        }

        return 'fail';
    }

    /**
     * Generates hash for the product object.
     *
     * @param \WC_Product $product Product object.
     *
     * @throws Exception Exception.
     */
    public function update_hash_for_product( $product ) {
        $product_hash = $this->get_hash_for_product( $product );

        if ( $product_hash ) {
            SpeedSearch::$options->set( 'product-hashes-generation-last-post-id', $product->get_id() );

            update_post_meta( $product->get_id(), Base::HASH_META_KEY, $product_hash );

            $counter = (int) SpeedSearch::$options->get( 'product-hashes-one-generation-counter' );
            SpeedSearch::$options->set( 'product-hashes-one-generation-counter', ++ $counter );
        }
    }

    /**
     * Generates hash for all products on the websites.
     *
     * Adds hash to their meta-fields.
     *
     * @throws Exception Exception.
     */
    public function generate_hash_for_all_products() {
        SpeedSearch::$options->set( 'product-hashes-generation-status', 2 ); // "2" for generation is in progress.

        // Hash generation.

        add_filter(
            'woocommerce_product_data_store_cpt_get_products_query',
            function( $query, $query_vars ) {
                if ( isset( $query_vars['speedsearch_hash'] ) && 'not_exists' === $query_vars['speedsearch_hash'] ) {
                    if ( in_array( Base::HASH_META_KEY, array_column( $query['meta_query'], 'key' ), true ) ) { // Don't do duplicate.
                        return $query;
                    }

                    $query['meta_query'][] = [
                        'key'     => Base::HASH_META_KEY,
                        'compare' => 'NOT EXISTS',
                    ];
                }

                return $query;
            },
            10,
            2
        );

        $products = wc_get_products(
            [
                'limit'            => self::BATCH_SIZE,
                'status'           => 'publish',
                'speedsearch_hash' => 'not_exists',
                'orderby'          => 'none',
            ]
        );

        if ( $products ) {
            SpeedSearch::$options->set( 'product-hashes-last-batch-post-ids', implode( ', ', wc_list_pluck( $products, 'get_id' ) ) );

            foreach ( $products as $product ) {
                SpeedSearch::$options->set( 'product-hashes-last-batch-post-id', $product->get_id() );
                $this->update_hash_for_product( $product );
            }
            SpeedSearch::$options->set( 'product-hashes-generation-status', 0 );

            $counter = (int) SpeedSearch::$options->get( 'product-hashes-batches-counter' );
            SpeedSearch::$options->set( 'product-hashes-batches-counter', ++ $counter );
        } else { // When no more products, stop hash generation.
            SpeedSearch::$options->set( 'product-hashes-generation-status', 3 ); // "3" for generation is finished.
        }
    }
}
