<?php
/**
 * Modifies theme options.
 *
 * @package SpeedSearch
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

/**
 * Disables Flatsome infinite-scroll because it's handled by SpeedSearch.
 */
add_filter(
    'theme_mod_flatsome_infinite_scroll',
    '__return_false'
);
