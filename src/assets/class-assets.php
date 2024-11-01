<?php
/**
 * Assets
 *
 * Main assets' handler.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Assets;

use Exception;
use SpeedSearch\Initial_Elements_Rendering\Elements_Rendering_Data;
use SpeedSearch\Posts;
use SpeedSearch\SpeedSearch;
use SpeedSearch\Themes;
use SpeedSearch\Demo_Mode as Demo;
use SpeedSearch\JSON_AJAX_Cache\Base as JSON_AJAX_Cache_Base;

/**
 * Assets class.
 */
final class Assets {

    /**
     * SpeedSearch object initial data that will be embedded to the page.
     *
     * @var array
     */
    public static $page_embed_data = [];

    /**
     * Main init.
     */
    public static function init() {

        // Common assets.

        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'init_common_assets' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'init_common_assets' ] );

        // Public assets.

        new Publ();

        // Public admin assets.

        if ( is_user_logged_in() && current_user_can( 'edit_theme_options' ) ) {
            new Public_Admin();
        }

        // Screens assets.

        add_action(
            'current_screen',
            function () {
                $screen = get_current_screen();

                // Plugins assets screen.

                if ( 'plugins' === $screen->base ) {
                    new Screens\Plugins();

                    add_action(
                        'admin_head',
                        function() {
                            Assets::print_top_prio_scripts();
                        },
                        0
                    );
                }

                // Admin publish-box assets.

                if ( 'post' === $screen->base ) {
                    new Screens\Post();

                    add_action(
                        'admin_head',
                        function() {
                            Assets::print_top_prio_scripts();
                        },
                        0
                    );
                }
            }
        );

        // Defer scripts.

        add_filter(
            'script_loader_tag',
            [ __CLASS__, 'defer_scripts' ],
            10,
            3
        );
    }


    /**
     * Common init.
     *
     * @throws Exception Exception.
     */
    public static function init_common_assets() {
        self::register_common_libs();
        self::register_common_scripts();
        self::register_common_styles();
    }


    /**
     * Registers common libs.
     */
    private static function register_common_libs() {

        // DataTables.

        wp_register_style(
            'speedsearch-lib-datatables',
            SPEEDSEARCH_URL . 'libs/DataTables/datatables.min.css',
            [],
            '1.13.9'
        );

        self::register_script(
            'speedsearch-lib-datatables',
            SPEEDSEARCH_URL . 'libs/DataTables/datatables.min.js',
            [
                'jquery',
            ],
            '1.13.9'
        );

        // nouislider.

        self::register_script(
            'speedsearch-lib-nouislider',
            SPEEDSEARCH_URL . 'libs/nouislider/nouislider.min.js',
            [],
            '15.2.0'
        );
        wp_register_style(
            'speedsearch-lib-nouislider',
            SPEEDSEARCH_URL . 'libs/nouislider/nouislider.min.css',
            [],
            '15.2.0'
        );

        // flatpickr.

        self::register_script(
            'speedsearch-lib-flatpickr-plugin-range',
            SPEEDSEARCH_URL . 'libs/flatpickr/plugins/rangePlugin.min.js',
            [],
            '4.6.13'
        );
        self::register_script(
            'speedsearch-lib-flatpickr',
            SPEEDSEARCH_URL . 'libs/flatpickr/flatpickr.min.js',
            [ 'speedsearch-lib-flatpickr-plugin-range' ],
            '4.6.13'
        );

        wp_register_style(
            'speedsearch-lib-flatpickr',
            SPEEDSEARCH_URL . 'libs/flatpickr/themes/airbnb.min.css',
            [],
            '4.6.13'
        );

        // SortableJS.

        self::register_script(
            'speedsearch-lib-sortablejs',
            SPEEDSEARCH_URL . 'libs/sortablejs/Sortable.min.js',
            [],
            '1.14.0'
        );

        // Common admin script.

        self::register_script(
            'speedsearch-admin-script',
            SPEEDSEARCH_URL . 'assets-build/admin/index.js',
            [],
            SPEEDSEARCH_VERSION
        );
    }


    /**
     * Registers common scripts.
     *
     * @throws Exception Exception.
     */
    private static function register_common_scripts() {
        $filters_script_deps = [ 'speedsearch-lib-nouislider', 'speedsearch-lib-flatpickr' ];
        self::register_script(
            'speedsearch-common-filters',
            SPEEDSEARCH_URL . 'assets-build/common/filters.js',
            $filters_script_deps,
            SPEEDSEARCH_VERSION
        );

        // Loads theme-specific common/filters.js.

        $current_theme_slug = SpeedSearch::$options->get( 'setting-current-theme-data' )['name'];
        if ( 'default' !== $current_theme_slug ) {
            $theme_path      = Themes::$theme_paths[ $current_theme_slug ];
            $asset_file_path = $theme_path . 'assets-build/common/filters.js';
            if ( SpeedSearch::$fs->is_file( $asset_file_path ) ) {
                wp_deregister_script( 'speedsearch-common-filters' );

                self::register_script(
                    'speedsearch-common-filters-parent',
                    SPEEDSEARCH_URL . 'assets-build/common/filters.js',
                    [],
                    SPEEDSEARCH_VERSION
                );

                $filters_script_deps[] = 'speedsearch-common-filters-parent';
                self::register_script(
                    'speedsearch-common-filters',
                    SpeedSearch::convert_path_to_url( $asset_file_path ),
                    $filters_script_deps,
                    SPEEDSEARCH_VERSION
                );
            }
        }
    }

    /**
     * Registers common styles.
     *
     * @throws Exception Exception.
     */
    private static function register_common_styles() {
        $filters_style_deps = [ 'speedsearch-lib-nouislider', 'speedsearch-lib-flatpickr' ];
        wp_register_style(
            'speedsearch-common-filters',
            SPEEDSEARCH_URL . 'assets-build/common/filters.css',
            $filters_style_deps,
            SPEEDSEARCH_VERSION
        );

        // Loads theme-specific common/filters.css.

        $current_theme_slug = SpeedSearch::$options->get( 'setting-current-theme-data' )['name'];
        if ( 'default' !== $current_theme_slug ) {
            $theme_path      = Themes::$theme_paths[ $current_theme_slug ];
            $asset_file_path = $theme_path . 'assets-build/common/filters.css';
            if ( SpeedSearch::$fs->is_file( $asset_file_path ) ) {
                wp_deregister_style( 'speedsearch-common-filters' );

                wp_register_style(
                    'speedsearch-common-filters-parent',
                    SPEEDSEARCH_URL . 'assets-build/common/filters.css',
                    [],
                    SPEEDSEARCH_VERSION
                );

                $filters_style_deps[] = 'speedsearch-common-filters-parent';
                wp_register_style(
                    'speedsearch-common-filters',
                    SpeedSearch::convert_path_to_url( $asset_file_path ),
                    $filters_style_deps,
                    SPEEDSEARCH_VERSION
                );
            }
        }
    }


    /**
     * Populates $page_embed_data object that will contain the initial 'speedsearch' object data.
     *
     * @throws Exception Exception.
     */
    private static function init_page_embed_data() {
        $settings = Elements_Rendering_Data::get_public_settings();

        self::$page_embed_data = array_merge(
            self::$page_embed_data,
            [
                'isAdmin'                => is_admin(),
                'version'                => SPEEDSEARCH_VERSION,
                // It's added separately because sometimes this variable is used when tags are hidden
                // (for example, by Picfee theme by "Thema" (tags-like) filter).
                'tags_initialShowLimit'  => [ 'data' => Elements_Rendering_Data::TAGS_INITIAL_SHOW_LIMIT ],
                'settings'               => $settings,
                'lastSettingsUpdateTime' => (int) SpeedSearch::$options->get( 'last-settings-update-time' ),
                'lastCacheFlushTime'     => (int) SpeedSearch::$options->get( 'cache-last-flush-time' ),
                // array_values() to make sure no indexes are missing, otherwise will be returned JSON Object (and nor array), which will break JS.
                'bodyClasses'            => array_values( get_body_class() ), // For pre-body fetching classes retrieval.
                'defaultSortByValue'     => isset( Elements_Rendering_Data::get_filters()['filters']['sort-by'] ) ?
                    Elements_Rendering_Data::get_filters()['filters']['sort-by']['defaultValue'] : false, // For pre-body fetching classes retrieval.
                'postsPerPage'           => $settings['pagination']['infiniteScroll']['isEnabled'] ?
                    $settings['pagination']['infiniteScroll']['postsPerBlock'] : $settings['pagination']['postsPerPage'],
                'filters'                => Elements_Rendering_Data::get_filters(),
                'categoriesData'         => Elements_Rendering_Data::get_categories(),
                // Sometimes products should not be shown (when no filters), and only the category archives.
                'productsWillDisplay'    => 'subcategories' !== woocommerce_get_loop_display_mode(),
                'loopDisplayMode'        => woocommerce_get_loop_display_mode(),
            ]
        );

        if (
            SpeedSearch::$options->get( 'setting-display-tags' ) ||
            is_product_tag()
        ) {
            self::$page_embed_data = array_merge(
                self::$page_embed_data,
                [
                    'tags' => Elements_Rendering_Data::get_tags(),
                ]
            );
        }

        $is_shop_page = is_shop() || is_product_taxonomy();

        $current_screen                 = is_admin() ? get_current_screen() : null;
        $is_speedsearch_appearance_menu = $current_screen && 'speedsearch_page_speedsearch-appearance' === $current_screen->id; // To make the menu to load faster.

        if ( $is_shop_page ) {
            if ( is_product_category() ) { // Fix a bug for category archives when they behave erratically.
                self::$page_embed_data = array_replace_recursive(
                    self::$page_embed_data,
                    [
                        'settings' => [
                            'categoriesStructure' => [
                                'addCategoriesAsUrlParts' => true,
                                'type'                    => 'full-without-shop-page',
                                'categories-prefix'       => ( (array) get_option( 'woocommerce_permalinks', [] ) )['category_base'],
                            ],
                            'allowToDeselectCategoryOnCategoryArchivePages' => SpeedSearch::$options->get( 'setting-allow-to-deselect-category-on-category-archive-pages' ),
                        ],
                    ]
                );
            }
        }

        if ( is_shop() || is_product_category() ) {
            self::$page_embed_data['categoriesHTML'] = woocommerce_maybe_show_product_subcategories();
        }

        if ( $is_speedsearch_appearance_menu ) {
            self::$page_embed_data = array_merge(
                self::$page_embed_data,
                [
                    'adminData'        => [
                        'autocompleteHeadings' => SpeedSearch::$options->get( 'setting-attribute-filters-autocomplete-headings' ),
                    ],
                    'allThemesData'    => Themes::$themes_data,
                    'currentThemeData' => SpeedSearch::$options->get( 'setting-current-theme-data' ),
                    'postsSettings'    => Posts::get_posts_settings(),
                ]
            );
        }

        $hidden_filters = SpeedSearch::$options->get( 'setting-hidden-filters' );

        if ( Demo::is_in_demo_mode() || $is_speedsearch_appearance_menu ) {
            $filters_limits = Demo::get_filters_limits(); // Return limits for demo mode.
        } else {
            $filters_limits = [];
            if ( ! in_array( 'date', $hidden_filters, true ) ) {
                $filters_limits['date'] = [
                    'min' => $is_shop_page ? SpeedSearch::$ajax->get_date_min( true, true ) : '',
                    'max' => $is_shop_page ? SpeedSearch::$ajax->get_date_max( true, true ) : '',
                ];
            }

            if ( ! in_array( 'price', $hidden_filters, true ) ) {
                $filters_limits['price'] = [
                    'min' => $is_shop_page ? SpeedSearch::$ajax->get_price_min( true, true ) : '',
                    'max' => $is_shop_page ? SpeedSearch::$ajax->get_price_max( true, true ) : '',
                ];
            }
        }

        self::$page_embed_data = array_merge(
            self::$page_embed_data,
            [
                'filtersLimits' => $filters_limits,
            ]
        );

        // Public data (not for admin but for front-end).

        $speedsearch_pages_ids = [
            'toplevel_page_speedsearch',
            'speedsearch_page_speedsearch-analytics',
            'speedsearch_page_speedsearch-appearance',
            'speedsearch_page_speedsearch-settings',
            'speedsearch_page_speedsearch-advanced',
            'product', // Post.
        ];

        if (
            is_admin() &&
            isset( $GLOBALS['current_screen'] ) &&
            in_array( $GLOBALS['current_screen']->id, $speedsearch_pages_ids, true )
        ) {
            $translations = self::get_admin_translations();

            $current_theme_translations = Themes::get_current_theme_translations();

            self::$page_embed_data['txt'] = array_replace_recursive(
                $translations,
                $current_theme_translations,
                isset( self::$page_embed_data['txt'] ) ? self::$page_embed_data['txt'] : []
            );

            self::$page_embed_data = array_merge(
                self::$page_embed_data,
                [
                    'nonceToken' => wp_create_nonce( 'speedsearch-menu' ),
                ]
            );
        } else {
            $data = [
                'ajaxurl'   => admin_url( 'admin-ajax.php' ),
                'imageUrls' => [
                    'placeholderImage'   => Posts::get_placeholder_image_url(),
                    'somethingWentWrong' => Posts::get_something_went_wrong_image_url(),
                ],
            ];

            if ( is_tax() ) {
                $queried_object = get_queried_object();
                if ( isset( $queried_object->term_id ) ) {
                    $data['currentTermName'] = $queried_object->name;
                }
            }

            self::$page_embed_data['txt'] = array_replace_recursive(
                Elements_Rendering_Data::get_public_translations(),
                isset( self::$page_embed_data['txt'] ) ? self::$page_embed_data['txt'] : []
            );

            self::$page_embed_data = array_merge(
                self::$page_embed_data,
                $data
            );
        }

        /**
         * Filter the page embed data (that will be added to SpeedSearch main object)
         *
         * @param array $page_embed_data Page embed data (SpeedSearch object initial values).
         */
        self::$page_embed_data = apply_filters( 'speedsearch_page_embed_data', self::$page_embed_data );
    }

    /**
     * Get admin translations.
     *
     * @return array Admin translations.
     */
    public static function get_admin_translations() {
        return [
            'resetAllFiltersNotice'                   => __( 'Are you sure you want to reset all plugin settings?', 'speedsearch' ),
            'back'                                    => __( 'Go Back', 'speedsearch' ),
            'image'                                   => __( 'Image', 'speedsearch' ),
            'color'                                   => __( 'Color', 'speedsearch' ),
            'assignSwatchesToFields'                  => __( 'Assign swatches to fields', 'speedsearch' ),
            'toggles'                                 => SpeedSearch::$options->get( 'setting-toggles-heading' ),
            'forceSyncNoticePart1'                    => __( 'Are you sure you want to force reindexing of all products?', 'speedsearch' ),
            'forceSyncNoticePart2'                    => __( 'If you stay on this page, you will see a notification when the reindexing is complete.', 'speedsearch' ),
            'forceSyncFinished'                       => __( 'Reindexing finished successfully.', 'speedsearch' ),
            'removeAllProductsHash1'                  => __( 'Are you sure you want to remove all products hash?', 'speedsearch' ),
            'removeAllProductsHash2'                  => __( 'Hash generation for all products will start when you open any page on the site.', 'speedsearch' ),
            'resetFeed1'                              => __( 'Are you sure you want to reset and regenerate the products feed?', 'speedsearch' ),
            'resetFeed2'                              => __( 'This can take a while and should not be used under normal circumstances. Use it only if the products have been out of sync for a very long time and removing the product hash did not help.', 'speedsearch' ),
            'feedRegenerationProcessHasStarted'       => __( 'Feed regeneration process has started.', 'speedsearch' ),
            'allProductsHashGenerationProcessStarted' => __( 'Products hash regeneration process has started.', 'speedsearch' ),
            'forceSyncFailed'                         => __( 'Failed to force reindexing. Please try again later.', 'speedsearch' ),
            'itTook'                                  => __( 'It took', 'speedsearch' ),
            'seconds'                                 => __( 'seconds', 'speedsearch' ),
            'yesResetAllSettings'                     => __( 'Yes, reset all plugin settings', 'speedsearch' ),
            'yesForceSync'                            => __( 'Yes, reindex all products', 'speedsearch' ),
            'yesInitiateProductsHashRegeneration'     => __( 'Yes, initiate products hash regeneration', 'speedsearch' ),
            'disabledBecauseOfTheChosenCategoriesStructure' => __( "Disabled because the chosen categories structure doesn't support it.", 'speedsearch' ),
            'setCustomAutocompleteTabSearchText'      => __( 'Set custom autocomplete tab search text', 'speedsearch' ),
            'autocompleteTabTextBeforeSearchText'     => __( 'Text before the autocomplete search query text', 'speedsearch' ),
            'autocompleteTabTextAfterSearchText'      => __( 'Text after the autocomplete search query text', 'speedsearch' ),
            'exportPopupTitle'                        => __( 'Do you want to export images?', 'speedsearch' ),
            'exportPopupText'                         => __( 'When you answer no, the images will be downloaded by their URLs upon import. Answer yes if the site is not reachable via the internet, or if you want to export the settings as a backup.', 'speedsearch' ),
            /* translators: %s is a plugin name. */
            'settingsAreImporting'                    => sprintf( __( '%s settings are being imported.', 'speedsearch' ), SpeedSearch::$name ),
            'pleaseWait'                              => __( 'Please wait.', 'speedsearch' ),
            'pauseSyncValueShouldBeGreaterThan0'      => __( 'Pause indexing value should be greater than 0', 'speedsearch' ),
            'pauseSyncValueCantBeGreaterThan5'        => __( "Pause indexing value can't be greater than 5", 'speedsearch' ),
            'syncWasPaused'                           => __( 'Indexing was paused', 'speedsearch' ),
            'cantPauseSync'                           => __( "Can't pause indexing", 'speedsearch' ),
            /* translators: %s is a name of ordering option, like "Default". */
            'areYouSureYouWantToDeleteOrderingOption' => __( 'Are you sure you want to delete ordering option "%s"?', 'speedsearch' ),
            'yes'                                     => __( 'Yes', 'speedsearch' ),
            'no'                                      => __( 'No', 'speedsearch' ),
            'settingsBar'                             => __( 'Settings bar', 'speedsearch' ),
            'settingTabsIntro'                        => __( 'The plugin settings are divided into multiple categories. Use this navigation tab to access the settings by category.', 'speedsearch' ),
            'dataTables'                              => [
                'emptyTable'     => __( 'No data available in table', 'speedsearch' ),
                /* translators: Keep _START_, _END_ and _TOTAL_ unchanged. */
                'info'           => __( 'Showing _START_ to _END_ of _TOTAL_ entries', 'speedsearch' ),
                'infoEmpty'      => __( 'Showing 0 to 0 of 0 entries', 'speedsearch' ),
                /* translators: Keep _MAX_ unchanged. */
                'infoFiltered'   => __( '(filtered from _MAX_ total entries)', 'speedsearch' ),
                /* translators: Keep _MENU_ unchanged. */
                'lengthMenu'     => __( 'Show _MENU_ entries', 'speedsearch' ),
                'loadingRecords' => __( 'Loading...', 'speedsearch' ),
                'zeroRecords'    => __( 'No matching records found', 'speedsearch' ),
                'paginate'       => [
                    'first'    => __( 'First', 'speedsearch' ),
                    'last'     => __( 'Last', 'speedsearch' ),
                    'next'     => __( 'Next', 'speedsearch' ),
                    'previous' => __( 'Previous', 'speedsearch' ),
                ],
            ],
        ];
    }

    /**
     * The list of loaded scripts, that will be deferred.
     *
     * @var string[]
     */
    private static $loaded_scripts = [];

    /**
     * Enqueue a script, to make it defer.
     *
     * @param string           $handle    Name of the script. Should be unique.
     * @param string           $src       Full URL of the script, or path of the script relative to the WordPress root directory.
     *                                    Default empty.
     * @param string[]         $deps      Optional. An array of registered script handles this script depends on. Default empty array.
     * @param string|bool|null $ver       Optional. String specifying script version number, if it has one, which is added to the URL
     *                                    as a query string for cache busting purposes. If version is set to false, a version
     *                                    number is automatically added equal to current installed WordPress version.
     *                                    If set to null, no version is added.
     * @param bool             $defer     Whether to defer the script.
     */
    public static function enqueue_script( $handle, $src = '', array $deps = [], $ver = false, $defer = true ) {
        wp_enqueue_script( $handle, $src, $deps, $ver, false );
        if ( $defer ) {
            self::$loaded_scripts[] = $handle;
        }
    }

    /**
     * Register a script, to make it defer.
     *
     * @param string           $handle    Name of the script. Should be unique.
     * @param string           $src       Full URL of the script, or path of the script relative to the WordPress root directory.
     *                                    Default empty.
     * @param string[]         $deps      Optional. An array of registered script handles this script depends on. Default empty array.
     * @param string|bool|null $ver       Optional. String specifying script version number, if it has one, which is added to the URL
     *                                    as a query string for cache busting purposes. If version is set to false, a version
     *                                    number is automatically added equal to current installed WordPress version.
     *                                    If set to null, no version is added.
     * @param bool             $defer     Whether to defer the script.
     */
    public static function register_script( $handle, $src, array $deps, $ver, $defer = true ) {
        wp_register_script( $handle, $src, $deps, $ver, false );
        if ( $defer ) {
            self::$loaded_scripts[] = $handle;
        }
    }

    /**
     * Defer scripts.
     *
     * @param string $tag    The `<script>` tag for the enqueued script.
     * @param string $handle The script's registered handle.
     * @param string $src    The script's source URL.
     */
    public static function defer_scripts( $tag, $handle, $src ) {
        if ( in_array( $handle, self::$loaded_scripts, true ) ) {
            $tag = str_replace( ' src=', ' defer src=', $tag );
        }
        return $tag;
    }

    /**
     * Prints posts top prio scripts (functions.js, url.js, posts.js).
     */
    public static function print_top_prio_scripts() {
        self::init_page_embed_data();

        $initial_data = self::$page_embed_data;

        // Print JSON cache retrieval script at the top of the page.
        JSON_AJAX_Cache_Base::print_json_cache_settings_retrieval_script();

        $functions_url = esc_attr( SPEEDSEARCH_URL . 'assets-build/common/functions.js?ver=' . SPEEDSEARCH_VERSION );

        ?>
        <script
        <?php
        ?>
        id='speedsearch-common-functions-js-extra'>
        var speedsearch_JSONCacheSettings = {};
        var speedsearch = <?php echo wp_json_encode( $initial_data ); ?>;
        </script>
        <script
        <?php
        echo sanitize_user_field( // Print a script part to pass the check.
            'speedsearch',
            " type='text/javascript' src='$functions_url' id='speedsearch-common-functions-js'></script>",
            0,
            'display'
        );
    }
}
