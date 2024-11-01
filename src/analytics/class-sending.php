<?php
/**
 * Analytics sending.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Analytics;

use SpeedSearch\SpeedSearch;
use SpeedSearch\Backend_Requests;

/**
 * A class for analytics data sending.
 */
final class Sending {

    /**
     * Interval.
     */
    const INTERVAL = HOUR_IN_SECONDS;

    /**
     * Hook name of the interval.
     */
    const INTERVAL_HOOK_NAME = 'speedsearch_analytics_data_sending';

    /**
     * Constructor.
     */
    public function __construct() {

        // Action.

        add_action( self::INTERVAL_HOOK_NAME, [ $this, 'send_the_analytics_data' ] );

        // Schedule an Interval.

        $schedule_interval_action = function() {
            as_schedule_recurring_action(
                time() + self::INTERVAL,
                self::INTERVAL,
                self::INTERVAL_HOOK_NAME,
                [],
                'speedsearch',
                true
            );
        };
        if ( did_action( 'action_scheduler_init' ) ) {
            $schedule_interval_action();
        } else {
            add_action( 'action_scheduler_init', $schedule_interval_action );
        }

        // Unschedule an interval on plugin deactivation.
        register_deactivation_hook( SPEEDSEARCH_FILE, [ __CLASS__, 'unschedule_interval' ] );
    }

    /**
     * Unschedule the interval.
     */
    public static function unschedule_interval() {
        as_unschedule_all_actions( self::INTERVAL_HOOK_NAME, [], 'speedsearch' );
        \SpeedSearch\Misc::stop_running_action_scheduler_action( self::INTERVAL_HOOK_NAME );
    }

    /**
     * Send the analytics data.
     */
    public function send_the_analytics_data() {
        $data = SpeedSearch::$options->get( 'analytics-data-buffer' );

        if ( $data ) {
            $response = Backend_Requests::send_analytics_data( $data );

            if (
                ! is_wp_error( $response ) &&
                200 === wp_remote_retrieve_response_code( $response )
            ) {
                SpeedSearch::$options->delete( 'analytics-data-buffer' );
            }
        }
    }
}
