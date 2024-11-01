<?php
/**
 * TinyMCE SpeedSearch block.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * A class for TinyMCE.
 */
final class TinyMCE {

    /**
     * Init.
     */
    public function __construct() {
        add_action( 'admin_head', [ $this, 'init' ] );

        // Load styles.

        add_action(
            'current_screen',
            function () {
                $screen = get_current_screen();

                if ( 'post' === $screen->base ) {
                    $this->load_styles();
                }
            }
        );
    }

    /**
     * Adds a TinyMCE button.
     *
     * @see ux_shortcode_button
     */
    public function init() {

        // Check user permissions.
        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
            return;
        }

        // Check if WYSIWYG is disabled.
        if ( 'true' !== get_user_option( 'rich_editing' ) ) {
            return;
        }

        // Init.

        add_filter( 'mce_external_plugins', [ $this, 'add_tinymce_plugin' ] );
        add_filter( 'mce_buttons', [ $this, 'add_tinymce_button' ] );

        // Data for plugin script.

        $shortcodes_data = array_map( // Get rid of the callbacks otherwise json_encode will not work.
            function ( $v ) {
                unset( $v['callback'] );
                return $v;
            },
            SpeedSearch::$shortcodes->data
        );

        wp_localize_script(
            'wp-tinymce',
            'speedsearch_tinyMCEDAta',
            [
                'data' => $shortcodes_data,
                'txt'  => [
                    'noOptionsAvailable' => __( 'No options available', 'speedsearch' ),
                ],
            ]
        );
    }

    /**
     * Add a custom TinyMCE plugin.
     *
     * @param array $plugin_array Plugins array.
     *
     * @return array Plugins.
     */
    public function add_tinymce_plugin( $plugin_array ) {
        $plugin_array['speedsearch_add_shortcodes'] = SPEEDSEARCH_URL . 'assets-build/admin/tinymce-plugin.js';
        return $plugin_array;
    }

    /**
     * Add a custom TinyMCE button.
     *
     * @param array $buttons Plugins array.
     *
     * @return array Buttons.
     */
    public function add_tinymce_button( $buttons ) {
        array_push( $buttons, 'speedsearch_add_shortcodes' );
        return $buttons;
    }

    /**
     * Load TinyMCE plugin styles.
     */
    public function load_styles() {
        wp_enqueue_style(
            'speedsearch-tinymce-plugin',
            SPEEDSEARCH_URL . 'assets-build/admin/tinymce-plugin.css',
            [],
            SPEEDSEARCH_VERSION
        );
    }
}
