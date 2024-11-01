<?php
/**
 * Widgets init.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Blocks\Widgets;

use SpeedSearch\SpeedSearch;

/**
 * Widgets init class.
 */
class Init {
    public function __construct() {
        add_action( 'widgets_init', [ $this, 'init_widgets' ] );
    }

    /**
     * Imports necessary files.
     */
    private function import_files() {
        $src_files = [
            'blocks/widgets/class-widget',
        ];
        foreach ( $src_files as $file ) {
            require_once SPEEDSEARCH_DIR . 'src/' . $file . '.php';
        }
    }

    public function init_widgets() {
        $this->import_files();

        foreach ( array_keys( SpeedSearch::$shortcodes->data ) as $shortcode_name ) {
            register_widget( new Widget( $shortcode_name ) );
        }
    }
}
