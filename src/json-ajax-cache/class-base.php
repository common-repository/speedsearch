<?php
/**
 * JSON (AJAX) Cache.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\JSON_AJAX_Cache;

use Exception;
use SpeedSearch\SpeedSearch;

/**
 * Base class for JSON (AJAX) Cache.
 */
final class Base {

    /**
     * JSON Cache responses settings filename.
     */
    const JSON_CACHE_RESPONSES_SETTINGS_FILENAME = 'json_cache_responses_settings';

    /**
     * SpeedSearch dir url within the uploads dir.
     *
     * @var string
     */
    public static $speedsearch_uploads_baseurl = '';

    /**
     * SpeedSearch dir path within the uploads dir.
     *
     * @var string
     */
    public static $speedsearch_uploads_basedir = '';

    /**
     * Fields class instance.
     *
     * @var Fields
     */
    private $fields;

    /**
     * Hook name of the interval.
     */
    const INTERVAL_HOOK_NAME = 'speedsearch_flush_html_cache';

    /**
     * Constructor.
     *
     * @throws Exception Exception.
     */
    public function __construct() {

        // Updates the file on cache flush fields change.
        $this->update_file_on_fields_change();

        $this->fields = new Fields();

        self::$speedsearch_uploads_baseurl = wp_upload_dir()['baseurl'] . '/speedsearch/';
        self::$speedsearch_uploads_basedir = wp_upload_dir()['basedir'] . '/speedsearch/';

        $this->update_last_flush_time_if_its_behind();
        $this->add_json_cache_responses_settings_file_if_it_not_exists();

        // Adds JSON Cache Settings retrieval script to the head.

        add_action( 'wp_head', [ __CLASS__, 'print_json_cache_settings_retrieval_script' ], 0 );
        add_action( 'admin_head', [ __CLASS__, 'print_json_cache_settings_retrieval_script' ], 0 );

        // If interval is changed.

        $this->handle_interval_change();

        /**
         * Schedule the cache flush interval.
         */

        // Action.

        add_action( self::INTERVAL_HOOK_NAME, [ 'SpeedSearch\Products_HTML_Cache', 'flush' ] );

        // Schedule an Interval.

        $interval_in_seconds = (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60;

        $schedule_interval_action = function() use ( $interval_in_seconds ) {
            as_schedule_recurring_action(
                time() + $interval_in_seconds,
                $interval_in_seconds,
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

        // Unschedule an interval on plugin deactivation.
        register_deactivation_hook( SPEEDSEARCH_FILE, [ __CLASS__, 'unschedule_interval' ] );
    }

    /**
     * Updates the file on cache flush fields change.
     */
    private function update_file_on_fields_change() {
        $option = Fields::OPTION_NAME;

        add_action( "add_option_$option", [ $this, 'update_json_cache_responses_settings_file' ] );
        add_action( "update_option_$option", [ $this, 'update_json_cache_responses_settings_file' ] );
        add_action( "delete_option_$option", [ $this, 'update_json_cache_responses_settings_file' ] );
    }

    /**
     * Handles interval change.
     *
     * Updates last flush time (if necessary) and
     * Updates JSON Cache settings file.
     *
     * @hook add_option_speedsearch-setting-cache-flush-interval
     * @hook update_option_speedsearch-setting-cache-flush-interval
     * @throws Exception Exception.
     */
    private function handle_interval_change() {
        $callback = function( $new_interval ) {

            // Update JSON AJAX cache last flush time if it's behind.

            $was_last_flush_time_changed_and_so_the_settings_file = $this->update_last_flush_time_if_its_behind();
            if ( ! $was_last_flush_time_changed_and_so_the_settings_file ) {
                $this->update_json_cache_responses_settings_file();
            }

            $interval_in_seconds = (int) $new_interval * 60;

            // Re-schedule products HTML cache flush interval.

            self::unschedule_interval();

            $schedule_interval_action = function() use ( $interval_in_seconds ) {
                as_schedule_recurring_action(
                    time() + $interval_in_seconds,
                    $interval_in_seconds,
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
        };

        add_action(
            'add_option_speedsearch-setting-cache-flush-interval',
            function( $option_name, $value ) use ( $callback ) {
                $callback( $value );
            },
            10,
            2
        );

        add_action(
            'update_option_speedsearch-setting-cache-flush-interval',
            function( $old_value, $value ) use ( $callback ) {
                if ( $value !== $old_value ) {
                    $callback( $value );
                }
            },
            10,
            2
        );
    }

    /**
     * Unschedule the interval.
     */
    public static function unschedule_interval() {
        as_unschedule_all_actions( self::INTERVAL_HOOK_NAME, [], 'speedsearch' );
        \SpeedSearch\Misc::stop_running_action_scheduler_action( self::INTERVAL_HOOK_NAME );
    }

    /**
     * Update last flush time if the next flush time is "behind" the current time.
     *
     * @return bool Whether the last flash time changed.
     * @throws Exception Exception.
     */
    public function update_last_flush_time_if_its_behind() {
        $last_flush_time     = (int) SpeedSearch::$options->get( 'json-cache-last-flush-time' );
        $interval_in_seconds = (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60;
        $next_flush_time     = $last_flush_time + $interval_in_seconds;

        if ( time() > $next_flush_time ) {
            if ( 0 === $last_flush_time ) { // If not set yet.
                $next_flush_time = time() + $interval_in_seconds;
            } else {
                while ( time() > $next_flush_time ) { // While the next flush is "behind".
                    $next_flush_time += $interval_in_seconds;
                }
            }

            $new_last_flush_time_val = $next_flush_time - $interval_in_seconds;
            if ( $new_last_flush_time_val > $last_flush_time ) { // Don't update "last flush time" backward (in case the interval increased).
                SpeedSearch::$options->set( 'json-cache-last-flush-time', $new_last_flush_time_val );

                $this->update_json_cache_responses_settings_file();

                return true;
            }
        }

        return false;
    }

    /**
     * Flushes the cache.
     *
     * Sets the last cache flush time to the current time.
     *
     * @throws Exception Exception.
     */
    public function flush() {
        SpeedSearch::$options->delete( 'json-cache-last-flush-time' );
        $this->update_last_flush_time_if_its_behind();
    }

    /**
     * Flushes the cache for the request hash.
     *
     * Updates "fields", by saving a hash of the request that lead to the error response:
     * So it will never be served from the CDN cache (because the .js file name will be different).
     *
     * @param string $request_hash Request hash.
     *
     * @throws Exception Exception.
     */
    public function flush_for_request_hash( $request_hash ) {
        $fields = $this->fields->get();

        if ( ! array_key_exists( 'hashes', $fields ) ) {
            $fields['hashes'] = [];
        }

        $fields['hashes'][ $request_hash ] = time();

        $this->fields->update( $fields );
    }

    /**
     * Adds JSON (AJAX) Cache settings file if it not exists.
     *
     * @throws Exception Exception.
     */
    public function add_json_cache_responses_settings_file_if_it_not_exists() {
        if ( ! SpeedSearch::$fs->is_file( self::$speedsearch_uploads_basedir . self::JSON_CACHE_RESPONSES_SETTINGS_FILENAME ) ) {
            $this->update_json_cache_responses_settings_file();
        }
    }

    /**
     * Updates JSON (AJAX) Cache settings file content.
     *
     * @throws Exception Exception.
     */
    public function update_json_cache_responses_settings_file() {
        $content = array_merge(
            [
                'flushInterval' => (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ),
                'lastFlushTime' => (int) SpeedSearch::$options->get( 'json-cache-last-flush-time' ),
            ],
            $this->fields->get()
        );
        $this->add_file_to_speedsearch_uploads_dir( self::JSON_CACHE_RESPONSES_SETTINGS_FILENAME, wp_json_encode( $content ) );
    }

    /**
     * Adds a specified file with the specified content to the speedsearch uploads dir.
     *
     * @param string $filename Name of the file to add.
     * @param string $content  File content.
     */
    private function add_file_to_speedsearch_uploads_dir( $filename, $content ) {
        if ( ! SpeedSearch::$fs->is_dir( self::$speedsearch_uploads_basedir ) ) {
            SpeedSearch::$fs->mkdir( self::$speedsearch_uploads_basedir );
        }

        SpeedSearch::$fs->put_contents( self::$speedsearch_uploads_basedir . $filename, $content, 0644 );
    }

    /**
     * Whether printed JSON Cache settings retrieval script.
     *
     * @var bool
     */
    public static $printed_json_cache_settings_retrieval_script = false;

    /**
     * Prints JSON cache settings script.
     */
    public static function print_json_cache_settings_retrieval_script() {
        if ( self::$printed_json_cache_settings_retrieval_script ) {
            return;
        } else {
            self::$printed_json_cache_settings_retrieval_script = true;
        }

        $speedsearch_files_dir        = self::$speedsearch_uploads_baseurl;
        $json_cache_settings_filename = self::JSON_CACHE_RESPONSES_SETTINGS_FILENAME;

        ob_start();

        ?>
        <script
        <?php
        ?>
        type='text/javascript'>
        /* <![CDATA[ */
        var speedsearch_filesDir = '<?php echo esc_html( $speedsearch_files_dir ); ?>';
        var speedsearch_jsonCacheSettingsFilename = '<?php echo esc_html( $json_cache_settings_filename ); ?>';
        /* ]]> */
        </script>
        <?php

        echo sanitize_user_field(
            'speedsearch',
            ( new \MatthiasMullie\Minify\JS( ob_get_clean() ) )->minify(),
            0,
            'display'
        );

        ?>
        <script
        <?php
        $script_src = esc_attr( SPEEDSEARCH_URL . 'assets-build/common/not-enqueued/json-cache-settings-retrieval.js?ver=' . SPEEDSEARCH_VERSION );
        echo sanitize_user_field( // Print a script part to pass the check.
            'speedsearch',
            " type='text/javascript' async src='$script_src'></script>",
            0,
            'display'
        );
        ?>
        <?php
    }
}
