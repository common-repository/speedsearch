<?php
/**
 * A class for file-based enriched cache.
 *
 * The idea is simple:
 *
 * When Object Cache is enabled and the number of products on the site is less than specified, save to Object Cache.
 * Otherwise, fallback to the file-based cached.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * File_Fallbacked_Cache class.
 */
final class File_Fallbacked_Cache {

    /**
     * Products limit for file-based cache.
     *
     * @var int
     */
    const USE_FILE_BASED_CACHE_PRODUCTS_LIMIT = 10000;

    /**
     * Returns the cache file path.
     *
     * @param string $key   The key under which the contents are stored.
     * @param string $group Optional. Where the contents are grouped. Default empty.
     *
     * @return string
     */
    private static function get_the_cache_file_path( $key, $group ) {
        $sanitized_key   = sanitize_file_name( $key );
        $sanitized_group = sanitize_file_name( $group );
        return path_join( self::get_dir_path(), "$sanitized_group:$sanitized_key" );
    }

    /**
     * Returns the cache dir.
     *
     * @return string
     */
    private static function get_dir_path() {
        $uploads_dir = path_join( wp_upload_dir()['basedir'], 'speedsearch' );
        return path_join( $uploads_dir, 'cache' );
    }

    /**
     * Whether to use file cache instead of object cache.
     *
     * @param bool $consider_products_limit_for_file_based_cache Consider products limit for file based cache.
     *
     * @return bool
     */
    public static function do_fallback_cache_to_files( $consider_products_limit_for_file_based_cache = true ) {
        return ! wp_using_ext_object_cache() ||
                $consider_products_limit_for_file_based_cache &&
                self::get_how_many_products_on_the_site() > self::USE_FILE_BASED_CACHE_PRODUCTS_LIMIT;
    }

    /**
     * Retrieves the data from either Object Cache or from the cache dir.
     *
     * @param string $key   The key under which the contents are stored.
     * @param string $group Optional. Where the contents are grouped. Default empty.
     *
     * @return mixed|false The cache contents on success, false on failure to retrieve contents.
     */
    public static function get( $key, $group = '' ) {
        if ( ! self::do_fallback_cache_to_files() ) {
            return wp_cache_get( $key, $group );
        } else {
            $file_path = self::get_the_cache_file_path( $key, $group );

            if ( ! SpeedSearch::$fs->is_file( $file_path ) ) {
                return false;
            }

            return maybe_unserialize( SpeedSearch::$fs->get_contents( $file_path ) );
        }
    }

    /**
     * Saves the data to either Object Cache or to the cache dir.
     *
     * @param string $key    The key to use for retrieval later.
     * @param mixed  $data   The contents to store.
     * @param string $group  Optional. Where to group the contents. Enables the same key
     *                       to be used across groups. Default empty.
     *
     * @return bool True on success, false on failure.
     */
    public static function set( $key, $data, $group = '' ) {
        if ( ! self::do_fallback_cache_to_files() ) {
            return wp_cache_set( $key, $data, $group );
        } else {
            // Make dir if not present.
            $dir_path = self::get_dir_path();

            $has_dir = SpeedSearch::$fs->is_dir( $dir_path );
            if ( ! $has_dir ) {
                $has_dir = wp_mkdir_p( $dir_path );
            }

            // Save to file.
            if ( $has_dir ) {
                return SpeedSearch::$fs->put_contents( self::get_the_cache_file_path( $key, $group ), maybe_serialize( $data ), 0644 );
            }

            return false;
        }
    }

    /**
     * Deletes the data from Object Cache and from the cache dir.
     *
     * @param string $key   The content under which key to delete.
     * @param string $group The cache contents are grouped. Default empty.
     */
    public static function delete( $key, $group = '' ) {
        wp_cache_delete( $key, $group );
        SpeedSearch::$fs->delete( self::get_the_cache_file_path( $key, $group ) );
    }

    /**
     * Get the count of how many products are on the site.
     *
     * @return int
     */
    private static function get_how_many_products_on_the_site() {
        global $wpdb;

        $products_count = get_transient( 'speedsearch-shop-pages-count' );
        if ( false !== $products_count ) {
            return $products_count;
        } else {
            $products_count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$wpdb->posts} AS p
                    WHERE p.post_type = 'product' 
                    AND p.post_status = 'publish'
                "
                )
            );

            set_transient( 'speedsearch-shop-pages-count', $products_count, MINUTE_IN_SECONDS );

            return $products_count;
        }
    }
}
