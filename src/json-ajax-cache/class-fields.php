<?php
/**
 * JSON AJAX Cache fields flush timestamps class.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\JSON_AJAX_Cache;

use Exception;
use SpeedSearch\SpeedSearch;
use WP_Post;

/**
 * Updates flush timestamp fields when necessary.
 */
final class Fields {

    /**
     * Option name.
     */
    const OPTION_NAME = 'speedsearch-json-cache-last-cache-flush-fields';

    /**
     * Constructor.
     *
     * @throws Exception Exception.
     */
    public function __construct() {
        $this->update_tax_fields_flush_timestamp();

        // If last flush timestamp option changed.

        $this->handle_last_flush_timestamp_change();
    }


    /**
     * Returns fields.
     *
     * @return array Cache fields where key is an identifier and the value is its flush timestamp.
     *
     * @throws Exception Exception.
     */
    public function get() {
        return SpeedSearch::$options->get( self::OPTION_NAME );
    }

    /**
     * Updates the cache flush timestamp fields.
     *
     * @param array $fields Cache fields where key is an identifier and the value is its flush timestamp.
     *
     * @throws Exception Exception.
     */
    public function update( array $fields ) {
        SpeedSearch::$options->set( self::OPTION_NAME, $fields );
    }

    /**
     * Delete the cache flush timestamp fields.
     *
     * @throws Exception Exception.
     */
    public function delete() {
        SpeedSearch::$options->delete( self::OPTION_NAME );
    }

    /**
     * Handles last flush timestamp change.
     *
     * Deletes 'speedsearch-json-cache-last-cache-flush-fields' option.
     */
    private function handle_last_flush_timestamp_change() {
        $option = 'speedsearch-json-cache-last-flush-time';

        add_action( "add_option_$option", [ $this, 'delete' ] );
        add_action( "update_option_$option", [ $this, 'delete' ] );
        add_action( "delete_option_$option", [ $this, 'delete' ] );
    }

    /**
     * Updates fields flush timestamp for "attributes", "attribute-terms", "tags" and "categories" on their change.
     *
     * @throws Exception Exception.
     */
    private function update_tax_fields_flush_timestamp() {

        // Attribute.

        add_action( 'woocommerce_attribute_updated', [ $this, 'attribute_updated' ], 10, 2 );
        add_action( 'woocommerce_attribute_deleted', [ $this, 'attribute_deleted' ], 10, 2 );

        // Term (attribute-term, tag, category) change.

        add_action( 'saved_term', [ $this, 'term_change' ], 10, 3 );
        add_action(
            'pre_delete_term',
            function( $term_id, $taxonomy ) {
                $this->term_change( $term_id, $term_id, $taxonomy );
            },
            10,
            2
        );

        // Product.

        // It's important to check both product states on its update - before and after.
        // Because if something (e.g. attribute) was added, then it will not be shown on "before",
        // And if it was removed, it will not be shown on "after".

        add_filter( 'wp_insert_post_data', [ $this, 'wp_insert_post_data' ], 0, 2 ); // Before the product updated.
        add_action( 'wp_insert_post', [ $this, 'wp_insert_post' ], 10, 2 );          // After.
    }

    /**
     * Updates last flush fields timestamp on attribute change.
     *
     * @param array $data    An array of slashed, sanitized, and processed post data.
     * @param array $postarr An array of sanitized (and slashed) but otherwise unmodified post data.
     *
     * @throws Exception Exception.
     */
    public function wp_insert_post_data( array $data, array $postarr ) {
        if (
            'product' === $data['post_type'] &&
            array_key_exists( 'ID', $postarr ) && 0 !== $postarr['ID'] // Not exists or 0 if the product was just created.
        ) {
            $post_id = $postarr['ID'];
            $this->handle_product( $post_id );
        }
        return $data;
    }

    /**
     * Updates last flush fields timestamp on attribute change.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     *
     * @throws Exception Exception.
     */
    public function wp_insert_post( $post_id, WP_Post $post ) {
        if ( 'product' === $post->post_type ) {
            $this->handle_product( $post_id );
        }
    }

    /**
     * Handles post data.
     *
     * @param int $post_id Post ID.
     *
     * @throws Exception Exception.
     */
    private function handle_product( $post_id ) {
        $product = wc_get_product( $post_id );

        $tags       = wc_get_product_terms( $post_id, 'product_tag', [ 'fields' => 'ids' ] );
        $categories = wc_get_product_terms( $post_id, 'product_cat', [ 'fields' => 'slugs' ] );
        $attributes = array_keys( $product->get_attributes() );

        if ( $tags || $categories || $attributes ) {
            $fields = $this->get();

            foreach ( $tags as $tag ) {
                $fields['tag'][ $tag ] = time();
            }

            foreach ( $categories as $category ) {
                $fields['cat'][ $category ] = time();
            }

            foreach ( $attributes as $attribute ) {
                $attr_name = substr( $attribute, 3 );

                if ( ! array_key_exists( $attr_name, $fields['attributes'] ) ) {
                    $fields['attributes'][ $attr_name ] = [];
                }

                $fields['attributes'][ $attr_name ]['flush'] = time();
            }

            $this->update( $fields );
        }
    }

    /**
     * Updates last flush fields timestamp on attribute change.
     *
     * @param int   $id   Added attribute ID.
     * @param array $data Attribute data.
     *
     * @throws Exception Exception.
     */
    public function attribute_updated( $id, array $data ) {
        $attr_name = $data['attribute_name'];

        $fields = $this->get();

        if ( ! array_key_exists( $attr_name, $fields['attributes'] ) ) {
            $fields['attributes'][ $attr_name ] = [];
        }

        $fields['attributes'][ $attr_name ]['flush'] = time();

        $this->update( $fields );
    }

    /**
     * Updates last flush fields timestamp on attribute deletion.
     *
     * @param int    $id        Attribute ID.
     * @param string $attr_name Attribute name.
     *
     * @throws Exception Exception.
     */
    public function attribute_deleted( $id, $attr_name ) {
        $fields = $this->get();

        if ( ! array_key_exists( $attr_name, $fields['attributes'] ) ) {
            $fields['attributes'][ $attr_name ] = [];
        }

        $fields['attributes'][ $attr_name ]['flush'] = time();

        $this->update( $fields );
    }

    /**
     * Updates last flush fields timestamp on WooCommerce term change.
     *
     * @param int    $term_id  Term ID.
     * @param int    $tt_id    Term taxonomy ID.
     * @param string $taxonomy Taxonomy slug.
     *
     * @throws Exception Exception.
     */
    public function term_change( $term_id, $tt_id, $taxonomy ) {
        $type = false;

        if ( 'product_tag' === $taxonomy ) {
            $type = 'tag';
        } elseif ( 'product_cat' === $taxonomy ) {
            $type = 'cat';
        } elseif ( // attribute-term.
            in_array( substr( $taxonomy, 3 ), wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_name' ), true ) // substr is to remove "pa_".
        ) {
            $type = 'attribute-term';
        }

        if ( $type ) {
            $fields = $this->get();

            if ( 'attribute-term' === $type ) {
                $attr_name = substr( $taxonomy, 3 );

                if ( ! array_key_exists( $attr_name, $fields['attributes'] ) ) {
                    $fields['attributes'][ $attr_name ] = [];
                }

                if ( ! array_key_exists( 'terms', $fields['attributes'][ $attr_name ] ) ) {
                    $fields['attributes'][ $attr_name ]['terms'] = [];
                }

                $term_name = get_term( $term_id )->name;
                $fields['attributes'][ $attr_name ]['terms'][ $term_name ] = time();
            } else { // Tag, cat.
                $fields[ $type ][ $term_id ] = time();
            }

            $this->update( $fields );
        }
    }
}
