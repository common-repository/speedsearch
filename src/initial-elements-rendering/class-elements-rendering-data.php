<?php
/**
 * Class for elements rendering data.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Initial_Elements_Rendering;

use Exception;
use SpeedSearch\Filters;
use SpeedSearch\HTML;
use SpeedSearch\Initial_Setup;
use SpeedSearch\Misc;
use SpeedSearch\Ordering;
use SpeedSearch\Posts;
use SpeedSearch\Themes;
use SpeedSearch\Cache;
use SpeedSearch\SpeedSearch;
use WP_Term;

/**
 * Class Elements Rendering Data.
 */
final class Elements_Rendering_Data {
    /**
     * Tags initial show limit.
     */
    const TAGS_INITIAL_SHOW_LIMIT = 14;

    /**
     * Returns tags.
     *
     * @return array Tags data.
     * @throws Exception Exception.
     */
    public static function get_tags() {
        static $data;
        if ( null !== $data ) { // Cache.
            return $data;
        }

        $data         = [];
        $data['data'] = [];
        $tags         = get_terms(
            [
                'taxonomy'   => 'product_tag',
                'hide_empty' => '1' === SpeedSearch::$options->get( 'setting-hide-unavailable-tags' ) && ! is_product_tag(),
            ]
        );
        foreach ( $tags as $tag ) {
            $data['data'][] = [
                'id'    => $tag->term_id,
                'name'  => $tag->name,
                'count' => $tag->count,
                'slug'  => $tag->slug,
            ];
        }

        return $data;
    }

    /**
     * Returns categories.
     *
     * @return array Categories data.
     * @throws Exception Exception.
     */
    public static function get_categories() {
        static $data;
        if ( null !== $data ) { // Cache.
            return $data;
        }

        $data = [];

        $categories_query = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => SpeedSearch::$options->get( 'setting-how-to-treat-empty' ) === 'hide',
        ];

        $order_categories_by_their_order = '1' === SpeedSearch::$options->get( 'setting-categories-order-by-their-order' );

        if ( $order_categories_by_their_order ) {
            $categories_query['orderby'] = 'term_order';
        }

        $categories = get_categories( $categories_query );
        if ( $categories ) {
            foreach ( $categories as $category ) {
                if ( 0 === $category->category_parent ) {
                    $data[ $category->slug ] = [
                        'id'         => $category->cat_ID,
                        'name'       => $category->name,
                        'isDisabled' => SpeedSearch::$options->get( 'setting-how-to-treat-empty' ) === 'show-disabled' && 0 === $category->count,
                    ];

                    /**
                     * Returns sub-categories for the category. Recursive function to get all the sub categories.
                     *
                     * @param WP_Term $category Category for which to get sub-categories.
                     *
                     * @return array Categories data.
                     * @throws Exception Exception.
                     */
                    $get_sub_categories = function( WP_Term $category ) use ( &$get_sub_categories, $order_categories_by_their_order ) {
                        $data = [];

                        $categories_query = [
                            'taxonomy'   => 'product_cat',
                            'parent'     => $category->term_id,
                            'hide_empty' => SpeedSearch::$options->get( 'setting-how-to-treat-empty' ) === 'hide',
                        ];

                        if ( $order_categories_by_their_order ) {
                            $categories_query['orderby'] = 'term_order';
                        }

                        $sub_categories = get_categories( $categories_query );
                        if ( $sub_categories ) {
                            foreach ( $sub_categories as $sub_category ) {
                                $data[ $sub_category->slug ] = [
                                    'id'         => $sub_category->cat_ID,
                                    'name'       => $sub_category->name,
                                    'isDisabled' => SpeedSearch::$options->get( 'setting-how-to-treat-empty' ) === 'show-disabled' && 0 === $sub_category->count,
                                ];

                                $subs = $get_sub_categories( $sub_category );
                                if ( $subs ) {
                                    $data[ $sub_category->slug ]['children'] = $subs;
                                }
                            }
                            if ( ! $order_categories_by_their_order ) {
                                ksort( $data );
                            }
                        }

                        return $data;
                    };

                    $subs = $get_sub_categories( $category );
                    if ( $subs ) {
                        $data[ $category->slug ]['children'] = $subs;
                    }
                }
                if ( ! $order_categories_by_their_order ) {
                    ksort( $data );
                }
            }
        }

        return $data;
    }

    /**
     * Returns public settings.
     *
     * @return array Public settings.
     * @throws Exception Exception.
     */
    public static function get_public_settings() {
        static $data;
        if ( null !== $data ) { // Cache.
            return $data;
        }

        $shop_page_id  = wc_get_page_id( 'shop' );
        $front_page_id = (int) get_option( 'page_on_front' );

        $data                                 = [];
        $data['autocomplete']                 = [
            'onlySwatchesFilters'                      => SpeedSearch::$options->get( 'setting-only-swatches-show-filters' ),
            'autocompleteHeadings'                     => SpeedSearch::$options->get( 'setting-attribute-filters-autocomplete-headings' ),
            'selectPreserveAllFilters'                 => '1' === SpeedSearch::$options->get( 'setting-autocomplete-select-preserve-all-filters' ),
            'openProductsInNewWindow'                  => '1' === SpeedSearch::$options->get( 'setting-autocomplete-open-products-in-new-window' ),
            'showTabsOnThePage'                        => '1' === SpeedSearch::$options->get( 'setting-autocomplete-show-tabs-on-page' ),
            'automaticFilteringBasedOnSelectedFilters' => '1' === SpeedSearch::$options->get( 'setting-autocomplete-automatic-filtering-based-on-search-terms' ),
            'showSelectedOptionTextInTheSearchAsSelectedText' => '1' === SpeedSearch::$options->get( 'setting-autocomplete-show-selected-option-text-in-the-search-as-selected-text' ),
            'automaticallySelectTheFirstResult'        => '1' === SpeedSearch::$options->get( 'setting-autocomplete-automatically-preselect-the-first-result' ),
            'deleteSearchBlocksAndInsteadShowSingularResultsWithLabelsBelow' => '1' === SpeedSearch::$options->get( 'setting-autocomplete-delete-search-blocks-and-instead-show-singular-results-with-labels-below' ),
        ];
        $data['shopPageUrl']                  = trailingslashit( wp_parse_url( get_permalink( $shop_page_id ), PHP_URL_PATH ) );
        $data['displayTogglesAsCheckboxes']   = '1' === SpeedSearch::$options->get( 'setting-display-toggles-as-checkboxes' );
        $data['shopPageTitle']                = get_the_title( $shop_page_id );
        $data['categoriesSupportMultiSelect'] = '1' === SpeedSearch::$options->get( 'setting-categories-support-multi-select' );
        $data['howToTreatEmpty']              = SpeedSearch::$options->get( 'setting-how-to-treat-empty' );
        $data['hideUnavailableTags']          = '1' === SpeedSearch::$options->get( 'setting-hide-unavailable-tags' );
        $data['pagination']                   = [
            'postsPerPage'   => Posts::get_posts_per_page_number(),
            'infiniteScroll' => [
                'isEnabled'     => '1' === SpeedSearch::$options->get( 'setting-is-infinite-scroll-enabled' ),
                'currentPage'   => 1,
                'postsPerBlock' => Posts::INFINITE_SCROLL_POSTS_BLOCK,
            ],
        ];
        $data['theme']                        = SpeedSearch::$options->get( 'setting-current-theme-data' );
        $data['postsFields']                  = SpeedSearch::$options->get( 'setting-posts-fields' );
        $data['categoriesStructure']          = SpeedSearch::$options->get( 'setting-categories-structure' );
        $data['productElementTag']            = HTML::get_product_element_tag();

        $breadcrumb_args = apply_filters(
            'woocommerce_breadcrumb_defaults',
            [
                'delimiter'   => '&nbsp;&#47;&nbsp;',
                'wrap_before' => '<nav class="woocommerce-breadcrumb">',
                'wrap_after'  => '</nav>',
                'before'      => '',
                'after'       => '',
                'home'        => _x( 'Home', 'breadcrumb', 'speedsearch' ),
            ]
        );

        $data['breadcrumbsSeparator']                  = isset( $breadcrumb_args['delimiter'] ) ? $breadcrumb_args['delimiter'] : '&nbsp;&#47;&nbsp;';
        $data['tagsSupportMultiselect']                = '1' === SpeedSearch::$options->get( 'setting-tags-support-multiselect' );
        $data['attributesURLParamsPrefix']             = SpeedSearch::$options->get( 'setting-attributes-url-params-prefix' );
        $data['currentCategoryCanBeDeselectedOnClick'] = SpeedSearch::$options->get( 'setting-current-category-can-be-deselected-on-click' );
        $data['demoModeEnabled']                       =
            '1' === SpeedSearch::$options->get( 'setting-demo-mode-enabled' ) && ! Initial_Setup::is_license_active();
        $data['autocompleteProductsLimit']             = Posts::AUTOCOMPLETE_PRODUCTS_LIMIT;
        $data['cacheFlushInterval']                    = (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' );

        $data['RECENTLY_VIEWED_PRODUCTS_PRODUCTS_SHOW_LIMIT'] = SPEEDSEARCH_RECENTLY_VIEWED_PRODUCTS_PRODUCTS_SHOW_LIMIT;

        // Is shop catalog page (not categories, tags, attribute term archvie).
        $is_shop_page = 1 === count( $GLOBALS['wp']->query_vars ) &&
                        isset( $GLOBALS['wp']->query_vars['post_type'] ) &&
                        'product' === $GLOBALS['wp']->query_vars['post_type'];

        /**
         * If permalink structure is different from plain and contains '/' at the end, then add categories as URL parts,
         * otherwise add them as URL params.
         */
        $data['categoriesStructure']['addCategoriesAsUrlParts'] =
            Misc::is_not_plaintext_permalink_structure() &&
            $front_page_id !== $shop_page_id; // Otherwise rewrite rules will overwrite all other pages (along with REST API, etc.).

        $data['fancyUrls'] = SpeedSearch::$options->get( 'setting-fancy-urls' ) &&
            Misc::is_not_plaintext_permalink_structure() &&
            // Fancy URLs are only for shop page because for the taxonomy archives they are a bit tricky.
            // Will have to get the store URL and pass it to JS to strip the beginning of the line (instead of speedsearch.settings.shopPageUrl).

            // Is shop page.
            $is_shop_page &&
            'full-without-shop-page' !== SpeedSearch::$options->get( 'setting-categories-structure' )['type'] &&
            $front_page_id !== $shop_page_id; // Otherwise rewrite rules will overwrite all other pages (along with REST API, etc.).

        $data['fancyUrlsPrefix'] = $data['fancyUrls'] ? SpeedSearch::$options->get( 'setting-prefix-before-fancy-urls' ) : '';

        $data['debugMode'] =
            [
                'enabled' => '1' === SpeedSearch::$options->get( 'setting-debug-mode' ) &&
                            is_user_logged_in() && current_user_can( 'edit_theme_options' ) &&
                            $is_shop_page,
            ];

        if ( $data['debugMode']['enabled'] ) {
            $data['debugMode']['nonce'] = wp_create_nonce( 'speedsearch-debug-mode' );
        }

        return $data;
    }

    /**
     * Public translations.
     *
     * @return array Public translations.
     * @throws Exception Exception.
     */
    public static function get_public_translations() {
        static $data;
        if ( null !== $data ) { // Cache.
            return $data;
        }

        $translations = [
            'paginationPrevious'              => __( 'Previous', 'speedsearch' ),
            'paginationNext'                  => __( 'Next', 'speedsearch' ),
            'productsForTheSearch'            => __( 'Products for the search', 'speedsearch' ),
            'productsWithTheWord'             => __( 'Products with the word(s)', 'speedsearch' ),
            'productsWithTheCategory'         => __( 'Products with the category', 'speedsearch' ),
            'productsWithTheTag'              => __( 'Products with the tag', 'speedsearch' ),
            'productsWithTheAttribute'        => __( 'Products with the attribute', 'speedsearch' ),
            'tag'                             => __( 'Tag', 'speedsearch' ),
            'category'                        => __( 'Category', 'speedsearch' ),
            'word-s'                          => __( 'Word(s)', 'speedsearch' ),
            'productsThatMatchWord'           => __( 'Some of the products that match', 'speedsearch' ),
            'productsThatMatchCategory'       => __( 'Some of the products that match category', 'speedsearch' ),
            'productsThatMatchTag'            => __( 'Some of the products that match tag', 'speedsearch' ),
            'productsThatMatchAttribute'      => __( 'Some of the products that match attribute', 'speedsearch' ),
            'nothingMatches'                  => __( 'Nothing matches', 'speedsearch' ),
            'previousSearches'                => __( 'Previous searches', 'speedsearch' ),
            'categoriesThatMatch'             => __( 'Categories that match', 'speedsearch' ),
            'tagsThatMatch'                   => __( 'Tags that match', 'speedsearch' ),
            'forSearch'                       => __( 'For search', 'speedsearch' ),
            'product'                         => __( 'Product', 'speedsearch' ),
            'products'                        => __( 'Products', 'speedsearch' ),
            'categories'                      => __( 'Categories', 'speedsearch' ),
            'showResults'                     => __( 'Show results', 'speedsearch' ),
            'tags'                            => __( 'Tags', 'speedsearch' ),
            'noFound'                         => __( 'No products match your filters.', 'speedsearch' ),
            'noPostsOnTheCurrentPageTryLater' => __( 'No posts found on the current page. Please try to visit this page later.', 'speedsearch' ),
            'maybeWishToSelectPrevFilter'     => __( 'Maybe you wish to [apply only the previous filter]', 'speedsearch' ),
            'servedFromCache'                 => __( '0 seconds', 'speedsearch' ),
            'resetAllFilters'                 => __( 'Reset all filters', 'speedsearch' ),
            'expand'                          => __( 'Expand', 'speedsearch' ),
            'deselect'                        => __( 'Deselect', 'speedsearch' ),
            'loading'                         => __( 'Loading...', 'speedsearch' ),
            'collapse'                        => __( 'Collapse', 'speedsearch' ),
            'toggle'                          => __( 'Toggle', 'speedsearch' ),
            'toggles'                         => SpeedSearch::$options->get( 'setting-toggles-heading' ),
            'orderBy'                         => __( 'Order by', 'speedsearch' ),
            'showingResult'                   => __( 'Showing 1-2 of 10', 'speedsearch' ),
            'showing100kResults'              => __( 'Showing 1-2 of more than 100k', 'speedsearch' ),
            /* translators: %s is (maybe) corrected search text. */
            'showingResultsFor'               => __( 'Showing results for: %s', 'speedsearch' ),
            /* translators: %s is (maybe) corrected search text. */
            'yourSearchDidNotMatchAnyResults' => __( 'Your search for %s did not match any results.', 'speedsearch' ),
            'results'                         => __( 'Results', 'speedsearch' ),
            'result'                          => __( 'Result', 'speedsearch' ),
            'somethingWentWrong'              => __( 'Something went wrong.', 'speedsearch' ),
            'somethingWentWrong2'             => __( 'Try to visit this page later.', 'speedsearch' ),
            'reloadThisPage'                  => __( 'Reload this page', 'speedsearch' ),
            'activeFilters'                   => __( 'Active filters', 'speedsearch' ),
            'match'                           => __( 'Match', 'speedsearch' ),
            'yes'                             => __( 'Yes', 'speedsearch' ),
            'no'                              => __( 'No', 'speedsearch' ),
            'text'                            => __( 'Text', 'speedsearch' ),
        ];

        $current_theme_translations = Themes::get_current_theme_translations();

        $data = array_merge(
            $translations,
            $current_theme_translations
        );

        return $data;
    }

    /**
     * Returns image data.
     *
     * @param int    $attachment_id Attachment ID.
     * @param string $size          Size.
     *
     * @return array|false
     */
    public static function get_image_data( $attachment_id, $size = 'thumbnail' ) {
        if ( ! $attachment_id ) {
            return false;
        }

        $image = wp_get_attachment_image_src( $attachment_id, $size );

        if ( false === $image ) {
            return false;
        }

        $image_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

        return [
            'url' => $image[0],
            'alt' => $image_alt,
        ];
    }

    /**
     * Returns filters.
     *
     * If filters are public, caches them for 1 minute.
     *
     * @return array Filters data.
     * @throws Exception Exception.
     */
    public static function get_filters() {
        if ( did_action( 'woocommerce_after_register_taxonomy' ) ) { // Don't cache incomplete data (without terms).
            static $data;
            if ( null !== $data ) { // Cache.
                return $data;
            }
        }

        if (
            array_key_exists( 'plugin', $_REQUEST ) && 'speedsearch' === $_REQUEST['plugin'] && // @codingStandardsIgnoreLine
            array_key_exists( 'action', $_REQUEST ) && 'get_filters' === $_REQUEST['action'] // @codingStandardsIgnoreLine
        ) {
            if ( function_exists( 'check_ajax_referer' ) ) {
                $is_admin = check_ajax_referer( 'speedsearch-menu', 'nonceToken', false ); // When retrieving with fetch().
            } else {
                $is_admin = false;
            }
        } else {
            $is_admin = is_admin(); // When retrieving with PHP for backend-side print (on shop archive pages).
        }

        if ( did_action( 'woocommerce_after_register_taxonomy' ) ) {
            if ( ! $is_admin ) { // Try to get them from the persistent cache.
                $cache = Cache::get(
                    'SpeedSearch\Elements_Rendering_Data',
                    'get_filters'
                );
                if ( false !== $cache ) {
                    return $cache;
                }
            }
        }

        $data            = [];
        $data['filters'] = [];

        $hidden_filters                           = SpeedSearch::$options->get( 'setting-hidden-filters' );
        $filters_to_hide_when_no_filters_selected =
            SpeedSearch::$options->get( 'setting-filters-to-hide-when-no-filters-selected' );

        // Text.

        $data['text'] = [
            'save'   => __( 'Save', 'speedsearch' ),
            'reset'  => __( 'Reset', 'speedsearch' ),
            'search' => __( 'Search', 'speedsearch' ),
        ];

        if ( $is_admin ) {
            $data['text'] = array_merge(
                $data['text'],
                [
                    'showFilter'                => __( 'Show filter', 'speedsearch' ),
                    'hideFilter'                => __( 'Hide filter', 'speedsearch' ),
                    'singleOptionFilter'        => __( 'Make single-select', 'speedsearch' ),
                    'multipleOptionsFilter'     => __( 'Make multi-select', 'speedsearch' ),
                    'showOnlySwatches'          => __( 'Hide text when swatches are present', 'speedsearch' ),
                    'showSwatchesWithText'      => __( 'Show swatches with text', 'speedsearch' ),
                    'hideWhenNoFiltersSelected' => __( 'Hide when no filters active', 'speedsearch' ),
                    'showWhenNoFiltersSelected' => __( 'Show when no filters active', 'speedsearch' ),
                ]
            );
        }

        // Sort by.

        $name                               = 'sort-by';
        $is_hidden                          = in_array( $name, $hidden_filters, true );
        $is_hidden_when_no_filters_selected = in_array( $name, $filters_to_hide_when_no_filters_selected, true );

        $filter_args = [
            'label'                         => __( 'Sort by', 'speedsearch' ),
            'isHidden'                      => $is_hidden,
            'isHiddenWhenNoFiltersSelected' => $is_hidden_when_no_filters_selected,
            'defaultValue'                  => Ordering::get_default_ordering_name(),
        ];

        foreach ( SpeedSearch::$options->get( 'setting-ordering-options' ) as $ordering_option_slug => $ordering_option ) {
            $filter_args['text'][ $ordering_option_slug ] = $ordering_option['text'];
        }

        $data['filters'][ $name ] = $filter_args;

        // Date.

        $name                               = 'date';
        $is_hidden                          = in_array( $name, $hidden_filters, true );
        $is_hidden_when_no_filters_selected = in_array( $name, $filters_to_hide_when_no_filters_selected, true );

        $data['filters'][ $name ] = [
            'label'                         => __( 'Date', 'speedsearch' ),
            'isHidden'                      => $is_hidden,
            'isHiddenWhenNoFiltersSelected' => $is_hidden_when_no_filters_selected,
        ];

        // Price.

        $name                               = 'price';
        $is_hidden                          = in_array( $name, $hidden_filters, true );
        $is_hidden_when_no_filters_selected = in_array( $name, $filters_to_hide_when_no_filters_selected, true );

        $data['filters'][ $name ] = [
            'label'                         => __( 'Price', 'speedsearch' ),
            'currencySymbol'                => get_woocommerce_currency_symbol(),
            'isHidden'                      => $is_hidden,
            'isHiddenWhenNoFiltersSelected' => $is_hidden_when_no_filters_selected,
        ];

        // Attributes.

        $single_select_filters = SpeedSearch::$options->get( 'setting-single-select-filters' );
        $only_swatches_filters = SpeedSearch::$options->get( 'setting-only-swatches-show-filters' );

        $attributes = wc_get_attribute_taxonomies();
        foreach ( $attributes as $attribute ) {
            $name                               = $attribute->attribute_name; // Filter name.
            $label                              = $attribute->attribute_label;
            $is_hidden                          = in_array( $name, $hidden_filters, true );
            $is_hidden_when_no_filters_selected = in_array( $name, $filters_to_hide_when_no_filters_selected, true );
            $is_single_select                   = in_array( $name, $single_select_filters, true );
            $is_only_swatches_show              = in_array( $name, $only_swatches_filters, true );

            $filter_args = [
                'label'                         => $label,
                'isSingleSelect'                => $is_single_select,
                'isOnlySwatchesShow'            => $is_only_swatches_show,
                'isHidden'                      => $is_hidden,
                'isHiddenWhenNoFiltersSelected' => $is_hidden_when_no_filters_selected,
            ];

            $attribute_terms = get_terms(
                [
                    'taxonomy'   => "pa_$name",
                    'hide_empty' => ! $is_admin && SpeedSearch::$options->get( 'setting-how-to-treat-empty' ) === 'hide',
                ]
            );
            if ( ! is_wp_error( $attribute_terms ) ) {
                foreach ( $attribute_terms as $attribute_term ) {
                    $filter_args['attributes'][ $attribute_term->slug ] = [
                        'name'  => htmlspecialchars_decode( $attribute_term->name ),
                        'id'    => $attribute_term->term_id,
                        'count' => $attribute_term->count,
                    ];

                    $swatch_image = get_term_meta( $attribute_term->term_id, 'speedsearch-swatch-image', true );
                    if ( $swatch_image ) {
                        $filter_args['attributes'][ $attribute_term->slug ]['swatch'] = is_array( $swatch_image ) ?
                            $swatch_image : self::get_image_data( (int) $swatch_image );
                    }
                }
            }

            $data['filters'][ $name ] = $filter_args;
        }

        // Toggles.

        /**
         * Sorts toggles according to their order.
         *
         * @param array $toggles_to_sort Toggles.
         *
         * @return array Toggles sorted according to their order.
         * @throws Exception Exception.
         */
        $sort_toggles_according_to_their_order = function( array $toggles_to_sort ) {
            $toggles_order = SpeedSearch::$options->get( 'setting-toggles-order' );
            $added_toggles = [];

            foreach ( $toggles_order as $ordered_toggle ) { // Adds to the array according to the order.
                if ( array_key_exists( $ordered_toggle, $toggles_to_sort ) ) {
                    $added_toggles[ $ordered_toggle ] = $toggles_to_sort[ $ordered_toggle ];
                    unset( $toggles_to_sort[ $ordered_toggle ] ); // And removes from $toggles_to_sort.
                }
            }

            // Sorts leftovers according to the initial position.

            $all_toggles = Filters::get_all_toggles();
            foreach ( $all_toggles as $t_key => $t_val ) {
                if ( array_key_exists( $t_key, $toggles_to_sort ) ) {
                    $added_toggles[ $t_key ] = $toggles_to_sort[ $t_key ];
                    unset( $toggles_to_sort[ $t_key ] ); // And removes from $toggles_to_sort.
                }
            }

            // The very leftovers that are not in $all_toggles will not be returned, because no one wants outdated toggles.

            return $added_toggles;
        };

        $all_toggles = Filters::get_all_toggles();

        $data['toggles'] = [
            'active' => $sort_toggles_according_to_their_order( SpeedSearch::$options->get( 'setting-active-toggles' ) ),
        ];

        if ( $is_admin ) {
            $data['toggles']['all'] = $sort_toggles_according_to_their_order( $all_toggles );
        }

        // Filter toggles (Adds active ones to the filters).

        $filters_toggles = SpeedSearch::$options->get( 'setting-filters-toggles' );
        foreach ( $filters_toggles as $filter_name => $filter_toggles ) {
            if ( array_key_exists( $filter_name, $data['filters'] ) ) { // If there is a such filter.
                foreach ( $filter_toggles as $filter_toggle ) {
                    if ( array_key_exists( $filter_toggle, $all_toggles ) ) { // If there is a such toggle in $all_toggles (not an outdated toggle).
                        $data['filters'][ $filter_name ]['toggles'][ $filter_toggle ] = $all_toggles[ $filter_toggle ]; // Adds the toggle next with the name.
                    }
                }
            }
        }

        if ( did_action( 'woocommerce_after_register_taxonomy' ) ) {
            if ( ! $is_admin ) { // Save the filters to the persistent cache.
                Cache::save(
                    'SpeedSearch\Elements_Rendering_Data',
                    'get_filters',
                    $data
                );
            }
        }

        return $data;
    }
}
