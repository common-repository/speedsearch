<?php
/**
 * Sync data feed hashes.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Sync_Data_Feed;

/**
 * A class for sync data feed hashes logic.
 */
final class Sync_Data_Feed_Hashes {

    /**
     * The list of fields for products hash.
     */
    const PRODUCT_HASH_FIELDS = [
        'ID',
        'post_author',
        'post_date_gmt',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'post_name',
        'post_modified_gmt',
        'post_content_filtered',
        'post_parent',
        'guid',
        'menu_order',
        'post_type',
        'post_mime_type',
    ];

    /**
     * The list of products meta fields for meta hash calculation.
     */
    const PRODUCT_META_HASH_FIELDS = [
        '_sku',
        '_regular_price',
        '_sale_price',
        '_tax_status',
        '_manage_stock',
        '_backorders',
        '_sold_individually',
        '_length',
        '_width',
        '_height',
        '_virtual',
        '_downloadable',
        '_download_limit',
        '_download_expiry',
        '_stock_status',
        '_product_attributes',
        '_price',
    ];

    /**
     * The list of fields for term hash.
     */
    const TERM_HASH_FIELDS = [
        'term_id',
        'name',
        'slug',
        'term_group',
    ];

    /**
     * The list of terms meta fields for meta hash calculation.
     */
    const TERM_META_HASH_FIELDS = [
        'order',
    ];

    /**
     * The list of fields for attributes hash.
     */
    const ATTRIBUTES_HASH_FIELDS = [
        'id'           => 'attribute_id',
        'name'         => 'attribute_label',
        'slug'         => 'attribute_name',
        'type'         => 'attribute_type',
        'order_by'     => 'attribute_orderby',
        'has_archives' => 'attribute_public',
    ];

    /**
     * Do the logic on plugin activation.
     *
     * @return void
     */
    public static function do_the_logic() {

        // Add deleted products as 'product.deleted'.

        $deleted_products = self::get_deleted_products();
        foreach ( $deleted_products as $deleted_product ) {
            $product_id   = (int) $deleted_product['id'];
            $product_data = maybe_unserialize( $deleted_product['data'] );

            Feed_Generation_Buffer::add_to_feed_generation_buffer(
                'product.deleted',
                $product_id,
                $product_data
            );
        }

        // Update the products that have to be updated.
        $product_ids_to_update = self::get_product_ids_to_regenerate_hash_for();
        foreach ( $product_ids_to_update as $product_id ) {
            as_enqueue_async_action( Sync_Data_Feed::PRODUCT_UPDATED_HOOK_NAME, [ $product_id ], 'speedsearch', true );
        }

        // Update the terms that have to be updated.
        $term_ids_to_update = self::get_term_ids_to_regenerate_hash_for();
        foreach ( $term_ids_to_update as $term_id ) {
            as_enqueue_async_action( Sync_Data_Feed::TERM_UPDATED_HOOK_NAME, [ $term_id ], 'speedsearch', true );
        }

        // Update the attributes that have to be updated.
        $attribute_ids_to_update = self::get_attribute_ids_to_regenerate_hash_for();
        foreach ( $attribute_ids_to_update as $attribute_id ) {
            as_enqueue_async_action( Sync_Data_Feed::ATTRIBUTE_UPDATED_HOOK_NAME, [ $attribute_id ], 'speedsearch', true );
        }
    }

    /**
     * Get the newly added product, that are not in the feed yet.
     *
     * @return array The list of product IDs.
     */
    public static function get_newly_added_products() {
        global $wpdb;

        $query = "
            SELECT p.ID FROM {$wpdb->posts} p
            WHERE
                p.post_type = 'product' AND
                p.post_status = 'publish' AND
                p.ID NOT IN (SELECT object_id FROM {$wpdb->speedsearch_feed_buffer} WHERE object_type = 'post')
        ";

        return array_map( 'intval', $wpdb->get_col( $query ) );
    }

    /**
     * Get the list of product IDs that were modified since the last time, based on their fields.
     *
     * @return array The list of product IDs.
     */
    public static function get_modified_product_ids_by_fields() {
        global $wpdb;

        // Calculate sha_object based on fields_for_sha1.
        $concat_fields    = implode(
            ', ',
            array_map(
                function( $v ) {
                    return 'p.' . $v;
                },
                self::PRODUCT_HASH_FIELDS
            )
        );
        $sha1_calculation = "SHA1(CONCAT_WS('|', $concat_fields))";

        $query = "
            SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->speedsearch_feed_buffer} b1 ON
                p.ID = b1.object_id AND
                b1.object_type = 'post'
            LEFT JOIN {$wpdb->speedsearch_feed_buffer} b2 ON
                b1.object_id = b2.object_id AND
                b1.object_type = b2.object_type AND
                b1.id < b2.id
            LEFT JOIN {$wpdb->speedsearch_feed_buffer} b3 ON
                b1.object_id = b3.object_id AND
                b1.object_type = b3.object_type AND
                b1.id < b3.id AND
                b2.id < b3.id
            WHERE p.post_type = 'product' AND
                  p.post_status = 'publish' AND
                  (
                    (
                        b2.id IS NOT NULL AND
                        b3.id IS NULL AND
                        b2.sha_object != $sha1_calculation
                    ) OR
                    (
                        b2.id IS NULL AND
                        b1.sha_object != $sha1_calculation
                    )
                  )
        ";

        return array_map( 'intval', $wpdb->get_col( $query ) );
    }

    /**
     * Get the list of product IDs that were modified since the last time, based on their metadata.
     *
     * @return array The list of product IDs.
     */
    public static function get_modified_product_ids_by_meta() {
        global $wpdb;

        $meta_keys_whitelist_string = "'" . implode( "', '", self::PRODUCT_META_HASH_FIELDS ) . "'";

        $query = "            
            SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN (
                SELECT pm.post_id, 
                       SHA1(GROUP_CONCAT(CONCAT_WS('|', pm.meta_key, pm.meta_value) ORDER BY pm.meta_id ASC SEPARATOR '|')) AS current_meta_sha1
                FROM {$wpdb->postmeta} pm
                WHERE pm.meta_key IN ($meta_keys_whitelist_string)
                GROUP BY pm.post_id
            ) AS pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->speedsearch_feed_buffer} b1 ON 
                p.ID = b1.object_id AND
                b1.object_type = 'post'
            LEFT JOIN {$wpdb->speedsearch_feed_buffer} b2 ON 
                b1.object_id = b2.object_id AND 
                b1.object_type = b2.object_type AND 
                b1.id < b2.id
            LEFT JOIN {$wpdb->speedsearch_feed_buffer} b3 ON 
                b1.object_id = b3.object_id AND 
                b1.object_type = b3.object_type AND 
                b1.id < b3.id AND 
                b2.id < b3.id
            WHERE p.post_type = 'product' AND
                  p.post_status = 'publish' AND
                  (
                    (
                        b2.id IS NOT NULL AND
                        b3.id IS NULL AND
                        b2.sha_meta != pm.current_meta_sha1
                    ) OR
                    (
                        b2.id IS NULL AND
                        b1.sha_meta != pm.current_meta_sha1
                    )
                  )
        ";

        return array_map( 'intval', $wpdb->get_col( $query ) );
    }

    /**
     * Returns the list of product IDs to regenerate the hash for.
     *
     * @return int[] Array.
     */
    public static function get_product_ids_to_regenerate_hash_for() {
        return array_unique(
            array_merge(
                self::get_newly_added_products(),
                self::get_modified_product_ids_by_fields(),
                self::get_modified_product_ids_by_meta()
            )
        );
    }

    /**
     * Get the list of term IDs that were modified since the last time, based on their fields.
     *
     * @return array The list of term IDs.
     */
    public static function get_modified_term_ids_by_fields() {
        global $wpdb;

        // Calculate sha_object based on fields_for_sha1.
        $concat_fields    = implode(
            ', ',
            array_map(
                function( $v ) {
                    return 't.' . $v;
                },
                self::TERM_HASH_FIELDS
            )
        );
        $sha1_calculation = "SHA1(CONCAT_WS('|', $concat_fields))";

        $query = "
            SELECT DISTINCT t.term_id
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->speedsearch_feed_buffer} b1 ON 
                t.term_id = b1.object_id AND
                b1.object_type = 'term'
            LEFT JOIN {$wpdb->speedsearch_feed_buffer} b2 ON 
                b1.object_id = b2.object_id AND 
                b1.object_type = b2.object_type AND 
                b1.id < b2.id
            LEFT JOIN {$wpdb->speedsearch_feed_buffer} b3 ON 
                b1.object_id = b3.object_id AND 
                b1.object_type = b3.object_type AND 
                b1.id < b3.id AND 
                b2.id < b3.id
            WHERE
                (
                    b2.id IS NOT NULL AND
                    b3.id IS NULL AND
                    b2.sha_object != $sha1_calculation
                ) OR
                (
                    b2.id IS NULL AND
                    b1.sha_object != $sha1_calculation
                )
        ";

        return array_map( 'intval', $wpdb->get_col( $query ) );
    }

    /**
     * Get the list of term IDs that were modified since the last time, based on their metadata.
     *
     * @return array The list of product IDs.
     */
    public static function get_modified_term_ids_by_meta() {
        global $wpdb;

        $meta_keys_whitelist_string = "'" . implode( "', '", self::TERM_META_HASH_FIELDS ) . "'";

        $query = "
            SELECT DISTINCT t.term_id
            FROM {$wpdb->terms} t
            INNER JOIN (
                SELECT tm.term_id, 
                       SHA1(GROUP_CONCAT(CONCAT_WS('|', tm.meta_key, tm.meta_value) ORDER BY tm.meta_id ASC SEPARATOR '|')) AS current_meta_sha1
                FROM {$wpdb->termmeta} tm
                WHERE tm.meta_key IN ($meta_keys_whitelist_string)
                GROUP BY tm.term_id
            ) AS tm ON t.term_id = tm.term_id
            INNER JOIN {$wpdb->speedsearch_feed_buffer} b1 ON 
                t.term_id = b1.object_id AND
                b1.object_type = 'term'
            LEFT JOIN {$wpdb->speedsearch_feed_buffer} b2 ON 
                b1.object_id = b2.object_id AND 
                b1.object_type = b2.object_type AND 
                b1.id < b2.id
            LEFT JOIN {$wpdb->speedsearch_feed_buffer} b3 ON 
                b1.object_id = b3.object_id AND 
                b1.object_type = b3.object_type AND 
                b1.id < b3.id AND 
                b2.id < b3.id
            WHERE
                (
                    b2.id IS NOT NULL AND
                    b3.id IS NULL AND
                    b2.sha_meta != tm.current_meta_sha1
                ) OR
                (
                    b2.id IS NULL AND
                    b1.sha_meta != tm.current_meta_sha1
                )
        ";

        return array_map( 'intval', $wpdb->get_col( $query ) );
    }

    /**
     * Returns the list of term IDs to regenerate the hash for.
     *
     * @return int[] Array.
     */
    public static function get_term_ids_to_regenerate_hash_for() {
        return array_unique(
            array_merge(
                self::get_modified_term_ids_by_fields(),
                self::get_modified_term_ids_by_meta()
            )
        );
    }

    /**
     * Get the list of attribute IDs that were modified since the last time, based on their fields.
     *
     * @return array The list of attribute IDs.
     */
    public static function get_modified_attribute_ids_by_fields() {
        global $wpdb;

        // Calculate sha_object based on fields_for_sha1.
        $concat_fields    = implode(
            ', ',
            array_map(
                function( $v ) {
                    return 'a.' . $v;
                },
                self::ATTRIBUTES_HASH_FIELDS
            )
        );
        $sha1_calculation = "SHA1(CONCAT_WS('|', $concat_fields))";

        $query = "
            SELECT DISTINCT a.attribute_id
            FROM {$wpdb->prefix}woocommerce_attribute_taxonomies a
            INNER JOIN {$wpdb->speedsearch_feed_buffer} b1 ON 
                a.attribute_id = b1.object_id AND
                b1.object_type = 'attribute'
            LEFT JOIN {$wpdb->speedsearch_feed_buffer} b2 ON 
                b1.object_id = b2.object_id AND 
                b1.object_type = b2.object_type AND 
                b1.id < b2.id
            LEFT JOIN {$wpdb->speedsearch_feed_buffer} b3 ON 
                b1.object_id = b3.object_id AND 
                b1.object_type = b3.object_type AND 
                b1.id < b3.id AND 
                b2.id < b3.id
            WHERE 
                (
                    b2.id IS NOT NULL AND
                    b3.id IS NULL AND
                    b2.sha_object != $sha1_calculation
                ) OR
                (
                    b2.id IS NULL AND
                    b1.sha_object != $sha1_calculation
                )
        ";

        return array_map( 'intval', $wpdb->get_col( $query ) );
    }

    /**
     * Returns the list of term IDs to regenerate the hash for.
     *
     * @return int[] Array.
     */
    public static function get_attribute_ids_to_regenerate_hash_for() {
        return self::get_modified_attribute_ids_by_fields();
    }

    /**
     * Get the deleted product, that are in the feed, but not present in WP yet.
     *
     * @return array The list of deleted product, with 2 values: ID and data.
     */
    public static function get_deleted_products() {
        global $wpdb;

        $query = "            
            SELECT b.object_id AS `id`, b.data AS `data`
            FROM {$wpdb->speedsearch_feed_buffer} b
            LEFT JOIN {$wpdb->speedsearch_feed_buffer} b2 
                ON b.object_id = b2.object_id 
                AND b.object_type = b2.object_type 
                AND b.id < b2.id
            LEFT JOIN {$wpdb->posts} p ON b.object_id = p.ID
            WHERE p.ID IS NULL 
                AND b.object_type = 'post' 
                AND b2.id IS NULL 
                AND b.action <> 'product.deleted'
        ";

        return $wpdb->get_results( $query, ARRAY_A );
    }
}
