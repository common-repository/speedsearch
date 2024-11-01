<?php
/**
 * Class for swatches.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * Class Swatches.
 */
final class Swatches {

    /**
     * Deletes swatches for all attributes.
     */
    public static function delete_all_attribute_swatches() {
        $attributes = wc_get_attribute_taxonomies();
        foreach ( $attributes as $attribute ) {
            $terms_ids = get_terms(
                [
                    'taxonomy'   => wc_attribute_taxonomy_name( $attribute->attribute_name ),
                    'fields'     => 'ids',
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
    }
}
