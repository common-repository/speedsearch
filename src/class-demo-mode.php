<?php
/**
 * Class for Demo Mode.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;

/**
 * Class Demo_Mode.
 */
final class Demo_Mode {

    /**
     * Whether the speedsearch demo mode is active.
     *
     * @return bool
     *
     * @throws Exception Exception.
     */
    public static function is_in_demo_mode() {
        return isset( $_GET['speedsearch-preview'] ) || // @codingStandardsIgnoreLine
            SpeedSearch::$options->get( 'setting-demo-mode-enabled' ) &&
            ! Initial_Setup::is_license_active();
    }

    /**
     * Returns filters limit for demo mode.
     *
     * Fake data.
     *
     * @return array Filters limit.
     */
    public static function get_filters_limits() {
        return [
            'price' => [
                'min' => [
                    'min' => '15',
                ],
                'max' => [
                    'max' => '150',
                ],
            ],
            'date'  => [
                'min' => [
                    'min' => '2020-03-05',
                ],
                'max' => [
                    'max' => '2020-05-20',
                ],
            ],
        ];
    }

    /**
     * Returns posts IDs for demo mode.
     *
     * @return array Of posts IDs.
     *
     * @throws Exception Exception.
     */
    public static function get_posts_ids() {
        return Posts::get_ids( -1 );
    }
}
