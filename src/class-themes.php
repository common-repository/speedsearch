<?php
/**
 * Themes
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;

/**
 * Class Themes.
 */
final class Themes {

    /**
     * Themes data.
     *
     * Key is theme slug and values are array of args:
     *
     * 'label' => Theme label.
     *
     * @var array[]
     */
    public static $themes_data = [];

    /**
     * Themes translations.
     *
     * @var array[]
     */
    public static $themes_translations = [];

    /**
     * Paths to different SpeedSearch themes by names.
     *
     * Key is theme slug and value is the path.
     *
     * @var array[string]
     */
    public static $theme_paths = [];

    /**
     * Init.
     *
     * Inits variables and includes theme.
     *
     * @throws Exception Exception.
     */
    public static function init() {
        self::$themes_data         = [
            'default' => [
                'label' => __( 'Default', 'speedsearch' ),
            ],
        ];
        self::$themes_translations = [
            'default' => [],
        ];

        /**
         * Imports themes.
         *
         * @param string $themes_dir_path
         */
        $import_themes = function( $themes_dir_path ) {
            $themes_dir_path = trailingslashit( $themes_dir_path );
            if ( SpeedSearch::$fs->is_dir( $themes_dir_path ) ) {
                $dir_items = array_diff( scandir( $themes_dir_path ), [ '..', '.' ] );
                foreach ( $dir_items as $item ) {
                    $theme_path = trailingslashit( $themes_dir_path . $item );
                    if ( SpeedSearch::$fs->is_dir( $theme_path ) ) {
                        $theme_file = $theme_path . 'index.php';
                        if ( ! SpeedSearch::$fs->is_file( $theme_file ) ) {
                            $error_message = sprintf(
                                /* translators: %s is a file name. */
                                __( "Can't find %s file. Either delete this SpeedSearch theme or add this file.", 'speedsearch' ),
                                $theme_file
                            );

                            trigger_error( esc_html( $error_message ), E_USER_ERROR ); // @codingStandardsIgnoreLine
                        }

                        require_once $theme_file; // Includes the theme main file.

                        // Theme slug.
                        $theme_slug = array_search( false, self::$theme_paths, true );
                        if ( $theme_slug ) {
                            self::$theme_paths[ $theme_slug ] = $theme_path;
                        }
                    }
                }
            }
        };

        // Child theme.
        $child_theme_plugin_themes_dir = get_stylesheet_directory() . '/speedsearch/themes/';
        $import_themes( $child_theme_plugin_themes_dir );

        // Parent theme.
        $parent_theme_plugin_themes_dir = get_template_directory() . '/speedsearch/themes/';
        if ( $child_theme_plugin_themes_dir !== $parent_theme_plugin_themes_dir ) { // Import only if there is a child theme.
            $import_themes( $parent_theme_plugin_themes_dir );
        }

        // If the currently selected theme's data not exists (it wasn't added from index.php), resets the selected theme.
        $current_theme_data = SpeedSearch::$options->get( 'setting-current-theme-data' );
        if ( $current_theme_data ) {
            $current_theme_slug = $current_theme_data['name'];

            if ( ! array_key_exists( $current_theme_slug, self::$themes_data ) ) {
                SpeedSearch::$options->delete( 'setting-current-theme-data' );
            }
        }
    }

    /**
     * Returns translations for the currently selected theme.
     *
     * Theme-specific translations.
     *
     * @return array
     *
     * @throws Exception Exception.
     */
    public static function get_current_theme_translations() {
        $current_theme_slug = SpeedSearch::$options->get( 'setting-current-theme-data' )['name'];
        return self::$themes_translations[ $current_theme_slug ];
    }

    /**
     * Adds a theme.
     *
     * @param string $theme_slug   Slug of the theme to add.
     * @param array  $data         Data of the theme to add.
     * @param array  $translations Theme translations (they can overwrite the default translations if their slug matches with the existing translation).
     */
    public static function add( $theme_slug, array $data, array $translations ) {
        if ( array_key_exists( $theme_slug, self::$themes_data ) ) { // If theme with this slug already exists.
            $error_message = sprintf(
                /* translators: %s is a theme slug. */
                __( 'SpeedSearch plugin theme with slug %s already added. Please use a different name.', 'speedsearch' ),
                $theme_slug
            );

            trigger_error( esc_html( $error_message ), E_USER_ERROR ); // @codingStandardsIgnoreLine
        }

        self::$themes_data[ $theme_slug ]         = $data;
        self::$themes_translations[ $theme_slug ] = $translations;
        self::$theme_paths[ $theme_slug ]         = false;
    }
}
