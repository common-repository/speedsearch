<?php
/**
 * Parse of the URL for SpeedSearch request params for the initial elements rendering.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Initial_Elements_Rendering;

use SpeedSearch\Cache;

/**
 * Class for parse of the URL for SpeedSearch request params.
 *
 * Parses the current URL for SpeedSearch params, saves them to the variable,
 * and returns them for the elements initial rendering, when necessary.
 */
class Parse_Url_For_Request_Params {

    /**
     * Transient for request params expiration.
     *
     * Add one more minute to the cache because the secondary request (e.g. get posts, filtering) is done a bit later after the request params were recorded
     * (as "search" is always the first request).
     */
    const TRANSIENT_EXPIRATION = Cache::TRANSIENT_CACHE_EXPIRATION + MINUTE_IN_SECONDS;

    /**
     * Get URL request name.
     *
     * Based on the current URL.
     *
     * @param string $request_uri Request URI.
     *
     * @return string URL request.
     */
    private static function get_name( $request_uri = false ) {
        return 'request-params-' . (
            $request_uri ?
                $request_uri :
                (
                    isset( $_SERVER['REQUEST_URI'] ) ?
                    sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) :
                    wp_rand( 1000000000, 9999999999 ) // Use a random to basically not to save / get any cache when can't detemrine URL.
                )
        );
    }

    /**
     * Remembers that particular request params belong to a particular URL.
     *
     * @param array  $request_params Request params to save.
     * @param string $request_uri    Request URI.
     *
     * Saves the value to a transient.
     */
    public static function save( array $request_params, $request_uri ) {
        unset( $request_params['plugin'] );
        unset( $request_params['action'] );
        unset( $request_params['currentPageAddress'] );
        unset( $request_params['postNums'] );

        Cache::save( self::get_name( $request_uri ), [], $request_params, false, self::TRANSIENT_EXPIRATION );
    }

    /**
     * Returns all URL params.
     *
     * @param bool $get_in_good_formatted_view Get in good formatted view.
     * @param bool $return_false_if_no_result  Return false if no result, instead of an empty string.
     *
     * @return array|false
     */
    public static function get(
        $get_in_good_formatted_view = true,
        $return_false_if_no_result = false
    ) {
        $params = Cache::get( self::get_name(), [], false );

        if ( $return_false_if_no_result && ! $params ) {
            return false;
        }

        if ( $get_in_good_formatted_view && is_array( $params ) ) {
            foreach ( $params as &$param ) {
                $json_decoded = json_decode( wp_unslash( $param ), true );
                if ( $json_decoded ) {
                    $param = $json_decoded;
                }
            }
        }

        return $params ? $params : [];
    }

    /**
     * Retrieves URL param.
     *
     * @param string $param                      Param to retrieve the values for.
     * @param string $get_in_good_formatted_view Get in good formatted view.
     *
     * @return array|string|false False if no params, value otherwise.
     */
    public static function get_url_param( $param, $get_in_good_formatted_view = true ) {
        $params = self::get( $get_in_good_formatted_view );

        return isset( $params[ $param ] ) ? $params[ $param ] : false;
    }
}
