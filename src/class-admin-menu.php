<?php
/**
 * Admin Menus
 *
 * Adds admin menus.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;

/**
 * Class Admin_Menu.
 */
final class Admin_Menu {
    /**
     * Adds the menu and inits assets loading for it.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'init_menu' ] );
        add_action( 'admin_menu', [ $this, 'remove_duplicate_submenu' ], 20 );
    }

    /**
     * Adds the menu and inits assets loading for it.
     */
    public function init_menu() {

        // Main menu.

        $menu_slug = add_menu_page(
            __( 'SpeedSearch', 'speedsearch' ),
            __( 'SpeedSearch', 'speedsearch' ),
            'manage_options',
            'speedsearch',
            function () {
                require_once SPEEDSEARCH_DIR . 'src/templates/admin/menu.php';
            },
            'dashicons-search',
            59
        );
        add_action(
            'load-' . $menu_slug,
            [ 'SpeedSearch\Assets\Screens\Menu', 'init' ]
        );
        add_action(
            'load-' . $menu_slug,
            [ 'SpeedSearch\Settings_Export', 'add_listeners' ]
        );
        add_action(
            'load-' . $menu_slug,
            [ 'SpeedSearch\Sync_Data_Feed\Feed_Buffer_Export', 'add_listeners' ]
        );
        add_action(
            'load-' . $menu_slug,
            function() {
                $this->print_admin_notices();
            }
        );

        if (
            ! Initial_Setup::is_store_authorized()
        ) {
            return;
        }

        // Analytics submenu.

        $menu_slug = add_submenu_page(
            'speedsearch',
            __( 'SpeedSearch Analytics', 'speedsearch' ),
            __( 'Analytics', 'speedsearch' ),
            'manage_options',
            'speedsearch-analytics',
            function () {
                require_once SPEEDSEARCH_DIR . 'src/templates/admin/analytics-menu.php';
            }
        );
        add_action(
            'load-' . $menu_slug,
            [ 'SpeedSearch\Assets\Screens\Analytics_Menu', 'init' ]
        );
        add_action(
            'load-' . $menu_slug,
            function() {
                $this->print_admin_notices( false );
            }
        );

        // Appearance submenu.

        $menu_slug = add_submenu_page(
            'speedsearch',
            __( 'SpeedSearch Appearance', 'speedsearch' ),
            __( 'Appearance', 'speedsearch' ),
            'manage_options',
            'speedsearch-appearance',
            function () {
                require_once SPEEDSEARCH_DIR . 'src/templates/admin/appearance-menu.php';
            }
        );
        add_action(
            'load-' . $menu_slug,
            [ 'SpeedSearch\Assets\Screens\Appearance_Menu', 'init' ]
        );
        add_action(
            'load-' . $menu_slug,
            [ 'SpeedSearch\Settings_Export', 'add_listeners' ]
        );
        add_action(
            'load-' . $menu_slug,
            function() {
                $this->print_admin_notices();
            }
        );

        // Settings submenu.

        $menu_slug = add_submenu_page(
            'speedsearch',
            __( 'SpeedSearch Settings', 'speedsearch' ),
            __( 'Settings', 'speedsearch' ),
            'manage_options',
            'speedsearch-settings', // Use the same slug as a parent, to replace it.
            function () {
                require_once SPEEDSEARCH_DIR . 'src/templates/admin/menu.php';
            }
        );
        add_action(
            'load-' . $menu_slug,
            [ 'SpeedSearch\Assets\Screens\Menu', 'init' ]
        );
        add_action(
            'load-' . $menu_slug,
            [ 'SpeedSearch\Settings_Export', 'add_listeners' ]
        );
        add_action(
            'load-' . $menu_slug,
            function() {
                $this->print_admin_notices();
            }
        );

        // Advanced submenu.

        $menu_slug = add_submenu_page(
            'speedsearch',
            __( 'SpeedSearch Advanced', 'speedsearch' ),
            __( 'Advanced', 'speedsearch' ),
            'manage_options',
            'speedsearch-advanced',
            function () {
                require_once SPEEDSEARCH_DIR . 'src/templates/admin/advanced-menu.php';
            }
        );
        add_action(
            'load-' . $menu_slug,
            [ 'SpeedSearch\Assets\Screens\Advanced_Menu', 'init' ]
        );
        add_action(
            'load-' . $menu_slug,
            [ 'SpeedSearch\Settings_Export', 'add_listeners' ]
        );
        add_action(
            'load-' . $menu_slug,
            [ 'SpeedSearch\Sync_Data_Feed\Feed_Buffer_Export', 'add_listeners' ]
        );
        add_action(
            'load-' . $menu_slug,
            function() {
                $this->print_admin_notices();
            }
        );
    }

    /**
     * Removes duplicate plugin submenu.
     */
    public static function remove_duplicate_submenu() {
        remove_submenu_page( 'speedsearch', 'speedsearch' );
    }

    /**
     * Print conditions-based admin notices.
     *
     * @param bool $show_sync_progress_notice Whether to show sync progress notice.
     */
    public function print_admin_notices( $show_sync_progress_notice = true ) {

        if ( $show_sync_progress_notice ) {
            if ( Initial_Setup::should_the_sync_progress_message_be_shown() ) {
                add_action( 'admin_notices', [ $this, 'show_initial_store_progress_message' ] );
            } else {
                add_action( 'admin_notices', [ $this, 'print_sync_status' ] );
            }
        }
    }

    /**
     * WC is not active error notice.
     *
     * @throws Exception Exception.
     */
    public function print_sync_status() {
        $sync_status = Backend_Requests::get( 'sync_status' );

        if (
            array_key_exists( 'error', $sync_status ) ||
            ! array_key_exists( 'totalProductsInDB', $sync_status )
        ) {
            return;
        }

        $total_products_in_db = $sync_status['totalProductsInDB'];

        ?>
            <div class="notice notice-info" data-title="<?php esc_html_e( 'Products indexing progress', 'speedsearch' ); ?>" data-intro="
            <?php
            esc_html_e( "Here you can find your current indexing status. For the indexing to be complete, this number should be equal to the number of products in your shop.<br><br>If you're starting the plugin for the first time, you'll have to wait for the indexing to complete before seeing the SpeedSearch changes in your shop.", 'speedsearch' );
            ?>
                ">
                <p>
                    <strong>SpeedSearch: </strong><?php esc_html_e( 'Indexed products:', 'speedsearch' ); ?> <?php echo esc_html( $total_products_in_db ); ?>
                </p>
            </div>
        <?php
    }


    /**
     * Get initial sync data progress bar block.
     *
     * @param float|int $initial_sync_progress Initial sync progress.
     *
     * @return string HTML.
     */
    public static function get_initial_sync_data_progress_bar_block( $initial_sync_progress ) {
        ob_start();
        ?>
        <progress class="mr-25" value="<?php echo esc_html( $initial_sync_progress ); ?>" max="100"> <?php echo esc_html( sprintf( '%d%%', $initial_sync_progress ) ); ?> </progress>
        <?php
        /* translators: %s is a plugin sync progress (from 0 to 100). */
        echo esc_html( sprintf( __( '%s%% indexed', 'speedsearch' ), $initial_sync_progress ) );
        ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Print initial sync progress.
     */
    public function show_initial_store_progress_message() {
        $initial_sync_progress = Initial_Setup::get_initial_sync_generation_progress();

        ?>
        <div class="notice speedsearch-hidden" id="speedsearch-initial-sync-progress">
            <p>
                <h2><?php esc_html_e( 'Your products are being indexed.', 'speedsearch' ); ?></h2>
                <p>
                    <?php
                        esc_html_e( 'We are currently adding your list of products to our server. This is needed to provide a fast searching and filtering experience. Depending on the number of products in your store, this may take a while. You can check the indexing status below.', 'speedsearch' );
                    ?>
                </p>
                <h3 id="speedsearch-initial-sync-data-progress-bar" class="mb-25">
                    <?php
                    echo sanitize_user_field(
                        'speedsearch',
                        self::get_initial_sync_data_progress_bar_block( $initial_sync_progress ),
                        0,
                        'display'
                    );
                    ?>
                </h3>
                <h2><?php esc_html_e( 'What can I do in the meantime?', 'speedsearch' ); ?></h2>
                <p>
                    <?php
                    esc_html_e( 'Once the store indexing is complete, SpeedSearch will automatically replace several element blocks in your WooCommerce store setup (think search bar, products list, filters section). We offer a variety of settings, so while you wait, we recommend you take a look at our documentation so you can find your way around more efficiently when the indexing is over.', 'speedsearch' );
                    ?>
                </p>
                <p>
                    <a target="_blank" href="https://www.woosa.com/help/docs/developer-documentation/">
                        <?php
                        esc_html_e( 'SpeedSearch developer documentation', 'speedsearch' );
                        ?>
                    </a>
                </p>
                <p>
                    <a target="_blank" href="https://www.woosa.com/help/docs/speedsearch-documentation/">
                        <?php
                        esc_html_e( 'SpeedSearch user manual', 'speedsearch' );
                        ?>
                    </a>
                </p>
                <p class="speedsearch-take-the-tour-block">
                    <?php
                    esc_html_e( 'Not a fan of reading through docs? No problem! You can take a tour of the plugin settings instead or start setting up right away! Your settings will be saved and applied once the indexing process is finished.', 'speedsearch' );
                    ?>
                </p>
                <div class="speedsearch-row">
                    <?php
                        submit_button( __( 'Take the tour', 'speedsearch' ), [ 'primary', 'mr-50' ], 'speedsearch-take-the-tour-btn' );
                    ?>
                    <?php
                        submit_button( __( 'Let me set up!', 'speedsearch' ), [ 'primary' ], 'speedsearch-let-me-setup-btn' );
                    ?>
                </div>
                <h2><?php esc_html_e( 'Force blocks replacement', 'speedsearch' ); ?></h2>
                <p>
                    <?php
                    esc_html_e( 'You can replace the WooCommerce store elements right now, but only the indexed products will be added to the search index, so website visitors will only be able to search through all the products once the indexing is over.', 'speedsearch' );
                    ?>
                </p>
                <p class="red-text">
                    <?php
                    esc_html_e( 'We strongly recommend you to not force the replacement.', 'speedsearch' );
                    ?>
                </p>
                <div class="speedsearch-row mb-10">
                    <p class="flex-align-center">
                        <?php
                        esc_html_e( 'Replace WC store with SpeedSearch regardless of the indexing status (do not wait for the indexing to finish):', 'speedsearch' );
                        ?>
                    </p>
                    <p>
                        <?php
                        submit_button( __( 'Force replace', 'speedsearch' ), [ 'primary' ], 'speedsearch-force-replace-btn' );
                        ?>
                    </p>
                    <p class="flex-align-center">
                        <?php
                        esc_html_e( 'Can be reverted in "Advanced" menu.', 'speedsearch' );
                        ?>
                    </p>
                </div>
        </div>
        <?php
    }
}
