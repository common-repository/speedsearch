<?php
/**
 * REST API Custom Controller for SpeedSearch Settings.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;
use SpeedSearch\Webhooks\Webhooks;
use SpeedSearch\Sync_Data_Feed\Sync_Data_Feed;
use WP_Error;
use WP_REST_Request;

/**
 * REST API Custom Controller class.
 */
class REST_SpeedSearch_Settings_Controller {

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
    protected $rest_base = 'speedsearch-settings';

    /**
     * Post type (for which permissions check will be done).
     *
     * @var string
     */
    protected $post_type = 'product';

    /**
     * Custom REST API handler.
     *
     * Returns SpeedSearch settings.
     *
     * @return array
     * @throws Exception Exception.
     */
    public static function get_settings() {
        $feed_last_file_index = SpeedSearch::$options->get( 'feed-last-file-index' );
        $feed_last_item_index = SpeedSearch::$options->get( 'feed-last-item-index' );

        global $wpdb;

        $total_published_products = (int) $wpdb->get_var(
                "SELECT COUNT(*)
            FROM {$wpdb->posts} AS p
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'"
        );

        return [
            'speedsearch_webhooks_secret'         => SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
            'woocommerce_hide_out_of_stock_items' => 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ),
            'version'                             => SPEEDSEARCH_VERSION,
            'analytics_ageing'                    => [
                'enabled'   => ! ! SpeedSearch::$options->get( 'setting-enable-analytics-ageing' ),
                'half_life' => (int) SpeedSearch::$options->get( 'setting-analytics-ageing-half-life' ),
            ],
            'feed'                                => [
                'url'             => trailingslashit( Sync_Data_Feed::get_feed_url() ),
                'last_file_index' => null !== $feed_last_file_index ? (int) $feed_last_file_index : null,
                'last_item_index' => null !== $feed_last_item_index ? (int) $feed_last_item_index : null,
            ],
            'total_published_products'            => $total_published_products,
        ];
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
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'get_items_permissions_check' ],
            ]
        );
    }
}
