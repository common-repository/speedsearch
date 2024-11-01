<?php
/**
 * A custom webhook to SpeedSearch BE on meta change.
 *
 * Topics can be (payload is $term_id):
 *
 * `tag.updated`
 * `tag.deleted`
 * `category.updated`
 * `category.deleted`
 * `attribute_term.updated`
 * `attribute_term.deleted`
 *
 * and (payload is $attribute_id):
 *
 * `attribute.updated`
 * `attribute.deleted`
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Webhooks;

use Exception;
use SpeedSearch\SpeedSearch;
use SpeedSearch\Sync_Data_Feed\Sync_Data_Feed;

/**
 * Manages functionality to notify SpeedSearch BE about changed products meta (attributes, categories, tags).
 */
final class Meta_Change_Webhooks {

    /**
     * Constructor.
     */
    public function __construct() {

        // Adds webhooks.

        add_filter( 'woocommerce_valid_webhook_resources', [ $this, 'add_new_topic_resources' ] );
        add_filter( 'woocommerce_webhook_topics', [ $this, 'add_new_webhook_topics' ] );
        add_filter( 'woocommerce_webhook_topic_hooks', [ $this, 'add_new_topic_hooks' ] );
        add_filter( 'woocommerce_webhook_payload', [ $this, 'add_payload' ], 10, 4 );

        $this->add_terms_changed_listeners();
    }

    /**
     * Add listeners for when the terms were changed.
     *
     * Should have a parity with {@see Sync_Data_Feed::add_terms_changed_listeners()}
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
     * Adds new valid topic resources.
     *
     * @param array $topic_events Existing valid events for resources.
     */
    public function add_new_topic_resources( array $topic_events ) {
        $new_resources = [
            'tag',
            'category',
            'attribute_term',
            'attribute',
        ];
        return array_merge( $topic_events, $new_resources );
    }

    /**
     * Adds new webhooks to the dropdown list on the WC Webhooks page.
     *
     * @param array $topics Array of topics with the i18n proper name.
     */
    public function add_new_webhook_topics( array $topics ) {

        // New topic array to add to the list, must match hooks being created.
        $new_topics = [
            'tag.updated'            => __( 'Tag updated', 'speedsearch' ),
            'tag.deleted'            => __( 'Tag deleted', 'speedsearch' ),
            'category.updated'       => __( 'Category updated', 'speedsearch' ),
            'category.deleted'       => __( 'Category deleted', 'speedsearch' ),
            'attribute_term.updated' => __( 'Attribute term updated', 'speedsearch' ),
            'attribute_term.deleted' => __( 'Attribute term deleted', 'speedsearch' ),
            'attribute.updated'      => __( 'Attribute updated', 'speedsearch' ),
            'attribute.deleted'      => __( 'Attribute deleted', 'speedsearch' ),
        ];

        return array_merge( $topics, $new_topics );
    }

    /**
     * New webhooks.
     *
     * An array that has the topic as resource.event with arrays of actions that call that topic.
     */
    const NEW_WEBHOOKS = [
        'tag.updated'            => [
            'speedsearch_tag_updated',
        ],
        'tag.deleted'            => [
            'speedsearch_tag_deleted',
        ],
        'category.updated'       => [
            'speedsearch_category_updated',
        ],
        'category.deleted'       => [
            'speedsearch_category_deleted',
        ],
        'attribute_term.updated' => [
            'speedsearch_attribute_term_updated',
        ],
        'attribute_term.deleted' => [
            'speedsearch_attribute_term_deleted',
        ],
        'attribute.updated'      => [
            'speedsearch_attribute_updated',
        ],
        'attribute.deleted'      => [
            'speedsearch_attribute_deleted',
        ],
    ];

    /**
     * Adds new webhook topics hooks.
     *
     * @param array $topic_hooks Existing topic hooks.
     */
    public function add_new_topic_hooks( array $topic_hooks ) {
        return array_merge( $topic_hooks, self::NEW_WEBHOOKS );
    }

    /**
     * Adds custom payload for the webhooks.
     *
     * @param mixed  $payload     Payload to be sent.
     * @param string $resource    Resource name (e.g. 'tag', 'coupon').
     * @param mixed  $resource_id First hook argument, typically the resource ID.
     * @param int    $webhook_id  ID of the webhook.
     *
     * @return mixed Payload.
     * @throws Exception Exception.
     */
    public function add_payload( $payload, $resource, $resource_id, $webhook_id ) {
        $speedsearch_webhooks_ids = SpeedSearch::$options->get( Webhooks::ALL_SPEEDSEARCH_WEBHOOKS_OPTION_NAME );

        if ( $speedsearch_webhooks_ids ) {
            if ( in_array( $resource, [ 'tag', 'category', 'attribute' ], true ) ) { // Meta resource type.
                $payload = [ 'id' => $resource_id ];
            } elseif ( 'attribute_term' === $resource ) {
                $term = get_term( $resource_id );

                $payload = [
                    'id'          => $resource_id,
                    'attributeId' => wc_attribute_taxonomy_id_by_name( $term->taxonomy ),
                    'name'        => $term->name,
                ];
            }
        }

        return $payload;
    }

    /**
     * Get WooCommerce taxonomy type by term ID.
     *
     * @param int $term_id Term ID.
     *
     * @return string|bool Type of term ID, or false if the term ID is no of the Woo (e.g. post tag).
     *                     If a string, then can be "tag", "category" or "attribute_term".
     */
    public static function get_wc_tax_type_by_term_id( $term_id ) {
        $term = get_term( $term_id );

        $type = false;

        if ( $term ) {
            $taxonomy_name = $term->taxonomy;

            if ( 'product_tag' === $taxonomy_name ) {
                $type = 'tag';
            } elseif ( 'product_cat' === $taxonomy_name ) {
                $type = 'category';
            } elseif ( 'pa_' === substr( $taxonomy_name, 0, 3 ) ) {
                $type = 'attribute_term';
            }
        }

        return $type;
    }

    /**
     * Sends a webhook when the term was created/changed.
     *
     * @param int $term_id Term ID.
     */
    public function term_updated( $term_id ) {
        $type = $this->get_wc_tax_type_by_term_id( $term_id );

        if ( $type ) {
            do_action( "speedsearch_${type}_updated", $term_id );
        }
    }

    /**
     * Sends a webhook when the term was deleted.
     *
     * @param int $term_id Term ID.
     */
    public function term_deleted( $term_id ) {
        $type = $this->get_wc_tax_type_by_term_id( $term_id );

        if ( $type ) {
            do_action( "speedsearch_${type}_deleted", $term_id );
        }
    }

    /**
     * Sends a webhook when the attribute was created/changed.
     *
     * @param int $attribute_id Updated attribute ID.
     */
    public function attribute_changed( $attribute_id ) {
        do_action( 'speedsearch_attribute_updated', $attribute_id );
    }

    /**
     * Sends a webhook when the attribute was deleted.
     *
     * @param int $attribute_id Updated attribute ID.
     */
    public function attribute_deleted( $attribute_id ) {
        do_action( 'speedsearch_attribute_deleted', $attribute_id );
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
            do_action( 'speedsearch_attribute_term_updated', $object_id );
        }
    }
}
