<?php
/**
 * Widgets init.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Blocks\WPBakery;

use Exception;
use SpeedSearch\SpeedSearch;

/**
 * Widgets init class.
 */
class Init {
    public function __construct() {
        add_action( 'vc_before_init', [ $this, 'register_custom_param_types' ] );
        add_action( 'vc_before_init', [ $this, 'init_widgets' ] );
    }

    /**
     * Imports necessary files.
     */
    private function import_files() {
        $src_files = [
            'blocks/wpbakery/class-widget',
        ];
        foreach ( $src_files as $file ) {
            require_once SPEEDSEARCH_DIR . 'src/' . $file . '.php';
        }
    }

    /**
     * Registers custom parameter types.
     *
     * @throws Exception Exception.
     */
    public function register_custom_param_types() {
        /**
         * A speedsearch_bakery_number for numbers.
         */
        vc_add_shortcode_param(
            'speedsearch_bakery_number',
            function( $settings, $value ) {
                $defaults = [
                    'param_name' => '',
                    'type'       => '',
                    'min'        => 0,
                    'max'        => 100,
                    'step'       => 1,
                    'value'      => 0,
                    'suffix'     => '',
                    'class'      => '',
                ];
                $settings = wp_parse_args( $settings, $defaults );

                $output  = '<div class="speedsearch-bakery-number-wrap">';
                $output .= '<input type="number" min="' . esc_attr( $settings['min'] ) . '" max="' .
                        esc_attr( $settings['max'] ) . '" step="' . esc_attr( $settings['step'] ) . '" class="wpb_vc_param_value ' .
                        esc_attr( $settings['param_name'] ) . ' ' . esc_attr( $settings['type'] ) . ' ' . esc_attr( $settings['class'] ) .
                        '" name="' . esc_attr( $settings['param_name'] ) . '" value="' . esc_attr( $value ) . '"/>' . esc_attr( $settings['suffix'] );
                $output .= '</div>';
                return $output;
            }
        );
    }

    /**
     * Inits widget.
     *
     * @throws Exception Exception.
     */
    public function init_widgets() {
        if ( defined( 'WPB_VC_VERSION' ) && version_compare( WPB_VC_VERSION, 4.8 ) >= 0 ) {
            $this->import_files();

            foreach ( array_keys( SpeedSearch::$shortcodes->data ) as $shortcode_name ) {
                new Widget( $shortcode_name );
            }
        }
    }
}
