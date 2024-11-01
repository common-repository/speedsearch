<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/speedsearch/themes/speedsearch_theme/templates/woocommerce/archive-product.php.
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

use SpeedSearch\SpeedSearch;

equipo_enovathemes_global_variables();

$product_post_size   = ( isset( $GLOBALS['equipo_enovathemes']['product-post-size'] ) && $GLOBALS['equipo_enovathemes']['product-post-size'] ) ? $GLOBALS['equipo_enovathemes']['product-post-size'] : 'medium';
$product_post_layout = ( isset( $GLOBALS['equipo_enovathemes']['product-post-layout'] ) && $GLOBALS['equipo_enovathemes']['product-post-layout'] ) ? $GLOBALS['equipo_enovathemes']['product-post-layout'] : 'grid';
$product_sidebar     = ( isset( $GLOBALS['equipo_enovathemes']['product-sidebar'] ) && $GLOBALS['equipo_enovathemes']['product-sidebar'] ) ? $GLOBALS['equipo_enovathemes']['product-sidebar'] : 'none';
$product_gap         = ( isset( $GLOBALS['equipo_enovathemes']['product-gap'] ) && 1 == $GLOBALS['equipo_enovathemes']['product-gap'] ) ? 'true' : 'false';

if ( is_active_sidebar( 'shop-widgets' ) && 'none' == $product_sidebar && ! defined( 'ENOVATHEMES_ADDONS' ) ) {
    $product_sidebar = 'left';
}

$class = array();

if ( 'list' == $product_post_layout && 'large' == $product_post_size ) {
    $product_post_size = 'medium';
}


if ( 'none' != $product_sidebar ) {
    $class[] = 'sidebar-active';
}

$class[] = 'post-layout';
$class[] = 'product-layout';
$class[] = $product_post_size;
$class[] = $product_post_layout;
if ( 'grid' == $product_post_layout ) {
    $class[] = 'gap-' . $product_gap;
}
$class[] = 'layout-sidebar-' . $product_sidebar;

?>
<?php get_header(); ?>

<?php do_action( 'equipo_enovathemes_title_section' ); ?>

<div class="<?php echo esc_attr( implode( ' ', $class ) ); ?>">
    <?php
    echo sanitize_user_field(
        'speedsearch',
        do_shortcode( // SpeedSearch shortcode.
            '[speedsearch hide_search="' . (int) ! SpeedSearch::$options->get( 'setting-add-search-field-inside-of-search-result-for-shop-page' ) . '"]'
        ),
        0,
        'display'
    );
    ?>
</div>

<?php get_footer(); ?>
