<?php
/**
 * Plugin Name: SpeedSearch
 * Description: Fast Search filter for WooCommerce.
 * Version:     1.7.52
 * Text Domain: speedsearch
 * Author:      TMM Technology
 * Author URI:  https://tmm.ventures/
 * Plugin URI:  https://speedsearch.io/
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Requires at least: 6.2
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

use Exception;

/**
 * Main SpeedSearch class.
 */
final class SpeedSearch {

    /**
     * File System instance.
     *
     * @var \WP_Filesystem_Base
     */
    public static $fs;

    /**
     * Options.
     *
     * @var Products_Hash\Init_Generation
     */
    public static $hash_generation;

    /**
     * Options.
     *
     * @var Options
     */
    public static $options;

    /**
     * WC Integration.
     *
     * @var WC_Integration
     */
    public static $wc_integration;

    /**
     * Integrations.
     *
     * @var Integrations
     */
    public static $integrations;

    /**
     * JSON AJAX Cache.
     *
     * @var JSON_AJAX_Cache\Base
     */
    public static $json_ajax_cache;

    /**
     * AJAX class.
     *
     * @var AJAX
     */
    public static $ajax;

    /**
     * Shortcodes class.
     *
     * @var Shortcodes
     */
    public static $shortcodes;

    /**
     * Sync_Data_Feed class.
     *
     * @var \SpeedSearch\Sync_Data_Feed\Sync_Data_Feed;
     */
    public static $sync_data_feed;

    /**
     * Plugin slug.
     *
     * @var string
     */
    public static $slug;

    /**
     * Plugin version.
     *
     * @var string
     */
    public static $version;

    /**
     * Plugin name.
     *
     * @var string
     */
    public static $name;

    /**
     * Constructor.
     *
     * @throws Exception Exception.
     */
    public function __construct() {
        $this->define_constants();
        $this->import_plugin_files();
        $this->init_filesystem_class();

        self::$fs = $GLOBALS['wp_filesystem'];

        // Errors collection.
        new Errors_Collection();

        // Activation.
        register_activation_hook( SPEEDSEARCH_FILE, [ $this, 'activation_handler' ] );

        add_action(
            'plugins_loaded',
            function() {
                new \WP_Plugins_Core\WP_Plugins_Core( $this );

                // Integrations.
                self::$integrations = new Integrations();

                /**
                 * Add WC hooks during AS action for the correct work of @see Products_HTML_Cache::validate_cache
                 *
                 * Because if include frontend hooks too late, the resulting HTML can be different from the original one, due
                 * to some function being defined on a late stage (e.g. in theme files).
                 *
                 * So to have the parity, we have to define frontend hooks as early as WC does that.
                 */
                if (
                    // Started automatically (via CRON).
                    defined( 'DOING_CRON' ) ||
                    // Starting manually (via Action Scheduler admin tab).
                    is_admin() &&
                    is_user_logged_in() &&
                    isset( $_REQUEST['page'] ) && // @codingStandardsIgnoreLine
                    'action-scheduler' === sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) && // @codingStandardsIgnoreLine
                    isset( $_REQUEST['row_action'] ) && // @codingStandardsIgnoreLine
                    'run' === sanitize_text_field( wp_unslash( $_REQUEST['row_action'] ) ) // @codingStandardsIgnoreLine
                ) {
                    WC()->frontend_includes();
                }
            },
            9 // Integrations should be called a bit earlier than other plugins are started to execute.
        );

        add_action(
            'init',
            function() {

                // Load translations.
                $this->load_plugin_textdomain();

                // Init options.
                self::$options = new Options();

                // Handle version update.
                $this->handle_version_update();

                // Deactivation.
                register_deactivation_hook( SPEEDSEARCH_FILE, [ $this, 'deactivation_handler' ] );

                // The plugin is being updated notice.
                if ( self::$version !== self::$options->get( 'last-handled-version-update' ) ) {
                    add_action(
                        'admin_notices',
                        function() {
                            ?>
                            <div class="notice notice-info">
                                <p>
                                    <b><?php echo esc_html( self::$name ); ?>: </b>
                                    <?php
                                    esc_html_e(
                                        'The plugin is being updated in the background.',
                                        'speedsearch'
                                    );
                                    ?>
                                </p>
                            </div>
                            <?php
                        }
                    );
                }

                // Enabled WC is a dependency (requirement) to start the plugin.
                WC_Dependency::add();

                // Init databases.
                new DB();

                // Cache.
                new Cache();

                // WooCommerce is active.
                if ( class_exists( 'WooCommerce' ) ) {
                    self::import_wc_dependent_plugin_files();

                    // MU Plugin (AJAX Helper (to deload all plugins except for the bare minimum AJAX requests)).
                    new Mu_Plugin();

                    // Rewrite Rules.
                    new Rewrite_Rules();

                    // Products Hash.
                    new Products_Hash\Base();

                    // WC Integration.
                    self::$wc_integration = new WC_Integration();

                    // Themes.
                    Themes::init();

                    // Assets.
                    Assets\Assets::init();

                    // Shortcodes.
                    self::$shortcodes = new Shortcodes();

                    // Init blocks.
                    new Blocks\Init();

                    // Adds plugin settings links to plugins admin screen.
                    add_filter( 'plugin_action_links_' . SPEEDSEARCH_BASENAME, [ $this, 'plugin_action_links' ] );

                    // Init sitelinks search box.
                    new Sitelinks_Search_Box();

                    // Initial setup.
                    new Initial_Setup();

                    // Add plugin status endpoint.
                    new Plugin_Status_Endpoint();

                    // Custom posts orderings.
                    new Ordering();

                    // Post publish box.
                    new Publish_Box();

                    // WooCommerce's status report.
                    new WooCommerce_Status_Report();

                    // Init TinyMCE.
                    new TinyMCE();

                    // Sync data feed.
                    self::$sync_data_feed = new Sync_Data_Feed\Sync_Data_Feed();

                    // Custom user.
                    new Custom_User();

                    // Webhooks.
                    new Webhooks\Webhooks(); // To init BE-communication secret.

                    // Analytics.
                    new Analytics\Init();

                    // Post-activation.
                    $this->post_activation_handler();

                    // Settings sharing.
                    new Settings_Sharing();

                    // Dynamic files patching.
                    new Dynamic_Scripts_Patching();

                    // Backend requests.
                    new Backend_Requests();

                    add_action(
                        'woocommerce_after_register_post_type',
                        function () {

                            // Post-post-activation.
                            $this->post_post_activation_handler();

                            // Menu.
                            new Admin_Menu();

                            // REST API.
                            new REST_API();

                            // JSON (AJAX) Cache.
                            self::$json_ajax_cache = new JSON_AJAX_Cache\Base();

                            // Products HTML Cache.
                            new Products_HTML_Cache();

                            // Initial page rendering.
                            if (
                                ! is_admin() &&
                                ! wp_doing_ajax() &&
                                (
                                    ! array_key_exists( 'plugin', $_REQUEST ) || // @codingStandardsIgnoreLine
                                    'speedsearch' !== $_REQUEST['plugin'] // @codingStandardsIgnoreLine
                                )
                            ) {
                                // Sends no-cache headers for shop pages if HTML is not complete (not all data is cached).
                                add_action( 'wp', [ 'SpeedSearch\HTML', 'send_no_cache_headers_for_shop_pages_if_html_is_not_complete' ] );
                            }

                            // AJAX Handler.
                            self::$ajax = new AJAX();
                        }
                    );
                }
            },
            0
        );
    }

    /**
     * Defines constants.
     */
    private function define_constants() {
        require_once __DIR__ . '/data/constants.php';

        /**
         * Plugin name.
         */
        self::$name = get_file_data( SPEEDSEARCH_FILE, [ 'Plugin Name' ] )[0];

        /**
         * Plugin slug.
         */
        $dir_parts  = explode( '/', SPEEDSEARCH_DIR );
        self::$slug = $dir_parts[ array_search( 'plugins', $dir_parts, true ) + 1 ];

        /**
         * Plugin version.
         */
        self::$version = SPEEDSEARCH_VERSION;
    }

    /**
     * Imports plugin files.
     */
    private function import_plugin_files() {
        $src_files = [
            'assets/class-assets',
            'assets/class-public-assets',
            'assets/class-public-admin-assets',
            'assets/screens/class-menu-assets',
            'assets/screens/class-analytics-menu',
            'assets/screens/class-advanced-menu',
            'assets/screens/class-appearance-menu',
            'assets/screens/class-assets-plugins-screen',
            'assets/screens/class-assets-post-screen',
            'blocks/class-init',
            'class-ajax',
            'class-html',
            'class-templating',
            'class-misc',
            'class-admin-menu',
            'class-errors-collection',
            'class-shortcodes',
            'class-filters',
            'class-posts',
            'class-cache',
            'class-swatches',
            'class-settings-export',
            'class-settings-import',
            'class-settings-sharing',
            'class-options',
            'class-ordering',
            'class-custom-user',
            'class-migrations',
            'analytics/class-init',
            'analytics/class-collection',
            'analytics/class-sending',
            'analytics/class-rendering',
            'class-sitelinks-search-box',
            'class-plugin-status-endpoint',
            'rest-api/class-rest-api',
            'rest-api/controllers/class-rest-products-hash-controller',
            'rest-api/controllers/class-rest-speedsearch-settings-controller',
            'rest-api/controllers/class-rest-product-hash-generation-data',
            'rest-api/controllers/class-rest-term-products',
            'rest-api/controllers/class-rest-backend-fix-counters',
            'class-backend-requests',
            'class-rewrite-rules',
            'class-wc-dependency',
            'products-hash/class-base',
            'class-mu-plugin',
            'class-themes',
            'class-file-fallbacked-cache',
            'class-integrations',
            'class-wc-integration',
            'class-demo-mode',
            'class-products-html-cache',
            'class-posts-data-final-output',
            'class-initial-setup',
            'class-publish-box',
            'class-tinymce',
            'class-dynamic-scripts-patching',
            'class-databases',
            'initial-elements-rendering/class-elements-rendering-data',
            'initial-elements-rendering/class-parse-url-for-request-params',
            'json-ajax-cache/class-base',
            'json-ajax-cache/class-fields',
            'webhooks/class-webhooks',
            'webhooks/class-meta-change-webhooks',
            'products-hash/class-init-regeneration',
            'sync-data-feed/class-feed-generation-buffer',
            'sync-data-feed/class-sync-data-feed-hashes',
            'sync-data-feed/class-feed-buffer-export',
        ];
        foreach ( $src_files as $file ) {
            require_once SPEEDSEARCH_DIR . 'src/' . $file . '.php';
        }

        $files = [
            'vendor/autoload_packages',
            'vendor/tmmtech/wp-plugins-core/wp-plugins-core',
        ];
        foreach ( $files as $file ) {
            require_once SPEEDSEARCH_DIR . $file . '.php';
        }
    }

    /**
     * Imports WC-dependent plugin files.
     */
    private static function import_wc_dependent_plugin_files() {
        $src_files = [
            'sync-data-feed/class-sync-data-feed',
            'sync-data-feed/class-feed-index-file',
            'class-woocommerce-status-report',
        ];
        foreach ( $src_files as $file ) {
            require_once SPEEDSEARCH_DIR . 'src/' . $file . '.php';
        }
    }

    /**
     * Inits filesystem class, which has to be declared in $wp_filesystem global variable.
     */
    private function init_filesystem_class() {
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if ( ! class_exists( 'WP_Filesystem_Base' ) ) {
            WP_Filesystem();
        }
    }

    /**
     * Loads text-domain.
     *
     * @since 0.8.55
     */
    private function load_plugin_textdomain() {
        load_plugin_textdomain(
            'speedsearch',
            false,
            dirname( SPEEDSEARCH_BASENAME ) . '/languages'
        );
    }

    /**
     * Converts path to URL.
     *
     * @param string $path Path.
     *
     * @return string URL
     */
    public static function convert_path_to_url( $path ) {
        return esc_url(
            str_replace(
                get_theme_root(),
                get_theme_root_uri(),
                wp_normalize_path( $path )
            )
        );
    }

    /**
     * Show action links on the plugin screen.
     *
     * @param mixed $links Plugin Action links.
     *
     * @return array
     */
    public function plugin_action_links( $links ) {
        $action_links = array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=speedsearch-settings' ) .
                '" aria-label="' . esc_attr__( 'View SpeedSearch settings', 'speedsearch' ) . '">' . esc_html__( 'Settings', 'woocommerce' ) . '</a>',
        );

        return array_merge( $action_links, $links );
    }

    /**
     * Handles version update.
     */
    private function handle_version_update() {
        if ( time() - MINUTE_IN_SECONDS > (int) get_option( 'speedsearch-updating' ) ) {
            $last_handled_version_update = self::$options->get( 'last-handled-version-update' );
            if ( self::$version !== $last_handled_version_update ) {
                update_option( 'speedsearch-updating', time() );

                add_action( // Flush JSON cache settings on plugin update (just in case).
                    'woocommerce_after_register_post_type',
                    function () {
                        // Flushes the cache.
                        if ( ! self::$json_ajax_cache ) {
                            self::$json_ajax_cache = new JSON_AJAX_Cache\Base();
                        }

                        self::$json_ajax_cache->flush();
                    },
                    11
                );

                new Migrations( $last_handled_version_update );

                new DB();
                DB::db_delta();

                self::$options->set( 'last-handled-version-update', self::$version );
                delete_option( 'speedsearch-updating' );
            }
        }
    }

    /**
     * Activation handled.
     *
     * Just marks that the plugin was activated but the activation wasn't handled yet.
     */
    public function activation_handler() {
        update_option( 'speedsearch-activation-handled', 'false' );

        // Save data of the user that activated the plugin, to send later (after store authorization).
        update_option( 'speedsearch-user-data-that-activated-the-plugin', Initial_Setup::get_current_user_data_to_send_to_be() );
    }

    /**
     * Site post-activation handler.
     *
     * Handler the plugin activation after it was marked in the above method.
     *
     * Sends the request to the backend.
     * And if the request fails, deactivates the plugin and sends the admin notice.
     */
    public function post_activation_handler() {
        if (
            is_admin() &&
            current_user_can( 'activate_plugins' ) &&
            isset( $_GET['activate'] ) && // @codingStandardsIgnoreLine
            'false' === get_option( 'speedsearch-activation-handled' )
        ) {
            delete_option( 'speedsearch-activation-handled' );

            if (
                self::$options->get( 'store-was-authorised' ) &&
                ! Backend_Requests::activate()
            ) {
                deactivate_plugins( SPEEDSEARCH_BASENAME );
                add_action(
                    'admin_notices',
                    function() {
                        echo wp_kses_post(
                            '<div class="notice notice-error is-dismissible">
                            <p><b>' . esc_html__( 'SpeedSearch error:', 'speedsearch' ) . '</b> ' . esc_html__( "Couldn't send activation request to the backend. Please try activating plugin later, or contact the plugin developers.", 'speedsearch' ) . '</p>
                            </div>'
                        );
                    }
                );
                unset( $_GET['activate'] ); // @codingStandardsIgnoreLine
            } else {
                add_option( 'speedsearch-post-activation-handled', 'no' );
            }
        }
    }

    /**
     * Site post-post-activation handler.
     */
    public function post_post_activation_handler() {
        if (
            isset( $_GET['activate'] ) ||
            'no' !== get_option( 'speedsearch-post-activation-handled' ) ||
            ! self::$options->get( 'initial-feed-generation-complete' )
        ) {
            return;
        }

        delete_option( 'speedsearch-post-activation-handled' );

        // Add the necessary hashes to the feed buffer on plugin re-activation (i.e. product/term changes when the plugin was inactive).
        Sync_Data_Feed\Sync_Data_Feed_Hashes::do_the_logic();
    }

    /**
     * Deactivation handler.
     */
    public function deactivation_handler() {

        // Delete user data that activated the plugin.

        self::$options->delete( 'user-data-that-activated-the-plugin' );

        /**
         * Deactivate the store, and maybe DELETE (if such $_GET param is passed).
         */

        $store = Backend_Requests::get( 'store_details' );
        if ( isset( $store['id'] ) ) {
            $store_id = $store['id'];

            // Just disable (without delete).
            if ( ! isset( $_GET['deleteDataFromServer'] ) ) { // @codingStandardsIgnoreLine
                Backend_Requests::disable_store( $store_id );
            } else { // Disable and delete.
                self::$options->delete( 'synced' );
                self::$options->delete( 'store-was-authorised' );
                self::$options->delete( 'setting-debug-mode-products' );
                delete_transient( 'speedsearch_store_authorized' );

                Backend_Requests::delete_store();
            }
        }

        // Delete on-deactivation data (that can't be easily added to uninstall.php).

        self::delete_on_deactivation_data();
    }

    /**
     * Delete on-deactivation data.
     */
    public static function delete_on_deactivation_data() {
        if ( class_exists( 'WooCommerce' ) ) {
            self::import_wc_dependent_plugin_files();

            // Disable webhooks.
            Webhooks\Webhooks::disable_webhooks();
        }

        // Delete custom user.
        Custom_User::delete();

        // Flushes the cache.
        if ( ! self::$json_ajax_cache ) {
            self::$json_ajax_cache = new JSON_AJAX_Cache\Base();
        }

        self::$json_ajax_cache->flush();
    }
}
new SpeedSearch();
