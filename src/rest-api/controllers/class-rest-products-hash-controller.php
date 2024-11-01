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
class REST_Products_Hash_Controller {

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
    protected $rest_base = 'products-hash';

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
     * @return array
     */
    public function get_all_products_hash() {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
            SELECT
                p.ID AS id,
                meta_value
            FROM
                $wpdb->posts p
                LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
                AND meta_key = 'speedsearch-product-hash'
            WHERE
                p.post_status = 'publish'
                AND p.post_type = 'product'
                AND %s = %s
            ",
                '', // Add empty placeholder variable which do not affect anything just to comply with the coding standards...
                ''
            ),
            ARRAY_N
        );

        $hashes = array_combine( array_column( $results, 0 ), array_column( $results, 1 ) );

        return $hashes;
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
            '/' . $this->rest_base,
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_all_products_hash' ],
                'permission_callback' => [ $this, 'get_items_permissions_check' ],
            ]
        );
    }
}
