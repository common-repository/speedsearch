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

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

?>

    <div class="row category-page-row">
        <div class="col large-12">
            <?php
            /**
             * Hook: woocommerce_before_main_content.
             *
             * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
             * @hooked woocommerce_breadcrumb - 20 (FL removed)
             * @hooked WC_Structured_Data::generate_website_data() - 30
             */
            do_action( 'woocommerce_before_main_content' );

            ?>

            <?php
            /**
             * Hook: woocommerce_archive_description.
             *
             * @hooked woocommerce_taxonomy_archive_description - 10
             * @hooked woocommerce_product_archive_description - 10
             */
            do_action( 'woocommerce_archive_description' );

            echo sanitize_user_field(
                'speedsearch',
                do_shortcode( // SpeedSearch shortcode.
                    '[speedsearch hide_search="' . (int) ! SpeedSearch::$options->get( 'setting-add-search-field-inside-of-search-result-for-shop-page' ) . '"]'
                ),
                0,
                'display'
            );

            /**
             * Hook: woocommerce_after_main_content.
             *
             * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
             */
            do_action( 'woocommerce_after_main_content' );
            ?>
        </div>
    </div>

<?php

get_footer( 'shop' );
