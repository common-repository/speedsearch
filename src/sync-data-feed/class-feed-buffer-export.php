<?php
/**
 * Class for Feed Buffer Export.
 *
 * Many methods here are inspired by WC_CSV_Exporter class.
 *
 * @package Cache-Warmer
 */

namespace SpeedSearch\Sync_Data_Feed;

use Exception;

/**
 * Feed Buffer Export Class.
 */
final class Feed_Buffer_Export {

    /**
     * Listens for the export action.
     *
     * Accepts: $_GET['nonce']
     *          $_GET['cache-warmer-action']
     *
     * @throws Exception Exception.
     */
    public static function add_listeners() {
        if (
            isset( $_GET['nonce'], $_GET['speedsearch-action'] ) && // @codingStandardsIgnoreLine
            wp_verify_nonce( wp_unslash( $_GET['nonce'] ), 'speedsearch-menu' ) && // @codingStandardsIgnoreLine
            'export-feed-buffer-table' === wp_unslash( $_GET['speedsearch-action'] ) // @codingStandardsIgnoreLine
        ) {
            self::export();
        }
    }

    /**
     * Does the export.
     *
     * @throws Exception Exception.
     */
    private static function export() {
        self::send_headers();
        self::send_content();
        die();
    }

    /**
     * Returns JSON filename.
     *
     * @return string
     */
    private static function get_filename() {
        return 'feed-buffer-export-' . time() . '.csv';
    }

    /**
     * Sends the export headers.
     */
    private static function send_headers() {
        if ( function_exists( 'gc_enable' ) ) {
            gc_enable(); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.gc_enableFound
        }
        if ( function_exists( 'apache_setenv' ) ) {
            @apache_setenv( 'no-gzip', 1 ); // @codingStandardsIgnoreLine
        }
        @ini_set( 'zlib.output_compression', 'Off' ); // @codingStandardsIgnoreLine
        @ini_set( 'output_buffering', 'Off' ); // @codingStandardsIgnoreLine
        @ini_set( 'output_handler', '' ); // @codingStandardsIgnoreLine
        ignore_user_abort( true );
        if ( function_exists( 'wc_set_time_limit' ) ) {
            wc_set_time_limit( 0 );
        }
        if ( function_exists( 'wc_nocache_headers' ) ) {
            wc_nocache_headers();
        }
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . self::get_filename() );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
    }

    /**
     * Sends the export content.
     */
    private static function send_content() {
        global $wpdb;

        // Open output stream.
        $output = fopen( 'php://output', 'w' );

        // Get column headers dynamically from the database table structure.
        $table_name = $wpdb->speedsearch_feed_buffer;
        $result     = $wpdb->get_results( "DESCRIBE {$table_name}", ARRAY_A );
        $headers    = array_column( $result, 'Field' );

        // Write headers to CSV.
        fputcsv( $output, $headers );

        // Fetch data from database.
        $query   = "SELECT * FROM {$table_name}";
        $results = $wpdb->get_results( $query, ARRAY_A );

        // Loop over rows and output them as CSV.
        foreach ( $results as $row ) {
            fputcsv( $output, $row );
        }

        // Close output stream and exit.
        fclose( $output );
        exit;
    }
}
