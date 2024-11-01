<?php
/**
 * Cache.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * Manages Cache.
 */
final class Cache {

    /**
     * Transient cache expiration.
     */
    const TRANSIENT_CACHE_EXPIRATION = MINUTE_IN_SECONDS;

    /**
     * Transient cache prefix.
     */
    const TRANSIENT_CACHE_PREFIX = 'speedsearch_cache_';

    /**
     * Constructor.
     */
    public function __construct() {
        // Inspired by Cloudflare plugin.
        add_action( 'autoptimize_action_cachepurged', [ __CLASS__, 'flush_all_caches' ] ); // Compat with https://wordpress.org/plugins/autoptimize.
        add_action( 'switch_theme', [ __CLASS__, 'flush_all_caches' ] ); // Switch theme.
        add_action( 'customize_save_after', [ __CLASS__, 'flush_all_caches' ] ); // Edit theme.
    }

    /**
     * Returns paged data from transient cache if exists, otherwise return false.
     *
     * @param string $endpoint  One of the backend endpoints. Each endpoint has its cache own.
     * @param mixed  $args      Arguments (categories, filters, attributes etc.) - used as a key for cache.
     * @param array  $data      Data to cache.
     * @param bool   $consider_last_settings_update_time If to consider last settings update time.
     * @param number $expiration Cache expiration.
     */
    public static function save( $endpoint, $args, array $data, $consider_last_settings_update_time = true, $expiration = self::TRANSIENT_CACHE_EXPIRATION ) {
        $request_hash = hash( 'md5', wp_json_encode( $args ) ); // serialize() is discouraged due to security issues.
        $key          = self::TRANSIENT_CACHE_PREFIX . $endpoint . '_' . $request_hash .
                        ( $consider_last_settings_update_time ? SpeedSearch::$options->get( 'last-settings-update-time' ) : '' );

        $data = [
            'data'       => $data,
            'expiration' => time() + $expiration,
        ];

        set_transient( $key, $data, $expiration );
    }

    /**
     * Cached data.
     *
     * @var array
     */
    public static array $cached_data = [];

    /**
     * Returns paged data from transient cache if exists, otherwise return false.
     *
     * @param string $endpoint One of the backend endpoints. Each endpoint has its cache own.
     * @param mixed  $args     Arguments (categories, filters, attributes etc).
     * @param bool   $consider_last_settings_update_time If to consider last settings update time.
     *
     * @return array|false Cached data or false if no cache found.
     */
    public static function get( $endpoint, $args, $consider_last_settings_update_time = true ) {
        $request_hash = hash( 'md5', wp_json_encode( $args ) ); // serialize() is discouraged due to security issues.
        $key          = self::TRANSIENT_CACHE_PREFIX . $endpoint . '_' . $request_hash .
                        ( $consider_last_settings_update_time ? SpeedSearch::$options->get( 'last-settings-update-time' ) : '' );

        // Cache during the page generation (so the transient can't expire during this time).
        // This is necessary because we send "no-cache" header for shop pages if all entities are in the cache,
        // so we wouldn't like it to expire during the page generation.
        if ( array_key_exists( $key, self::$cached_data ) ) {
            return self::$cached_data[ $key ];
        }

        $transient_data = get_transient( $key );
        if (
            false !== $transient_data &&
            is_array( $transient_data ) &&
            array_key_exists( 'expiration', $transient_data ) &&
            $transient_data['expiration'] > time() &&
            array_key_exists( 'data', $transient_data )
        ) {
            self::$cached_data[ $key ] = $transient_data['data'];
            return $transient_data['data'];
        } else {
            return false;
        }
    }

    /**
     * Flush all caches on customizer settings change (general caches & products hash caches)
     */
    public static function flush_all_caches() {

        // Flush JSON (AJAX) cache (Sets JSON Cache flush time to the current time).

        SpeedSearch::$json_ajax_cache->flush();

        // General cache flush time (currently affects IDB only).

        SpeedSearch::$options->set( 'cache-last-flush-time', round( microtime( true ) * 1000 ) );

        // Flush products hashes.

        Products_HTML_Cache::flush();
    }
}
