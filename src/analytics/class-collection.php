<?php
/**
 * Analytics collection.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Analytics;

use SpeedSearch\SpeedSearch;

/**
 * A class that collects analysis data (views, carts, purchases).
 */
final class Collection {

    /**
     * Constructor.
     */
    public function __construct() {

        // Product views.

        add_action(
            'wp',
            function() {
                if ( is_product() ) {
                    $product_id = get_the_ID();
                    if ( $product_id ) {
                        $this->increment_counter( $product_id, 'views' );
                    }
                }
            }
        );

        // Product added to cart.

        add_action( 'woocommerce_add_to_cart', [ $this, 'added_to_cart_action_tracker' ], 10, 3 );

        // Product purchased.

        add_filter(
            'woocommerce_order_status_processing',
            [
                $this,
                'order_paid_action_tracker',
            ],
            10,
            2
        );
    }

    /**
     * Add analytics data that will be sent.
     *
     * @param int    $product_id ID of the product.
     * @param string $key        Key (views / carts / purchases).
     * @param int    $increment  Increment.
     */
    private function increment_counter( $product_id, $key, $increment = 1 ) {
        $data = SpeedSearch::$options->get( 'analytics-data-buffer' );

        if ( ! isset( $data[ $product_id ] ) ) {
            $data[ $product_id ] = [];
        }

        if ( ! isset( $data[ $product_id ][ $key ] ) ) {
            $data[ $product_id ][ $key ] = 0;
        }

        $data[ $product_id ][ $key ] += $increment;

        SpeedSearch::$options->set( 'analytics-data-buffer', $data );
    }

    /**
     * Added to cart product action tracker.
     *
     * @param string $cart_item_key Product cart item key.
     * @param int    $product_id    The ID of the product.
     * @param int    $quantity      Quantity that was added to the cart.
     */
    public function added_to_cart_action_tracker( $cart_item_key, $product_id, $quantity ) {
        $this->increment_counter( $product_id, 'cart', $quantity );
    }

    /**
     * Order complete action tracker.
     *
     * @param int       $order_id Order ID.
     * @param \WC_Order $order    WC Order object.
     */
    public function order_paid_action_tracker( $order_id, $order ) {
        $products              = $order->get_items();
        $increment_per_product = [];

        foreach ( $products as $product ) {
            $product_id                           = $product->get_product_id();
            $increment_per_product[ $product_id ] =
                ( isset( $increment_per_product[ $product_id ] ) ? $increment_per_product[ $product_id ] : 0 ) + $product->get_quantity();
        }

        foreach ( $increment_per_product as $product_id => $quantity ) {
            $this->increment_counter( $product_id, 'purchases', $quantity );
        }
    }
}
