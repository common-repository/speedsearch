<?php
/**
 * Products Hash generation.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Products_Hash;

use Exception;
use SpeedSearch\SpeedSearch;
use SpeedSearch\Sync_Data_Feed\Feed_Generation_Buffer;
use SpeedSearch\Sync_Data_Feed\Sync_Data_Feed;

/**
 * Generates Products hash.
 */
final class Init_Regeneration {

    /**
     * Interval between the checks if there were changed taxonomies whose
     * products the hash should be updated.
     */
    const CHECK_CHANGED_TAXONOMIES_INTERVAL = MINUTE_IN_SECONDS;

    /**
     * Hook name of the interval.
     */
    const INTERVAL_HOOK_NAME = 'speedsearch_check_changed_taxonomies';

    /**
     * Constructor.
     */
    public function __construct() {

        // Term (attribute-term, tag, category) change.

        add_action( 'saved_term', [ $this, 'init_hash_regeneration_for_products_within_the_term' ] );
        add_action( 'pre_delete_term', [ $this, 'init_hash_regeneration_for_products_within_the_term' ] );

        // Taxonomies change (attributes).

        $this->regenerate_hash_on_product_taxonomies_changes();
    }

    /**
     * Checks for changed product taxonomies (e.g. attributes) with Action Scheduler interval.
     */
    private function regenerate_hash_on_product_taxonomies_changes() {
        add_action( 'woocommerce_attribute_updated', [ $this, 'attribute_change' ] );
        add_action( 'woocommerce_attribute_deleted', [ $this, 'attribute_change' ] );

        // Interval to check for changed taxonomies (attributes).

        $this->init_interval_to_check_for_changed_product_taxonomies();
    }

    /**
     * Adds the attribute taxonomy name to the option of the list of attributes for which the hash should be updated.
     *
     * @param int $id Updated attribute ID.
     *
     * @throws Exception Exception.
     */
    public function attribute_change( $id ) {
        $taxonomies_to_update_products_hash_for = SpeedSearch::$options->get( 'taxonomies-to-update-products-hash-for' );
        if ( ! in_array( $id, $taxonomies_to_update_products_hash_for, true ) ) {
            $taxonomies_to_update_products_hash_for[] = $id;
            SpeedSearch::$options->set( 'taxonomies-to-update-products-hash-for', $taxonomies_to_update_products_hash_for );
        }
    }

    /**
     * Inits Action Scheduler interval to check for changed product taxonomies (e.g. attributes).
     */
    private function init_interval_to_check_for_changed_product_taxonomies() {

        // Action.

        add_action( self::INTERVAL_HOOK_NAME, [ $this, 'as_update_products_hash_for_changed_taxonomies' ] );

        // Interval.

        $schedule_interval_action = function() {
            as_schedule_recurring_action(
                time() + self::CHECK_CHANGED_TAXONOMIES_INTERVAL,
                self::CHECK_CHANGED_TAXONOMIES_INTERVAL,
                self::INTERVAL_HOOK_NAME,
                [],
                'speedsearch',
                true
            );
        };
        if ( did_action( 'action_scheduler_init' ) ) {
            $schedule_interval_action();
        } else {
            add_action( 'action_scheduler_init', $schedule_interval_action );
        }

        // Unschedule interval on plugin deactivation.
        register_deactivation_hook( SPEEDSEARCH_FILE, [ __CLASS__, 'unschedule_interval' ] );
    }

    /**
     * Unschedule the interval.
     */
    public static function unschedule_interval() {
        as_unschedule_all_actions( self::INTERVAL_HOOK_NAME, [], 'speedsearch' );
        as_unschedule_all_actions( Init_Generation::INTERVAL_HOOK_NAME, [], 'speedsearch' );

        \SpeedSearch\Misc::stop_running_action_scheduler_action( self::INTERVAL_HOOK_NAME );
    }

    /**
     * Updates all products hash within the product taxonomies which were changed recently.
     *
     * @throws Exception Exception.
     */
    public function as_update_products_hash_for_changed_taxonomies() {
        $taxonomies_to_update_products_hash_for = SpeedSearch::$options->get( 'taxonomies-to-update-products-hash-for' );

        if ( count( $taxonomies_to_update_products_hash_for ) ) {
            foreach ( $taxonomies_to_update_products_hash_for as $key => $taxonomy_id ) {
                // Back-compat when tax names instead of IDs were used.
                $taxonomy_name = is_numeric( $taxonomy_id ) ? wc_attribute_taxonomy_name_by_id( $taxonomy_id ) : $taxonomy_id;

                if ( $taxonomy_name ) {
                    $terms = get_terms( $taxonomy_name );
                    if ( ! is_wp_error( $terms ) ) { // Tax exists.
                        foreach ( $terms as $term ) {
                            $this->init_hash_regeneration_for_products_within_the_term( $term->term_id );
                        }
                    }
                }

                unset( $taxonomies_to_update_products_hash_for[ $key ] );
                $taxonomies_to_update_products_hash_for = array_values( $taxonomies_to_update_products_hash_for );

                SpeedSearch::$options->set( 'taxonomies-to-update-products-hash-for', $taxonomies_to_update_products_hash_for );
            }
        }
    }

    /**
     * Inits hash regeneration for the products within the term by removing theirs
     * 'speedsearch-product-hash' meta and setting 'speedsearch-product-hashes-generation-status' meta to 0.
     *
     * @param int $term_id Term ID.
     *
     * @throws Exception Exception.
     */
    public function init_hash_regeneration_for_products_within_the_term( $term_id ) {
        $term          = get_term( $term_id );
        $taxonomy_name = $term->taxonomy;

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => [
                [
                    'taxonomy'         => $taxonomy_name,
                    'field'            => 'term_id',
                    'terms'            => $term_id,
                    'include_children' => false,
                ],
            ],
            'fields'         => 'ids',
            'orderby'        => 'none',
        ];

        $products_ids = get_posts( $args );

        $was_some_feed_meta_deleted = false;

        foreach ( $products_ids as $product_id ) { // Removes "speedsearch-product-hash" meta.
            delete_post_meta( $product_id, Base::HASH_META_KEY );

            $was_feed_meta_deleted = delete_post_meta( $product_id, Sync_Data_Feed::FEED_HANDLED_META_NAME ); // For the feed.
            Feed_Generation_Buffer::add_to_prune_file( $product_id );
            if ( $was_feed_meta_deleted ) {
                $was_some_feed_meta_deleted = true;
            }
        }

        if ( $was_some_feed_meta_deleted ) {
            as_enqueue_async_action( Sync_Data_Feed::INTERVAL_HOOK_NAME, [], 'speedsearch' );
        }

        // Inits the hash regeneration for the products without the hash.
        SpeedSearch::$options->set( 'product-hashes-generation-status', 0 );
    }

    /**
     * Init hash regeneration for a product.
     *
     * @param int $product_id Product ID.
     *
     * @throws Exception Exception.
     */
    public function init_hash_regeneration_for_a_product( $product_id ) {
        $was_meta_deleted = delete_post_meta( $product_id, Base::HASH_META_KEY );
        if ( $was_meta_deleted ) {
            // Inits the hash regeneration for the products without the hash.
            SpeedSearch::$options->set( 'product-hashes-generation-status', 0 );
        }
    }
}
