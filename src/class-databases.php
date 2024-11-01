<?php
/**
 * Custom database tables.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use SpeedSearch\Sync_Data_Feed\Sync_Data_Feed_Hashes;

/**
 * Manages custom DB tables.
 */
final class DB {

    /**
     * Custom tables prefix.
     */
    const TABLES_PREFIX = 'speedsearch_';

    /**
     * Constructor (initialize tables).
     */
    public function __construct() {
        $this->init_tables();
    }

    /**
     * Inits tables.
     */
    public function init_tables() {
        global $wpdb;

        $tables_prefix = self::get_tables_prefix();

        foreach ( self::get_tables() as $table ) {
            $wpdb->{"speedsearch_$table"} = "$tables_prefix$table";
        }
    }

    /**
     * Creates/modifies database tables for the plugin.
     *
     * TODO: Delete the previous 'migrations' logic from other parts of the plugin (as we use DB delta now).
     *
     * @see WC_Install::create_tables()
     */
    public static function db_delta() {
        global $wpdb;
        $wpdb->hide_errors();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // DB Delta.

        dbDelta( self::get_schema() );
    }

    /**
     * Return a list of Cache-Warmer tables.
     *
     * Parent tables are above children tables.
     *
     * @return array Tables list.
     */
    public static function get_tables() {
        return [
            'feed_buffer',
        ];
    }

    /**
     * Return full tables prefix.
     *
     * @return string Tables prefix.
     */
    public static function get_tables_prefix() {
        global $wpdb;
        return $wpdb->prefix . self::TABLES_PREFIX;
    }

    /**
     * Returns tables schema.
     */
    public static function get_schema() {
        global $wpdb;

        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }

        /**
         * - warm_ups_logs - log_phase:
         *
         * 0 - default warm up.
         * 1 - when trying to fetch failed to retrieve links for the last time.
         *
         * INSERT IGNORE - ID 0 is for logs for unscheduled warm-ups.
         */
        $tables = "
            CREATE TABLE {$wpdb->speedsearch_feed_buffer} (
              id BIGINT UNSIGNED AUTO_INCREMENT,
              `created` DATETIME(6) DEFAULT NOW(6),
              `action` VARCHAR(500) NOT NULL,
              `object_id` VARCHAR(500) NOT NULL,
              `object_type` ENUM('post', 'attribute', 'term', '') NOT NULL,
              `hash` VARCHAR(40) NOT NULL, -- Hash on the data.
              `sha_object` VARCHAR(40) NOT NULL, -- Hash on the content (content added via PHP, then processed with CONCAT_WS('|',)).
              `sha_meta` VARCHAR(40) NOT NULL, -- SHA sum of object metadata.
              `data` LONGTEXT NOT NULL,
              `opened` DATETIME(6) DEFAULT NULL,
              `written` TINYINT NOT NULL DEFAULT 0,
              PRIMARY KEY (id)
            ) $collate ENGINE=InnoDB
        ";

        return $tables;
    }

    /**
     * Creates a row in the feed_buffer table.
     *
     * This method takes data, and inserts a new row into the feed_buffer table.
     *
     * @param string $action      Action (usually the same as webhook).
     * @param int    $object_id   ID of the object.
     * @param string $object_type Object type.
     * @param array  $data        An array of the data to add.
     * @param string $hash        Hash to add.
     *
     * @return void
     */
    public static function create_buffer_row(
        string $action,
        int $object_id,
        string $object_type,
        array $data,
        string $hash
    ) {
        global $wpdb;

        // Calculate sha_object based on fields_for_sha1.
        $concat_fields    = implode(
            ', ',
            array_map(
                function( $v ) {
                    return 't2.' . $v;
                },
                'post' === $object_type ?
                    Sync_Data_Feed_Hashes::PRODUCT_HASH_FIELDS :
                    (
                        'term' === $object_type ?
                            Sync_Data_Feed_Hashes::TERM_HASH_FIELDS :
                            Sync_Data_Feed_Hashes::ATTRIBUTES_HASH_FIELDS // Attribute.
                    )
            )
        );
        $sha1_calculation = "SHA1(CONCAT_WS('|', $concat_fields))";

        if ( 'post' === $object_type ) {
            $meta_keys_whitelist_string = "'" . implode( "', '", Sync_Data_Feed_Hashes::PRODUCT_META_HASH_FIELDS ) . "'";

            $query = $wpdb->prepare(
                "
                INSERT INTO {$wpdb->speedsearch_feed_buffer} (action, object_id, object_type, data, hash, sha_object, sha_meta)
                SELECT %s, %s, %s, %s, %s, $sha1_calculation, SHA1(GROUP_CONCAT(CONCAT_WS('|', t3.meta_key, t3.meta_value) ORDER BY t3.meta_id ASC SEPARATOR '|'))
                FROM {$wpdb->posts} t2
                JOIN {$wpdb->postmeta} t3 ON t2.ID = t3.post_id AND t3.meta_key IN ($meta_keys_whitelist_string)
                WHERE t2.ID = %d
                ",
                $action,
                $object_id,
                $object_type,
                maybe_serialize( $data ),
                $hash,
                $object_id
            );
        } elseif ( 'term' === $object_type ) {
            $meta_keys_whitelist_string = "'" . implode( "', '", Sync_Data_Feed_Hashes::TERM_META_HASH_FIELDS ) . "'";

            $query = $wpdb->prepare(
                "
                INSERT INTO {$wpdb->speedsearch_feed_buffer} (action, object_id, object_type, data, hash, sha_object, sha_meta)
                SELECT %s, %s, %s, %s, %s, $sha1_calculation, SHA1(GROUP_CONCAT(CONCAT_WS('|', t3.meta_key, t3.meta_value) ORDER BY t3.meta_id ASC SEPARATOR '|'))
                FROM {$wpdb->terms} t2
                JOIN {$wpdb->termmeta} t3 ON t2.term_id = t3.term_id AND t3.meta_key IN ($meta_keys_whitelist_string)
                WHERE t2.term_id = %d
                ",
                $action,
                $object_id,
                $object_type,
                maybe_serialize( $data ),
                $hash,
                $object_id
            );
        } else { // Attribute.
            $query = $wpdb->prepare(
                "
                INSERT INTO {$wpdb->speedsearch_feed_buffer} (action, object_id, object_type, data, hash, sha_object, sha_meta)
                SELECT %s, %s, %s, %s, %s, $sha1_calculation, ''
                FROM {$wpdb->prefix}woocommerce_attribute_taxonomies t2
                WHERE t2.attribute_id = %d
                ",
                $action,
                $object_id,
                $object_type,
                maybe_serialize( $data ),
                $hash,
                $object_id
            );
        }

        // Execute the query.
        $wpdb->query( $query );
    }

    /**
     * Retrieves and processes a specified number of records from the feed_buffer table.
     * It fetches records where `written` is 0 and `opened` is NULL and then updates the 'opened' field to the current time.
     *
     * @param int $number The number of records to process.
     *
     * @return array The array of retrieved records.
     */
    public static function get_feed_buffer_unwritten_records( int $number ): array {
        global $wpdb;

        // Begin transaction.
        $wpdb->query( 'START TRANSACTION' ); // @codingStandardsIgnoreLine

        try {
            // Fetch records where 'opened' is NULL.

            $records = $wpdb->get_results( // @codingStandardsIgnoreLine
                $wpdb->prepare(
                    "
                        SELECT id, `action`, data, hash
                        FROM {$wpdb->speedsearch_feed_buffer}
                        WHERE written = 0 AND (opened IS NULL OR opened <= NOW() - INTERVAL 1 HOUR)
                        ORDER BY id LIMIT %d FOR UPDATE", // @codingStandardsIgnoreLine
                    $number
                ),
                ARRAY_A
            );

            // Update 'opened' field for each record.
            foreach ( $records as $record ) {
                $wpdb->query( // @codingStandardsIgnoreLine
                    $wpdb->prepare(
                        "UPDATE {$wpdb->speedsearch_feed_buffer} SET opened = NOW(6) WHERE id = %d", // @codingStandardsIgnoreLine
                        $record['id']
                    )
                );
            }

            // Commit the transaction.
            $wpdb->query( 'COMMIT' ); // @codingStandardsIgnoreLine

            return $records;
        } catch ( \Exception $e ) {
            // Roll back the transaction on error.
            $wpdb->query( 'ROLLBACK' ); // @codingStandardsIgnoreLine

            return [];
        }
    }

    /**
     * Truncates feed buffer table.
     *
     * @param bool $retain_deleted_rows Whether to retain the product.deleted rows.
     */
    public static function truncate_feed_buffer_table( bool $retain_deleted_rows = false ) {
        global $wpdb;

        if ( $retain_deleted_rows ) {
            $wpdb->query( "DELETE FROM {$wpdb->speedsearch_feed_buffer} WHERE `action` != 'product.deleted';" ); // @codingStandardsIgnoreLine
            $wpdb->query( "UPDATE {$wpdb->speedsearch_feed_buffer} SET `opened` = NULL, `written` = 0 WHERE `action` = 'product.deleted';" ); // @codingStandardsIgnoreLine
        } else {
            $wpdb->query( "TRUNCATE TABLE {$wpdb->speedsearch_feed_buffer}" ); // @codingStandardsIgnoreLine
        }
    }

    /**
     * Marks a record in the feed_buffer table as written by setting its 'written' field to 1.
     *
     * This method updates the 'written' field of a specific record, identified by its ID,
     * in the feed_buffer table, marking it as written.
     *
     * @param int $id The ID of the record to be updated.
     *
     * @return bool True on success, false on failure.
     */
    public static function mark_buffer_feed_row_as_written( int $id ): bool {
        global $wpdb;

        // Prepare the SQL query to update the 'written' field.
        $result = $wpdb->update( // @codingStandardsIgnoreLine
            $wpdb->speedsearch_feed_buffer,
            [ 'written' => 1 ], // Columns to update.
            [ 'id' => $id ],    // Where clause to identify the record.
            [ '%d' ],           // Format of the columns to update.
            [ '%d' ]            // Format of the where clause.
        );

        return false !== $result;
    }

    /**
     * Gets the count of rows in the feed_buffer table.
     *
     * @return int The count of rows.
     */
    public static function get_feed_buffer_count(): int {
        global $wpdb;

        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->speedsearch_feed_buffer}" ); // Use get_var to get the first value from the first row of the result set.

        return (int) $count;
    }
}
