<?php
/**
 * A class with misc methods.
 *
 * Usually, considers methods which could not grouped to any class.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class Misc.
 */
final class Misc {

    /**
     * Whether the permalinks structure is not plain (i.e. not "http://speedsearch.test:8043/?p=123" but "http://speedsearch.test:8043/sample-post/").
     *
     * @return bool
     */
    public static function is_not_plaintext_permalink_structure() {
        return get_option( 'permalink_structure' ) && str_ends_with( get_option( 'permalink_structure' ), '/' );
    }

    /**
     * Returns current URL.
     *
     * @return string|bool Current URL, or false if it can't determine current URL.
     */
    public static function get_current_url() {
        if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
            return ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) ? 'https' : 'http' )
                    . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
        } else {
            return false;
        }
    }

    /**
     * Sends "no-cache" headers.
     */
    public static function send_no_cache_headers() {
        header( 'Cache-Control: no-cache, no-store, must-revalidate' ); // HTTP 1.1.
        header( 'Pragma: no-cache' ); // HTTP 1.0.
        header( 'Expires: 0' ); // Proxies.
    }

    /**
     * Get attribute taxonomy ID by term ID.
     *
     * @param int $term_id Term ID.
     *
     * @return null
     */
    public static function get_attribute_taxonomy_id_by_term_id( $term_id ) {
        $term = get_term( $term_id );

        if ( ! is_wp_error( $term ) && ! empty( $term ) ) {
            $taxonomy = $term->taxonomy;

            $attribute_taxonomies = wc_get_attribute_taxonomies();

            foreach ( $attribute_taxonomies as $attribute_taxonomy ) {
                if ( 'pa_' . $attribute_taxonomy->attribute_name === $taxonomy ) {
                    return $attribute_taxonomy->attribute_id;
                }
            }
        }

        return null;
    }

    /**
     * Enriches product data.
     *
     * Adds variable products prices, and adds attribute terms data.
     *
     * @param array            $product_data Product data.
     * @param \WC_Product|null $product      Product.
     *
     * @return array Enriched product data.
     */
    public static function enrich_product_data( &$product_data, $product = null ) {
        if ( null === $product ) {
            $product = wc_get_product( $product_data['id'] );
        }

        if ( 'variable' === $product->get_type() ) {
            $product_data['speedsearch_variable_product_prices'] = [
                'min' => $product->get_variation_price(),
                'max' => $product->get_variation_price( 'max' ),
            ];
        }

        if ( isset( $product_data['attributes'] ) ) {
            $product_attributes_options = [];
            foreach ( $product->get_attributes() as $product_attribute ) {
                if ( is_object( $product_attribute ) ) {
                    $product_attributes_options[ $product_attribute->get_id() ] = $product_attribute->get_options();
                }
            }

            if ( $product_attributes_options ) {
                foreach ( $product_data['attributes'] as &$attribute_data ) {
                    $terms_data = [];

                    $term_ids = $product_attributes_options[ $attribute_data['id'] ];
                    foreach ( $term_ids as $term_id ) {
                        $term_data = get_term( $term_id );
                        if ( $term_data && ! is_wp_error( $term_data ) ) {
                            $terms_data[ $term_data->name ] = [
                                'slug' => $term_data->slug,
                                'id'   => $term_id,
                            ];
                        }
                    }

                    $attribute_data['speedsearch_attribute_terms_data'] = $terms_data;
                }
            }
        }

        return $product_data;
    }

    /**
     * Formats price to WC format.
     *
     * @param float $price Price.
     *
     * @return string Formatted price.

     * @throws Exception Exception.
     */
    public static function format_price( $price ) {
        return sprintf(
            get_woocommerce_price_format(),
            get_woocommerce_currency_symbol(),
            number_format( (float) $price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() )
        );
    }

    /**
     * Get a list of directory files.
     *
     * @param string $dir_path        Directory name.
     * @param string $files_extension The extension of the files to list.
     *
     * @return string[] The list of full paths of directory files.
     */
    public static function get_dir_files( $dir_path, $files_extension = 'php' ) {
        $dir_files = [];
        $iterator  = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $dir_path, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied".
        );

        foreach ( $iterator as $path ) {
            if ( $path->isFile() && $path->getExtension() === $files_extension ) {
                $dir_files[] = $path->getPathname();
            }
        }

        return $dir_files;
    }

    /**
     * Stop running action scheduler actions.
     *
     * @param string $action_name Name of the action.
     * @param string $group       Name of the group.
     */
    public static function stop_running_action_scheduler_action( $action_name, $group = 'speedsearch' ) {
        $running_actions = as_get_scheduled_actions(
            [
                'hook'     => $action_name,
                'group'    => $group,
                'status'   => \ActionScheduler_Store::STATUS_RUNNING,
                'per_page' => -1,
            ],
            'ids'
        );
        if ( $running_actions ) {
            $as = new \ActionScheduler_DBStore();
            foreach ( $running_actions as $action_id ) {
                $as->delete_action( $action_id );
            }
        }
    }
}
