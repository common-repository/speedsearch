<?php
/**
 * Template for admin menu
 *
 * @package SpeedSearch
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

use SpeedSearch\Backend_Requests;
use SpeedSearch\DB;
use SpeedSearch\Initial_Setup;
use SpeedSearch\Ordering;
use SpeedSearch\SpeedSearch;
use SpeedSearch\Templating;

/*
 * Print DB management panel when "tmm-do" URL param is set.
 */
if ( isset( $_GET['tmm-do'] ) ) {
    $tmm_do = sanitize_text_field( wp_unslash( $_GET['tmm-do'] ) );

    /**
     * Prints migrations log.
     */
    $print_migrations_log = function() {
        $db_migration_log = SpeedSearch::$options->get( 'db-migration-log' );

        ?>

        <div class="speedsearch-row">
            last-db-success-migration-number = <?php echo esc_html( SpeedSearch::$options->get( 'last-db-success-migration-number' ) ); ?>
        </div>
        <div class="speedsearch-row">
            last-db-migration-success = <?php echo esc_html( SpeedSearch::$options->get( 'last-db-migration-success' ) ? 'yes' : 'no' ); ?>
        </div>

        <br>
        <br>
        <b>Migrations log:</b>
        <br>
        <br>

        <table>
            <thead>
            <tr>
                <th>Migration #</th>
                <th>Query</th>
                <th>Time Before</th>
                <th>Time After</th>
                <th>Error</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $db_migration_log as $log ) : ?>
                <tr>
                    <td><?php echo esc_html( $log['migration_number'] ); ?></td>
                    <td><?php echo esc_html( $log['query'] ); ?></td>
                    <td><?php echo esc_html( $log['time_before'] ); ?></td>
                    <td><?php echo esc_html( $log['time_after'] ); ?></td>
                    <td><?php echo esc_html( $log['error'] ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
    };

    ?>
    <div class="wrap">
        <div class="speedsearch-container">
            <div class="speedsearch-row">
                <a class="speedsearch-db-debug-button <?php echo esc_attr( 'db-migrations-log' === $tmm_do ? 'active' : '' ); ?>"
                   href="<?php echo esc_attr( admin_url( 'admin.php?page=speedsearch-settings&tmm-do=db-migrations-log' ) ); ?>">Migrations log</a>
                <a class="speedsearch-db-debug-button <?php echo esc_attr( 'apply-all-migrations' === $tmm_do ? 'active' : '' ); ?>"
                   href="<?php echo esc_attr( admin_url( 'admin.php?page=speedsearch-settings&tmm-do=apply-all-migrations' ) ); ?>">Apply all migrations</a>
                <a class="speedsearch-db-debug-button <?php echo esc_attr( 'reset-migrations-log' === $tmm_do ? 'active' : '' ); ?>"
                   href="<?php echo esc_attr( admin_url( 'admin.php?page=speedsearch-settings&tmm-do=reset-migrations-log' ) ); ?>">Reset migrations log</a>
                <a class="speedsearch-db-debug-button <?php echo esc_attr( 'delete-db-tables' === $tmm_do ? 'active' : '' ); ?>"
                   href="<?php echo esc_attr( admin_url( 'admin.php?page=speedsearch-settings&tmm-do=delete-db-tables' ) ); ?>">Delete plugin DB tables</a>
            </div>
            <br><br>
            <?php

            if ( 'db-migrations-log' === $tmm_do ) {
                $print_migrations_log();
            } elseif ( 'apply-all-migrations' === $tmm_do ) {
                SpeedSearch::$options->delete( 'last-db-success-migration-number' );
                SpeedSearch::$options->delete( 'last-db-migration-success' );
                DB::do_migrations();

                ?>
                <br>
                <br>
                <b>All DB migrations were applied.</b>
                <hr>
                <?php $print_migrations_log(); ?>
                <?php
            } elseif ( 'reset-migrations-log' === $tmm_do ) {
                SpeedSearch::$options->delete( 'db-migration-log' );

                ?>
                <br>
                <br>
                <b>Migrations log was reset.</b>
                <hr>
                <?php $print_migrations_log(); ?>
                <?php
            } elseif ( 'delete-db-tables' === $tmm_do ) {
                SpeedSearch::$options->delete( 'last-db-success-migration-number' );
                SpeedSearch::$options->delete( 'last-db-migration-success' );

                SpeedSearch::$options->delete( 'db-migration-log' );

                global $wpdb;

                $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->speedsearch_feed_buffer}" );

                ?>
                <br>
                <br>
                <b>All plugin DB tables were deleted.</b>
                <hr>
                <?php
            } else {
                ?>
                <b>No such a command.</b>
                <?php
            }

            ?>
        </div>
    </div>
    <?php

    return;
}

?>
<div class="wrap">
<?php


if ( ! Initial_Setup::is_store_authorized() ) { // Authorisation check.
    $auth_response = Backend_Requests::get( 'auth' );

    if ( isset( $auth_response['url'] ) ) {
        ?>
        <div id="speedsearch-auth-store-block">
            <h2>
                <?php
                    SpeedSearch::$options->get( 'store-was-authorised' ) ?
                        esc_attr_e( 'The backend has lost connection to your store. Please authorize the reconnection.', 'speedsearch' ) :
                        esc_attr_e( 'Please authorize your store', 'speedsearch' );
                ?>
            </h2>
            <a href="<?php echo esc_attr( $auth_response['url'] ); ?>"><?php submit_button( __( 'Auth', 'speedsearch' ) ); ?></a>
        </div>
        <?php
    } else {
        ?>
        <h2>
            <?php esc_attr_e( 'Something went wrong. Please visit this page later.', 'speedsearch' ); ?>
        </h2>
        <h4>
            <?php esc_attr_e( "You can send this debug info to the plugin's developer along with the description of the issue and your environment.", 'speedsearch' ); ?>
        </h4>
        <pre class="speedsearch-debug-block"><?php echo esc_html( print_r( $auth_response, true ) ); // @codingStandardsIgnoreLine ?></pre>
        <?php
    }

    return;
}

?>
    <h1 class="speedsearch-header"><?php esc_html_e( 'SpeedSearch Settings', 'speedsearch' ); ?></h1>

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

                <div class="wp-plugins-core-tab-content" data-tab-name="general-settings"
                        data-title="<?php esc_html_e( 'Settings body', 'speedsearch' ); ?>"
                        data-intro="
                        <?php
                        esc_html_e( "Once you select a tab, you'll see all the available settings in this section.<br><br>", 'speedsearch' );
                        esc_html_e( 'This is where you can customize the plugin to fit your needs.', 'speedsearch' );
                        ?>
                        ">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'General Settings', 'speedsearch' ); ?></h2>

                    <h2 class="speedsearch-block-heading"><?php esc_html_e( 'General Settings', 'speedsearch' ); ?></h2>

                    <div class="speedsearch-row speedsearch-how-to-treat-empty-row">
                        <label class="speedsearch-with-description">
                            <span class="speedsearch-label-block">
                                <span><?php esc_html_e( 'Display mode for empty categories, and attributes (with no products):', 'speedsearch' ); ?></span>
                                <span class="speedsearch-description">
                                    <?php
                                    echo sanitize_user_field(
                                        'speedsearch',
                                        __(
                                            "There are 3 display modes for categories and attributes that aren't linked to any product entries. This is particularly relevant when designing the user experience of the filtering process.<br><br>As the product list narrows down based on the already selected categories and filters, there is the option to display as normal the filters & categories that don't contain matching products, display them in a disabled state (greyed out, unclickable), or hide them altogether.",
                                            'speedsearch'
                                        ),
                                        0,
                                        'display'
                                    );
                                    ?>
                                    <br><br>
                                    <?php
                                    esc_html_e(
                                        "As the product list narrows down based on the already selected categories and filters, there is the option to display as normal the filters & categories that don't contain matching products, display them in a disabled state (greyed out, unclickable), or hide them altogether.",
                                        'speedsearch'
                                    );
                                    ?>
                                </span>
                            </span>
                            <select class="speedsearch-input speedsearch-select-input" name="how-to-treat-empty">
                                <option value="show" <?php selected( SpeedSearch::$options->get( 'setting-how-to-treat-empty' ), 'show' ); ?>>
                                    <?php esc_html_e( 'Show', 'speedsearch' ); ?>
                                </option>
                                <option value="show-disabled" <?php selected( SpeedSearch::$options->get( 'setting-how-to-treat-empty' ), 'show-disabled' ); ?>>
                                    <?php esc_html_e( 'Show disabled', 'speedsearch' ); ?>
                                </option>
                                <option value="hide" <?php selected( SpeedSearch::$options->get( 'setting-how-to-treat-empty' ), 'hide' ); ?>>
                                    <?php esc_html_e( 'Hide', 'speedsearch' ); ?>
                                </option>
                            </select>
                        </label>
                    </div>

                    <div class="speedsearch-column">
                        <h2 class="speedsearch-block-heading mt-20 mb-20">
                            <?php esc_html_e( 'Hide filters on archive pages', 'speedsearch' ); ?>
                        </h2>
                        <p class="mb-30">
                            <?php
                            esc_html_e(
                                'Affects all types of archive pages: attribute term archive page, product tag archive, product category archive.',
                                'speedsearch'
                            );
                            ?>
                        </p>
                        <div class="speedsearch-row">
                            <label>
                                <span><?php esc_html_e( 'Hide tags:', 'speedsearch' ); ?></span>
                                <input class="speedsearch-input" name="archive-pages-hide-tags" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-archive-pages-hide-tags' ), '1' ); ?>>
                            </label>
                        </div>
                        <div class="speedsearch-row">
                            <label>
                                <span><?php esc_html_e( 'Hide filters (and toggles):', 'speedsearch' ); ?></span>
                                <input class="speedsearch-input" name="archive-pages-hide-filters" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-archive-pages-hide-filters' ), '1' ); ?>>
                            </label>
                        </div>
                        <div class="speedsearch-row">
                            <label>
                                <span><?php esc_html_e( 'Hide categories:', 'speedsearch' ); ?></span>
                                <input class="speedsearch-input" name="archive-pages-hide-categories" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-archive-pages-hide-categories' ), '1' ); ?>>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="autocomplete">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Autocomplete', 'speedsearch' ); ?></h2>

                    <div class="speedsearch-row">
                        <label>
                            <span><?php esc_html_e( 'Display the currently selected autocomplete recommendation as highlighted text in the search bar:', 'speedsearch' ); ?></span>
                            <input class="speedsearch-input" name="autocomplete-show-selected-option-text-in-the-search-as-selected-text" type="checkbox"
                                <?php
                                checked(
                                    SpeedSearch::$options->get( 'setting-autocomplete-show-selected-option-text-in-the-search-as-selected-text' ),
                                    '1'
                                )
                                ?>
                                >
                        </label>
                    </div>
                    <div class="speedsearch-row">
                        <label class="speedsearch-with-description">
                            <span class="speedsearch-label-block">
                                <span><?php esc_html_e( 'Display a second search bar on the shop page for searching within search results:', 'speedsearch' ); ?></span>
                                <span class="speedsearch-description">
                                    <?php
                                    esc_html_e(
                                        'To enable this on other pages, you can use the argument "search_in_results" for "speedsearch" shortcode: [speedsearch search_in_results="1"]',
                                        'speedsearch'
                                    );
                                    ?>
                                </span>
                            </span>
                            <input class="speedsearch-input" name="autocomplete-setting-add-search-field-inside-of-search-result-for-shop-page" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-add-search-field-inside-of-search-result-for-shop-page' ), '1' ); ?>>
                        </label>
                    </div>
                    <div class="speedsearch-row">
                        <label class="speedsearch-with-description">
                            <span class="speedsearch-label-block">
                                <span><?php esc_html_e( 'Apply automatic filtering based on search terms, tags, categories:', 'speedsearch' ); ?></span>
                                <span class="speedsearch-description">
                                    <?php
                                    esc_html_e(
                                        'When enabled, if a search term matches an existing filter, the filter will be recognized and applied automatically.',
                                        'speedsearch'
                                    );
                                    ?>
                                </span>
                            </span>
                            <input class="speedsearch-input" name="autocomplete-automatic-filtering-based-on-search-terms" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-autocomplete-automatic-filtering-based-on-search-terms' ), '1' ); ?>>
                        </label>
                    </div>
                    <div class="speedsearch-row flex-align-start">
                        <div class="speedsearch-column mt-30">
                            <h3 class="speedsearch-block-heading"><?php esc_html_e( 'Left Panel', 'speedsearch' ); ?></h3>
                            <label class="speedsearch-with-description">
                                <span class="speedsearch-label-block">
                                    <span><?php esc_html_e( 'Automatically preselect the first autocomplete recommendation:', 'speedsearch' ); ?></span>
                                    <span class="speedsearch-description">
                                        <?php
                                        esc_html_e(
                                            'The autocomplete recommendations can be selected using the arrow down key. If the automatic selection is disabled, the arrow down functionality still remains and can be used.',
                                            'speedsearch'
                                        );
                                        ?>
                                    </span>
                                    </span>
                                <input class="speedsearch-input" name="autocomplete-automatically-preselect-the-first-result" type="checkbox"
                                    <?php
                                    checked( SpeedSearch::$options->get( 'setting-autocomplete-automatically-preselect-the-first-result' ), '1' );
                                    ?>
                                >
                            </label>

                            <label class="mt-30 speedsearch-with-description">
                                <span class="speedsearch-label-block">
                                    <span><?php esc_html_e( 'On filter select, preserve the currently selected filters (instead of resetting them):', 'speedsearch' ); ?></span>
                                    <span class="speedsearch-description">
                                        <?php
                                        esc_html_e(
                                            'Autocomplete recognizes and suggests attributes as autocomplete recommendations. When selected, they act as filters on the resulting product list.',
                                            'speedsearch'
                                        );
                                        ?>
                                        <br><br>
                                        <?php
                                        esc_html_e(
                                            'If enabled, this property allows for multiple filter selections from the autocomplete recommendations. When disabled, only the last filter will be applied.',
                                            'speedsearch'
                                        );
                                        ?>
                                    </span>
                                </span>
                                <input class="speedsearch-input" name="autocomplete-select-preserve-all-filters" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-autocomplete-select-preserve-all-filters' ), '1' ); ?>>
                            </label>

                            <label class="mt-30">
                                <span><?php esc_html_e( 'Redirect to attribute/tag/category archive (from archive pages):', 'speedsearch' ); ?></span>
                                <input class="speedsearch-input" name="autocomplete-redirect-to-attribute-archive" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-autocomplete-redirect-to-attribute-archive' ), '1' ); ?>>
                            </label>

                            <div class="speedsearch-row mt-30">
                                <label class="speedsearch-with-description">
                                    <span class="speedsearch-label-block">
                                        <span><?php esc_html_e( 'Use predefined ordering of the autocomplete recommendations blocks (Words, Categories, Tags, Attributes):', 'speedsearch' ); ?></span>
                                        <span class="speedsearch-description">
                                            <?php
                                            esc_html_e(
                                                'By default, the autocomplete recommendations will be grouped into several blocks: Words, Categories, Tags, and Attributes; based on the recommendation type. By enabling this setting, the blocks will always have the same order in the recommendations list.',
                                                'speedsearch'
                                            );
                                            ?>
                                        </span>
                                    </span>
                                    <input class="speedsearch-input" name="autocomplete-blocks-fixed-order" type="checkbox"
                                        <?php checked( SpeedSearch::$options->get( 'setting-autocomplete-blocks-fixed-order' ), '1' ); ?>>
                                </label>
                            </div>
                            <div class="speedsearch-row mt-30">
                                <label class="speedsearch-with-description">
                                    <span class="speedsearch-label-block">
                                        <span><?php esc_html_e( 'Mix autocomplete recommendations types:', 'speedsearch' ); ?></span>
                                        <span class="speedsearch-description">
                                            <?php
                                            esc_html_e(
                                                'If enabled, recommendations will be mixed, instead of being displayed in grouped blocks. The recommendation type will be displayed as a label under the main text.',
                                                'speedsearch'
                                            );
                                            ?>
                                        </span>
                                    </span>
                                    <input class="speedsearch-input" name="autocomplete-delete-search-blocks-and-instead-show-singular-results-with-labels-below" type="checkbox"
                                        <?php checked( SpeedSearch::$options->get( 'setting-autocomplete-delete-search-blocks-and-instead-show-singular-results-with-labels-below' ), '1' ); ?>>
                                </label>
                            </div>
                        </div>
                        <div class="speedsearch-column mt-30">
                            <h3 class="speedsearch-block-heading"><?php esc_html_e( 'Right Panel (products)', 'speedsearch' ); ?></h3>
                            <label class="speedsearch-with-description">
                                <span class="speedsearch-label-block">
                                    <span><?php esc_html_e( 'Display the tabs from the Autocomplete Right Panel in the product overview:', 'speedsearch' ); ?></span>
                                    <span class="speedsearch-description">
                                        <?php
                                        esc_html_e(
                                            'The Autocomplete Right Panel contains tabs specific to each shop (e.g. Products, Pages, Artists, Themes, etc.) that makes it easier for users to navigate the recommendations list. By enabling this feature, the tabbed view will also be applied to the product overview which is displayed once the search is performed.',
                                            'speedsearch'
                                        );
                                        ?>
                                    </span>
                                </span>
                                <input class="speedsearch-input" name="autocomplete-show-tabs-on-page" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-autocomplete-show-tabs-on-page' ), '1' ); ?>>
                            </label>
                            <label class="speedsearch-with-description mt-30">
                                <span class="speedsearch-label-block">
                                    <span><?php esc_html_e( 'Open products in the new tab:', 'speedsearch' ); ?></span>
                                    <span class="speedsearch-description">
                                        <?php
                                        esc_html_e(
                                            'When clicking on a product recommendation, open it in a new tab.',
                                            'speedsearch'
                                        );
                                        ?>
                                    </span>
                                </span>
                                <input class="speedsearch-input" name="autocomplete-open-products-in-new-window" type="checkbox"
                                    <?php checked( SpeedSearch::$options->get( 'setting-autocomplete-open-products-in-new-window' ), '1' ); ?>>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="categories">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Categories', 'speedsearch' ); ?></h2>
                    <h2 class="speedsearch-block-heading mb-30"><?php esc_html_e( 'Categories Settings', 'speedsearch' ); ?></h2>
                    <div class="speedsearch-row">
                        <label>
                            <span><?php esc_html_e( 'Allow multiple categories selection:', 'speedsearch' ); ?></span>
                            <input class="speedsearch-input" name="categories-support-multi-select" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-categories-support-multi-select' ), '1' ); ?>>
                        </label>
                    </div>
                    <div class="speedsearch-row">
                        <label>
                            <span><?php esc_html_e( 'Display categories based on their predefined order, instead of alphabetically:', 'speedsearch' ); ?></span>
                            <input class="speedsearch-input" name="categories-order-by-their-order" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-categories-order-by-their-order' ), '1' ); ?>>
                        </label>
                    </div>
                    <div class="speedsearch-row">
                        <label>
                            <span><?php esc_html_e( 'Allow current category deselection on click:', 'speedsearch' ); ?></span>
                            <input class="speedsearch-input" name="current-category-can-be-deselected-on-click" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-current-category-can-be-deselected-on-click' ), '1' ); ?>>
                        </label>
                    </div>
                    <div class="speedsearch-row">
                        <label>
                            <span><?php esc_html_e( 'Allow to deselect category on category archive pages:', 'speedsearch' ); ?></span>
                            <input class="speedsearch-input" name="allow-to-deselect-category-on-category-archive-pages" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-allow-to-deselect-category-on-category-archive-pages' ), '1' ); ?>>
                        </label>
                    </div>
                    <div class="speedsearch-column speedsearch-categories-structure">
                        <h4 class="speedsearch-block-heading mb-30"><?php esc_html_e( 'Categories URL structure', 'speedsearch' ); ?></h4>
                        <div class="speedsearch-row">
                            <?php if ( ! get_option( 'permalink_structure' ) ) : // If permalink structure is plain (?page_id=3465). ?>
                                <h4 class="speedsearch-block-heading mb-10">
                                    <?php esc_html_e( 'Changing categories structure is not allowed when you have ' ); ?>
                                    <a target="_blank" href="options-permalink.php"><?php esc_html_e( '"Plain" permalink structure.', 'speedsearch' ); ?></a>
                                </h4>
                            <?php else : ?>
                                <table class="speedsearch-select-table">
                                    <thead>
                                    <tr>
                                        <th>
                                            <?php esc_html_e( 'Name', 'speedsearch' ); ?>
                                        </th>
                                        <th>
                                            <?php esc_html_e( 'Structure (example)', 'speedsearch' ); ?>
                                        </th>
                                        <th>
                                            <?php esc_html_e( 'Enabled', 'speedsearch' ); ?>
                                        </th>
                                    </tr>
                                    </thead>
                                    <?php
                                    $categories_structure = SpeedSearch::$options->get( 'setting-categories-structure' );
                                    ?>
                                    <tbody>
                                    <tr>
                                        <td>
                                            <?php esc_html_e( 'Last (with shop main page)', 'speedsearch' ); ?>
                                        </td>
                                        <td>
                                            <?php echo esc_attr( rtrim( get_permalink( wc_get_page_id( 'shop' ) ), '/' ) ); ?><b>/nike</b>
                                        </td>
                                        <td>
                                            <label>
                                                <input class="speedsearch-input" name="categories-structure" data-val="last-with-shop-page" type="checkbox"
                                                    <?php checked( $categories_structure['type'], 'last-with-shop-page' ); ?>>
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php esc_html_e( 'Full (without shop main page)', 'speedsearch' ); ?>
                                        </td>
                                        <td>
                                            <?php echo esc_attr( get_home_url() ); ?>/<label>
                                                <input
                                                        class="speedsearch-input" type="text" name="categories-prefix"
                                                        value="<?php echo esc_attr( $categories_structure['categories-prefix'] ); ?>">
                                            </label><b>/clothing/shoes/nike</b>
                                        </td>
                                        <td>
                                            <label>
                                                <input class="speedsearch-input" name="categories-structure" data-val="full-without-shop-page" type="checkbox"
                                                    <?php checked( $categories_structure['type'], 'full-without-shop-page' ); ?>>
                                            </label>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="wp-plugins-core-tab-content" data-tab-name="pagination">
                    <h2 class="wp-plugins-core-tab-heading"><?php esc_html_e( 'Pagination', 'speedsearch' ); ?></h2>
                    <h2 class="speedsearch-block-heading"><?php esc_html_e( 'Pagination Settings', 'speedsearch' ); ?></h2>

                    <div class="speedsearch-row">
                        <label>
                            <span><?php esc_html_e( 'Enable infinite scroll:', 'speedsearch' ); ?></span>
                            <input class="speedsearch-input" name="is-infinite-scroll-enabled" type="checkbox"
                                <?php checked( SpeedSearch::$options->get( 'setting-is-infinite-scroll-enabled' ), '1' ); ?>>
                        </label>
                    </div>
                    <div class="speedsearch-row speedsearch-pagination-row">
                        <label class="speedsearch-with-description">
                            <?php
                            $posts_per_page_from_theme_integration = apply_filters( 'loop_shop_per_page', 0 );
                            ?>
                            <span class="speedsearch-label-block">
                                <span><?php esc_html_e( 'Posts per page:', 'speedsearch' ); ?></span>
                                <span class="speedsearch-description">
                                    <?php
                                    esc_html_e(
                                        'When using pagination, products will be loaded in navigable pages with the number of products per page specified.',
                                        'speedsearch'
                                    );

                                    if ( $posts_per_page_from_theme_integration ) {
                                        echo esc_html( ' ' );
                                        esc_html_e(
                                            'You can change the number of products per page in the theme customizer.',
                                            'speedsearch'
                                        );
                                    }
                                    ?>
                                </span>
                            </span>
                            <input class="speedsearch-input speedsearch-number-input" maxlength="2" name="posts-per-page" type="text"
                                <?php disabled( '1' === SpeedSearch::$options->get( 'setting-is-infinite-scroll-enabled' ) || $posts_per_page_from_theme_integration ); ?>
                                data-posts-per-page-from-theme-integration="<?php echo esc_attr( $posts_per_page_from_theme_integration ? '1' : '0' ); ?>"
                                value="<?php echo esc_html( $posts_per_page_from_theme_integration ? $posts_per_page_from_theme_integration : SpeedSearch::$options->get( 'setting-posts-per-page' ) ); ?>">
                        </label>
                    </div>

                    <h2 class="speedsearch-block-heading mt-20"><?php esc_html_e( 'Ordering options', 'speedsearch' ); ?></h2>

                    <div class="speedsearch-ordering-options-block">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Name', 'speedsearch' ); ?></th>
                                    <th><?php esc_html_e( 'Enabled', 'speedsearch' ); ?></th>
                                    <th><?php esc_html_e( 'Default', 'speedsearch' ); ?></th>
                                    <th><?php esc_html_e( 'Sort by', 'speedsearch' ); ?></th>
                                    <th><?php esc_html_e( 'Reorder', 'speedsearch' ); ?></th>
                                    <th><?php esc_html_e( 'Delete', 'speedsearch' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $ordering_options = SpeedSearch::$options->get( 'setting-ordering-options' );

                            $first_elem = [ 'placeholder' => array_values( $ordering_options )[0] ];

                            $i = 0;
                            foreach ( array_merge( $first_elem, $ordering_options ) as $ordering_option_slug => $ordering_option ) :
                                $i ++;
                                $placeholder              = 1 === $i;
                                $standard_ordering_option = isset( $ordering_option['standard'] ) && $ordering_option['standard'] && ! $placeholder;
                                ?>
                                <tr class="speedsearch-ordering-option <?php echo esc_attr( $standard_ordering_option ? 'speedsearch-standard-ordering-option' : '' ); ?> <?php echo $placeholder ? 'speedsearch-hidden' : ''; ?>"
                                        data-option-slug="<?php echo esc_attr( $ordering_option_slug ); ?>">
                                    <td>
                                        <label>
                                            <input class="speedsearch-input" name="speedsearch-ordering-option-name-<?php echo esc_attr( $ordering_option_slug ); ?>" type="text"
                                                value="<?php echo esc_attr( ! $placeholder ? $ordering_option['text'] : '' ); ?>">
                                        </label>
                                    </td>
                                    <td>
                                        <label>
                                            <input class="speedsearch-input" name="speedsearch-ordering-option-enabled-<?php echo esc_attr( $ordering_option_slug ); ?>" type="checkbox"
                                            <?php checked( $ordering_option['enabled'] ); ?>
                                            <?php disabled( ! $placeholder && isset( $ordering_option['default'] ) && $ordering_option['default'] ); ?>>
                                        </label>
                                    </td>
                                    <td>
                                        <label>
                                            <input class="speedsearch-input"
                                                value="<?php echo esc_attr( $ordering_option_slug ); ?>"
                                                name="speedsearch-ordering-option-default"
                                                type="radio"
                                                <?php checked( ! $placeholder && isset( $ordering_option['default'] ) && $ordering_option['default'] ); ?>
                                                <?php disabled( ! $placeholder && ! $ordering_option['enabled'] ); ?>>
                                        </label>
                                    </td>
                                    <td class="speedsearch-sorting-option-sort-by-block">
                                        <?php
                                        if ( $placeholder ) {
                                            $sort_by_params = [ '' => '-' ];
                                        } elseif ( isset( $ordering_option['standard'] ) && $ordering_option['standard'] ) {
                                            $sort_by_params = Backend_Requests::get_sort_by_property_params( $ordering_option_slug );
                                        } else {
                                            $sort_by_params = $ordering_option['sort_by'];
                                        }

                                        $options = [
                                            'optgroups' => [
                                                'optgroup_class_1' => 'Group 1',
                                                'optgroup_class_2' => 'Group 2',
                                            ],
                                            'options'   => [
                                                'optgroup_class_1' => [
                                                    'option1' => 'Option 1',
                                                    'option2' => 'Option 2',
                                                ],
                                                'optgroup_class_2' => [
                                                    'option3' => 'Option 3',
                                                    'option4' => 'Option 4',
                                                ],
                                                'option5' => 'Option 5',
                                            ],
                                        ];

                                        foreach ( $sort_by_params as $sort_by_param => $direction ) {
                                            ?>
                                            <div class="speedsearch-row">
                                                <label>
                                                    <?php
                                                        $tags = get_terms(
                                                            [
                                                                'taxonomy' => 'product_tag',
                                                                'fields'   => 'id=>name',
                                                            ]
                                                        );

                                                        $tags = array_combine(
                                                            array_map(
                                                                function( $key ) {
                                                                    return 'tag-' . $key;
                                                                },
                                                                array_keys( $tags )
                                                            ),
                                                            $tags
                                                        );

                                                        Templating::print_dropdown(
                                                            '',
                                                            [
                                                                'optgroups' => [
                                                                    'no_selection' => __( 'No selection', 'speedsearch' ),
                                                                    'property'     => __( 'Property', 'speedsearch' ),
                                                                    'tags'         => __( 'Tag', 'speedsearch' ),
                                                                    'stats'        => __( 'Stats', 'speedsearch' ),
                                                                ],
                                                                'options' => [
                                                                    'no_selection' => [
                                                                        '' => '-',
                                                                    ],
                                                                    'property' => array_combine( Ordering::ORDERING_PROPERTIES, Ordering::ORDERING_PROPERTIES ),
                                                                    'tags'     => $tags,
                                                                    'stats'    => [
                                                                        'views'     => 'views',
                                                                        'purchases' => 'purchases',
                                                                        'cart'      => 'cart',
                                                                    ],
                                                                ],
                                                            ],
                                                            $sort_by_param,
                                                            $standard_ordering_option
                                                        );
                                                    ?>
                                                </label>
                                                <label>
                                                    <?php
                                                        Templating::print_dropdown(
                                                            '',
                                                            array_merge(
                                                                [ '' => '-' ],
                                                                [
                                                                    'asc'  => 'asc',
                                                                    'desc' => 'desc',
                                                                ]
                                                            ),
                                                            $direction,
                                                            $standard_ordering_option
                                                        );
                                                    ?>
                                                </label>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </td>
                                    <td class="speedsearch-reorder-sorting-options-handle"></td>
                                    <td class="speedsearch-delete-sorting-option-button">
                                        <?php
                                        if ( ! $standard_ordering_option ) :
                                            submit_button(
                                                __( 'Delete', 'speedsearch' ),
                                                [ 'remove', 'speedsearch-delete-ordering-option-btn' ]
                                            );
                                        endif;
                                        ?>
                                    </td>
                                </tr>
                                <?php
                            endforeach;
                            ?>
                            </tbody>
                        </table>
                        <div class="speedsearch-add-ordering-option">
                            <?php
                            submit_button(
                                __( 'Add new', 'speedsearch' ),
                                [ 'remove', 'speedsearch-add-ordering-option-btn' ]
                            );
                            ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <hr>
        <div class="speedsearch-container">
            <div class="speedsearch-column mt-m20"
                    data-title="<?php esc_html_e( 'Save button', 'speedsearch' ); ?>"
                    data-intro="
                    <?php
                    esc_html_e( "Don't forget to save the changes you make! Your new settings will be applied only after you press the \"Save\" button.<br><br>Pay attention! Some setting changes will be reflected in your shop after one minute. This is because the public filters data is cached in persistent cache for 1 minute.", 'speedsearch' );
                    ?>
                    ">
                <?php submit_button( __( 'Save', 'speedsearch' ), [ 'primary', 'speedsearch-submit' ] ); ?>
            </div>
        </div>
        <hr>
        <div class="speedsearch-container">
            <div class="speedsearch-row"
                data-title="<?php esc_html_e( 'Intro start', 'speedsearch' ); ?>"
                data-intro="<?php esc_html_e( 'You can start this introduction again at anytime by pressing this button.', 'speedsearch' ); ?>">
                <a class="speedsearch-link speedsearch-plugin-tour-link"
                    data-step="1" data-tooltip-class="speedsearch-intro-tour-step-1" data-highlight-class="speedsearch-intro-tour-step-1-highlight"
                    data-title="<?php esc_html_e( 'Welcome message', 'speedsearch' ); ?>" data-position="top"
                    data-intro="
                    <?php
                        esc_html_e( 'Thank you for using SpeedSearch!<br><br>', 'speedsearch' );
                        esc_html_e( "Let's take a look at what you can do with our plugin settings.<br><br>", 'speedsearch' );
                        esc_html_e( "Would you rather go take the tour at a later time? Feel free to close the wizard for now and open it whenever you're ready. You can do this via the \"Plugin Introduction\" button at the bottom of the page.", 'speedsearch' );
                    ?>
                    ">
                    <?php esc_html_e( 'Plugin introduction', 'speedsearch' ); ?>
                </a>
            </div>
        </div>
    </form>
</div>
