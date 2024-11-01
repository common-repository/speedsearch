<?php
/**
 * Categories template.
 *
 * Accepts: $html_id
 *
 * This template can be overridden by copying it to yourtheme/speedsearch/themes/speedsearch_theme/templates/parts/categories.php.
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
use SpeedSearch\SpeedSearch;

if ( is_product_taxonomy() && SpeedSearch::$options->get( 'setting-archive-pages-hide-categories' ) ) {
    return;
}

?>

<div class="speedsearch-categories"
    <?php
    echo sanitize_user_field(
        'speedsearch',
        isset( $html_id ) && $html_id ? ' id="' . esc_attr( $html_id ) . '"' : '',
        0,
        'display'
    );
    ?>
>
    <ul>
        <a href="/" class="speedsearch-category"><span data-slug="all-categories"><?php esc_html_e( 'All Categories', 'speedsearch' ); ?></span></a>
        <?php
        echo sanitize_user_field(
            'speedsearch',
            HTML::get_categories_html(),
            0,
            'display'
        );
        ?>
    </ul>
</div>
