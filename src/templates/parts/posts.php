<?php
/**
 * Posts container.
 *
 * This template can be overridden by copying it to yourtheme/speedsearch/themes/speedsearch_theme/templates/parts/posts.php.
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

use SpeedSearch\SpeedSearch;
use SpeedSearch\HTML;

$posts_data = HTML::$was_html_total_posts_counter_inited ? SpeedSearch::$ajax->search( true, true ) : '';

$show_posts_integration_text = SpeedSearch::$integrations->is_current_theme_products_integration_present &&
                                '1' === SpeedSearch::$options->get( 'setting-posts-enable-theme-integration' );

$products_container_tag = HTML::get_products_container_tag();

?>

<div class="speedsearch-posts-container <?php echo esc_attr( ! $show_posts_integration_text ? 'no-theme-integration' : '' ); ?>"
    <?php
    echo sanitize_user_field(
        'speedsearch',
        isset( $html_id ) && $html_id ? ' id="' . esc_attr( $html_id ) . '"' : '',
        0,
        'display'
    );
    ?>
>
    <<?php echo esc_html( $products_container_tag ); ?> class="speedsearch-posts <?php echo esc_attr( HTML::get_products_container_classes() ); ?>">
        <?php
        echo sanitize_user_field(
            'speedsearch',
            HTML::get_posts_html( $posts_data ),
            0,
            'display'
        );
        ?>
    </<?php echo esc_html( $products_container_tag ); ?>>
    <?php
    echo sanitize_user_field(
        'speedsearch',
        HTML::get_pagination_html( $posts_data ),
        0,
        'display'
    );
    ?>
</div>
