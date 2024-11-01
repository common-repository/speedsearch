<?php
/**
 * Template for admin menu
 *
 * @package SpeedSearch
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

use SpeedSearch\SpeedSearch;
use SpeedSearch\HTML;

?>
<div class="wrap">
    <h1 class="speedsearch-header"><?php esc_html_e( 'SpeedSearch Appearance', 'speedsearch' ); ?></h1>

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

                <div class="speedsearch-row speedsearch-hidden">
                    <label>
                        <span><?php esc_html_e( 'Replace WC store with SpeedSearch regardless of the sync status (do not wait for the sync to finish):', 'speedsearch' ); ?></span>
                        <input class="speedsearch-input" name="do-not-wait-for-sync-to-finish" type="checkbox"
                            <?php checked( SpeedSearch::$options->get( 'setting-do-not-wait-for-sync-to-finish' ), '1' ); ?>>
                    </label>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="themes">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Themes', 'speedsearch' ); ?></h2>

                    <div class="speedsearch-container speedsearch-settings-filters">
                        <h2 class="speedsearch-block-heading"><?php esc_html_e( 'Theme Settings', 'speedsearch' ); ?></h2>
                        <div class="speedsearch-row">
                            <label>
                                <span><?php esc_html_e( 'Current theme:', 'speedsearch' ); ?></span>
                                <select class="speedsearch-input speedsearch-select-input" name="current-theme">
                                </select>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="filters">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Filters', 'speedsearch' ); ?></h2>

                    <div class="speedsearch-container speedsearch-settings-filters">
                        <h2 class="speedsearch-block-heading"><?php esc_html_e( 'Filters Settings', 'speedsearch' ); ?></h2>
                        <div class="speedsearch-row">
                            <?php HTML::render_template( 'parts/filters.php', [ 'display_for_settings' => true ] ); ?>
                        </div>
                        <div class="speedsearch-column speedsearch-reset-filters-order-btn-container">
                            <?php submit_button( __( 'Reset Filters Order', 'speedsearch' ), [ 'secondary', 'speedsearch-reset-filters-order' ], 'reset-filters-order' ); ?>
                        </div>

                        <h3 class="speedsearch-block-heading"><?php esc_html_e( 'Toggles', 'speedsearch' ); ?></h3>
                        <div class="speedsearch-row toggles-row"></div>

                        <div class="speedsearch-column speedsearch-reset-toggles-order-btn-container mb-30">
                            <?php submit_button( __( 'Reset Toggles Order', 'speedsearch' ), [ 'secondary', 'speedsearch-reset-toggles-order' ], 'reset-toggles-order' ); ?>
                        </div>

                        <h3 class="speedsearch-block-heading mt-30"><?php esc_html_e( 'Toggles settings', 'speedsearch' ); ?></h3>
                        <div class="speedsearch-row">
                            <label>
                                <span class="mb-30"><?php esc_html_e( 'Display toggles as checkboxes:', 'speedsearch' ); ?></span>
                                <input class="speedsearch-input" name="display-toggles-as-checkboxes" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-display-toggles-as-checkboxes' ), '1' ); ?>>
                            </label>
                        </div>
                        <div class="speedsearch-row mb-30">
                            <label>
                                <span><?php esc_html_e( 'Heading:', 'speedsearch' ); ?></span>
                                <input class="speedsearch-input" name="setting-toggles-heading" type="text"
                                       value="<?php echo esc_attr( SpeedSearch::$options->get( 'setting-toggles-heading' ) ); ?>">
                            </label>
                        </div>
                    </div>
                    <hr>
                    <div class="speedsearch-container speedsearch-filters-demo-container">
                        <h2 class="speedsearch-block-heading filters-demo-open"><?php esc_html_e( 'Filters Demo', 'speedsearch' ); ?></h2>
                        <div class="speedsearch-row">
                            <?php HTML::render_template( 'parts/filters.php' ); ?>
                        </div>
                    </div>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="posts">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Posts', 'speedsearch' ); ?></h2>

                    <div class="speedsearch-container speedsearch-settings-filters">
                        <h2 class="speedsearch-block-heading"><?php esc_html_e( 'Post Blocks Settings', 'speedsearch' ); ?></h2>
                        <div class="speedsearch-row <?php echo esc_attr( ! SpeedSearch::$integrations->is_current_theme_products_integration_present ? 'speedsearch-hidden' : '' ); ?>">
                            <label>
                                <span><?php esc_html_e( 'Enable theme integration:', 'speedsearch' ); ?></span>
                                <input class="speedsearch-input" name="posts-enable-theme-integration" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-posts-enable-theme-integration' ), '1' ); ?>>
                            </label>
                        </div>

                        <?php
                        $show_posts_integration_text = SpeedSearch::$integrations->is_current_theme_products_integration_present &&
                                                       '1' === SpeedSearch::$options->get( 'setting-posts-enable-theme-integration' );

                        if ( $show_posts_integration_text ) :
                            ?>
                            <h4 class="speedsearch-block-heading mb-10">
                                <?php esc_html_e( 'We found a product integration for the current theme. You can modify the products layout in your theme settings.', 'speedsearch' ); ?>
                            </h4>
                        <?php endif; ?>
                        <div class="speedsearch-row <?php echo esc_attr( $show_posts_integration_text ? 'speedsearch-hidden' : '' ); ?>">
                            <table class="speedsearch-posts-fields">
                                <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Field #', 'speedsearch' ); ?></th>
                                    <th><?php esc_html_e( 'Text before the content', 'speedsearch' ); ?></th>
                                    <th><?php esc_html_e( 'Field content type', 'speedsearch' ); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="tags">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Tags', 'speedsearch' ); ?></h2>

                    <div class="speedsearch-container speedsearch-settings-filters">
                        <h2 class="speedsearch-block-heading"><?php esc_html_e( 'Tags Settings', 'speedsearch' ); ?></h2>
                        <div class="speedsearch-row">
                            <label>
                                <span><?php esc_html_e( 'Display tags:', 'speedsearch' ); ?></span>
                                <input class="speedsearch-input" name="display-tags" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-display-tags' ), '1' ); ?>>
                            </label>
                        </div>
                        <div class="speedsearch-row">
                            <label class="speedsearch-with-description">
                                <span class="speedsearch-label-block">
                                    <span><?php esc_html_e( 'Hide unavailable tags:', 'speedsearch' ); ?></span>
                                    <span class="speedsearch-description">
                                        <?php
                                        esc_html_e(
                                            "This is particularly relevant when designing the user experience of the filtering process. As the product list narrows down based on the already selected filters & tags, there is the option to display as normal the tags that don't contain matching products or hide them.",
                                            'speedsearch'
                                        );
                                        ?>
                                    </span>
                                </span>
                                <input class="speedsearch-input" name="hide-unavailable-tags" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-hide-unavailable-tags' ), '1' ); ?>>
                            </label>
                        </div>
                        <div class="speedsearch-row">
                            <label>
                                <span><?php esc_html_e( 'Enable multiple tags selection:', 'speedsearch' ); ?></span>
                                <input class="speedsearch-input" name="tags-support-multiselect" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-tags-support-multiselect' ), '1' ); ?>>
                            </label>
                        </div>
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
    </form>
</div>
