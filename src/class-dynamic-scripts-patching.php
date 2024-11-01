<?php
/**
 * Dynamic Scripts Patching.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * A class for dynamic scripts patching.
 */
final class Dynamic_Scripts_Patching {

    /**
     * Interval.
     */
    const INTERVAL = DAY_IN_SECONDS;

    /**
     * Hook name of the interval.
     */
    const INTERVAL_HOOK_NAME = 'speedsearch_dynamic_scripts_patching_delete_old_files';

    /**
     * A list of patches.
     *
     * Array key is the JS script name, and the value is the list of replacements (where key is "from" and value is "to").
     *
     * @var array
     */
    public static $patches = [];

    /**
     * Init.
     */
    public function __construct() {
        add_filter( 'script_loader_src', [ $this, 'change_asset_src' ], 10, 2 );

        // Schedule an interval.

        add_action( self::INTERVAL_HOOK_NAME, [ $this, 'delete_old_files' ] );

        $schedule_interval_action = function() {
            as_schedule_recurring_action(
                time(),
                self::INTERVAL,
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

        register_deactivation_hook( SPEEDSEARCH_FILE, [ __CLASS__, 'unschedule_interval' ] );
    }

    /**
     * Patched scripts' dir.
     *
     * @return string
     */
    private static function get_patched_scripts_dir() {
        $uploads_dir = path_join( wp_upload_dir()['basedir'], 'speedsearch' );

        return path_join( $uploads_dir, 'patched-scripts' );
    }

    /**
     * Convert URL to path, inside wp-content dir.
     *
     * @param string $url A script URL.
     *
     * @return string A script path.
     */
    private static function url_to_path( $url ) {
        $url = explode( '?', $url ) [0];

        return str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $url );
    }

    /**
     * Dynamically patches the file, and replaces it.
     *
     * @param string $src Source (href) of the script.
     * @param string $handle Script handle.
     */
    public function change_asset_src( $src, $handle ) {
        if ( ! array_key_exists( $handle, self::$patches ) ) {
            return $src;
        }

        $patched_scripts_dir = self::get_patched_scripts_dir();

        // Make the dir.

        if ( ! SpeedSearch::$fs->is_dir( $patched_scripts_dir ) ) {
            wp_mkdir_p( $patched_scripts_dir );
        }

        $script_path = self::url_to_path( $src );

        $content = SpeedSearch::$fs->get_contents( $script_path );

        if ( false === $content ) {
            return $src;
        }

        $hash = sha1( $content . wp_json_encode( self::$patches[ $handle ] ) );

        $extension = pathinfo( wp_parse_url( $src, PHP_URL_PATH ) )['extension'];

        $destination_path = path_join( $patched_scripts_dir, $hash . '.' . $extension );

        if ( ! SpeedSearch::$fs->is_file( $destination_path ) ) { // If the file was not patched.

            // Do the patching.

            $new_content = str_replace(
                array_keys( self::$patches[ $handle ] ),
                array_values( self::$patches[ $handle ] ),
                $content
            );

            $was_content_added = SpeedSearch::$fs->put_contents(
                $destination_path,
                $new_content,
                0644
            );


            if ( false === $was_content_added ) {
                return $src;
            }

            // Save the file to the list of the patched files (to do the previous days pruning later).

            $list_of_patched_files = SpeedSearch::$options->get( 'dynamically-patched-scripts' );
            $today                 = wp_date( 'Y-m-d' );

            if ( ! isset( $list_of_patched_files[ $today ] ) ) { // When adding a file for a new day.
                $list_of_patched_files[ $today ] = [];
            }

            $list_of_patched_files[ $today ][] = $destination_path;

            SpeedSearch::$options->set( 'dynamically-patched-scripts', $list_of_patched_files );
        }

        return str_replace( wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $destination_path );
    }

    /**
     * Deletes files older than two days.
     */
    public function delete_old_files() {
        $list_of_patched_files = SpeedSearch::$options->get( 'dynamically-patched-scripts' );
        if ( empty( $list_of_patched_files ) ) {
            return;
        }

        $threshold_timestamp = strtotime( wp_date( 'Y-m-d', time() - DAY_IN_SECONDS * 2 ) );
        $is_updated          = false;

        foreach ( $list_of_patched_files as $date => $files ) {
            $date_timestamp = strtotime( $date );
            if ( $date_timestamp < $threshold_timestamp ) {
                foreach ( $files as $file ) {
                    if ( SpeedSearch::$fs->is_file( $file ) ) {
                        SpeedSearch::$fs->delete( $file );
                    }
                }

                unset( $list_of_patched_files[ $date ] );
                $is_updated = true;
            }
        }

        if ( $is_updated ) {
            SpeedSearch::$options->set( 'dynamically-patched-scripts', $list_of_patched_files );
        }
    }

    /**
     * Unschedule the interval.
     */
    public static function unschedule_interval() {
        as_unschedule_all_actions( self::INTERVAL_HOOK_NAME, [], 'speedsearch' );
        \SpeedSearch\Misc::stop_running_action_scheduler_action( self::INTERVAL_HOOK_NAME );
    }
}

