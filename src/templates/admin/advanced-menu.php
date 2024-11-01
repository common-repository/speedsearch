<?php
/**
 * Template for admin menu
 *
 * @package SpeedSearch
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

use SpeedSearch\Misc;
use SpeedSearch\SpeedSearch;

?>
<div class="wrap">
    <h1 class="speedsearch-header"><?php esc_html_e( 'SpeedSearch Advanced Settings', 'speedsearch' ); ?></h1>

    <form class="speedsearch-form">
        <div class="speedsearch-container speedsearch-general-settings">
            <div class="wp-plugins-core-tabs-container">
                <div class="speedsearch-admin-loader-container">
                    <div class="speedsearch-admin-loader">
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="advanced-settings">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Advanced Settings', 'speedsearch' ); ?></h2>

                    <div class="speedsearch-row">
                        <label>
                            <span><?php esc_html_e( 'Replace WC store with SpeedSearch regardless of the sync status (do not wait for the sync to finish):', 'speedsearch' ); ?></span>
                            <input class="speedsearch-input" name="do-not-wait-for-sync-to-finish" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-do-not-wait-for-sync-to-finish' ), '1' ); ?>>
                        </label>
                    </div>

                    <div class="speedsearch-row">
                        <label class="speedsearch-with-description">
                            <span class="speedsearch-label-block">
                                <span><?php esc_html_e( 'Attributes prefix for URL params:', 'speedsearch' ); ?></span>
                                <span class="speedsearch-description">
                                    <?php
                                    esc_html_e(
                                        'By default, attributes are prefixed when displayed as URL params with the substring "pa_". You can change this substring from here.',
                                        'speedsearch'
                                    );
                                    ?>
                                </span>
                            </span>
                            <input class="speedsearch-input" name="attributes-url-params-prefix" type="text"
                                value="<?php echo esc_attr( SpeedSearch::$options->get( 'setting-attributes-url-params-prefix' ) ); ?>">
                        </label>
                    </div>

                    <div class="speedsearch-row">
                        <label class="speedsearch-with-description">
                            <span class="speedsearch-label-block">
                                <span><?php esc_html_e( 'Fancy URLs:', 'speedsearch' ); ?></span>
                                <span class="speedsearch-description">
                                    <?php
                                    echo sanitize_user_field(
                                        'speedsearch',
                                        sprintf(
                                        /* translators: %s is a fancy URLs prefix. */
                                            __(
                                                'Instead of <b>/shop?tags=123&pa_color=red</b> will be <b>/shop/%s/tags/123/pa_color/red</b>',
                                                'speedsearch'
                                            ),
                                            SpeedSearch::$options->get( 'setting-prefix-before-fancy-urls' )
                                        ),
                                        0,
                                        'display'
                                    );
                                    ?>
                                </span>
                                <?php
                                if ( ! Misc::is_not_plaintext_permalink_structure() ) :
                                    ?>
                                    <span class="speedsearch-description">
                                    <?php
                                    echo sanitize_user_field(
                                        'speedsearch',
                                        sprintf(
                                        /* translators: %s is a URL. */
                                            __(
                                                '<b>Note:</b> To make this setting to work, you should have <a href="%s" target="_blank">permalinks structure</a> other than "plaintext" (i.e. without "/?p=123").',
                                                'speedsearch'
                                            ),
                                            admin_url( 'options-permalink.php' )
                                        ),
                                        0,
                                        'display'
                                    );
                                    ?>
                                </span>
                                    <?php
                                endif;
                                ?>
                            </span>
                            <input class="speedsearch-input" name="setting-fancy-urls" type="checkbox"
                                <?php
                                checked(
                                    SpeedSearch::$options->get( 'setting-fancy-urls' ) && Misc::is_not_plaintext_permalink_structure(),
                                    '1'
                                );
                                ?>
                                <?php
                                disabled(
                                    ! Misc::is_not_plaintext_permalink_structure()
                                );
                                ?>
                            >
                        </label>
                    </div>

                    <div class="speedsearch-row">
                        <label class="speedsearch-with-description">
                            <span class="speedsearch-label-block">
                                <span><?php esc_html_e( 'Prefix before fancy URLs part:', 'speedsearch' ); ?></span>
                                <span class="speedsearch-description">
                                    <?php
                                    echo sanitize_user_field(
                                        'speedsearch',
                                        sprintf(
                                        /* translators: %s is a URL. */
                                            __(
                                                'For example, if prefix is <b>q</b>, then URL on the shop page will be <i>https://example.com/shop/<b>q</b>/tags/123/pa_color/red</i>.',
                                                'speedsearch'
                                            ) .
                                            __(
                                                '<br><br><b>Warning:</b> Do not edit if you do not understand. Choose prefix very carefully to not overlap with any pages or product filters.',
                                                'speedsearch'
                                            ) .
                                            __(
                                                '<br><br><b>Note:</b> Does not work when "Full (without shop main page)" categories structure is enabled.',
                                                'speedsearch'
                                            ),
                                            admin_url( 'options-permalink.php' )
                                        ),
                                        0,
                                        'display'
                                    );
                                    ?>
                                </span>
                            </span>
                            <input class="speedsearch-input" name="setting-prefix-before-fancy-urls" type="text"
                                value="<?php echo esc_attr( SpeedSearch::$options->get( 'setting-prefix-before-fancy-urls' ) ); ?>">
                        </label>
                    </div>

                    <h2 class="speedsearch-block-heading"><?php esc_html_e( 'Images', 'speedsearch' ); ?></h2>

                    <div class="speedsearch-row">
                        <label>
                            <span><?php esc_html_e( 'When product image alt is missing, use product title as the alt:', 'speedsearch' ); ?></span>
                            <input class="speedsearch-input" name="when-no-image-alt-use-product-title" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-when-no-image-alt-use-product-title' ), '1' ); ?>>
                        </label>
                    </div>

                    <h2 class="speedsearch-block-heading"><?php esc_html_e( 'Indexing', 'speedsearch' ); ?></h2>

                    <div class="speedsearch-row horizontal-submit-button">
                        <label class="mb-0">
                            <span><?php esc_html_e( 'Pause indexing for (days):', 'speedsearch' ); ?></span>
                            <input class="speedsearch-input speedsearch-number-input" maxlength="1" name="pause-sync-for" type="text" value="1">
                        </label>
                        <?php
                        submit_button(
                            __( 'Pause indexing', 'speedsearch' ),
                            [ 'secondary', 'speedsearch-pause-sync' ]
                        );
                        ?>
                    </div>

                    <h2 class="speedsearch-block-heading mt-30"><?php esc_html_e( 'Webhooks', 'speedsearch' ); ?></h2>
                    <div class="speedsearch-row">
                        <label class="speedsearch-with-description">
                            <span class="speedsearch-label-block">
                                <span><?php esc_html_e( 'Do not use webhooks:', 'speedsearch' ); ?></span>
                                <span class="speedsearch-description">
                                    <?php
                                    esc_html_e(
                                        'In some setups webhooks can have a detrimental effect on performance, when they are disabled, new products and product updates will take more time to be integrated in the search index and to appear on the site. When checked, no webhooks are created.',
                                        'speedsearch'
                                    );
                                    ?>
                                </span>
                            </span>
                            <input class="speedsearch-input" name="do-not-use-webhooks" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-do-not-use-webhooks' ), '1' ); ?>>
                        </label>
                    </div>

                    <h2 class="speedsearch-block-heading mt-30"><?php esc_html_e( 'Analytics ageing', 'speedsearch' ); ?></h2>
                    <div class="speedsearch-row">
                        <label>
                            <span class="speedsearch-label-block">
                                <?php esc_html_e( 'Enabled:', 'speedsearch' ); ?>
                            </span>
                            <input class="speedsearch-input" name="enable-analytics-ageing" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-enable-analytics-ageing' ), '1' ); ?>>
                        </label>
                    </div>

                    <div class="speedsearch-row">
                        <label>
                            <span><?php esc_html_e( 'Half-life (days):', 'speedsearch' ); ?></span>

                            <input class="speedsearch-input speedsearch-number-input" maxlength="6" name="analytics-ageing-half-life" type="text"
                                value="<?php echo esc_attr( SpeedSearch::$options->get( 'setting-analytics-ageing-half-life' ) ); ?>">
                        </label>
                    </div>

                    <h2 class="speedsearch-block-heading mt-30"><?php esc_html_e( 'Debug mode', 'speedsearch' ); ?></h2>
                    <div class="speedsearch-row">
                        <label>
                            <span class="speedsearch-label-block">
                                <?php esc_html_e( 'Enabled:', 'speedsearch' ); ?>
                            </span>
                            <input class="speedsearch-input" name="setting-debug-mode" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-debug-mode' ), '1' ); ?>>
                        </label>
                    </div>

                    <div class="speedsearch-column">
                        <span class="speedsearch-label-block mb-20">
                            <?php esc_html_e( 'Select up to 5 products:', 'speedsearch' ); ?>
                        </span>
                        <?php
                        $selected_products = array_map( 'intval', SpeedSearch::$options->get( 'setting-debug-mode-products' ) );
                        $args              = [
                            'status' => 'publish',
                            'limit'  => -1,
                        ];
                        $products          = wc_get_products( $args );

                        for ( $i = 1; $i <= 5; $i++ ) {
                            $has_select = false;

                            $options_html = '';
                            foreach ( $products as $product ) {
                                $selected = isset( $selected_products[ $i - 1 ] ) && $selected_products[ $i - 1 ] === $product->get_ID();
                                if ( $selected ) {
                                    $has_select = true;
                                }
                                $options_html .= '<option value="' .
                                        $product->get_id() . '" ' .
                                        selected( $selected, true, false ) . '>' .
                                        $product->get_name() . ' (' . $product->get_ID() . ')' .
                                        '</option>';
                            }
                            $options_html = '<option' . selected( ! $has_select, true, false ) . ' value="">-</option>' . $options_html;

                            echo sanitize_user_field(
                                'speedsearch',
                                '<select class="mb-10 ' . ( 1 === $i || $has_select ? '' : 'speedsearch-hidden' ) . '" name="debug-mode-selected-products[' . $i . ']">',
                                0,
                                'display'
                            );
                            echo sanitize_user_field(
                                'speedsearch',
                                $options_html,
                                0,
                                'display'
                            );
                            echo sanitize_user_field(
                                'speedsearch',
                                '</select>',
                                0,
                                'display'
                            );
                        }
                        ?>
                    </div>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="cache">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Cache', 'speedsearch' ); ?></h2>
                    <h2 class="speedsearch-block-heading"><?php esc_html_e( 'Cache Settings', 'speedsearch' ); ?></h2>
                    <div class="speedsearch-row">
                        <label>
                            <span><?php esc_html_e( 'Cache flush interval (minutes):', 'speedsearch' ); ?></span>
                            <input class="speedsearch-input speedsearch-number-input" maxlength="6" name="cache-flush-interval" type="text"
                                value="<?php echo esc_attr( SpeedSearch::$options->get( 'setting-cache-flush-interval' ) ); ?>">
                        </label>
                    </div>
                    <h2 class="speedsearch-block-heading mt-20 mb-30"><?php esc_html_e( 'Clear Cache', 'speedsearch' ); ?></h2>
                    <div class="speedsearch-row">
                        <?php
                        submit_button(
                            __( 'Flush all cache', 'speedsearch' ),
                            [ 'secondary', 'speedsearch-cache-flush' ]
                        );
                        ?>
                    </div>
                    <h2 class="speedsearch-block-heading mt-50 mb-30"><?php esc_html_e( 'Products HTML in Object Cache', 'speedsearch' ); ?></h2>
                    <div class="speedsearch-row">
                        <?php
                        submit_button(
                            __( 'Flush all products HTML in Object Cache', 'speedsearch' ),
                            [ 'secondary', 'speedsearch-products-object-cache-flush' ],
                            'speedsearch-products-object-cache-flush'
                        );
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="speedsearch-container">
            <div class="speedsearch-column mt-m20">
                <?php submit_button( __( 'Save', 'speedsearch' ), [ 'primary', 'speedsearch-submit' ] ); ?>
            </div>
        </div>
        <hr>
        <div class="speedsearch-container">
            <div class="speedsearch-row mt-15">
                <div class="speedsearch-export-import-block"
                    data-title="<?php esc_html_e( 'Settings import/export', 'speedsearch' ); ?>"
                    data-intro="<?php esc_html_e( 'You can export and import your current plugin settings.<br><br>This is particularly handy when you want to recreate the searching and filtering experience on another site. We also recommend you use it as a backup before making major settings changes (it\'s always good to have a working version in case you\'re not happy with the changes you made).', 'speedsearch' ); ?>">
                    <a class="speedsearch-link speedsearch-export-settings-link"><?php esc_html_e( 'Export Settings', 'speedsearch' ); ?></a>
                    <a class="speedsearch-link speedsearch-import-settings-link ml-0"><?php esc_html_e( 'Import Settings', 'speedsearch' ); ?></a>
                </div>
                <input type="file" name="speedsearch-import-settings-link-file-input" accept="application/json, application/zip">
            </div>
        </div>
        <div class="speedsearch-container">
            <div class="speedsearch-row">
                <a class="speedsearch-link speedsearch-reset-settings-link mt-15"
                    data-title="<?php esc_html_e( 'Reset', 'speedsearch' ); ?>"
                    data-intro="<?php esc_html_e( 'You can always reset the plugin settings via this button.', 'speedsearch' ); ?>">
                    <?php esc_html_e( 'Reset All Settings', 'speedsearch' ); ?></a>
            </div>
        </div>
        <div class="speedsearch-container">
            <div class="speedsearch-row">
                <a class="speedsearch-link speedsearch-force-sync-link mt-15"
                    data-title="<?php esc_html_e( 'Force sync', 'speedsearch' ); ?>"
                    data-intro="<?php esc_html_e( 'You can use this button to force the synchronization of all website products.<br><br>In normal circumstances, this should not be used, as sync is always running in the background.', 'speedsearch' ); ?>">

                    <b><?php esc_html_e( 'Reindexing:', 'speedsearch' ); ?></b>
                    <?php esc_html_e( 'Reindex all products', 'speedsearch' ); ?>
                </a>
            </div>
        </div>
        <div class="speedsearch-container">
            <div class="speedsearch-row">
                <a class="speedsearch-link speedsearch-remove-all-products-hash-link mt-15"
                    data-title="<?php esc_html_e( 'Remove products hash', 'speedsearch' ); ?>"
                    data-intro="<?php esc_html_e( 'SpeedSearch is faster than other plugins because we keep a copy of your product information directly on our server. Product hashing is part of the logic set in place to make this process possible.', 'speedsearch' ); ?>">
                    <?php esc_html_e( 'Remove all products hash', 'speedsearch' ); ?>
                </a>
            </div>
        </div>
        <div class="speedsearch-container">
            <div class="speedsearch-row">
                <a class="speedsearch-link speedsearch-reset-feed-link mt-15">
                    <?php esc_html_e( 'Regenerate products feed', 'speedsearch' ); ?>
                </a>
            </div>
        </div>
        <div class="speedsearch-container">
            <div class="speedsearch-row">
                <a class="speedsearch-link speedsearch-show-debug-data-link mt-15"
                    data-title="<?php esc_html_e( 'Debug data', 'speedsearch' ); ?>"
                    data-intro="<?php esc_html_e( 'If some functionality seems to not work as expected, you can get this debug data and send it to the support team when you request assistance.', 'speedsearch' ); ?>">
                    <?php esc_html_e( 'Show debug data', 'speedsearch' ); ?>
                </a>
            </div>
        </div>
        <div class="speedsearch-container">
            <div class="speedsearch-row">
                <a class="speedsearch-link speedsearch-export-feed-buffer-table-link mt-15">
                    <?php esc_html_e( 'Export feed buffer table', 'speedsearch' ); ?>
                </a>
            </div>
        </div>
    </form>
</div>
