<?php
/**
 * Posts filters template (sort by, price, attributes).
 *
 * Accepts: $display_for_settings
 *
 * This template can be overridden by copying it to yourtheme/speedsearch/themes/speedsearch_theme/templates/parts/filters.php.
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

use SpeedSearch\Filters;
use SpeedSearch\SpeedSearch;

if ( is_product_taxonomy() && SpeedSearch::$options->get( 'setting-archive-pages-hide-filters' ) ) {
    return;
}

?>

<div class="speedsearch-filters-block speedsearch-filters<?php echo esc_attr( isset( $display_for_settings ) ? ' speedsearch-filters-settings' : '' ); ?>"
    <?php
    echo sanitize_user_field(
        'speedsearch',
        isset( $html_id ) && $html_id ? ' id="' . esc_attr( $html_id ) . '"' : '',
        0,
        'display'
    );
    ?>
>
        <?php Filters::render( isset( $display_for_settings ) ? $display_for_settings : false ); ?>
        <div class="speedsearch-toggles-container">
                <?php
                echo sanitize_user_field(
                    'speedsearch',
                    Filters::get_toggles_html( false, isset( $display_for_settings ) ),
                    0,
                    'display'
                );
                ?>
        </div>
</div>
