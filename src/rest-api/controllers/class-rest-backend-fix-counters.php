<?php
/**
 * REST API Custom Controller for the retrieval of some counters for the backend fix.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use WP_Error;
use WP_REST_Request;

/**
 * REST API Custom Controller class.
 */
class REST_SpeedSearch_Backend_Fix_Counters_Controller {

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
    protected $rest_base = 'speedsearch-backend-fix-counts';

    /**
     * Post type (for which permissions check will be done).
     *
     * @var string
     */
    protected $post_type = 'product';

    /**
     * Custom REST API handler.
     *
     * Returns products counters.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return array
     */
    public function get_counters( $request ) {
        return [
            'variations'      => $this->get_variations_count(),
            'attributesTerms' => $this->get_terms_count(),
        ];
    }

    /**
     * Get the count of published variations.
     *
     * @return int
     */
    public function get_variations_count() {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "
                    SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='product_variation' and post_status=%s;
                ",
                'publish'
            )
        );
    }

    /**
     * Get the count of all attributes terms.
     *
     * @return int
     */
    public function get_terms_count() {
        $count = 0;

        $attributes = wc_get_attribute_taxonomies();
        foreach ( $attributes as $attribute ) {
            $count += wp_count_terms( "pa_$attribute->attribute_name" );
        }

        return $count;
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
                'callback'            => [ $this, 'get_counters' ],
                'permission_callback' => [ $this, 'get_items_permissions_check' ],
            ]
        );
    }
}
