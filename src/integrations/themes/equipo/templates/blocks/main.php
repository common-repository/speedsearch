<?php
/**
 * Main posts template (categories and posts block) for the theme.
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

<div class="container et-clearfix speedsearch-main-container"<?php echo esc_html( isset( $html_id ) && $html_id ? ' id="' . $html_id . '"' : '' ); ?>">
<div class="layout-sidebar product-sidebar et-clearfix speedsearch-column speedsearch-filters-column">
    <?php if ( ! $hide_search ) : ?>
        <?php HTML::render_template( 'parts/search.php', [ 'search_in_results' => true ] ); ?>
    <?php endif; ?>
    <?php HTML::render_template( 'parts/active-filters.php' ); ?>
    <?php HTML::render_template( 'parts/categories.php' ); ?>
    <?php HTML::render_template( 'parts/filters.php' ); ?>
    <?php HTML::render_template( 'parts/tags.php' ); ?>
</div>
<div class="layout-content product-content et-clearfix speedsearch-column">
    <div class="speedsearch-mobile-top-block">
        <div class="speedsearch-open-mobile-filters-btn">
            <div class="speedsearch-open-mobile-icon"></div>
            <?php esc_html_e( 'Filters', 'speedsearch' ); ?>
        </div>
        <?php HTML::render_template( 'parts/active-filters.php' ); ?>
    </div>

    <?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>

        <h1 class="page-title"><?php woocommerce_page_title(); ?></h1>

    <?php endif; ?>

    <?php do_action( 'woocommerce_archive_description' ); ?>

    <?php HTML::render_template( 'parts/posts.php' ); ?>
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
