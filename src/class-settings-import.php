<?php
/**
 * Class for Settings Import.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;

/**
 * Settings Import Class.
 */
final class Settings_Import {

    /**
     * Imports settings from the file.
     *
     * @param string $base_64_content A file base 64 content.
     * @param string $file_type       A file type.
     *
     * @throws Exception Exception.
     */
    public static function import( $base_64_content, $file_type ) {
        $is_archive = 'application/json' !== $file_type;

        $content = base64_decode( explode( ',', $base_64_content )[1] ); // @codingStandardsIgnoreLine

        if ( ! $is_archive ) { // @codingStandardsIgnoreLine
            $data = (array) json_decode( $content, true ); // @codingStandardsIgnoreLine
        } else { // Archive.
            $temp_file = tmpfile();
            $path      = stream_get_meta_data( $temp_file )['uri'];

            // @codingStandardsIgnoreLine
            SpeedSearch::$fs->put_contents( $path, $content, 0644 );

            $zip = new \ZipArchive;
            $zip->open( $path );
            $data = (array) json_decode( $zip->getFromName( Settings_Export::ARCHIVE_DATA_FILE_NAME ), true );
        }

        // 1. Images.

        $imports_dir = path_join( wp_get_upload_dir()['basedir'], 'speedsearch-import' );
        $imports_url = path_join( wp_get_upload_dir()['baseurl'], 'speedsearch-import' );

        if ( ! SpeedSearch::$fs->is_dir( $imports_dir ) ) {
            SpeedSearch::$fs->mkdir( $imports_dir );
        }

        foreach ( $data['images'] as $image_id => &$image_data ) {
            if ( $image_data ) {
                $image_extension = $image_data['filetype']['ext'];
                $image_name      = "$image_id.$image_extension";
                $image_path      = path_join( $imports_dir, $image_name );
                $image_url       = path_join( $imports_url, $image_name );

                // 1.1. Upload an image.

                if ( $is_archive ) {
                    if ( ! copy( 'zip://' . $path . '#' . Settings_Export::ARCHIVE_DATA_IMAGES_DIR_NAME . '/' . $image_name, $image_path) ) {
                        $image_data = false;
                        continue;
                    }
                } else {
                    $image_request = wp_remote_get( $image_data['src'] );
                    if ( is_wp_error( $image_request ) ) {
                        $image_data = false;
                        continue;
                    }
                    SpeedSearch::$fs->put_contents( $image_path, $image_request['body'], 0644 );
                }

                // 1.2. Insert image post type for the uploaded image.

                $attachment = [
                    'post_mime_type' => $image_data['filetype']['type'],
                    'guid'           => $image_url,
                    'post_title'     => $image_data['title'],
                    'post_content'   => $image_data['description'],
                    'post_excerpt'   => $image_data['caption'],
                    'post_status'    => 'inherit',
                ];

                $inserted_image_id = wp_insert_attachment( $attachment, $image_path );

                if ( ! $inserted_image_id || is_wp_error( $inserted_image_id ) ) {
                    $image_data = false;
                    continue;
                }

                // 1.2.1. Generate the metadata for the attachment, create sub-sizes, and update the database record.
                $attach_data = wp_generate_attachment_metadata( $inserted_image_id, $image_path );
                wp_update_attachment_metadata( $inserted_image_id, $attach_data );

                // 1.2.2. Add alt.
                update_post_meta( $inserted_image_id, '_wp_attachment_image_alt', $image_data['alt'] );

                $image_data['id'] = $inserted_image_id;
            }
        }

        if ( $is_archive ) {
            fclose( $temp_file ); // @codingStandardsIgnoreLine
        }

        // 2. Options.

        // 2.1. Options Images.

        foreach ( SpeedSearch::$options->image_id_options as $image_option ) {
            if ( $data['options'][ $image_option ] ) {
                $image = $data['images'][ $data['options'][ $image_option ] ];

                if ( $image && isset( $image['id'] ) ) {
                    $data['options'][ $image_option ] = $image['id'];
                } else {
                    $data['options'][ $image_option ] = '';
                }
            }
        }

        // 2.2. Save Options.

        foreach ( $data['options'] as $option_name => $option_value ) {
            if ( in_array( $option_name, SpeedSearch::$options->settings_options, true ) ) {
                SpeedSearch::$options->set( $option_name, $option_value );
            }
        }

        // 3. Attributes (swatches).

        foreach ( $data['attributes'] as $attribute_name => $attribute_data ) {
            foreach ( $attribute_data as $term_slug => $swatch_data ) {
                $term = get_term_by( 'slug', $term_slug, "pa_$attribute_name" ); // Search for the term by slug.

                if ( ! $term || is_wp_error( $term ) ) {
                    continue;
                }

                if ( ! is_array( $swatch_data ) ) { // Image swatch (not color swatch).
                    $image = $data['images'][ $swatch_data ];

                    if ( $image && isset( $image['id'] ) ) {
                        update_term_meta( $term->term_id, 'speedsearch-swatch-image', $image['id'] );
                    }
                } else {
                    update_term_meta( $term->term_id, 'speedsearch-swatch-image', $swatch_data );
                }
            }
        }
    }
}
