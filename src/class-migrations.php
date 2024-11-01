<?php
/**
 * A class to manage migrations.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * Class Migrations.
 */
final class Migrations {

    /**
     * Constructor.
     *
     * @param int $last_handled_version_update Last handled version update.
     */
    public function __construct( $last_handled_version_update ) {
        if ( ! $last_handled_version_update ) { // No need to migrate for the fresh installation.
            return;
        }

        $methods_raw = array_values(
            array_filter(
                get_class_methods( $this ),
                function ( $method ) {
                    return str_starts_with( $method, 'v_' );
                }
            )
        );
        sort( $methods_raw );

        $versions_with_methods = [];
        foreach ( $methods_raw as $method ) {
            $version                           = str_replace( '_', '.', str_replace( 'v_', '', $method ) );
            $versions_with_methods[ $version ] = $method;
        }

        $versions_to_run_migrators_for = array_filter(
            array_keys( $versions_with_methods ),
            function ( $method ) use ( $last_handled_version_update ) {
                return 1 === version_compare( $method, $last_handled_version_update );
            }
        );

        foreach ( $versions_to_run_migrators_for as $version_to_run_migrator_for ) {
            $this->{$versions_with_methods[ $version_to_run_migrator_for ]}();
        }
    }

    /**
     * Deletes the feed DBs, and re-generates it, but with a new schema.
     */
    private function v_1_7_45() {
        global $wpdb;

        new DB();

        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->speedsearch_feed_buffer}" );

        DB::db_delta();

        add_action(
            'woocommerce_after_register_post_type',
            function() {
                Sync_Data_Feed\Sync_Data_Feed::reset_feed();
                Backend_Requests::force_sync();
            }
        );
    }
}
