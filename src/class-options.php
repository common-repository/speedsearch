<?php
/**
 * Class to handle plugin options.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;
use SpeedSearch\Webhooks\Webhooks;

/**
 * Manages Options using the WordPress options API.
 */
final class Options {

    /**
     * The slug for options.
     */
    const OPTIONS_SLUG = 'speedsearch';

    /**
     * All options.
     *
     * Values:
     *  'default':  Default option value.           If not set, equals false.
     *  'autoload': Whether to autoload the option. If not set, equals true.
     *
     * @var array
     */
    public $all_options = [];

    /**
     * Settings options.
     *
     * @var array
     */
    public $settings_options = [];

    /**
     * Image ID options.
     *
     * @var array
     */
    public $image_id_options = [];

    /**
     * Constructor.
     */
    public function __construct() {
        $this->all_options = require SPEEDSEARCH_DIR . 'data/options.php';

        $this->define_options();
        $this->add_listeners();
    }

    /**
     * Defines different types of options.
     */
    private function define_options() {

        /**
         * Defines settings options.
         */

        foreach ( $this->all_options as $option => $option_data ) {
            if ( preg_match( '@^speedsearch-setting-@', $option ) ) {
                $this->settings_options[] = $option;
            }
        }

        /**
         * Defines image ID options.
         */

        foreach ( $this->all_options as $option => $option_data ) {
            if (
                array_key_exists( 'type', $option_data ) &&
                'image_id' === $option_data['type']
            ) {
                $this->image_id_options[] = $option;
            }
        }
    }

    /**
     * Adds listeners for options to execute the logic on their change.
     */
    private function add_listeners() {
        $options_with_handlers = [
            'speedsearch-setting-do-not-use-webhooks' => [ $this, 'do_not_use_webhooks_option' ],
        ];

        foreach ( $options_with_handlers as $option => $handler ) {
            add_action( "delete_option_$option", $handler, 10, 1 );
            add_action( "add_option_$option", $handler, 10, 2 );
            add_action( "update_option_$option", $handler, 10, 3 );
        }
    }

    /**
     * Handles "do not use webhooks" option change.
     *
     * @param mixed  $old_value The old option value.
     * @param mixed  $value     The new option value.
     * @param string $option    Option.
     *
     * @throws Exception Exception.
     */
    public function do_not_use_webhooks_option( $old_value = null, $value = null, $option = null ) {
        if ( null === $value ) { // delete_option_$option (1 args).
            ; // Add some placeholder to not get CS violation.
        } else {
            if (
                null !== $option &&
                $value !== $old_value
            ) { // update_option_$option (3 args).
                if ( $value ) {
                    Webhooks::disable_webhooks();
                }
            } else { // add_option_$option (2 args).
                if ( $value ) {
                    Webhooks::disable_webhooks();
                }
            }
        }
    }

    /**
     * Validates option name, and if it does not exist, throws an exception.
     *
     * @param string $option_name Name of the option to validate.
     *
     * @throws Exception Exception.
     */
    private function validation_option( $option_name ) {
        if ( ! array_key_exists( $option_name, $this->all_options ) ) {
            throw new Exception( SpeedSearch::$name . ': ' . esc_html__( 'Unknown option name:', 'speedsearch' ) . ' ' . esc_html( $option_name ) );
        }
    }

    /**
     * Gets the option value. Returns the default value if the value does not exist.
     *
     * @param string $option_name Name of the option to get.
     *
     * @return mixed Option value.
     *
     * @throws Exception Exception.
     */
    public function get( $option_name ) {
        try {
            $this->validation_option( $option_name );
        } catch ( Exception $e ) {
            $option_name = self::OPTIONS_SLUG . '-' . $option_name;
            $this->validation_option( $option_name );
        }

        $option_data = $this->all_options[ $option_name ];
        return get_option( $option_name, array_key_exists( 'default', $option_data ) ? $option_data['default'] : false );
    }

    /**
     * Sets the option. Update the value if the option for the given name already exists.
     *
     * @param string $option_name Name of the option to set.
     * @param mixed  $value       Value to set for the option.
     *
     * @throws Exception Exception.
     */
    public function set( $option_name, $value ) {
        try {
            $this->validation_option( $option_name );
        } catch ( Exception $e ) {
            $option_name = self::OPTIONS_SLUG . '-' . $option_name;
            $this->validation_option( $option_name );
        }

        $option_data = $this->all_options[ $option_name ];
        update_option( $option_name, $value, array_key_exists( 'autoload', $option_data ) ? $option_data['autoload'] : null );
    }

    /**
     * Deletes the option value.
     *
     * @param string $option_name Name of the option to delete.
     *
     * @throws Exception Exception.
     */
    public function delete( $option_name ) {
        try {
            $this->validation_option( $option_name );
        } catch ( Exception $e ) {
            $option_name = self::OPTIONS_SLUG . '-' . $option_name;
            $this->validation_option( $option_name );
        }

        delete_option( $option_name );
    }
}
