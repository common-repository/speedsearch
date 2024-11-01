<?php
/**
 * Widget class.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Blocks\Widgets;

use WC_Widget;
use SpeedSearch\SpeedSearch;

/**
 * Widget class.
 *
 * @see WC_Widget
 */
class Widget extends WC_Widget {

    /**
     * Shortcode data.
     *
     * @var array
     */
    protected $data;

    /**
     * Sets up a new instance.
     */
    public function __construct( $shortcode_name ) {
        $this->data   = SpeedSearch::$shortcodes->data[ $shortcode_name ];
        $widget_title = $this->data['title'];

        $this->widget_name        = 'SpeedSearch ' . $widget_title;
        $this->widget_description = 'SpeedSearch ' . $widget_title;
        $this->widget_cssclass    = "speedsearch widget_$shortcode_name";
        $this->widget_id          = "speedsearch_$shortcode_name";
        $this->settings           = $this->speedsearch_convert_arguments();

        parent::__construct();
    }

    /**
     * Converts arguments.
     *
     * @return array Arguments.
     */
    private function speedsearch_convert_arguments() {
        $arguments = $this->data['arguments'];
        $converted_arguments = [];

        foreach ( $arguments as $argument_name => $argument_data ) {
            switch ( $argument_data['type'] ) {
                case 'string':
                    $converted_arguments[ $argument_name ] = [
                        'type'  => 'text',
                        'std'   => $argument_data['default'],
                        'label' => $argument_data['label'],
                    ];

                    break;
                case 'bool':
                    $converted_arguments[ $argument_name ] = [
                        'type'  => 'checkbox',
                        'std'   => $argument_data['default'],
                        'label' => $argument_data['label'],
                    ];

                    break;
                case 'number':
                    $converted_arguments[ $argument_name ] = [
                        'type'  => 'number',
                        'std'   => $argument_data['default'],
                        'min'   => $argument_data['min'],
                        'max'   => $argument_data['max'],
                        'step'  => 1,
                        'label' => $argument_data['label'],
                    ];

                    break;
                case 'select':
                    $converted_arguments[ $argument_name ] = [
                        'type'    => 'select',
                        'std'     => $argument_data['default'],
                        'label'   => $argument_data['label'],
                        'options' => $argument_data['options']
                    ];

                    break;
            }
        }

        return $converted_arguments;
    }

    /**
     * Outputs the content for the widget instance.
     *
     * @param array $args     Display arguments including 'before_title', 'after_title',
     *                        'before_widget', and 'after_widget'.
     * @param array $instance Settings for the current widget instance.
     */
    public function widget( $args, $instance ) {
        $this->widget_start( $args, $instance );
        echo sanitize_user_field(
            'speedsearch',
            $this->data['callback']( $instance ),
            0,
            'display'
        );
        $this->widget_end( $args );
    }
}
