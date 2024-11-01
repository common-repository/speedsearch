<?php
/**
 * Plugin initial setup.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;

/**
 * Class Initial_Setup.
 */
final class Initial_Setup {

    /**
     * Constructor.
     */
    public function __construct() {
        /**
         * Add a "speedsearch_authed_success" URL param to the page to which the redirect occurs after the plugin auth is approved.
         *
         * The reason: Usually, it takes a longer time for the BE to process the auth than for the browser to refresh the page,
         *             which means the second visit after the auth can lead to the auth page again (as asking BE "is auth done?" will lead to the "No" response).
         *             And this will lead either to the double auth, or the user will be just confused (like: "I already authed. Why it asks me to auth again?").
         *
         * So we simply add a "speedsearch_authed_success" parameter to the page on which the plugin redirects after the auth,
         * and then check this param instead of making BE auth-asking request.
         */
        add_action(
            'woocommerce_auth_page_header',
            function() {
                add_filter( // Add a URL param to the escaped string.
                    'clean_url',
                    function( $escaped_string ) {
                        parse_str( html_entity_decode( $escaped_string ), $url_params );

                        if (
                            str_contains( $escaped_string, '/access_granted/?app_name=SpeedSearch' ) && // Only for speedsearch.
                            ! str_contains( $escaped_string, 'speedsearch_authed_success' ) && // Do not infinite loop.
                            isset( $url_params['wc_auth_nonce'] ) &&
                            wp_verify_nonce( $url_params['wc_auth_nonce'], 'wc_auth_grant_access' ) &&
                            isset( $url_params['return_url'] )
                        ) {
                            return esc_html(
                                add_query_arg(
                                    'return_url',
                                    rawurlencode(
                                        add_query_arg(
                                            'speedsearch_authed_success',
                                            '1',
                                            $url_params['return_url']
                                        )
                                    ),
                                    html_entity_decode( $escaped_string )
                                )
                            );
                        }

                        return $escaped_string;
                    }
                );
            }
        );
    }

    /**
     * Checks whether SpeedSearch license is active.
     *
     * @return bool
     */
    public static function is_license_active() {
            return true;
    }

    /**
     * Get license key, if present.
     *
     * @return string License key. Or '' if license is not active.
     */
    public static function get_license_key() {
        return 'active' === get_option( 'speedsearch__license_status' ) ?
            get_option( 'speedsearch__license_key' ) :
            '';
    }

    /**
     * Checks whether the current store is authorized in the SpeedSearch BE.
     *
     * @return bool
     */
    public static function is_store_authorized() {
        // Right after the initial auth (after redirect), do not check for this.
        if ( isset( $_GET['speedsearch_authed_success'] ) && '1' === $_GET['speedsearch_authed_success'] ) { // @codingStandardsIgnoreLine
            return true;
        }

        $result = (bool) get_transient( 'speedsearch_store_authorized' );

        if ( ! $result ) {
            $store  = Backend_Requests::get( 'stores', [], false, true );
            $result = isset( $store['id'] );

            if ( true === $result ) {
                set_transient( 'speedsearch_store_authorized', true, 4 * HOUR_IN_SECONDS );
                SpeedSearch::$options->set( 'store-was-authorised', true );
            }
        }

        // Send admin credentials.

        if (
            $result &&
            ! SpeedSearch::$options->get( 'were-admin-credentials-sent' ) &&
            Backend_Requests::send_admin_credentials() // Send admin credentials.
        ) {
            SpeedSearch::$options->set( 'were-admin-credentials-sent', true );
        }

        return $result;
    }

    /**
     * Initial sync generation progress (0 to 100).
     *
     * @return int|float Initial sync generation progress.
     */
    public static function get_initial_sync_generation_progress() {
        if ( self::is_store_synced() ) {
            return 100;
        }

        if ( SpeedSearch::$options->get( 'initial-feed-generation-complete' ) ) {
            $sync_status = Backend_Requests::get( 'sync_status' );

            if ( isset( $sync_status['feed']['lastProcessedIndex'] ) ) {
                $last_processed_index                   = $sync_status['feed']['lastProcessedIndex'];
                $initial_feed_generation_complete_index = (int) SpeedSearch::$options->get( 'initial-feed-generation-complete-on-index' );

                if ( $last_processed_index >= $initial_feed_generation_complete_index ) {
                    return 100;
                } else {
                    // Second 50 percents.
                    return min( 50 + round( $last_processed_index / $initial_feed_generation_complete_index * 100 / 2, 2 ), 100 );
                }
            } else {
                return 50;
            }
        } else {
            $progress = SpeedSearch::$options->get( 'feed-generation-progress' );

            if ( isset( $progress['generated'], $progress['total'] ) ) {
                // First 50 percents.
                return max( round( $progress['generated'] / $progress['total'] * 100 / 2, 2 ), 0 );
            } else {
                return 0;
            }
        }
    }

    /**
     * Get current user data for BE send.
     */
    public static function get_current_user_data_to_send_to_be() {
        $current_user = wp_get_current_user();

        return [
            'email' => $current_user->user_email,
            'name'  => trim( $current_user->first_name . ' ' . $current_user->last_name ),
        ];
    }

    /**
     * Checks whether the current store was synced in the SpeedSearch BE.
     *
     * @param bool $consider_setting_instead_of_getting_true_value Consider the setting (do-not-wait-for-sync-to-finish) instead of getting the true value.
     *
     * @return bool
     *
     * @throws Exception Exception.
     */
    public static function is_store_synced( $consider_setting_instead_of_getting_true_value = true ) {
        if (
            $consider_setting_instead_of_getting_true_value &&
            '1' === SpeedSearch::$options->get( 'setting-do-not-wait-for-sync-to-finish' )
        ) {
            return true;
        }

        if ( SpeedSearch::$options->get( 'synced' ) ) {
            return true;
        }

        if ( ! self::is_store_authorized() ) {
            return false;
        }

        $is_store_synced = false;

        if ( ! SpeedSearch::$options->get( 'initial-feed-generation-complete' ) ) {
            return false;
        }

        $sync_status = Backend_Requests::get( 'sync_status' );
        if ( isset( $sync_status['feed']['lastProcessedIndex'] ) ) {
            $last_processed_index = $sync_status['feed']['lastProcessedIndex'];

            $initial_feed_generation_complete_index = (int) SpeedSearch::$options->get( 'initial-feed-generation-complete-on-index' );

            if ( $last_processed_index >= $initial_feed_generation_complete_index ) {
                $is_store_synced = true;
            }
        }

        if ( $is_store_synced ) {
            SpeedSearch::$options->set( 'synced', true );
        }

        return $is_store_synced;
    }

    /**
     * Whether the sync progress notification should be shown.
     *
     * @return bool
     */
    public static function should_the_sync_progress_message_be_shown() {
        return self::is_store_authorized() &&
            ! self::is_store_synced();
    }
}
