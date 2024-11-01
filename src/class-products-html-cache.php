<?php
/**
 * Object cache for products HTML.
 *
 * Caches a single product to object cache, and flushes the cache on product change
 * or when some products' HTML (during the check on the interval) mismatch the cached values
 * - which means some plugins or theme settings modified the products HTML.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;
use WP_Query;

/**
 * Object cache for products HTML.
 */
final class Products_HTML_Cache {

    /**
     * ID of the last product to retrieve.
     *
     * Also, can be used as an indicator whether the output buffer of this class is active.
     *
     * @var null|int
     */
    public $last_product_id;

    /**
     * Action to do on print end.
     *
     * @var null|string
     */
    public $product_print_end_action;

    /**
     * Action name.
     */
    const VALIDATION_ACTION_NAME = 'speedsearch_products_object_cache_validation';

    /**
     * Interval between actions.
     *
     * 5 minutes.
     */
    const VALIDATIONS_INTERVAL = MINUTE_IN_SECONDS * 5;

    /**
     * How many products to use for the validation.
     *
     * Compares this amount of products with the "wc_get_template_part( 'content', 'product' );" output, and if they are not equal,
     * flushes the HTML object caches for all products, because it means that the all products HTML was modified due to the theme settings
     * or some of the plugins.
     */
    const HOW_MANY_PRODUCTS_USE_FOR_VALIDATIONS = 5;

    /**
     * Init.
     */
    public function __construct() {
        // Saves cache on product retrieval.
        // Uses "wc_get_template_part( 'content', 'product' );" as a starting point to start saving the caches.
        // And uses  "the_post" and "loop_end" as point to save the cache for the product.

        if (
            SpeedSearch::$integrations->is_current_theme_products_integration_present &&
            '1' === SpeedSearch::$options->get( 'setting-posts-enable-theme-integration' )
        ) {
            add_filter( 'wc_get_template_part', [ $this, 'product_print_start' ], 1, 3 ); // Starts at "wc_get_template_part( 'content', 'product' );".
            add_action( 'the_post', [ $this, 'product_print_end' ] ); // For all products except the last one.
            add_action( 'loop_end', [ $this, 'product_print_end' ] ); // For the last product.
        }

        // Cache clean up on a product update.

        add_action( 'save_post_product', [ $this, 'delete' ] );

        // Cache validation intervals.
        // It's used if products' HTML was changed due to the changes in the theme settings,
        // or due to the third-party plugins.

        add_action( self::VALIDATION_ACTION_NAME, [ $this, 'validate_cache' ] );

        $this->init_cache_validation_interval();

        // Unschedule interval on plugin deactivation.
        register_deactivation_hook( SPEEDSEARCH_FILE, [ $this, 'remove_cache_validation_interval' ] );
    }

    /**
     * Product print start.
     *
     * Either save to cache, or print from cache.
     *
     * @hook wc_get_template_part
     *
     * @param string $template Path to the template.
     * @param mixed  $slug     Template slug.
     * @param string $name     Template name (default: '').
     */
    public function product_print_start( $template, $slug, $name ) {
        if (
            ! HTML::$doing_placeholder_posts && // No cache for placeholder posts (because they have mangled thumbnails).
            AJAX::$doing_search &&
            'content' === $slug && 'product' === $name
        ) {
            $this->last_product_id = get_the_ID();
            if ( $this->last_product_id ) {
                $product_html_cache = File_Fallbacked_Cache::get( "product-html-$this->last_product_id", 'speedsearch' );

                if ( false !== $product_html_cache ) {  // Print HTML from the cache.
                    $counter = (int) wp_cache_get( 'how_many_times_product_html_cache_was_used', 'speedsearch' );
                    wp_cache_set( 'how_many_times_product_html_cache_was_used', ++ $counter, 'speedsearch' );
                    echo sanitize_user_field(
                        'speedsearch',
                        $product_html_cache,
                        0,
                        'display'
                    );

                    // To verify that the product is valid.
                    do_action( 'speedsearch_before_product_cache_print', $this->last_product_id );

                    return ''; // No template to use, because we print the HTML from the object cache.
                } else {
                    $this->product_print_end_action = 'flush-ob-and-set-cache';
                }

                ob_start();
            }
        }
        return $template;
    }

    /**
     * Product print end.
     *
     * @hook the_post
     * @hook loop_end
     */
    public function product_print_end() {
        if ( 'flush-ob-and-set-cache' === $this->product_print_end_action ) {
            $html = ob_get_clean();
            self::set( $this->last_product_id, $html );

            echo sanitize_user_field(
                'speedsearch',
                $html,
                0,
                'display'
            );
        }
        $this->product_print_end_action = false;
    }

    /**
     * Saves the product to the Object cache.
     *
     * @param int    $product_id Product ID.
     * @param string $html       HTML to save.
     */
    public static function set( $product_id, $html ) {
        $counter = (int) wp_cache_get( 'products_html_object_cache_set_counter', 'speedsearch' );
        wp_cache_set( 'products_html_object_cache_set_counter', ++ $counter, 'speedsearch' );

        // Save product HTML.

        File_Fallbacked_Cache::set( "product-html-$product_id", $html, 'speedsearch' );

        // Updates meta cache value which stores the IDs of all HTML cached products.

        $post_ids_for_which_html_cache_was_created = SpeedSearch::$options->get( 'post-ids-for-which-html-cache-was-created' );
        if ( false === $post_ids_for_which_html_cache_was_created ) {
            $post_ids_for_which_html_cache_was_created = [];
        }

        if ( ! in_array( $product_id, $post_ids_for_which_html_cache_was_created, true ) ) { // Adds product to array if not found.
            $post_ids_for_which_html_cache_was_created[] = $product_id;

            SpeedSearch::$options->set( 'post-ids-for-which-html-cache-was-created', $post_ids_for_which_html_cache_was_created );
        }
    }

    /**
     * Deletes the product from the Object cache.
     *
     * @param int $product_id Product ID.
     *
     * @throws Exception Exception.
     */
    public static function delete( $product_id ) {
        $counter = (int) SpeedSearch::$options->get( 'products-html-object-cache-delete-counter' );
        SpeedSearch::$options->set( 'products-html-object-cache-delete-counter', ++ $counter );

        // Delete product HTML.

        File_Fallbacked_Cache::delete( "product-html-$product_id", 'speedsearch' );

        // Updates meta cache value which stores the IDs of all HTML cached products.

        $post_ids_for_which_html_cache_was_created = SpeedSearch::$options->get( 'post-ids-for-which-html-cache-was-created' );
        if ( false === $post_ids_for_which_html_cache_was_created ) {
            $post_ids_for_which_html_cache_was_created = [];
        }

        $key = array_search( $product_id, $post_ids_for_which_html_cache_was_created, true );
        if ( false !== $key ) { // Removes product from array if found.
            unset( $post_ids_for_which_html_cache_was_created[ $key ] );

            SpeedSearch::$options->set( 'post-ids-for-which-html-cache-was-created', $post_ids_for_which_html_cache_was_created );
        }
    }

    /**
     * Flush the cache (Delete all data out of it).
     */
    public static function flush() {
        self::delete_all();
        self::reset_all_counters();
    }

    /**
     * Deletes all products cache object HTML.
     *
     * @throws Exception Exception.
     */
    private static function delete_all() {
        $counter = (int) SpeedSearch::$options->get( 'products-html-object-cache-delete-all-counter' );
        SpeedSearch::$options->set( 'products-html-object-cache-delete-all-counter', ++ $counter );

        $post_ids_for_which_html_cache_was_created = SpeedSearch::$options->get( 'post-ids-for-which-html-cache-was-created' );
        if ( $post_ids_for_which_html_cache_was_created ) {
            foreach ( $post_ids_for_which_html_cache_was_created as $product_id ) {
                self::delete( $product_id );
            }
        }
    }

    /**
     * Resets all counters.
     *
     * @throws Exception Exception.
     */
    private static function reset_all_counters() {
        SpeedSearch::$options->delete( 'products-html-object-cache-delete-counter' );
        SpeedSearch::$options->delete( 'products-html-object-cache-delete-all-counter' );
        SpeedSearch::$options->delete( 'products-html-object-cache-validations-counter' );
        SpeedSearch::$options->delete( 'products-html-object-cache-validations-flush-counter' );
        SpeedSearch::$options->delete( 'post-ids-for-which-html-cache-was-created' );
        wp_cache_delete( 'products_html_object_cache_set_counter', 'speedsearch' );
        wp_cache_delete( 'how_many_times_product_html_cache_was_used', 'speedsearch' );
    }

    /**
     * Maybe create cache cleanup interval.
     */
    private function init_cache_validation_interval() {
        $schedule_interval_action = function() {
            as_schedule_recurring_action(
                time() + self::VALIDATIONS_INTERVAL,
                self::VALIDATIONS_INTERVAL,
                self::VALIDATION_ACTION_NAME,
                [],
                'speedsearch',
                true
            );
        };
        if ( did_action( 'action_scheduler_init' ) ) {
            $schedule_interval_action();
        } else {
            add_action( 'action_scheduler_init', $schedule_interval_action );
        }
    }

    /**
     * Removes cache validation interval.
     */
    public function remove_cache_validation_interval() {
        as_unschedule_all_actions( self::VALIDATION_ACTION_NAME, [], 'speedsearch' );
        \SpeedSearch\Misc::stop_running_action_scheduler_action( self::VALIDATION_ACTION_NAME );
    }

    /**
     * Cache validation.
     *
     * Gets 5 random cached posts HTML and compares them with the actual output,
     * and if there is a differences, flushes cache for all products.
     *
     * The difference can be due to the theme or some of the plugins, so if it's a different for one product,
     * then there is a huge chance it's different for many, so we should remove the cache for all products.
     *
     * @throws Exception Exception.
     */
    public function validate_cache() {
        $counter = (int) SpeedSearch::$options->get( 'products-html-object-cache-validations-counter' );
        SpeedSearch::$options->set( 'products-html-object-cache-validations-counter', ++ $counter );

        $post_ids_for_which_html_cache_was_created = SpeedSearch::$options->get( 'post-ids-for-which-html-cache-was-created' );
        if ( $post_ids_for_which_html_cache_was_created ) {
            $count                         = count( $post_ids_for_which_html_cache_was_created );
            $how_many_products_to_retrieve = min( $count, self::HOW_MANY_PRODUCTS_USE_FOR_VALIDATIONS );
            $random_product_keys           = (array) array_rand( $post_ids_for_which_html_cache_was_created, $how_many_products_to_retrieve );
            $product_ids                   = array_map(
                function( $key ) use ( $post_ids_for_which_html_cache_was_created ) {
                    return $post_ids_for_which_html_cache_was_created[ $key ];
                },
                $random_product_keys
            );

            remove_filter( 'wc_get_template_part', [ $this, 'product_print_start' ], 1 );

            Posts_Data_Final_Output::before();

            foreach ( $product_ids as $product_id ) {
                $args    = [
                    'p'         => $product_id,
                    'post_type' => 'product',
                ];
                $product = new WP_Query( $args );

                if ( $product->have_posts() ) {
                    $product->the_post();

                    $cached_product_html = File_Fallbacked_Cache::get( "product-html-$product_id", 'speedsearch' );

                    if ( false !== $cached_product_html ) {
                        ob_start();
                        wc_get_template_part( 'content', 'product' );
                        $current_product_html = ob_get_clean();

                        // If the stored cache is not the same as the actual cache.
                        if ( $current_product_html !== $cached_product_html ) {
                            $counter = (int) SpeedSearch::$options->get( 'speedsearch-products-html-object-cache-validations-flush-counter' );
                            SpeedSearch::$options->set( 'speedsearch-products-html-object-cache-validations-flush-counter', ++ $counter );

                            self::flush();
                            wp_reset_postdata();
                            return;
                        }
                    }
                }
            }
            wp_reset_postdata();

            Posts_Data_Final_Output::after();
        }
    }
}
