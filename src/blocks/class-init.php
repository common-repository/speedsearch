<?php
/**
 * Blocks init.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Blocks;

/**
 * Blocks init.
 *
 * @see WP_Widget
 */
class Init {

    /**
     * Blocks to init.
     *
     * @var array
     */
    public $blocks_initializers = [
        'SpeedSearch\Blocks\Widgets\Init',
        'SpeedSearch\Blocks\UX_Builder\Init',
        'SpeedSearch\Blocks\Elementor\Init',
        'SpeedSearch\Blocks\WPBakery\Init',
    ];

    /**
     * Imports necessary files.
     */
    private function import_files() {
        $src_files = [
            'blocks/widgets/class-init',
            'blocks/ux-builder/class-init',
            'blocks/elementor/class-init',
            'blocks/wpbakery/class-init',
        ];
        foreach ( $src_files as $file ) {
            require_once SPEEDSEARCH_DIR . 'src/' . $file . '.php';
        }
    }

    public function __construct() {
        $this->import_files();

        foreach ( $this->blocks_initializers as $block_initializer ) {
            new $block_initializer();
        }
    }
}
