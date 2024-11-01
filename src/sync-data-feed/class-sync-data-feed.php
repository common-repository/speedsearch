<?php
/**
 * Sync data feed.
 *
 * Writes feed data for the sync to the .json files on the server.
 *
 * There are two ways to get the feed:
 *
 * 1. https://example.com/speedsearch-feed/1 (slower, as the server return it);
 * 2. Directly via JSON file from the server (the path is returned from the settings endpoint):
 *
 *    ['feed']['url'] (also available 'last_file_index' and 'last_item_index').
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Sync_Data_Feed;

use Exception;
use SpeedSearch\DB;
use SpeedSearch\SpeedSearch;
use SpeedSearch\Webhooks\Meta_Change_Webhooks;
use WC_REST_Products_Controller;

/**
 * A class for sync data feed.
 */
final class Sync_Data_Feed extends WC_REST_Products_Controller {

    /**
     * Interval.
     */
    const INTERVAL = MINUTE_IN_SECONDS * 5;

    /**
     * Hook name of the interval.
     */
    const INTERVAL_HOOK_NAME = 'speedsearch_sync_data_feed_generation';

    /**
     * Hook name of the buffer flush interval.
     */
    const BUFFER_FLUSH_INTERVAL_HOOK_NAME = 'speedsearch_feed_buffer_flush';

    /**
     * Prune interval.
     */
    const PRUNE_INTERVAL = HOUR_IN_SECONDS;

    /**
     * Hook name of the pruning interval.
     */
    const PRUNE_INTERVAL_HOOK_NAME = 'speedsearch_sync_data_prune_feed_files';

    /**
     * Product meta key of the flag if the feed was generation (when not present => not generated).
     */
    const FEED_HANDLED_META_NAME = 'speedsearch-sync-feed-handled';

    /**
     * Batch size for feed generation. Number of elements to generate the feed for in one run.
     *
     * If your server is fast, can be increased.
     */
    const BATCH_SIZE = 50;

    /**
     * Max file size (number of lines) for the feed file.
     *
     * When it's over, another feed file is created (like 0.json, 1.json).
     */
    const FILE_MAX_LINES = 1000;

    /**
     * Product updated action name.
     *
     * @var string
     */
    const PRODUCT_UPDATED_HOOK_NAME = 'speedsearch_trigger_product_updated';

    /**
     * Term updated action name.
     *
     * @var string
     */
    const TERM_UPDATED_HOOK_NAME = 'speedsearch_trigger_term_updated';

    /**
     * Attribute updated action name.
     *
     * @var string
     */
    const ATTRIBUTE_UPDATED_HOOK_NAME = 'speedsearch_trigger_attribute_updated';

    /**
     * Constructor.
     */
    public function __construct() {

        // Schedule the main feed generation interval (will add rows to the buffer, and then process from it, in a separate interval).

        add_action( self::INTERVAL_HOOK_NAME, [ $this, 'generate_sync_feed' ], 10, 0 );

        $schedule_interval_action = function() {
            as_schedule_recurring_action(
                time(),
                self::INTERVAL,
                self::INTERVAL_HOOK_NAME,
                [],
                'speedsearch',
                true,
                9
            );
        };
        if ( did_action( 'action_scheduler_init' ) ) {
            $schedule_interval_action();
        } else {
            add_action( 'action_scheduler_init', $schedule_interval_action );
        }

        register_deactivation_hook( SPEEDSEARCH_FILE, [ __CLASS__, 'unschedule_interval' ] );

        // Schedule feed JSON generation interval.

        add_action( self::BUFFER_FLUSH_INTERVAL_HOOK_NAME, [ $this, 'flush_the_feed_buffer' ], 10, 0 );

        $schedule_interval_action = function() {
            as_schedule_recurring_action(
                time(),
                self::INTERVAL,
                self::BUFFER_FLUSH_INTERVAL_HOOK_NAME,
                [],
                'speedsearch',
                true,
                9
            );
        };
        if ( did_action( 'action_scheduler_init' ) ) {
            $schedule_interval_action();
        } else {
            add_action( 'action_scheduler_init', $schedule_interval_action );
        }

        register_deactivation_hook( SPEEDSEARCH_FILE, [ __CLASS__, 'unschedule_interval' ] );

        // Schedule a pruning interval.

        add_action( self::PRUNE_INTERVAL_HOOK_NAME, [ $this, 'prune_feed_files' ] );

        $schedule_interval_action = function() {
            as_schedule_recurring_action(
                time(),
                self::PRUNE_INTERVAL,
                self::PRUNE_INTERVAL_HOOK_NAME,
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

        register_deactivation_hook( SPEEDSEARCH_FILE, [ $this, 'unschedule_pruning_interval' ] );

        // Before the product is deleted.
        add_action( 'delete_post', [ $this, 'post_deleted' ], 10, 2 );

        // Add rewrite rule (to get custom feed lines).
        $this->add_url_rewrite_rule();

        // Template redirect to return custom feed lines.
        add_action( 'template_redirect', [ $this, 'template_redirect' ], 10, 0 );

        // Add listeners for when the terms were changed.
        $this->add_terms_changed_listeners();

        // Add hooks for product creation, updates, deletion, and restoration.

        add_action( 'woocommerce_process_product_meta', [ $this, 'product_created' ] );
        add_action( 'woocommerce_new_product', [ $this, 'product_created' ] );
        add_action( 'woocommerce_new_product_variation', [ $this, 'product_created' ] );

        add_action( 'save_post_product', [ $this, 'product_updated' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'product_updated' ] );
        add_action( 'woocommerce_update_product', [ $this, 'product_updated' ] );
        add_action( 'woocommerce_update_product_variation', [ $this, 'product_updated' ] );

        add_action( 'trashed_post', [ $this, 'product_updated' ] );

        add_action( 'untrashed_post', [ $this, 'product_restored' ] );

        // Custom actions.

        add_action( self::PRODUCT_UPDATED_HOOK_NAME, [ $this, 'product_updated' ] );
        add_action( self::TERM_UPDATED_HOOK_NAME, [ $this, 'term_updated' ] );
        add_action( self::ATTRIBUTE_UPDATED_HOOK_NAME, [ $this, 'attribute_changed' ] );
    }

    /**
     * Add listeners for when the terms were changed.
     *
     * Should have a parity with {@see Meta_Change_Webhooks::add_terms_changed_listeners()}
     */
    public function add_terms_changed_listeners() {

        // Term (attribute-term, tag, category) change / deletion.

        add_action( 'saved_term', [ $this, 'term_updated' ] );
        add_action( 'pre_delete_term', [ $this, 'term_deleted' ] );

        // Attribute change / deletion.

        add_action( 'woocommerce_attribute_updated', [ $this, 'attribute_changed' ] );
        add_action( 'woocommerce_attribute_deleted', [ $this, 'attribute_deleted' ] );

        // Term meta modification.

        add_action( 'updated_term_meta', [ $this, 'term_meta_updated' ], 10, 4 );
        add_action( 'deleted_term_meta', [ $this, 'term_meta_updated' ], 10, 4 );
    }

    /**
     * Adds all attribute terms to the feed.
     */
    public function add_all_wc_attributes_and_their_terms_to_the_feed() {
        $attributes = wc_get_attribute_taxonomies();

        // Attributes.

        foreach ( $attributes as $attribute ) {
            $this->attribute_changed( $attribute->attribute_id );
        }

        // Attribute terms.

        foreach ( $attributes as $attribute ) {
            $name = wc_attribute_taxonomy_name( $attribute->attribute_name );

            $terms = get_terms(
                $name,
                [
                    'hide_empty' => false,
                ]
            );

            foreach ( $terms as $term ) {
                $this->term_updated( $term->term_id );
            }
        }

        // Categories.

        $product_categories = get_terms(
            'product_cat',
            [
                'hide_empty' => false,
            ]
        );

        foreach ( $product_categories as $category ) {
            $this->term_updated( $category->term_id );
        }

        // Tags.

        $product_tags = get_terms(
            'product_tag',
            [
                'hide_empty' => false,
            ]
        );

        foreach ( $product_tags as $tag ) {
            $this->term_updated( $tag->term_id );
        }
    }

    /**
     * Get product data.
     *
     * @param int|\WC_Product $product                    Product object or ID.
     * @param bool            $with_additional_enrichment Whether to additionally enrich the product data.
     *
     * @return array|false Product data or false if it can't get the data.
     *
     * @see WC_REST_Products_V2_Controller::prepare_object_for_response
     * @see WC_API_Products::get_product_data
     */
    public function get_the_product_data( $product, $with_additional_enrichment = true ) {
        if ( is_numeric( $product ) ) {
            $product = wc_get_product( $product );
        }

        if ( ! $product ) {
            return false;
        }

        $data = [
            'id'                    => $product->get_id(),
            'parent_id'             => $product->get_parent_id(),
            'name'                  => $product->get_name(),
            'slug'                  => $product->get_slug(),
            'type'                  => $product->get_type(),
            'featured'              => $product->is_featured(),
            'catalog_visibility'    => $product->get_catalog_visibility(),
            'short_description'     => apply_filters( 'woocommerce_short_description', $product->get_short_description() ),
            'total_sales'           => $product->get_total_sales(),
            'shipping_required'     => $product->needs_shipping(),
            'shipping_taxable'      => $product->is_shipping_taxable(),
            'reviews_allowed'       => $product->get_reviews_allowed(),
            'average_rating'        => wc_format_decimal( $product->get_average_rating(), 2 ),
            'rating_count'          => $product->get_rating_count(),
            'upsell_ids'            => array_map( 'absint', $product->get_upsell_ids() ),
            'cross_sell_ids'        => array_map( 'absint', $product->get_cross_sell_ids() ),
            'purchase_note'         => wpautop( do_shortcode( wp_kses_post( $product->get_purchase_note() ) ) ),
            'images'                => $this->get_images( $product ),
            'default_attributes'    => $product->get_default_attributes(),
            'meta_data'             => json_decode( wp_json_encode( $product->get_meta_data() ), true ),
            'is_variation'          => $product->is_type( 'variation' ),
            'is_visible'            => $product->is_visible(),
            'has_variations'        => $product->has_child(),
            'permalink'             => $product->get_permalink(),
            'date_created'          => wc_rest_prepare_date_response( strtotime( $product->get_date_created() ), false ),
            'date_created_gmt'      => wc_rest_prepare_date_response( strtotime( $product->get_date_created() ) ),
            'date_modified'         => wc_rest_prepare_date_response( strtotime( $product->get_date_modified() ), false ),
            'date_modified_gmt'     => wc_rest_prepare_date_response( strtotime( $product->get_date_modified() ) ),
            'description'           => wpautop( do_shortcode( $product->get_description() ) ),
            'sku'                   => $product->get_sku(),
            'price'                 => $product->get_price(),
            'regular_price'         => $product->get_regular_price(),
            'sale_price'            => $product->get_sale_price() ? $product->get_sale_price() : null,
            'date_on_sale_from'     => wc_rest_prepare_date_response( $product->get_date_on_sale_from(), false ),
            'date_on_sale_from_gmt' => wc_rest_prepare_date_response( $product->get_date_on_sale_from() ),
            'date_on_sale_to'       => wc_rest_prepare_date_response( $product->get_date_on_sale_to(), false ),
            'date_on_sale_to_gmt'   => wc_rest_prepare_date_response( $product->get_date_on_sale_to() ),
            'on_sale'               => $product->is_on_sale(),
            'status'                => $product->get_status(),
            'purchasable'           => $product->is_purchasable(),
            'virtual'               => $product->is_virtual(),
            'downloadable'          => $product->is_downloadable(),
            'downloads'             => $product->get_downloads(),
            'download_limit'        => $product->get_download_limit(),
            'download_expiry'       => $product->get_download_expiry(),
            'tax_status'            => $product->get_tax_status(),
            'manage_stock'          => $product->managing_stock(),
            'stock_quantity'        => $product->get_stock_quantity(),
            'stock_status'          => $product->get_stock_status(),
            'backorders'            => $product->get_backorders(),
            'backorders_allowed'    => $product->backorders_allowed(),
            'backordered'           => $product->is_on_backorder(),
            'weight'                => $product->get_weight(),
            'width'                 => $product->get_width(),
            'height'                => $product->get_height(),
            'length'                => $product->get_length(),
            'shipping_class'        => $product->get_shipping_class(),
            'shipping_class_id'     => ( 0 !== $product->get_shipping_class_id() ) ? $product->get_shipping_class_id() : null,
            'menu_order'            => $product->get_menu_order(),
            'categories'            => self::get_taxonomy_terms( $product ),
            'tags'                  => self::get_taxonomy_terms( $product, 'tag' ),
            'attributes'            => $this->get_attributes( $product ),
            'variations'            => [],
            'grouped_products'      => [],
        ];

        // Variations.
        if ( $product->is_type( 'variable' ) && $product->has_child() ) {
            $data['variations'] = $product->get_children();
        }

        // Grouped products.
        if ( $product->is_type( 'grouped' ) && $product->has_child() ) {
            $data['grouped_products'] = $product->get_children();
        }

        $product_data = \SpeedSearch\Misc::enrich_product_data( $data, $product );

        if ( $with_additional_enrichment ) {

            // 1. Enrich product data (add terms (cats, tags, attribute terms) data).

            // 1.1. Categories.

            if ( isset( $product_data['categories'] ) ) {
                foreach ( $product_data['categories'] as &$category ) {
                    $category = array_merge( $category, self::get_category_data( $category['id'] ) );
                }
            }

            // 1.2. Tags.

            if ( isset( $product_data['tags'] ) ) {
                foreach ( $product_data['tags'] as &$tag ) {
                    $tag = array_merge( $tag, self::get_tag_data( $tag['id'] ) );
                }
            }

            // 1.3. Attributes.

            if ( isset( $product_data['attributes'] ) ) {
                foreach ( $product_data['attributes'] as &$attribute ) {
                    if ( isset( $attribute['speedsearch_attribute_terms_data'] ) ) {
                        foreach ( $attribute['speedsearch_attribute_terms_data'] as &$attribute_term ) {
                            $attribute_term = array_merge(
                                $attribute_term,
                                self::get_attribute_term_data( $attribute_term['id'] )
                            );
                        }
                    }

                    // Add missing attribute data.

                    foreach ( wc_get_attribute_taxonomies() as $an_attribute ) {
                        if ( (int) $attribute['id'] === (int) $an_attribute->attribute_id ) {
                            $attribute = array_merge(
                                $attribute,
                                [
                                    'slug'         => wc_attribute_taxonomy_name( $an_attribute->attribute_name ),
                                    'type'         => $an_attribute->attribute_type,
                                    'order_by'     => $an_attribute->attribute_orderby,
                                    'has_archives' => (bool) $an_attribute->attribute_public,
                                ]
                            );
                            break;
                        }
                    }
                }
            }

            // 1.4. Variable product variations.

            if ( isset( $product_data['variations'] ) && $product_data['variations'] ) {
                $variations_data = [];

                foreach ( $product_data['variations'] as $variation_id ) {
                    $variations_data[] = $this->get_the_product_data( $variation_id, false );
                }

                $product_data['variations'] = $variations_data;
            }
        }

        return $product_data;
    }

    /**
     * Get tag data.
     *
     * @param int $tag_id Tag ID.
     *
     * @return array|false Tag data or false if it can't get the data.
     */
    public static function get_tag_data( $tag_id ) {
        $tag = get_term( $tag_id, 'product_tag' );

        if ( ! $tag || is_wp_error( $tag ) ) {
            return false;
        }

        return [
            'id'          => $tag->term_id,
            'name'        => $tag->name,
            'slug'        => $tag->slug,
            'count'       => $tag->count,
            'description' => $tag->description,
        ];
    }

    /**
     * Get attribute term data.
     *
     * @param int $term_id Tag ID.
     *
     * @return array|false Attribute term data or false if it can't get the data.
     */
    public static function get_attribute_term_data( $term_id ) {
        $term = get_term( $term_id );

        if ( ! $term || is_wp_error( $term ) ) {
            return false;
        }

        return [
            'id'           => $term->term_id,
            'attribute_id' => \SpeedSearch\Misc::get_attribute_taxonomy_id_by_term_id( $term_id ),
            'menu_order'   => $term->term_order,
            'count'        => $term->count,
            'name'         => $term->name,
            'slug'         => $term->slug,
            'term_meta'    => get_term_meta( $term_id ),
            'description'  => $term->description,
        ];
    }

    /**
     * Get attribute data.
     *
     * @param int $attribute_id Tag ID.
     *
     * @return array|false Attribute data or false if it can't get the data.
     */
    public static function get_attribute_data( $attribute_id ) {
        global $wpdb;

        $attribute = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = %d",
                $attribute_id
            )
        );

        if ( ! $attribute ) {
            return false;
        }

        return [
            'id'           => $attribute->attribute_id,
            'name'         => $attribute->attribute_label,
            'slug'         => 'pa_' . $attribute->attribute_name,
            'type'         => $attribute->attribute_type,
            'order_by'     => $attribute->attribute_orderby,
            'has_archives' => boolval( $attribute->attribute_public ),
        ];
    }

    /**
     * Get category data.
     *
     * @param int $category_id Category ID.
     *
     * @return array|false Category data or false if it can't get the data.
     */
    public static function get_category_data( $category_id ) {
        $term = get_term( $category_id, 'product_cat' );

        if ( ! $term || is_wp_error( $term ) ) {
            return false;
        }

        $parent_data = [];
        if ( $term->parent ) {
            $parent_data = self::get_category_data( $term->parent );
        }

        $display = get_term_meta( $term->term_id, 'display_type', true );

        $data = [
            'id'          => $term->term_id,
            'name'        => $term->name,
            'slug'        => $term->slug,
            'parent'      => $term->parent,
            'display'     => $display ? $display : 'default',
            'image'       => null,
            'menu_order'  => (int) get_term_meta( $term->term_id, 'order', true ),
            'count'       => $term->count,
            'description' => $term->description,
            'parent_data' => $parent_data,
        ];

        // Get category image.
        $image_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
        if ( $image_id ) {
            $attachment = get_post( $image_id );

            $data['image'] = [
                'id'                => (int) $image_id,
                'date_created'      => wc_rest_prepare_date_response( $attachment->post_date, false ),
                'date_created_gmt'  => wc_rest_prepare_date_response( $attachment->post_date_gmt ),
                'date_modified'     => wc_rest_prepare_date_response( $attachment->post_modified, false ),
                'date_modified_gmt' => wc_rest_prepare_date_response( $attachment->post_modified_gmt ),
                'src'               => wp_get_attachment_url( $image_id ),
                'name'              => get_the_title( $attachment ),
                'alt'               => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
            ];
        }

        return $data;
    }

    /**
     * Get webhook topic from action name.
     *
     * @param string $action Action name.
     *
     * @return string Webhook topic.
     *
     * @throws Exception Exception.
     */
    private function get_webhook_topic_from_action( $action ) {
        foreach ( Meta_Change_Webhooks::NEW_WEBHOOKS as $webhook_name => $actions ) {
            if ( in_array( $action, $actions, true ) ) {
                return $webhook_name;
            }
        }
        throw new Exception( __( 'No webhook for such an action.', 'speedsearch' ) );
    }

    /**
     * Get term data (tag, category, attribute term) by ID.
     *
     * @param int $term_id Term ID.
     *
     * @return array|false Data.
     */
    public function get_term_data_by_id( $term_id ) {
        $type = Meta_Change_Webhooks::get_wc_tax_type_by_term_id( $term_id );

        $data = false;

        if ( 'attribute_term' === $type ) {
            $term = get_term( $term_id );
            if ( $term && ! is_wp_error( $term ) ) {
                $data = self::get_attribute_term_data( $term_id );
            }
        } elseif ( 'category' === $type ) {
            $data = self::get_category_data( $term_id );
        } elseif ( 'tag' === $type ) {
            $data = self::get_tag_data( $term_id );
        }

        return $data;
    }

    /**
     * Sends a webhook when the term was created/changed.
     *
     * @param int $term_id Term ID.
     */
    public function term_updated( $term_id ) {
        $type = Meta_Change_Webhooks::get_wc_tax_type_by_term_id( $term_id );
        if ( $type ) {
            Feed_Generation_Buffer::add_to_feed_generation_buffer(
                $this->get_webhook_topic_from_action( "speedsearch_{$type}_updated" ),
                $term_id,
                $this->get_term_data_by_id( $term_id )
            );
        }
    }

    /**
     * Sends a webhook when the term was deleted.
     *
     * @param int $term_id Term ID.
     */
    public function term_deleted( $term_id ) {
        $type = Meta_Change_Webhooks::get_wc_tax_type_by_term_id( $term_id );
        if ( $type ) {
            Feed_Generation_Buffer::add_to_feed_generation_buffer(
                $this->get_webhook_topic_from_action( "speedsearch_{$type}_deleted" ),
                $term_id,
                $this->get_term_data_by_id( $term_id )
            );
        }
    }

    /**
     * Sends a webhook when the attribute was created/changed.
     *
     * @param int $attribute_id Updated attribute ID.
     */
    public function attribute_changed( $attribute_id ) {
        Feed_Generation_Buffer::add_to_feed_generation_buffer(
            $this->get_webhook_topic_from_action( 'speedsearch_attribute_updated' ),
            $attribute_id,
            $this->get_attribute_data( $attribute_id ),
        );
    }

    /**
     * Sends a webhook when the attribute was deleted.qwe
     *
     * @param int $attribute_id Updated attribute ID.
     */
    public function attribute_deleted( $attribute_id ) {
        Feed_Generation_Buffer::add_to_feed_generation_buffer(
            $this->get_webhook_topic_from_action( 'speedsearch_attribute_deleted' ),
            $attribute_id,
            $this->get_attribute_data( $attribute_id )
        );
    }

    /**
     * Product updated.
     *
     * @param int $post_id Post ID.
     */
    public function product_created( $post_id ) {
        self::$created_products[ $post_id ] = true;

        Feed_Generation_Buffer::add_to_feed_generation_buffer(
            'product.created',
            $post_id,
            $this->get_the_product_data( $post_id )
        );
    }

    /**
     * Product updated.
     *
     * @param int  $post_id            Post ID.
     * @param bool $on_feed_generation On feed generation (not on real webhook action).
     */
    public function product_updated( int $post_id, bool $on_feed_generation = false ) {
        if ( 'product' !== get_post_type( $post_id ) ) {
            return;
        }

        /*
         * We have many hooks attached, and some can be called several times (e.g. save_post_product and product_updated).
         */

        static $updated_products = [];

        if ( isset( $updated_products[ $post_id ] ) ) {
            return;
        }

        $updated_products[ $post_id ] = true;

        /*
         * Add to the feed buffer.
         */

        // TODO: On "restored", this does not work, as the order is different (first 'updated' is called, then 'restored').
        // TODO: Find a solution for it to delete 'restored' somehow.

        if ( // Do not do duplicates.
            ! isset( self::$created_products[ $post_id ] ) &&
            ! isset( self::$deleted_products[ $post_id ] ) &&
            ! isset( self::$restored_products[ $post_id ] )
        ) {
            $product_data = $this->get_the_product_data( $post_id );

            $product_date_modified = strtotime( $product_data['date_modified_gmt'] );
            $product_meta          = array_column( $product_data['meta_data'], 'value', 'key' );

            if (
                ! isset( $product_meta['speedsearch_product_last_modified_time'] ) ||
                (int) $product_meta['speedsearch_product_last_modified_time'] <= $product_date_modified
            ) {
                update_post_meta(
                    $post_id,
                    'speedsearch_product_last_modified_time',
                    $product_date_modified
                );

                update_post_meta(
                    $post_id,
                    'speedsearch-add-to-feed-buffer-last-time',
                    time()
                );

                Feed_Generation_Buffer::add_to_feed_generation_buffer(
                    'product.updated',
                    $post_id,
                    $product_data,
                    $on_feed_generation
                );
            }
        }
    }

    /**
     * The list of deleted products for the current session.
     *
     * When the product is in "created" array, then do not call "update" for it (to avoid duplicates).
     *
     * @var array
     */
    private static array $created_products = [];

    /**
     * The list of deleted products for the current session.
     *
     * When the product is in "deleted" array, then do not call "update" for it (to avoid duplicates).
     *
     * @var array
     */
    private static array $deleted_products = [];

    /**
     * The list of deleted products for the current session.
     *
     * When the product is in "restored" array, then do not call "update" for it (to avoid duplicates).
     *
     * @var array
     */
    private static array $restored_products = [];

    /**
     * When the product was deleted.
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     */
    public function post_deleted( $post_id, $post ) {
        if ( 'product' === $post->post_type ) {
            self::$deleted_products[ $post_id ] = true;

            Feed_Generation_Buffer::add_to_feed_generation_buffer(
                'product.deleted',
                $post_id,
                $this->get_the_product_data( $post_id )
            );
        }
    }

    /**
     * Product restored.
     *
     * @param int $post_id Post ID.
     */
    public function product_restored( $post_id ) {
        if ( 'product' !== get_post_type( $post_id ) ) {
            return;
        }

        self::$restored_products[ $post_id ] = true;

        Feed_Generation_Buffer::add_to_feed_generation_buffer(
            'product.restored',
            $post_id,
            $this->get_the_product_data( $post_id )
        );
    }

    /**
     * Handles term meta update.
     *
     * @param int    $meta_id     ID of updated metadata entry.
     * @param int    $object_id   ID of the object metadata is for.
     * @param string $meta_key    Metadata key.
     * @param mixed  $_meta_value Metadata value. Serialized if non-scalar.
     */
    public function term_meta_updated( $meta_id, $object_id, $meta_key, $_meta_value ) {
        $term = get_term( $object_id );

        if ( ! $term ) {
            return;
        }

        $taxonomy = get_taxonomy( $term->taxonomy );

        // Check if it's a WC attribute.
        if (
            array_key_exists( 0, $taxonomy->object_type ) && 'product' === $taxonomy->object_type[0] &&
            'pa_' === substr( $taxonomy->name, 0, 3 )
        ) {
            Feed_Generation_Buffer::add_to_feed_generation_buffer(
                $this->get_webhook_topic_from_action( 'speedsearch_attribute_term_updated' ),
                $object_id,
                $this->get_term_data_by_id( $object_id ),
            );
        }
    }

    /**
     * Unschedule the interval.
     */
    public static function unschedule_interval() {
        as_unschedule_all_actions( self::INTERVAL_HOOK_NAME, [], 'speedsearch' );
        as_unschedule_all_actions( self::BUFFER_FLUSH_INTERVAL_HOOK_NAME, [], 'speedsearch' );
    }

    /**
     * Unschedule the pruning interval.
     */
    public static function unschedule_pruning_interval() {
        as_unschedule_all_actions( self::PRUNE_INTERVAL_HOOK_NAME, [], 'speedsearch' );

        \SpeedSearch\Misc::stop_running_action_scheduler_action( self::PRUNE_INTERVAL_HOOK_NAME );
    }

    /**
     * Returns the feed URL.
     *
     * @return string
     */
    public static function get_feed_url() {
        $uploads_dir = path_join( wp_upload_dir()['baseurl'], 'speedsearch' );
        return path_join( $uploads_dir, 'feed' );
    }

    /**
     * Get the count of how many products left to generate
     *
     * @return int Count of how many products left to generate.
     */
    public static function get_how_many_products_left_to_generate() {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} AS p
            LEFT JOIN {$wpdb->postmeta} AS pm
            ON p.ID = pm.post_id
            AND pm.meta_key = %s
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            AND pm.meta_value IS NULL",
                self::FEED_HANDLED_META_NAME
            )
        );
    }

    /**
     * Generates sync feed.
     */
    public function generate_sync_feed() {
        $progress = SpeedSearch::$options->get( 'feed-generation-progress' );
        if ( ! isset( $progress['generated'] ) ) { // Add initial progress.
            $how_many_products_left_to_generate = self::get_how_many_products_left_to_generate();

            if ( ! $how_many_products_left_to_generate ) {
                return;
            }

            $progress = [
                'generated' => 0,
                'total'     => $how_many_products_left_to_generate,
            ];

            if ( ! SpeedSearch::$options->get( 'feed-last-item-index' ) ) { // Add terms data to the beginning of the feed.
                $this->add_all_wc_attributes_and_their_terms_to_the_feed();
            }
        }
        SpeedSearch::$options->set( 'feed-generation-progress', $progress );

        // Add the products' data.

        add_filter(
            'woocommerce_product_data_store_cpt_get_products_query',
            function( $query, $query_vars ) {
                if ( isset( $query_vars['speedsearch_feed'] ) && 'not_exists' === $query_vars['speedsearch_feed'] ) {
                    if ( in_array( self::FEED_HANDLED_META_NAME, array_column( $query['meta_query'], 'key' ), true ) ) { // Don't do duplicate.
                        return $query;
                    }

                    $query['meta_query'][] = [
                        'key'     => self::FEED_HANDLED_META_NAME,
                        'compare' => 'NOT EXISTS',
                    ];
                }

                return $query;
            },
            10,
            2
        );

        $product_ids = wc_get_products(
            [
                'limit'            => self::BATCH_SIZE,
                'status'           => 'publish',
                'speedsearch_feed' => 'not_exists',
                'orderby'          => 'none',
                'return'           => 'ids',
            ]
        );

        // Update total when more products are returned.

        if ( $progress['generated'] + count( $product_ids ) > $progress['total'] ) {
            $progress['total'] = $progress['generated'] + self::get_how_many_products_left_to_generate();
        }

        // Update total when less products are returned.

        if (
            count( $product_ids ) < self::BATCH_SIZE && // The end, no more products.
            // Total products (to be generated) are less than total.
            $progress['generated'] + count( $product_ids ) < $progress['total']
        ) {
            $progress['total'] = $progress['generated'] + count( $product_ids );
        }

        foreach ( $product_ids as $product_id ) {

            // Update generated products count.

            $progress['generated'] += 1;

            // Add to the feed.

            $this->product_updated( $product_id, true );

            /**
             * Not necessary added, but just "handled" (i.e. at least tried).
             *
             * Maybe failed to fetch the product data for some external reason (like some bad plugin).
             *
             * Otherwise, if we do only for "added", that could cause infinite loop (AS action after AS action).
             */
            update_post_meta( $product_id, self::FEED_HANDLED_META_NAME, true );
        }

        if ( count( $product_ids ) === self::BATCH_SIZE ) { // Schedule batch action to run immediately.
            SpeedSearch::$options->set( 'feed-generation-progress', $progress );

            // Run another enqueue to generate the buffer.
            as_enqueue_async_action( self::INTERVAL_HOOK_NAME, [], 'speedsearch' );
        } else {
            SpeedSearch::$options->set( 'initial-feed-generation-complete', true );

            SpeedSearch::$options->set(
                'initial-feed-generation-complete-on-index',
                DB::get_feed_buffer_count() - 1
            );

            SpeedSearch::$options->delete( 'feed-generation-progress' );

            // Schedule to run the buffer `flush` on feed buffer generation finish.
            as_enqueue_async_action( self::BUFFER_FLUSH_INTERVAL_HOOK_NAME, [], 'speedsearch' );
        }
    }

    /**
     * Flushes the feed buffer to the JSON file.
     */
    public function flush_the_feed_buffer() {
        $buffer_records = \SpeedSearch\DB::get_feed_buffer_unwritten_records( self::BATCH_SIZE );

        if ( ! $buffer_records ) {
            return;
        }

        // Flush the buffer to feed files.

        foreach ( $buffer_records as $buffer_record ) {
            $this->add_a_record(
                $buffer_record['action'],
                maybe_unserialize( $buffer_record['data'] ),
                $buffer_record['hash']
            );

            \SpeedSearch\DB::mark_buffer_feed_row_as_written( $buffer_record['id'] );
        }

        // Update indexes file.

        Feed_Index_File::update_indexes();

        // Run another task immediately when there is still something to flush.

        if ( self::BATCH_SIZE === count( $buffer_records ) ) {
            as_enqueue_async_action( self::BUFFER_FLUSH_INTERVAL_HOOK_NAME, [], 'speedsearch', false, 9 );
        }
    }

    /**
     * Encodes an array of associative arrays into a JSON Lines string.
     *
     * @param array $array_of_records Array of associative arrays to be encoded.
     *
     * @return string A JSON Lines formatted string.
     */
    public static function jsonl_encode( array $array_of_records ) : string {
        $jsonl_string = '';
        foreach ( $array_of_records as $record ) {
            $jsonl_string .= wp_json_encode( $record ) . PHP_EOL;
        }
        return $jsonl_string;
    }

    /**
     * Decodes a JSON Lines string into an array of associative arrays.
     *
     * @param string $jsonl_string A JSON Lines formatted string.
     *
     * @return array An array of associative arrays decoded from the JSON Lines input.
     */
    public static function jsonl_decode( string $jsonl_string ) : array {
        $lines   = explode( PHP_EOL, trim( $jsonl_string ) );
        $records = [];
        foreach ( $lines as $line ) {
            if ( ! empty( $line ) ) {
                $records[] = json_decode( $line, true );
            }
        }
        return $records;
    }

    /**
     * Adds a record.
     *
     * @param string $action Action (usually the same as webhook).
     * @param array  $data   Data to add.
     * @param string $hash   Hash.
     */
    private function add_a_record( string $action, array $data, string $hash ) {
        if ( ! $data ) { // Exit if no data (can happen when the product was deleted, and the hook before its deletion is being called).
            return;
        }

        // Make the feed dir, if it does not exist.

        $feed_dir = Feed_Generation_Buffer::get_feed_dir();

        if ( ! SpeedSearch::$fs->is_dir( $feed_dir ) ) {
            wp_mkdir_p( $feed_dir );
        }

        // Add a record.

        $file_index = (int) SpeedSearch::$options->get( 'feed-last-file-index' );

        $last_item_index_option_val = SpeedSearch::$options->get( 'feed-last-item-index' );
        $next_index                 = null === $last_item_index_option_val ? 0 : $last_item_index_option_val + 1;

        $next_file_lines = ( $next_index + 1 ) % self::FILE_MAX_LINES;
        if (
            $next_index + 1 > self::FILE_MAX_LINES && // Do not increment file index fo 0.json (otherwise it will be skipped and it will go to 1.json immediately).
            1 === $next_file_lines
        ) {
            $file_index ++;
        }

        $file_path = path_join( $feed_dir, $file_index . '.jsonl' );

        // First save the new indexes to minimize the chance of race conditions.
        SpeedSearch::$options->set( 'feed-last-file-index', $file_index );
        SpeedSearch::$options->set( 'feed-last-item-index', $next_index );

        $entry = [
            'index' => (int) $next_index,
            'name'  => $action,
            'data'  => $data,
            'hash'  => $hash,
        ];

        // Write to file.

        if ( file_exists( $file_path ) ) {
            $json_contents   = self::jsonl_decode( SpeedSearch::$fs->get_contents( $file_path ) );
            $json_contents[] = $entry;

            SpeedSearch::$fs->put_contents( $file_path, self::jsonl_encode( $json_contents ), 0644 );
        } else {
            SpeedSearch::$fs->put_contents( $file_path, self::jsonl_encode( [ $entry ] ), 0644 );
        }
    }

    /**
     * Removes all products hash.
     */
    public static function reset_feed() {

        // Unschedule all intervals so nothing interferes.

        self::unschedule_interval();

        // Delete related options.

        SpeedSearch::$options->delete( 'feed-last-file-index' );
        SpeedSearch::$options->delete( 'feed-last-item-index' );
        SpeedSearch::$options->delete( 'feed-generation-progress' );
        SpeedSearch::$options->delete( 'initial-feed-generation-complete' );
        SpeedSearch::$options->delete( 'initial-feed-generation-complete-on-index' );

        // Remove feed dir with all data.

        $feed_dir = Feed_Generation_Buffer::get_feed_dir();

        array_map( [ SpeedSearch::$fs, 'delete' ], glob( "$feed_dir/*.*" ) );
        SpeedSearch::$fs->delete( $feed_dir );

        // Deletes hash for products on plugin deactivation.

        $all_products_ids = get_posts(
            [
                'post_type'   => 'product',
                'numberposts' => -1,
                'fields'      => 'ids',
            ]
        );
        foreach ( $all_products_ids as $product_id ) {
            delete_post_meta( $product_id, self::FEED_HANDLED_META_NAME );
            delete_post_meta( $product_id, 'speedsearch_product_last_modified_time' );
            delete_post_meta( $product_id, 'speedsearch-add-to-feed-buffer-last-time' );
        }

        // Truncate feed buffer table.

        \SpeedSearch\DB::truncate_feed_buffer_table( true );

        // Schedule a feed generation action to run immediately.

        as_enqueue_async_action( self::INTERVAL_HOOK_NAME, [], 'speedsearch', false, 9 );
    }

    /**
     * Adds a URL rewrite rule for the speedsearch-feed endpoint.
     */
    public function add_url_rewrite_rule() {
        add_rewrite_rule( '^speedsearch-feed/(\d+)/?$', 'index.php?speedsearch_feed=$matches[1]', 'top' );
        add_rewrite_tag( '%speedsearch_feed%', '(\d+)' );
    }

    /**
     * Returns the feed data since the provided index up to FILE_MAX_LINES.
     *
     * @param int $start_index The index to start reading the data from.
     *
     * @return array JSON file lines.
     */
    public function get_sync_feed( $start_index ) {
        $requested_lines = [];

        $start_file_number = (int) ( $start_index / self::FILE_MAX_LINES );
        $start_line_number = $start_index % self::FILE_MAX_LINES;
        $feed_dir          = Feed_Generation_Buffer::get_feed_dir();

        for ( $i = $start_file_number; $i <= $start_file_number + 1; $i++ ) {
            $file_path = path_join( $feed_dir, "$i.json" );

            if ( SpeedSearch::$fs->is_file( $file_path ) ) {
                $file_handle = fopen( $file_path, 'r' ); // @codingStandardsIgnoreLine
                if ( $file_handle ) {
                    $line_counter = 0;
                    while ( ( $line_data = fgets( $file_handle ) ) !== false ) { // @codingStandardsIgnoreLine
                        if ( $i === $start_file_number && $line_counter < $start_line_number ) {
                            $line_counter++;
                            continue;
                        }
                        if ( $i === $start_file_number + 1 && $line_counter >= $start_line_number ) {
                            break;
                        }
                        $line_data = trim( $line_data );
                        if ( $line_data ) {
                            $requested_lines[] = $line_data;
                        }
                        $line_counter++;
                    }
                    fclose( $file_handle ); // @codingStandardsIgnoreLine
                }
            } else {
                break;
            }
        }

        return $requested_lines;
    }


    /**
     * Handles the template redirect for speedsearch-feed requests.
     */
    public function template_redirect() {
        global $wp_query;

        if ( isset( $wp_query->query_vars['speedsearch_feed'] ) ) {
            $start_index = (int) $wp_query->query_vars['speedsearch_feed'];
            $content     = $this->get_sync_feed( $start_index );

            header( 'Content-Type: application/json; charset=utf-8' );
            header( "Content-Disposition: attachment; filename={$start_index}.json" );
            \SpeedSearch\Misc::send_no_cache_headers();

            foreach ( $content as $line ) {
                echo sanitize_user_field(
                    'speedsearch',
                    $line . PHP_EOL,
                    0,
                    'display'
                );
            }

            exit;
        }
    }

    /**
     * Prunes feed files.
     */
    public function prune_feed_files() {
        $feed_dir        = Feed_Generation_Buffer::get_feed_dir();
        $prune_file_path = path_join( $feed_dir, Feed_Generation_Buffer::PRUNE_FILE_NAME );

        if ( ! SpeedSearch::$fs->is_file( $prune_file_path ) ) {
            return;
        }

        $prune_file_new_path = path_join( $feed_dir, 'prune-' . time() . '.txt' );
        rename( $prune_file_path, $prune_file_new_path );
        $prune_data = file( $prune_file_new_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

        $prune_map = [];
        foreach ( $prune_data as $line ) {
            list( $index, $product_id ) = explode( ':', $line );
            $prune_map[ $product_id ]   = (int) $index;
        }

        $files = glob( path_join( $feed_dir, '*.jsonl' ) );

        foreach ( $files as $file_path ) {
            $json_contents = self::jsonl_decode( SpeedSearch::$fs->get_contents( $file_path ) );

            $json_contents_pruned = [];

            foreach ( $json_contents as $item ) {
                if (
                    isset( $item['name'] ) &&
                    str_starts_with( $item['name'], 'product' ) // 'product.created' / 'product.updated' / 'product.deleted' / 'product.restored'.
                ) {
                    $index = (int) $item['index'];
                    $data  = (array) $item['data'];

                    if ( ! isset( $data['id'] ) || ! isset( $prune_map[ $data['id'] ] ) || $index > $prune_map[ $data['id'] ] ) {
                        $json_contents_pruned[] = $item;
                    }
                } else {
                    $json_contents_pruned[] = $item;
                }
            }

            SpeedSearch::$fs->put_contents( $file_path, self::jsonl_encode( $json_contents_pruned ), 0644 );
        }

        SpeedSearch::$fs->delete( $prune_file_new_path );
    }
}
