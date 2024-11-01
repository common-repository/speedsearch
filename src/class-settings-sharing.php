<?php
/**
 * A class for sharing the settings.
 *
 * 1. Plugin generates a temporary secret.
 * 2. Plugin creates a settings json and encrypts it with the secret using openssl_encrypt and method aes-256-cbc.
 * 3. Plugin generates a version 4 GUID and uses this as filename (+js extension) to save the encrypted json.
 * 4. Plugin posts the GUID and secret to the backend.
 * 5. Backend uses the GUID to download the JS file, and the secret to decrypt it.
 *
 * The file that BE should request is: https://example.com/speedsearch/settings/{uuid}.js
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * Class Settings_Sharing.
 */
final class Settings_Sharing {

    /**
     * Interval.
     */
    const INTERVAL = HOUR_IN_SECONDS * 6;

    /**
     * Hook name of the interval.
     */
    const INTERVAL_HOOK_NAME = 'speedsearch_start_settings_sharing_handshake';

    /**
     * Constructor.
     */
    public function __construct() {

        // Action.

        add_action( self::INTERVAL_HOOK_NAME, [ $this, 'init' ] );

        // Schedule an Interval.

        $schedule_interval_action = function() {
            as_schedule_recurring_action(
                time() + self::INTERVAL,
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

        // Unschedule an interval on plugin deactivation.
        register_deactivation_hook( SPEEDSEARCH_FILE, [ __CLASS__, 'unschedule_interval' ] );

        // Output the file.
        $this->output_file();
    }

    /**
     * Unschedule the interval.
     */
    public static function unschedule_interval() {
        as_unschedule_all_actions( self::INTERVAL_HOOK_NAME, [], 'speedsearch' );
        \SpeedSearch\Misc::stop_running_action_scheduler_action( self::INTERVAL_HOOK_NAME );
    }

    /**
     * Init.
     */
    public function init() {
        $this->populate_the_buffer();
        $this->ping_the_be();
    }

    /**
     * Output the settings file, if it's requested.
     */
    public function output_file() {
        if (
            isset( $_SERVER['REQUEST_URI'] ) &&
            str_ends_with( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '.js' ) &&
            preg_match( '/.*\/speedsearch\/settings\/.*\.js$/', sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) )
        ) { // @codingStandardsIgnoreLine
            $data = SpeedSearch::$options->get( 'settings-sharing-buffer' );

            $debug_data                   = SpeedSearch::$options->get( 'settings-sharing-debug-data' );
            $debug_data['lastBuffer']     = $data;
            $debug_data['lastBufferTime'] = time();
            SpeedSearch::$options->set( 'settings-sharing-debug-data', $debug_data );

            if ( $data ) {
                $guid    = $data['guid'];
                $content = $data['data'];

                $requested_js_file       = trim( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/' );
                $requested_js_file_parts = explode( '/', explode( '.', $requested_js_file )[0] );
                $requested_js_file_name  = end( $requested_js_file_parts );

                $debug_data['lastRequestedJsFileData']     = $data;
                $debug_data['lastRequestedJsFileRealName'] = $requested_js_file_name;
                $debug_data['lastBufferTime']              = time();
                SpeedSearch::$options->set( 'settings-sharing-debug-data', $debug_data );

                if ( $guid === $requested_js_file_name ) {
                    // Update debug data.
                    $debug_data['lastRequestedFileTime'] = time();
                    $debug_data['lastRequestedFileData'] = $data;
                    SpeedSearch::$options->set( 'settings-sharing-debug-data', $debug_data );

                    self::send_content_type_header();
                    self::send_no_cache_headers();
                    header( 'Content-Disposition: attachment; filename="' . $requested_js_file . '"' );
                    header( 'Content-Length: ' . strlen( $content ) );
                    die(
                        sanitize_user_field(
                            'speedsearch',
                            $content,
                            0,
                            'display'
                        )
                    );
                }
            }
        }
    }

    /**
     * Populate the buffer with the new settings data.
     */
    private function populate_the_buffer() {
        $guid     = wp_generate_uuid4();
        $settings = wp_json_encode( REST_SpeedSearch_Settings_Controller::get_settings() );

        $key       = sodium_crypto_secretbox_keygen();
        $nonce     = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
        $encrypted = sodium_crypto_secretbox( $settings, $nonce, $key );

        $encrypted_data = base64_encode( $nonce . $encrypted ); // @codingStandardsIgnoreLine

        SpeedSearch::$options->set(
            'settings-sharing-buffer',
            [
                'secret' => base64_encode( $key ), // @codingStandardsIgnoreLine
                'guid'   => $guid,
                'data'   => $encrypted_data,
            ]
        );
    }

    /**
     * Send the data to the BE (guid and secret).
     */
    private function ping_the_be() {
        $data = SpeedSearch::$options->get( 'settings-sharing-buffer' );

        $response = Backend_Requests::start_settings_sharing_handshake( $data['guid'], $data['secret'] );

        // Update debug data.
        $debug_data                 = [];
        $debug_data['lastResponse'] = $response;
        SpeedSearch::$options->set( 'settings-sharing-debug-data', $debug_data );
    }

    /**
     * Sends content type header.
     */
    private static function send_content_type_header() {
        header( 'Content-Type: application/javascript' );
    }

    /**
     * Sends "no-cache" headers.
     */
    public static function send_no_cache_headers() {
        header( 'Cache-Control: no-cache, no-store, must-revalidate' ); // HTTP 1.1.
        header( 'Pragma: no-cache' ); // HTTP 1.0.
        header( 'Expires: 0' ); // Proxies.
    }
}
