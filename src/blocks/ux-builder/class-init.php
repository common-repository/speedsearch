<?php
/**
 * UX_Builder init.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Blocks\UX_Builder;

use SpeedSearch\SpeedSearch;

/**
 * UX_Builder widgets init class.
 */
class Init {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'ux_builder_setup', [ $this, 'init_blocks' ] );
    }

    /**
     * Inits blocks.
     */
    public function init_blocks() {
        if ( ! function_exists( 'add_ux_builder_shortcode' ) ) {
            return;
        }

        foreach ( array_keys( SpeedSearch::$shortcodes->data ) as $shortcode_name ) {
            $this->initializer( $shortcode_name );
        }
    }

    /**
     * Single block initializer.
     *
     * @param string $shortcode_name Shortcode name.
     */
    public function initializer( $shortcode_name ) {
        $data = SpeedSearch::$shortcodes->data[ $shortcode_name ];
        $widget_title   = $data['title'];
        $shortcode_name = $data['shortcode'];

        add_ux_builder_shortcode( $shortcode_name, [
            'name'      => 'SpeedSearch ' . ucfirst( $widget_title ),
            'category'  => 'SpeedSearch',
            'options'   => $this->convert_arguments( $data['arguments'] ),
        ] );
    }

    /**
     * Converts arguments.
     *
     * @return array Arguments.
     */
    private function convert_arguments( $arguments ) {
        $converted_arguments = [];

        foreach ( $arguments as $argument_name => $argument_data ) {
            switch ( $argument_data['type'] ) {
                case 'string':
                    $converted_arguments[ $argument_name ] = [
                        'type'    => 'textfield',
                        'default' => $argument_data['default'],
                        'heading' => $argument_data['label'],
                    ];

                    break;
                case 'bool':
                    $converted_arguments[ $argument_name ] = [
                        'type'    => 'checkbox',
                        'default' => $argument_data['default'] ? 'true' : 'false',
                        'heading' => $argument_data['label'],
                    ];

                    break;
                case 'number':
                    $converted_arguments[ $argument_name ] = [
                        'type'     => 'slider',
                        'vertical' => true,
                        'heading'  => $argument_data['label'],
                        'default'  => $argument_data['default'],
                        'min'      => $argument_data['min'],
                        'max'      => $argument_data['max'],
                    ];

                    break;
                case 'select':
                    $converted_arguments[ $argument_name ] = [
                        'type'    => 'select',
                        'default' => $argument_data['default'],
                        'heading' => $argument_data['label'],
                        'options' => $argument_data['options']
                    ];

                    break;
            }
        }

        return $converted_arguments;
    }
}
