<?php
/**
 * Webhooks functionality.
 *
 * Creates webhooks and generates webhooks secret.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Webhooks;

use Exception;
use SpeedSearch\Custom_User;
use WC_Webhook;
use SpeedSearch\SpeedSearch;

/**
 * Manages Webhooks functionality.
 */
final class Webhooks {

    /**
     * Name of the option where the webhook secret is stored.
     */
    const WEBHOOK_SECRET_OPTION_NAME = 'speedsearch-webhooks-secret';

    /**
     * SpeedSearch Webhooks delivery URL.
     */
    const SPEEDSEARCH_WEBHOOKS_DELIVERY_URL = SPEEDSEARCH_SERVER . '/webhooks';

    /**
     * The name of the interval.
     */
    const INTERVAL_HOOK_NAME = 'speedsearch_validate_webhooks';

    /**
     * Webhooks API version.
     */
    const SPEEDSEARCH_WEBHOOKS_API_VERSION = 3;

    /**
     * Option names that stores IDs of SpeedSearch webhooks.
     */
    const ALL_SPEEDSEARCH_WEBHOOKS_OPTION_NAME = 'speedsearch-webhooks-ids';

    /**
     * Topics for which the webhooks are created.
     */
    const WEBHOOK_TOPICS = [
        'product.updated',
        'product.deleted',
        'product.restored',
        'product.created',
        // Custom webhooks @see SpeedSearch\Meta_Change_Webhooks.
        'tag.updated',
        'tag.deleted',
        'category.updated',
        'category.deleted',
        'attribute_term.updated',
        'attribute_term.deleted',
        'attribute.updated',
        'attribute.deleted',
    ];

    /**
     * Interval between SpeedSearch webhooks validation checks.
     */
    const WEBHOOKS_VALIDATION_INTERVAL = HOUR_IN_SECONDS;

    /**
     * Constructor.
     *
     * @throws Exception Exception.
     */
    public function __construct() {
        // Webhooks secret init.
        $this->init_webhooks_secret(); // Needed to be inited anyway as it's used almost for all BE requests (along with the webhooks).

        if ( ! SpeedSearch::$options->get( 'setting-do-not-use-webhooks' ) ) {
            // Custom webhooks to notify BE about meta changes.
            new Meta_Change_Webhooks(); // Also registers custom topics.

            // Creates webhooks if not created yet.
            $this->init_webhooks();

            // Interval to validate SpeedSearch webhooks.
            $this->init_speedsearch_webhooks_validation_interval();
        }

        // Unschedule an interval on plugin deactivation.
        register_deactivation_hook( SPEEDSEARCH_FILE, [ __CLASS__, 'unschedule_interval' ] );
    }

    /**
     * Generates webhooks secret if it's not generated yet.
     *
     * @throws Exception Exception.
     */
    private function init_webhooks_secret() {
        if ( ! SpeedSearch::$options->get( self::WEBHOOK_SECRET_OPTION_NAME ) ) {
            $secret = md5( microtime() . wp_rand() );
            SpeedSearch::$options->set( self::WEBHOOK_SECRET_OPTION_NAME, $secret );
        }
    }

    /**
     * Validates a webhook (that all fields match the initially set values).
     *
     * @param int    $id    Webhook ID.
     * @param string $topic Webhook topic (e.g. product.updated).
     *
     * @throws Exception Exception. A wc_get_webhook() call.
     *
     * @return bool Whether the webhook properties match the initially set ones.
     */
    private function validate_a_webhook( $id, $topic ) {
        $webhook = wc_get_webhook( $id );

        return $webhook &&
            $topic                                         === $webhook->get_topic() && // @codingStandardsIgnoreLine
            'wp_api_v' . self::SPEEDSEARCH_WEBHOOKS_API_VERSION  === $webhook->get_api_version() && // @codingStandardsIgnoreLine
            SpeedSearch::$options->get( self::WEBHOOK_SECRET_OPTION_NAME ) === $webhook->get_secret() && // @codingStandardsIgnoreLine
            self::SPEEDSEARCH_WEBHOOKS_DELIVERY_URL              === $webhook->get_delivery_url() && // @codingStandardsIgnoreLine
            $webhook->get_status() === 'active';
    }

    /**
     * Deletes a webhook.
     *
     * @param int $id Webhook ID.
     *
     * @throws Exception Exception. A wc_get_webhook() call.
     */
    private static function delete_a_webhook( $id ) {
        $webhook = wc_get_webhook( $id );
        if ( $webhook ) {
            $webhook->delete( true );
        }
    }

    /**
     * Creates a webhook.
     *
     * @param string $topic Webhook topic (e.g. product.updated).
     *
     * @return int|false Webhook ID or false is couldn't create.
     *
     * @throws Exception Exception.
     */
    private function create_a_webhook( $topic ) {
        $user_id = Custom_User::get_id();

        $webhook_id = false;

        if ( $user_id ) {
            $webhook = new WC_Webhook();
            $webhook->set_name( "SpeedSearch $topic" );
            $webhook->set_user_id( $user_id ); // User ID used while generating the webhook payload.
            $webhook->set_topic( $topic ); // Event used to trigger a webhook.
            $webhook->set_api_version( self::SPEEDSEARCH_WEBHOOKS_API_VERSION );
            $webhook->set_secret( SpeedSearch::$options->get( self::WEBHOOK_SECRET_OPTION_NAME ) ); // Secret to validate webhook when received.
            $webhook->set_delivery_url( self::SPEEDSEARCH_WEBHOOKS_DELIVERY_URL ); // URL where webhook should be sent.
            $webhook->set_status( 'active' ); // Webhook status.
            $webhook->save();
            $webhook_id = $webhook->get_id();

            // Delete duplicate webhooks, if any.

            self::duplicate_duplicate_webhooks( $user_id, $topic, $webhook_id );
        }

        return $webhook_id;
    }

    /**
     * Delete duplicate webhooks
     *
     * @param int    $user_id           User ID.
     * @param string $topic             Webhook topic.
     * @param string $webhook_id_not_is Webhook ID not is.
     */
    private static function duplicate_duplicate_webhooks( $user_id, $topic, $webhook_id_not_is ) {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE
                        FROM {$wpdb->prefix}wc_webhooks
                        WHERE user_id = %d AND topic = %s AND webhook_id <> %d",
                $user_id,
                $topic,
                $webhook_id_not_is
            )
        );
    }

    /**
     * Creates webhooks if they are not created yet.
     *
     * @throws Exception Exception.
     */
    private function init_webhooks() {
        $webhooks_ids = SpeedSearch::$options->get( self::ALL_SPEEDSEARCH_WEBHOOKS_OPTION_NAME );

        if ( ! $webhooks_ids ) {
            foreach ( self::WEBHOOK_TOPICS as $topic ) {
                $webhook_id = $this->create_a_webhook( $topic );

                if ( $webhook_id ) {
                    $webhooks_ids[ $topic ] = $webhook_id;
                }
            }

            SpeedSearch::$options->set( self::ALL_SPEEDSEARCH_WEBHOOKS_OPTION_NAME, $webhooks_ids );
        }
    }

    /**
     * Validates all SpeedSearch webhooks, and recreates the ones which are failing the validation.
     *
     * @throws Exception Exception. A $this->validate_a_webhook() and self::delete_a_webhook() calls.
     */
    public function as_validate_webhooks() {
        $webhooks_ids = SpeedSearch::$options->get( self::ALL_SPEEDSEARCH_WEBHOOKS_OPTION_NAME );

        $was_anything_changed = false;

        foreach ( self::WEBHOOK_TOPICS as $topic ) {
            if (
                Custom_User::does_the_user_exist() &&
                ! array_key_exists( $topic, $webhooks_ids ) ||
                ! $this->validate_a_webhook( $webhooks_ids[ $topic ], $topic )
            ) { // If the webhook properties do not match the initially set ones.
                if ( array_key_exists( $topic, $webhooks_ids ) ) {
                    self::delete_a_webhook( $webhooks_ids[ $topic ] );
                }

                $webhook_id = $this->create_a_webhook( $topic );

                if ( $webhook_id ) {
                    $webhooks_ids[ $topic ] = $webhook_id;
                    $was_anything_changed   = true;
                }
            }
        }

        if ( $was_anything_changed ) {
            SpeedSearch::$options->set( self::ALL_SPEEDSEARCH_WEBHOOKS_OPTION_NAME, $webhooks_ids );
        }
    }

    /**
     * Inits Action Scheduler interval for webhooks validation.
     */
    private function init_speedsearch_webhooks_validation_interval() {

        // Action.

        add_action( self::INTERVAL_HOOK_NAME, [ $this, 'as_validate_webhooks' ] );

        // Interval.

        $schedule_interval_action = function() {
            as_schedule_recurring_action(
                time(),
                self::WEBHOOKS_VALIDATION_INTERVAL,
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
    }

    /**
     * Unschedule the interval.
     */
    public static function unschedule_interval() {
        as_unschedule_all_actions( self::INTERVAL_HOOK_NAME, [], 'speedsearch' );

        \SpeedSearch\Misc::stop_running_action_scheduler_action( self::INTERVAL_HOOK_NAME );
    }

    /**
     * Disable webhooks.
     */
    public static function disable_webhooks() {

        /**
         * Unschedule the interval.
         */

        self::unschedule_interval();

        /**
         * Deletes all webhooks.
         */

        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE
                        FROM {$wpdb->prefix}wc_webhooks
                        WHERE user_id = %d",
                Custom_User::get_id()
            )
        );

        /**
         * Delete all webhooks option.
         */

        SpeedSearch::$options->delete( self::ALL_SPEEDSEARCH_WEBHOOKS_OPTION_NAME );
    }
}
