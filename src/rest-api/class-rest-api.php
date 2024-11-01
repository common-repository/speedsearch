<?php
/**
 * WC REST API extension with custom Controllers.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use WC_REST_Product_Attribute_Terms_Controller;
use WC_REST_Products_Controller;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST_API Class.
 */
final class REST_API {

    /**
     * Constructor.
     */
    public function __construct() {
        // Adds new SpeedSearch REST API endpoints.
        add_filter( 'woocommerce_rest_api_get_rest_namespaces', [ $this, 'add_controllers' ] );

        // Extends default REST API.
        add_filter( 'rest_pre_echo_response', [ $this, 'extend_default_rest_api' ], 10, 3 );
    }

    /**
     * Extends WooCommerce REST API Controllers with custom controllers.
     *
     * @param array $controllers List of Namespaces and their controller classes.
     *
     * @return array
     */
    public function add_controllers( array $controllers ) {
        $controllers['wc/v3']['products-hash']                = 'SpeedSearch\REST_Products_Hash_Controller';
        $controllers['wc/v3']['speedsearch-settings']               = 'SpeedSearch\REST_SpeedSearch_Settings_Controller';
        $controllers['wc/v3']['speedsearch-term-products']          = 'SpeedSearch\REST_Product_Hash_Generation_Data_Controller';
        $controllers['wc/v3']['product-hash-generation-data'] = 'SpeedSearch\REST_SpeedSearch_Term_Products_Controller';
        $controllers['wc/v3']['speedsearch-backend-fix-counts']     = 'SpeedSearch\REST_SpeedSearch_Backend_Fix_Counters_Controller';
        return $controllers;
    }

    /**
     * Extends default REST API.
     *
     * @param mixed           $result  Response data to send to the client.
     * @param WP_REST_Server  $server  Server instance.
     * @param WP_REST_Request $request Request used to generate the response.
     *
     * @return array|void
     */
    public function extend_default_rest_api( $result, WP_REST_Server $server, WP_REST_Request $request ) {
        if ( ! is_array( $result ) ) { // Can be stdClass (OptinMonster plugin).
            return $result;
        }

        $attributes = $request->get_attributes();
        if (
            ! isset( $result['data'] ) && // When the request is some error or something like that, there is 'data' value present.
            $attributes && array_key_exists( 'callback', $attributes )
        ) {
            $callback = $attributes['callback'];

            if (
                is_array( $callback ) && 2 === count( $callback )
            ) {
                if ( $callback[0] instanceof WC_REST_Product_Attribute_Terms_Controller ) {
                    // Single term: products/attributes/11/terms/447
                    if ( 'get_item' === $callback[1] ) {
                        $result['speedsearch_term_meta'] = get_term_meta( $result['id'] );
                    // Terms list: products/attributes/11/terms
                    } elseif ( 'get_items' === $callback[1] ) {
                        foreach ( $result as $i => $term  ) {
                            $result[ $i ]['speedsearch_term_meta'] = get_term_meta( $term['id'] );
                        }
                    }
                } elseif ( $callback[0] instanceof WC_REST_Products_Controller ) {
                    // Single products: products/6307
                    if ( 'get_item' === $callback[1] ) {
                        Misc::enrich_product_data( $result );
                    // Many products: products
                    } elseif ( 'get_items' === $callback[1] ) {
                        foreach ( $result as &$product_data ) {
                            Misc::enrich_product_data( $product_data );
                        }
                    }
                }
            }
        }

        return $result;
    }
}
