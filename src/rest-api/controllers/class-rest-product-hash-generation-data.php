<?php
/**
 * REST API Custom Controller for the retrieval of the hash of the products.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use WP_Error;
use WP_REST_Request;

/**
 * REST API Custom Controller class.
 */
class REST_Product_Hash_Generation_Data_Controller {

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
    protected $rest_base = 'product-hash-generation-data';

    /**
     * Post type (for which permissions check will be done).
     *
     * @var string
     */
    protected $post_type = 'product';

    /**
     * Custom REST API handler.
     *
     * Retrieves products hash from their meta-fields.
     *
     * @param WP_REST_Request $request Where the body is an JSON object with the following values:
     *
     * product_id - The ID of the post to get the JSON before hash generation for.
     *
     * @return array
     */
    public function get_product_hash_generation_json( $request ) {
        $product_id = (int) $request->get_param( 'product_id' );

        return SpeedSearch::$hash_generation->get_hash_for_product( $product_id, true );
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
            '/' . $this->rest_base . '/(?P<product_id>[\d-]+)',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_product_hash_generation_json' ],
                'args'                => [
                    'product_id' => [
                        'required'    => true,
                        'type'        => 'number',
                        'description' => 'The ID of the post to get the json for..',
                    ],
                ],
                'permission_callback' => [ $this, 'get_items_permissions_check' ],
            ]
        );
    }
}
