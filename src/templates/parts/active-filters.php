<?php
/**
 * Currently active filters block.
 *
 * Should be the same as "updateActiveFilters" method.
 *
 * Accepts: ( $html_id )
 *
 * This template can be overridden by copying it to yourtheme/speedsearch/themes/speedsearch_theme/templates/parts/active-filters.php.
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

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

?>

<div class="speedsearch-currently-active-filters-block"
    <?php
    echo sanitize_user_field(
        'speedsearch',
        isset( $html_id ) && $html_id ? ' id="' . esc_attr( $html_id ) . '"' : '',
        0,
        'display'
    );
    ?>
>
    <span class="speedsearch-currently-active-filters-heading"><?php esc_html_e( 'Active filters', 'speedsearch' ); ?></span>
    <div class="speedsearch-currently-active-filters-container">
    </div>
    <div class="speedsearch-filter speedsearch-filters-reset-button">
        <div class="speedsearch-filter-btn-container">
            <button class="speedsearch-filter-btn"><span><?php esc_html_e( 'Reset filters', 'speedsearch' ); ?></span></button>
        </div>
    </div>
</div>
