<?php
/**
 * Class for Settings Export.
 *
 * Many methods here are inspired by WC_CSV_Exporter class.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;

/**
 * Settings Export Class.
 */
final class Settings_Export {

    /**
     * The name of data file in archive.
     *
     * @var string
     */
    const ARCHIVE_DATA_FILE_NAME = 'data.json';

    /**
     * The name of data file in archive.
     *
     * @var string
     */
    const ARCHIVE_DATA_IMAGES_DIR_NAME = 'images';

    /**
     * Whether to bundle the images.
     *
     * @var bool
     */
    private static $bundle_images;

    /**
     * Listens for the export action.
     *
     * Accepts: $_GET['nonce']
     *          $_GET['speedsearch-action']
     *
     * @throws Exception Exception.
     */
    public static function add_listeners() {
        if (
            isset( $_GET['nonce'], $_GET['speedsearch-action'] ) &&
            wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'speedsearch-menu' ) &&
            'export-settings' === sanitize_text_field( wp_unslash( $_GET['speedsearch-action'] ) )
        ) {
            self::export();
        }
    }

    /**
     * Does the export.
     *
     * @throws Exception Exception.
     */
    private static function export() {
        self::$bundle_images = isset( $_GET['bundleImages'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['bundleImages'] ) ); // @codingStandardsIgnoreLine

        self::send_headers();
        self::send_content( self::get_data() );
        die();
    }

    /**
     * Returns filename extension.
     *
     * @return string
     */
    private static function get_extension() {
        if ( self::$bundle_images ) {
            return 'zip';
        } else {
            return '.json';
        }
    }

    /**
     * Returns filename.
     *
     * @return string
     */
    private static function get_filename() {
        return 'speedsearch-settings-' . time() . '.' . self::get_extension();
    }

    /**
     * Sends content type header.
     */
    private static function send_content_type_header() {
        if ( self::$bundle_images ) {
            header( 'Content-Type: application/zip' );
        } else {
            header( 'Content-Type: application/json; charset=utf-8' );
        }
    }

    /**
     * Sends the export headers.
     */
    private static function send_headers() {
        if ( function_exists( 'gc_enable' ) ) {
            gc_enable(); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.gc_enableFound
        }
        if ( function_exists( 'apache_setenv' ) ) {
            @apache_setenv( 'no-gzip', 1 ); // @codingStandardsIgnoreLine
        }
        @ini_set( 'zlib.output_compression', 'Off' ); // @codingStandardsIgnoreLine
        @ini_set( 'output_buffering', 'Off' ); // @codingStandardsIgnoreLine
        @ini_set( 'output_handler', '' ); // @codingStandardsIgnoreLine
        ignore_user_abort( true );
        wc_set_time_limit();
        wc_nocache_headers();
        self::send_content_type_header();
        header( 'Content-Disposition: attachment; filename=' . self::get_filename() );
        Misc::send_no_cache_headers();
    }

    /**
     * Gets data object which contains all plugin setting for export.
     *
     * @return array All plugin settings options with their values.
     *
     * @throws Exception Exception.
     */
    public static function get_data() {
        $data = [
            'options'    => [],
            'attributes' => [],
            'images'     => [],
        ];

        $image_ids = [];

        // 1. Setting Options.

        foreach ( SpeedSearch::$options->settings_options as $option ) {
            $data['options'][ $option ] = SpeedSearch::$options->get( $option );

            // 1.1. Images options.

            foreach ( SpeedSearch::$options->image_id_options as $image_option ) {
                if ( $data['options'][ $image_option ] ) {
                    $image_ids[] = $data['options'][ $image_option ];
                }
            }
        }

        // 2. Attributes (swatches).

        $attributes = wc_get_attribute_taxonomies();
        foreach ( $attributes as $attribute ) {
            $attribute_name = $attribute->attribute_name;

            $terms_with_swatches = get_terms(
                [
                    'taxonomy'   => wc_attribute_taxonomy_name( $attribute_name ),
                    'fields'     => 'id=>slug',
                    'meta_query' => [
                        [
                            'key'     => 'speedsearch-swatch-image',
                            'compare' => 'EXISTS',
                        ],
                    ],
                ]
            );

            if ( $terms_with_swatches ) {
                $data['attributes'][ $attribute_name ] = [];

                foreach ( $terms_with_swatches as $term_id => $term_slug ) {
                    $swatch_image = get_term_meta( $term_id, 'speedsearch-swatch-image', true );
                    if ( $swatch_image ) {
                        if ( ! is_array( $swatch_image ) ) { // Save swatch image (not color).
                            $image_ids[] = (int) $swatch_image;
                        }
                        $data['attributes'][ $attribute_name ][ $term_slug ] = $swatch_image;
                    }
                }
            }
        }

        // 3. Images.

        foreach ( array_unique( $image_ids ) as $image_id ) {
            $data['images'][ $image_id ] = self::get_image_data( $image_id );
        }

        return $data;
    }

    /**
     * Returns image data.
     *
     * @param int $attachment_id Image ID.
     *
     * @return array|false
     */
    private static function get_image_data( $attachment_id ) {
        $attachment = get_post( $attachment_id );
        if ( $attachment ) {
            $data = [
                'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
                'title'       => $attachment->post_title,
                'caption'     => $attachment->post_excerpt,
                'description' => $attachment->post_content,
                'src'         => $attachment->guid,
                'filetype'    => wp_check_filetype( wp_get_original_image_path( $attachment->ID ) ),
            ];

            if ( self::$bundle_images ) {
                $data['path'] = wp_get_original_image_path( $attachment->ID );
            }

            return $data;
        } else {
            return false;
        }
    }

    /**
     * Sends the export content.
     *
     * If the bundle_images is on, then sends an archive, otherwise a plain-text file.
     *
     * @param array $data JSON data.
     */
    private static function send_content( $data ) {
        if ( self::$bundle_images ) { // Send archive file.
            $temp_file = tmpfile();
            $path      = stream_get_meta_data( $temp_file )['uri'];
            $zip       = new \ZipArchive();
            $zip->open( $path, \ZipArchive::OVERWRITE );
            $zip->addEmptyDir( self::ARCHIVE_DATA_IMAGES_DIR_NAME );
            foreach ( $data['images'] as $image_id => &$image_data ) { // Add images.
                if ( $image_data ) {
                    $image_extension = $image_data['filetype']['ext'];
                    $image_name      = "$image_id.$image_extension";
                    $zip->addFile( $image_data['path'], self::ARCHIVE_DATA_IMAGES_DIR_NAME . "/$image_name" );
                    unset( $image_data['path'] );
                }
            }
            $zip->addFromString( self::ARCHIVE_DATA_FILE_NAME, wp_json_encode( $data ) );
            $zip->close();
            if ( ! headers_sent() ) {
                header( 'Content-Length: ' . filesize( $path ) );
            }
            readfile( $path ); // @codingStandardsIgnoreLine
            fclose( $temp_file ); // @codingStandardsIgnoreLine
        } else { // Send plain-text file.
            wp_send_json( $data ); // @codingStandardsIgnoreLine
        }
    }
}
