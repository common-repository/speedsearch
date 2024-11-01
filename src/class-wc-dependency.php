<?php
/**
 * Makes WC a dependency (a requirement) to start the plugin.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * Class WC Dependency.
 */
final class WC_Dependency {

    const WC_FILE = 'woocommerce/woocommerce.php';

    /**
     * Init.
     */
    public static function add() {
        // Deactivates the plugin on activation if WC is not active.
        if ( is_admin() && current_user_can( 'activate_plugins' ) && function_exists( 'is_plugin_active' ) && ! is_plugin_active( self::WC_FILE ) ) {
            self::deactivate_speedsearch_plugin();
            if ( isset( $_GET['activate'] ) ) { // @codingStandardsIgnoreLine
                add_action( 'admin_notices', [ 'SpeedSearch\WC_Dependency', 'wc_is_not_active_admin_notice' ] );
                unset( $_GET['activate'] ); // @codingStandardsIgnoreLine
            }
        }
    }

    /**
     * WC is not active error notice.
     */
    public static function deactivate_speedsearch_plugin() {
        deactivate_plugins( SPEEDSEARCH_BASENAME );
    }

    /**
     * WC is not active error notice.
     */
    public static function wc_is_not_active_admin_notice() {
        echo sanitize_user_field(
            'speedsearch',
            '<div class="notice notice-error is-dismissible">
                <p>' . esc_html__( 'To activate SpeedSearch plugin, you must have WooCommerce plugin active.', 'speedsearch' ) . '</p>
            </div>',
            0,
            'display'
        );
    }
}
