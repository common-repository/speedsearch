<?php
/**
 * WooCommerce's status report extension for SpeedSearch.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use WC_Admin_Status;

/**
 * Class WooCommerce_Status_Report Rules.
 */
final class WooCommerce_Status_Report {

    /**
     * Templates dir.
     */
    const TEMPLATE_FILES_DIRECTORY = SPEEDSEARCH_DIR . 'src/templates/';

    /**
     * Init.
     */
    public function __construct() {
        // Custom SpeedSearch theme template overrides.
        add_action( 'woocommerce_system_status_report', [ $this, 'display_status_block' ] );

        // Admin notice when the custom SpeedSearch theme has outdated files.
        add_action( 'admin_notices', [ $this, 'admin_notice_has_outdated_template_overrides' ] );
    }

    /**
     * Get a list of template files.
     *
     * @return string[] The list of template files.
     *
     * @see \WC_REST_System_Status_V2_Controller::get_theme_info()
     */
    private function get_template_files_list() {
        $plugin_templates = Misc::get_dir_files( self::TEMPLATE_FILES_DIRECTORY );

        // Trim an irrelevant beginning from the files list.

        $files_data = [];

        foreach ( $plugin_templates as $plugin_template ) {
            $relative_path = preg_replace( '@^' . preg_quote( WP_CONTENT_DIR . '/plugins/speedsearch/src/templates', '@' ) . '@', '', $plugin_template );

            $override_template = HTML::get_current_theme_template_override_file( $relative_path );

            if ( $override_template ) {
                $theme_version = WC_Admin_Status::get_file_version( $override_template );
                $core_version  = WC_Admin_Status::get_file_version( $plugin_template );

                $files_data[] = [
                    'file'         => preg_replace( '@^' . preg_quote( WP_CONTENT_DIR . '/themes/', '@' ) . '@', '', $override_template ),
                    'version'      => $theme_version,
                    'core_version' => $core_version,
                ];
            }
        }

        return $files_data;
    }

    /**
     * Checks if at least one of the template overrides is outdated.
     *
     * @return bool True if at least one template override is outdated, false otherwise.
     */
    public function has_outdated_template_overrides() {
        $templates = $this->get_template_files_list();

        foreach ( $templates as $template ) {
            if ( empty( $template['version'] ) || version_compare( $template['version'], $template['core_version'], '<' ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Display status block.
     */
    public function display_status_block() {
        $files = $this->get_template_files_list();

        ?>
        <table id="speedsearch-template-overrides" class="wc_status_table widefat" cellspacing="0">
            <thead>
            <tr>
                <th colspan="3" data-export-label="Templates">
                    <h2><?php esc_html_e( 'SpeedSearch Templates', 'speedsearch' ); ?>
                        <?php
                        echo sanitize_user_field(
                            'speedsearch',
                            wc_help_tip( esc_html__( 'This section shows any files that are overriding the default SpeedSearch template pages.', 'speedsearch' ) ),
                            0,
                            'display'
                        );
                        ?>
                    </h2>
                </th>
            </tr>
            </thead>
            <tbody>
            <?php if ( ! empty( $files ) ) : ?>
                <tr>
                    <td data-export-label="Overrides"><?php esc_html_e( 'Overrides', 'speedsearch' ); ?></td>
                    <td class="help">&nbsp;</td>
                    <td>
                        <?php
                        $total_overrides = count( $files );
                        for ( $i = 0; $i < $total_overrides; $i++ ) {
                            $override = $files[ $i ];
                            if ( $override['core_version'] && ( empty( $override['version'] ) || version_compare( $override['version'], $override['core_version'], '<' ) ) ) {
                                $current_version = $override['version'] ? $override['version'] : '-';
                                printf(
                                /* Translators: %1$s: Template name, %2$s: Template version, %3$s: Core version. */
                                    esc_html__( '%1$s version %2$s is out of date. The core version is %3$s', 'speedsearch' ),
                                    '<code>' . esc_html( $override['file'] ) . '</code>',
                                    '<strong style="color:red">' . esc_html( $current_version ) . '</strong>',
                                    esc_html( $override['core_version'] )
                                );
                            } else {
                                echo esc_html( $override['file'] );
                            }
                            if ( ( count( $files ) - 1 ) !== $i ) {
                                echo ', ';
                            }
                            echo '<br />';
                        }
                        ?>
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <td data-export-label="Overrides"><?php esc_html_e( 'Overrides', 'speedsearch' ); ?>:</td>
                    <td class="help">&nbsp;</td>
                    <td>&ndash;</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * The list of screens on which to show status notices.
     */
    const SCREENS_TO_SHOW_STATUS_NOTICES_ON = [
        // SpeedSearch pages..
        'woocommerce_page_speedsearch',
        // Dashboard pages.
        'dashboard',
        'themes',
        'theme-editor',
        'update-core',
        'update-core-network',
        'site-health',
    ];

    /**
     * Admin notice when the custom SpeedSearch theme has outdated files.
     */
    public function admin_notice_has_outdated_template_overrides() {
        $current_theme_slug = SpeedSearch::$options->get( 'setting-current-theme-data' )['name'];
        if (
            'default' !== $current_theme_slug &&
            in_array( get_current_screen()->id, self::SCREENS_TO_SHOW_STATUS_NOTICES_ON, true ) &&
            $this->has_outdated_template_overrides()
        ) {
            $current_theme_name = isset( Themes::$themes_data[ $current_theme_slug ]['label'] ) ?
                Themes::$themes_data[ $current_theme_slug ]['label'] :
                $current_theme_slug;

            ?>
            <div class="notice notice-warning">
                <h3>
                    <?php esc_html_e( 'SpeedSearch', 'speedsearch' ); ?>
                </h3>
                <p>
                    <?php
                    echo sanitize_user_field(
                        'speedsearch',
                        sprintf(
                            /* translators: %1$s: Theme name, %2$s: The URL to the status page. */
                            __( '<strong>Your SpeedSearch theme (%1$s) contains outdated copies of some SpeedSearch template files.</strong> These files may need updating to ensure they are compatible with the current version of SpeedSearch. Suggestions:', 'speedsearch' ), // @codingStandardsIgnoreLine
                            esc_html( $current_theme_name )
                        ),
                        0,
                        'display'
                    );
                    ?>
                </p>
                <ol>
                    <li><?php esc_html_e( 'If you copied over a template file to change something, then you will need to copy the new version of the template and apply your changes again.', 'speedsearch' ); ?></li>
                    <li><?php esc_html_e( 'If you are unfamiliar with code/templates and resolving potential conflicts, reach out to a developer for assistance.', 'speedsearch' ); ?></li>
                </ol>
                <p class="submit">
                    <a class="button button-large button-primary" href="<?php echo esc_url( network_admin_url( 'admin.php?page=wc-status#speedsearch-template-overrides' ) ); ?>"><?php esc_html_e( 'View affected templates', 'speedsearch' ); ?></a>
                </p>
            </div>
            <?php
        }
    }
}
