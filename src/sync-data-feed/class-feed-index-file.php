<?php
/**
 * Feed index file.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Sync_Data_Feed;

/**
 * A class for feed index file.
 */
final class Feed_Index_File {

    /**
     * Feed index filename.
     */
    const FILENAME = 'index.jsonl';

    /**
     * Get filepath.
     *
     * @return string
     */
    private static function get_filepath(): string {
        return path_join( Feed_Generation_Buffer::get_feed_dir(), self::FILENAME );
    }

    /**
     * Updates index file..
     */
    public static function update_indexes() {
        $files = glob( path_join( Feed_Generation_Buffer::get_feed_dir(), '*.jsonl' ) );

        natsort( $files );

        $contents = [];

        foreach ( $files as $file_path ) {
            $filename_parts = explode( '.', basename( $file_path ) );

            if (
                2 !== count( $filename_parts ) ||
                ! is_numeric( $filename_parts[0] )
            ) {
                continue;
            }

            $contents[] = [
                'index' => $filename_parts[0],
                'hash'  => hash_file( 'sha1', $file_path ),
            ];
        }

        if ( ! \SpeedSearch\SpeedSearch::$options->get( 'initial-feed-generation-complete' ) ) {
            $contents[] = []; // Add an empty array to the end as an indicator that the initial feed generation is in progress.
        }

        \SpeedSearch\SpeedSearch::$fs->put_contents( self::get_filepath(), Sync_Data_Feed::jsonl_encode( $contents ), 0644 );
    }
}
