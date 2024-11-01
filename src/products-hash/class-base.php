<?php
/**
 * Products Hash generation entry point.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Products_Hash;

use Exception;
use SpeedSearch\SpeedSearch;

/**
 * Base class for Products Hash.
 */
final class Base {

    /**
     * Product meta key for the hash.
     */
    const HASH_META_KEY = 'speedsearch-product-hash';

    /**
     * Constructor.
     *
     * @throws Exception Exception.
     */
    public function __construct() {
        if ( (int) SpeedSearch::$options->get( 'product-hashes-generation-status' ) < 3 ) { // Increases action scheduler timeout while the hash generation is not finished yet.
            add_filter( 'action_scheduler_failure_period', [ $this, 'get_new_action_scheduler_timeout' ] );
        }

        add_action(
            'woocommerce_after_register_post_type',
            function() {
                $this->import_wc_dependent_files();
                SpeedSearch::$hash_generation = new Init_Generation();
            }
        );

        // Init regeneration (do the early init to avoid the flush).
        new Init_Regeneration();

        // Resets hash on plugin deactivation.
        register_deactivation_hook( SPEEDSEARCH_FILE, [ $this, 'remove_all_products_hash' ] );
    }

    /**
     * Imports WC-Dependent files.
     */
    private function import_wc_dependent_files() {
        $files = [
            'products-hash/class-init-generation',
        ];
        foreach ( $files as $file ) {
            require_once SPEEDSEARCH_DIR . 'src/' . $file . '.php';
        }
    }

    /**
     * Removes all products hash.
     */
    public static function remove_all_products_hash() {
        /**
         * Removes hash generation status.
         */
        SpeedSearch::$options->delete( 'product-hashes-generation-status' );

        // And the counters - at the moment, used exclusively for debugging.

        SpeedSearch::$options->delete( 'product-hashes-one-generation-counter' );
        SpeedSearch::$options->delete( 'product-hashes-batches-counter' );
        SpeedSearch::$options->delete( 'product-hashes-generation-last-as-id' );
        SpeedSearch::$options->delete( 'product-hashes-generation-last-post-id' );
        SpeedSearch::$options->delete( 'product-hashes-last-batch-post-ids' );
        SpeedSearch::$options->delete( 'product-hashes-last-batch-post-id' );

        // Deletes hash for products on plugin deactivation.

        $all_products_ids = get_posts(
            [
                'post_type'   => 'product',
                'numberposts' => -1,
                'post_status' => 'publish',
                'fields'      => 'ids',
            ]
        );
        foreach ( $all_products_ids as $product_id ) {
            delete_post_meta( $product_id, self::HASH_META_KEY );
        }
    }

    /**
     * Increases action scheduler timeout.
     */
    public static function get_new_action_scheduler_timeout() {
        return 900; // 15 minutes per batch.
    }
}
