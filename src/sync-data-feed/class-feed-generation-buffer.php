<?php
/**
 * Feed generation buffer logic.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Sync_Data_Feed;

use SpeedSearch\SpeedSearch;

/**
 * A class for feed generation buffer logic.
 */
final class Feed_Generation_Buffer {

    /**
     * Prune file name.
     */
    const PRUNE_FILE_NAME = 'prune.txt';

    /**
     * Returns the feed dir.
     *
     * @return string
     */
    public static function get_feed_dir() {
        $uploads_dir = path_join( wp_upload_dir()['basedir'], 'speedsearch' );
        return path_join( $uploads_dir, 'feed' );
    }

    /**
     * Adds product ID to the prune file.
     *
     * @param int|null $product_id The product id.
     * @param int      $index      The index of the line.
     */
    public static function add_to_prune_file( $product_id, $index = null ) {
        if ( null === $index ) {
            $index = SpeedSearch::$options->get( 'feed-last-item-index' );
        }

        $feed_dir = self::get_feed_dir();

        // Make the dir.

        if ( ! SpeedSearch::$fs->is_dir( $feed_dir ) ) {
            wp_mkdir_p( $feed_dir );
        }

        $prune_file_path = path_join( $feed_dir, self::PRUNE_FILE_NAME );

        // Get existing content.
        $existing_content = '';
        if ( SpeedSearch::$fs->exists( $prune_file_path ) ) {
            $existing_content = SpeedSearch::$fs->get_contents( $prune_file_path );
        }

        // Append new content.
        $new_content = $existing_content . $index . ':' . $product_id . PHP_EOL;

        SpeedSearch::$fs->put_contents( $prune_file_path, $new_content, 0644 ); // @codingStandardsIgnoreLine
    }

    /**
     * Adds a row to the feed generation buffer, which will be executed later, on the interval.
     *
     * @param string $action    Action (usually the same as webhook).
     * @param int    $object_id ID of the object.
     * @param array  $data   An array of the data to add.
     * @param bool   $on_feed_generation On feed generation (not on real webhook action).
     */
    public static function add_to_feed_generation_buffer(
        string $action,
        int $object_id,
        array $data = [],
        bool $on_feed_generation = false
    ) {
        $hash = sha1( wp_json_encode( $data ) );

        global $wpdb;

        $last_hash = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT hash FROM {$wpdb->speedsearch_feed_buffer} WHERE `action` = %s AND `object_id` = %d ORDER BY created DESC LIMIT 1",
                $action,
                $object_id
            )
        );

        if ( // Do not add duplicates.
            $last_hash &&
            $last_hash === $hash
        ) { // But because hashes do not contain everything within, we also check for meta hashes and field hashes, in case it was triggerred rightly.
            if ( str_starts_with( $action, 'attribute.' ) ) {
                $ids = Sync_Data_Feed_Hashes::get_attribute_ids_to_regenerate_hash_for();
            } elseif ( str_starts_with( $action, 'product.' ) ) {
                $ids = Sync_Data_Feed_Hashes::get_product_ids_to_regenerate_hash_for();
            } else {
                $ids = Sync_Data_Feed_Hashes::get_term_ids_to_regenerate_hash_for();
            }

            if ( ! in_array( $object_id, $ids, true ) ) {
                return;
            }
        }

        if (
            ! $on_feed_generation &&
            str_starts_with( $action, 'product' ) && // 'product.created' / 'product.updated' / 'product.deleted' / 'product.restored'.
            isset( $data['id'] )
        ) {
            self::add_to_prune_file( $data['id'] );
        }

        $object_type = str_starts_with( $action, 'attribute.' ) ?
            'attribute' :
            (
            str_starts_with( $action, 'product.' ) ?
                'post' :
                'term'
            );

        $fields_for_sha1 = [];
        if ( 'post' === $object_type ) {
            $product = get_post( $object_id, ARRAY_A );
            if ( $product ) {
                foreach ( Sync_Data_Feed_Hashes::PRODUCT_HASH_FIELDS as $field ) {
                    $fields_for_sha1[ $field ] = $product[ $field ] ?? '';
                }
            }
        } elseif ( 'term' === $object_type ) {
            $term = get_term( $object_id, '', ARRAY_A );
            if ( $term ) {
                foreach ( Sync_Data_Feed_Hashes::TERM_HASH_FIELDS as $field ) {
                    $fields_for_sha1[ $field ] = $term[ $field ] ?? '';
                }
            }
        } elseif ( 'attribute' === $object_type ) {
            foreach ( array_keys( Sync_Data_Feed_Hashes::ATTRIBUTES_HASH_FIELDS ) as $field ) {
                $fields_for_sha1[ $field ] = $data[ $field ] ?? '';

                if ( isset( $data[ $field ] ) ) {
                    if ( 'has_archives' === $field ) {
                        $fields_for_sha1[ $field ] = (int) $fields_for_sha1[ $field ];
                    }

                    if ( 'id' === $field ) {
                        $fields_for_sha1[ $field ] = (int) $fields_for_sha1[ $field ];
                    }
                }
            }
        }

        \SpeedSearch\DB::create_buffer_row(
            $action,
            $object_id,
            $object_type,
            $data,
            $hash
        );
    }
}
