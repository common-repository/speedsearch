<?php
/**
 * Main posts template (categories and posts block).
 *
 * This template can be overridden by copying it to yourtheme/speedsearch/themes/speedsearch_theme/templates/blocks/main.php.
 *
 * HOWEVER, on occasion SpeedSearch will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package SpeedSearch
 * @version 1.6.20
 */

/**
 * Suppress inspection warning for potentially undefined variables.
 *
 * @noinspection PhpUndefinedVariableInspection
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

use SpeedSearch\HTML;

?>

<div class="speedsearch-main-container"
        <?php
        echo sanitize_user_field(
            'speedsearch',
            isset( $html_id ) && $html_id ? ' id="' . esc_attr( $html_id ) . '"' : '',
            0,
            'display'
        );
        ?>
    >
    <div class="speedsearch-row">
        <?php HTML::render_template( 'parts/categories.php' ); ?>
        <div class="speedsearch-column">
            <?php if ( ! $hide_search ) : ?>
                <?php HTML::render_template( 'parts/search.php', [ 'search_in_results' => true ] ); ?>
            <?php endif; ?>
            <?php HTML::render_template( 'parts/active-filters.php' ); ?>
            <?php HTML::render_template( 'parts/filters.php' ); ?>
            <?php HTML::render_template( 'parts/tags.php' ); ?>
            <div class="speedsearch-column">
                <div class="speedsearch-mobile-top-block">
                    <div class="speedsearch-open-mobile-filters-btn">
                        <div class="speedsearch-open-mobile-icon"></div>
                        <?php esc_html_e( 'Filters', 'speedsearch' ); ?>
                    </div>
                </div>
            </div>
            <?php HTML::render_template( 'parts/posts.php' ); ?>
        </div>
    </div>
</div>

<?php
if ( ! HTML::$mobile_filters_block_container_was_added ) :
    HTML::$mobile_filters_block_container_was_added = true;
    ?>

<div class="speedsearch-mobile-filters-block-container">
    <?php if ( ! $hide_search ) : ?>
        <?php HTML::render_template( 'parts/search.php', [ 'search_in_results' => true ] ); ?>
    <?php endif; ?>
    <?php HTML::render_template( 'parts/active-filters.php' ); ?>
    <?php HTML::render_template( 'parts/categories.php' ); ?>
    <?php HTML::render_template( 'parts/filters.php' ); ?>
    <?php HTML::render_template( 'parts/tags.php' ); ?>
</div>

    <?php
endif;
?>
