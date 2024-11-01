<?php
/**
 * Widgets init.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Blocks\Elementor;

use Elementor\Widgets_Manager;
use Elementor\Elements_Manager;
use Exception;
use SpeedSearch\SpeedSearch;

/**
 * Widgets init class.
 */
class Init {
    public function __construct() {
        add_action( 'elementor/widgets/register', [ $this, 'init_widgets' ] );
        add_action(  'elementor/elements/categories_registered', [ $this, 'add_widgets_category' ] );
    }

    /**
     * Imports necessary files.
     */
    private function import_files() {
        $src_files = [
            'blocks/elementor/class-widget',
        ];
        foreach ( $src_files as $file ) {
            require_once SPEEDSEARCH_DIR . 'src/' . $file . '.php';
        }
    }

    /**
     * Adds category for Elementor widgets
     *
     * @param Elements_Manager $elements_manager Elements Manager.
     */
    public function add_widgets_category( $elements_manager ) {
        // If the category is empty, it'll be automatically hidden by Elementor.
        $elements_manager->add_category(
            'speedsearch',
            array(
                'title'  => 'SpeedSearch',
                'icon'   => 'fa fa-plug',
            )
        );
    }

    /**
     * Inits widget.
     *
     * @param Widgets_Manager $widgets_manager Elementor widgets manager.
     *
     * @throws Exception Exception.
     */
    public function init_widgets( $widgets_manager ) {
        $this->import_files();

        foreach ( array_keys( SpeedSearch::$shortcodes->data ) as $shortcode_name ) {
            $widgets_manager->register( new Widget( [], null, $shortcode_name ) );
        }
    }
}
