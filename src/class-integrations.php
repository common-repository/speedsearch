<?php
/**
 * Integrations with themes, plugins.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use SpeedSearch\Assets\Assets;

/**
 * Integrations.
 */
final class Integrations {

    /**
     * Integrations dir.
     */
    const INTEGRATIONS_DIR = SPEEDSEARCH_DIR . 'src/integrations/';

    /**
     * Themes integrations dir.
     */
    const THEMES_INTEGRATIONS_DIR = self::INTEGRATIONS_DIR . 'themes/';

    /**
     * Plugins integrations dir.
     */
    const PLUGINS_INTEGRATIONS_DIR = self::INTEGRATIONS_DIR . 'plugins/';

    /**
     * Current theme integrations dir.
     *
     * @var string
     */
    public $current_theme_integrations_dir;

    /**
     * The list of integration template overrides.
     *
     * @var array
     */
    public $template_overrides;

    /**
     * Whether the current theme has integration for products (products.php).
     *
     * @var string
     */
    public $is_current_theme_products_integration_present = false;

    /**
     * Init.
     */
    public function __construct() {
        add_action( 'after_setup_theme', [ $this, 'themes_integrations' ], 11 ); // After SpeedSearch options were defined.
        $this->plugins_integrations();
    }

    /**
     * Inits themes integrations.
     *
     * Includes all files with paths SPEEDSEARCH_DIR/integrations/themes/{current-theme/{any-dir}/{file-with-the-same-name-as-its-dir}.php
     */
    public function themes_integrations() {
        $current_theme_path_parts             = explode( '/', get_template_directory() );
        $current_theme_slug                   = end( $current_theme_path_parts );
        $this->current_theme_integrations_dir = self::THEMES_INTEGRATIONS_DIR . $current_theme_slug;
        if ( SpeedSearch::$fs->is_dir( $this->current_theme_integrations_dir ) ) {

            // Adds integrations data.

            Assets::$page_embed_data = array_merge(
                Assets::$page_embed_data,
                [
                    'integrationData' => [],
                ]
            );

            /**
             * Includes single integration files.
             *
             * Includes all files with paths SPEEDSEARCH_DIR/integrations/themes/{current-theme/{any-dir}/{file-with-the-same-name-as-its-dir}.php
             */
            $integration_dir_dirs = array_diff( scandir( $this->current_theme_integrations_dir ), [ '..', '.' ] );
            foreach ( $integration_dir_dirs as $integration_name ) {
                $integration_dir = "$this->current_theme_integrations_dir/$integration_name";
                if ( SpeedSearch::$fs->is_dir( $integration_dir ) ) {

                    /**
                     * Includes integration file.
                     */
                    $integration_file = "$integration_dir/$integration_name.php";
                    $script_data      = []; // Can be populated by the theme script.
                    if ( SpeedSearch::$fs->is_file( $integration_file ) ) {
                        if ( 'products' === $integration_name ) {
                            $this->is_current_theme_products_integration_present = true;
                        }

                        require_once $integration_file;
                    }

                    $assets_integration_dir = SPEEDSEARCH_DIR . "assets-build/integrations/themes/$current_theme_slug/$integration_name";

                    /**
                     * Include integration templates.
                     */
                    if ( 'templates' === $integration_name ) {
                        $template_files = Misc::get_dir_files( $integration_dir );
                        foreach ( $template_files as $template_file ) {
                            $template_files_relative_path                               = array_values(
                                array_slice( explode( '/templates/', $template_file ), -1 )
                            )[0];
                            $this->template_overrides[ $template_files_relative_path ] = $template_file;
                        }
                    }

                    /**
                     * Includes integration scripts file.
                     */
                    $script_file = "$assets_integration_dir.js";
                    if ( SpeedSearch::$fs->is_file( $script_file ) ) {
                        add_filter(
                            'speedsearch_page_embed_data',
                            function( $page_embed_data ) use ( $current_theme_slug, $integration_name, $script_file, $script_data ) {
                                $script_handle = "speedsearch-$current_theme_slug-theme-integration-$integration_name";
                                Assets::enqueue_script(
                                    $script_handle,
                                    str_replace( SPEEDSEARCH_DIR, SPEEDSEARCH_URL, $script_file ),
                                    [],
                                    SPEEDSEARCH_VERSION
                                );

                                // Adds the integration data.

                                $page_embed_data['integrationData'] = array_merge(
                                    $page_embed_data['integrationData'],
                                    [
                                        str_replace( '-', '_', $integration_name ) => [
                                            $script_data,
                                        ],
                                    ]
                                );

                                return $page_embed_data;
                            },
                            50
                        );
                    }

                    /**
                     * Includes integration style file.
                     */
                    $style_file = "$assets_integration_dir.css";
                    if ( SpeedSearch::$fs->is_file( $style_file ) ) {
                        add_action(
                            'wp_enqueue_scripts',
                            function() use ( $current_theme_slug, $integration_name, $style_file ) {
                                $style_handle = "speedsearch-$current_theme_slug-theme-integration-$integration_name";
                                wp_enqueue_style(
                                    $style_handle,
                                    str_replace( SPEEDSEARCH_DIR, SPEEDSEARCH_URL, $style_file ),
                                    [],
                                    SPEEDSEARCH_VERSION
                                );
                            },
                            50
                        );
                    }
                }
            }
        }
    }

    /**
     * Inits plugins integrations.
     */
    private function plugins_integrations() {
        $plugins_integrations = array_diff( scandir( self::PLUGINS_INTEGRATIONS_DIR ), array( '..', '.' ) );

        $active_plugins = array_map(
            function( $v ) {
                return explode( '/', $v )[0];
            },
            apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
        );

        foreach ( $plugins_integrations as $integration_file ) {
            $plugin_slug = explode( '.', $integration_file )[0];

            $integration_file = self::PLUGINS_INTEGRATIONS_DIR . "/$integration_file";

            if ( in_array( $plugin_slug, $active_plugins, true ) ) {
                require_once $integration_file;
            }
        }
    }
}
