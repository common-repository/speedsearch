<?php
/**
 * Analytics:
 *
 * - Track and send once per hour
 *
 * 1. Product views (views);
 * 2. Added to cart (cart);
 * 3. Purchases (purchases).
 *
 * In a single array:
 *
 * {
 *   6650: {
 *    'views': 45,
 *    'cart': 3
 *   },
 *   6652: {
 *    'views': 7
 *    'purchases': 10
 *   }
 * }
 *
 * If no data to send for the next hour, then nothing will be sent.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Analytics;

/**
 * A class for analytics init.
 */
final class Init {

    /**
     * Constructor.
     */
    public function __construct() {
        new Collection();
        new Sending();
    }
}
