<?php
/**
 * Widget class.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Blocks\WPBakery;

use Exception;
use SpeedSearch\SpeedSearch;

/**
 * Widget class.
 */
class Widget {

    /**
     * Widget initializer.
     *
     * @param string $shortcode_name Param name.
     *
     * @return void
     *
     * @throws Exception Exception.
     */
    public function __construct( $shortcode_name ) {
        $data         = SpeedSearch::$shortcodes->data[ $shortcode_name ];
        $widget_title = $data['title'];

        vc_map(
            [
                'base'        => $data['shortcode'],
                'name'        => 'SpeedSearch ' . $widget_title,
                'description' => 'SpeedSearch ' . $widget_title,
                'category'    => __( 'SpeedSearch' ),
                'icon'        => '',
                'params'      => $this->convert_arguments( $data['arguments'] ),
            ]
        );
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
                    $converted_arguments[] = [
                        'param_name' => $argument_name,
                        'type'       => 'textfield',
                        'heading'    => $argument_data['label'],
                        'value'      => $argument_data['default'],
                    ];

                    break;
                case 'bool':
                    $converted_arguments[] = [
                        'param_name' => $argument_name,
                        'type'       => 'checkbox',
                        'heading'    => $argument_data['label'],
                        'value'      => $argument_data['default'] ? 'yes' : '',
                    ];

                    break;
                case 'number':
                    $converted_arguments[] = [
                        'param_name' => $argument_name,
                        'type'       => 'speedsearch_bakery_number',
                        'heading'    => $argument_data['label'],
                        'value'      => $argument_data['default'],
                        'min'        => $argument_data['min'],
                        'max'        => $argument_data['max'],
                    ];

                    break;
                case 'select':
                    $converted_arguments[] = [
                        'param_name' => $argument_name,
                        'type'       => 'dropdown',
                        'heading'    => $argument_data['label'],
                        'std'        => $argument_data['default'],
                        'value'      => array_flip( $argument_data['options'] ),
                    ];

                    break;
            }
        }

        return $converted_arguments;
    }
}
