<?php
/**
 * An endpoint for plugin status notifications.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use WP_REST_Server;

/**
 * Class Plugin_Status_Endpoint.
 */
final class Plugin_Status_Endpoint {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action(
            'rest_api_init',
            function() {
                register_rest_route(
                    'speedsearch',
                    '/status',
                    [
                        'methods'             => WP_REST_Server::READABLE, // GET.
                        'callback'            => [ $this, 'get_plugin_status_data' ],
                        'permission_callback' => '__return_true',
                    ]
                );
            }
        );
    }

    /**
     * Returns general status.
     *
     * Can be: healthy or unhealthy.
     *
     * @return string
     */
    public function get_status() {
        return (
            'reachable' === $this->get_backend_status()
        ) ?
            'healthy' :
            'unhealthy';
    }

    /**
     * Returns backend status.
     *
     * Can be: reachable or unreachable or disconnected (for “Selected store was not found.”) or error (when we get another error back)
     *
     * @return string
     */
    public function get_backend_status() {
        $response = Backend_Requests::sync_status();

        $response_code = wp_remote_retrieve_response_code( $response );

        return is_wp_error( $response ) ?
            'unreachable' :
                (
                    200 === $response_code ?
                        'reachable' :
                        (
                            404 === $response_code ? // 404 implies that there is "Selected store was not found." error.
                                'disconnected' :
                                'error'
                        )
                );
    }

    /**
     * Last error at.
     *
     * @return null|int
     */
    public function get_last_error_at() {
        $last_plugin_error = get_option( 'speedsearch-last-plugin-error' );

        return $last_plugin_error ? array_key_last( $last_plugin_error ) : null;
    }

    /**
     * Last error message.
     *
     * @return null|int
     */
    public function get_last_error_message() {
        $last_plugin_error = get_option( 'speedsearch-last-plugin-error' );

        return $last_plugin_error ? end( $last_plugin_error ) : null;
    }

    /**
     * Returns plugin status data.
     *
     * @return array
     */
    public function get_plugin_status_data() {
        return [
            'service'          => 'speedsearch',
            'status'           => $this->get_status(),
            'backend'          => $this->get_backend_status(),
            'lastErrorAt'      => $this->get_last_error_at(),
            'lastErrorMessage' => $this->get_last_error_message(),
            'timestamp'        => time(),
        ];
    }
}
