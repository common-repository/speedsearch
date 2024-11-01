<?php
/**
 * Recently viewed products.
 *
 * Accepts: ( $html_id )
 *
 * This template can be overridden by copying it to yourtheme/speedsearch/themes/speedsearch_theme/templates/other/recently-viewed-products.php.
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

use SpeedSearch\HTML;

$products_container_tag = HTML::get_products_container_tag();

?>

<<?php echo esc_html( $products_container_tag ); ?> class="speedsearch-posts speedsearch-recently-viewed-products <?php echo esc_attr( HTML::get_products_container_classes() ); ?>
    <?php
    echo sanitize_user_field(
        'speedsearch',
        isset( $add_most_popular_products_if_limit_is_not_hit ) && $add_most_popular_products_if_limit_is_not_hit ? ' add-most-popular-products-if-limit-is-not-hit ' : '',
        0,
        'display'
    );
    ?>
    "
    <?php
    echo sanitize_user_field(
        'speedsearch',
        isset( $thumbnail_image_size ) && $thumbnail_image_size ? ' data-thumbnail-image-size="' . esc_attr( $thumbnail_image_size ) . '"' : '',
        0,
        'display'
    );
    ?>
    <?php
    echo sanitize_user_field(
        'speedsearch',
        isset( $show_limit ) && $show_limit ? ' data-show-limit="' . esc_attr( $show_limit ) . '"' : '',
        0,
        'display'
    );
    ?>
    <?php
    echo sanitize_user_field(
        'speedsearch',
        isset( $html_id ) && $html_id ? ' id="' . esc_attr( $html_id ) . '"' : '',
        0,
        'display'
    );
    ?>
>
</<?php echo esc_html( $products_container_tag ); ?>>

