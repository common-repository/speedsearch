<?php
/**
 * Template for admin menu
 *
 * @package SpeedSearch
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

?>
<div class="wrap">
    <h1 class="speedsearch-header"><?php esc_html_e( 'SpeedSearch Analytics', 'speedsearch' ); ?></h1>

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

                <div class="speedsearch-column speedsearch-analytics-column speedsearch-loading">
                    <div class="speedsearch-row">
                        <div class="speedsearch-analytics-block" data-type="most_searched_words">
                            <?php esc_html_e( 'Most searched words', 'speedsearch' ); ?>
                        </div>
                        <div class="speedsearch-analytics-block" data-type="most_searched_sentences">
                            <?php esc_html_e( 'Most searched sentences', 'speedsearch' ); ?>
                        </div>
                    </div>
                    <div class="speedsearch-row mb-40">
                        <div class="speedsearch-analytics-block" data-type="most_searched_words_without_result">
                            <?php esc_html_e( 'Search words without results', 'speedsearch' ); ?>
                        </div>
                        <div class="speedsearch-analytics-block" data-type="most_popular_results">
                            <?php esc_html_e( 'Most viewed products', 'speedsearch' ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
