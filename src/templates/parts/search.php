<?php
/**
 * Template for search
 *
 * Accepts: $small_size
 *          $align
 *
 * This template can be overridden by copying it to yourtheme/speedsearch/themes/speedsearch_theme/templates/parts/search.php.
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

$classes = [];
if ( isset( $small_size ) && $small_size ) {
    $classes[] = 'speedsearch-size-small';
}
if ( isset( $search_in_results ) && $search_in_results ) {
    $classes[] = 'search-in-results';
}
if ( isset( $align ) && in_array( $align, [ 'left', 'center', 'right' ], true ) ) {
    $classes[] = 'align-' . $align;
}
?>

<div class="speedsearch-search-container <?php echo esc_attr( implode( ' ', $classes ) ); ?>"
    <?php
    echo sanitize_user_field(
        'speedsearch',
        isset( $html_id ) && $html_id ? ' id="' . esc_attr( $html_id ) . '"' : '',
        0,
        'display'
    );
    ?>
>
    <div class="speedsearch-search-block">
        <div class="speedsearch-search">
        <span class="magnifying-glass">
            <svg height="1em" width="1em" focusable="false" fill="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="false">
            <path d="m23.03 21.97-7.164-7.164A8.969 8.969 0 0 0 18 9a9 9 0 1 0-9 9 8.969 8.969 0 0 0 5.806-2.134l7.164 7.164a.748.748 0 0 0 1.06 0 .75.75 0 0 0 0-1.06zM1.5 9A7.5 7.5 0 0 1 9 1.5 7.509 7.509 0 0 1 16.5 9a7.5 7.5 0 1 1-15 0z"></path>
            </svg>
        </span>
            <form class="speedsearch-search-form" action="<?php echo esc_attr( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" method="get">
                <input
                        name="text"
                        class="speedsearch-search-input"
                        type="search"
                        placeholder="<?php esc_attr_e( 'Search', 'speedsearch' ); ?>"
                        aria-label="Search">
                <span class="speedsearch-search-clear-btn">
                <svg xmlns='http://www.w3.org/2000/svg' height='23px' width='23px' focusable='false' fill='currentColor' viewBox='0 0 24 24'>
                    <path d='M16.2 7.8c-.3-.3-.8-.3-1.1 0L12 10.9 8.8 7.8c-.3-.3-.8-.3-1 0-.3.2-.3.7 0 1L11 12l-3.2 3.2c-.3.3-.3.8 0 1.1.1.1.3.2.5.2s.4-.1.5-.2l3.2-3.2 3.2 3.2c.1.1.3.2.5.2s.4-.1.5-.2c.3-.3.3-.8 0-1.1L13.1 12l3.2-3.2c.2-.3.2-.8-.1-1z'></path>
                    <path d='M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.6 0 12 0zm0 22.5C6.2 22.5 1.5 17.8 1.5 12S6.2 1.5 12 1.5 22.5 6.2 22.5 12 17.8 22.5 12 22.5z'></path>
                </svg>
            </form>
        </span>
        </div>
        <div class="speedsearch-search-results-container">
            <div class="speedsearch-search-results-text-part"></div>
            <div class="speedsearch-search-results-visual-part"></div>
        </div>
    </div>
</div>
