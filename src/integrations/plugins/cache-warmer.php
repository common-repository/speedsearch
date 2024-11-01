<?php
/**
 * Cache-warmer plugin integration.
 *
 * @package SpeedSearch
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

add_filter(
    'cache-warmer-options',
    function( $options ) {
        $options['cache-warmer-setting-url-params']['default'] = array_merge(
            $options['cache-warmer-setting-url-params']['default'],
            [ [ 'name' => 'speedsearch-warmer', 'value' => '1' ] ]
        );

        return $options;
    }
);
