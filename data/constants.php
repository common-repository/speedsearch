<?php
/**
 * Defines plugin constants.
 *
 * @package SpeedSearch
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

$plugin_file = realpath( __DIR__ . '/../speedsearch.php' );

/*
 * The URL to the plugin.
 *
 * @var string SPEEDSEARCH_URL
 */
define( 'SPEEDSEARCH_URL', plugin_dir_url( $plugin_file ) );

/*
 * The filesystem directory path to the plugin.
 *
 * @var string SPEEDSEARCH_DIR
 */
define( 'SPEEDSEARCH_DIR', plugin_dir_path( $plugin_file ) );

/*
 * The version of the plugin.
 *
 * @var string SPEEDSEARCH_VERSION
 */
define( 'SPEEDSEARCH_VERSION', get_file_data( $plugin_file, [ 'Version' ] )[0] );

/*
 * The filename of the plugin including the path.
 *
 * @var string SPEEDSEARCH_FILE
 */
define( 'SPEEDSEARCH_FILE', $plugin_file );

/*
 * Default SpeedSearch backend server.
 *
 * @var string SPEEDSEARCH_DEFAULT_SERVER
 */
define( 'SPEEDSEARCH_SERVER', isset( $_ENV['SPEEDSEARCH_SERVER'] ) ? untrailingslashit( sanitize_text_field( $_ENV['SPEEDSEARCH_SERVER'] ) ) : 'https://saffy.speedsearchpro.com' );

/*
 * Plugin basename.
 */
define( 'SPEEDSEARCH_BASENAME', plugin_basename( SPEEDSEARCH_FILE ) );

/*
 * Limit of how many maximum recently viewed products to store.
 */
define( 'SPEEDSEARCH_RECENTLY_VIEWED_PRODUCTS_PRODUCTS_SHOW_LIMIT', 12 );

/*
 * Plugin version type (based on the distribution channel).
 */
define( 'SPEEDSEARCH_VERSION_TYPE', 'tmm' );
