<?php
/**
 * SpeedSearch Uninstall
 *
 * Deletes SpeedSearch options and other data.
 *
 * @package SpeedSearch
 * @since 0.3.1
 */

// Security check.

use SpeedSearch\DB;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

require_once __DIR__ . '/data/constants.php';

// Deletes options.

$all_options      = require __DIR__ . '/data/options.php';
$settings_options = [];

foreach ( $all_options as $option => $option_data ) {
    delete_option( $option );
}

delete_option( 'speedsearch-updating' );

// Delete all swatches.

$attributes = wc_get_attribute_taxonomies();
foreach ( $attributes as $attribute ) {
    $terms_ids = get_terms(
        [
            'taxonomy'   => wc_attribute_taxonomy_name( $attribute->attribute_name ),
            'fields'     => 'ids',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'     => 'speedsearch-swatch-image',
                    'compare' => 'EXISTS',
                ],
            ],
        ]
    );

    foreach ( $terms_ids as $term_id ) {
        delete_term_meta( $term_id, 'speedsearch-swatch-image' );
    }
}

// Removes transients.

delete_transient( 'speedsearch_store_authorized' );

// Deletes /speedsearch/ uploads dir with all files.

$speedsearch_uploads_dir = wp_upload_dir()['basedir'] . '/speedsearch/';
$iterator                = new RecursiveDirectoryIterator( $speedsearch_uploads_dir, RecursiveDirectoryIterator::SKIP_DOTS );
$files                   = new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::CHILD_FIRST );
foreach ( $files as $file ) {
    if ( $file->isDir() ) {
        rmdir( $file->getRealPath() );
    } else {
        unlink( $file->getRealPath() );
    }
}

rmdir( $speedsearch_uploads_dir );

// Deletes tables.

require_once __DIR__ . '/src/class-databases.php';

global $wpdb;

new DB();

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->speedsearch_feed_buffer}" );

/**
 *  The rest (user, webhooks, feed, self-request REST API credentials, etc.) - are deleted on plugin deactivation.
 *
 *  Because they require plugin method calls.
 */
