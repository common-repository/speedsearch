<?php
/**
 * REST API Custom Controller for the retrieval of the products that belong to the specified term ID.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use WP_Error;
use WP_REST_Request;

/**
 * REST API Custom Controller class.
 */
class REST_SpeedSearch_Term_Products_Controller {

    /**
     * Namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v3';

    /**
     * Endpoint name.
     *
     * @var string
     */
    protected $rest_base = 'speedsearch-term-products';

    /**
     * Post type (for which permissions check will be done).
     *
     * @var string
     */
    protected $post_type = 'product';

    /**
     * Custom REST API handler.
     *
     * Retrieves products that belong to the specified term ID.
     *
     * @param WP_REST_Request $request Where the body is an JSON object with the following values:
     *
     * term_id - The ID of the term to get the products for.
     *
     * @return array
     */
    public function get_term_products( $request ) {
        $term_id = (int) $request->get_param( 'term_id' );

        $term = get_term( $term_id );

        if ( $term && ! is_wp_error( $term ) ) {
            $taxonomy = $term->taxonomy;

            $post_args = [
                'posts_per_page' => -1,
                'post_type'      => 'product',
                'tax_query'      => [
                    [
                        'taxonomy' => $taxonomy,
                        'field'    => 'term_id',
                        'terms'    => $term_id,
                    ],
                ],
                'fields'         => 'ids',
            ];
            $products  = get_posts( $post_args );
            return $products;
        }

        return [];
    }

    /**
     * Check if a given request has access to read items.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check( $request ) {
        if ( ! wc_rest_check_post_permissions( $this->post_type ) ) {
            return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'speedsearch' ), array( 'status' => rest_authorization_required_code() ) );
        }

        return true;
    }

    /**
     * Register the routes.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<term_id>[\d-]+)',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_term_products' ],
                'args'                => [
                    'term_id' => [
                        'required'    => true,
                        'type'        => 'number',
                        'description' => 'The ID of the term to get the products for.',
                    ],
                ],
                'permission_callback' => [ $this, 'get_items_permissions_check' ],
            ]
        );
    }
}
