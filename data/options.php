<?php
/**
 * Defines plugin options.
 *
 * Format:
 *  'default':  Default option value.           If not set, equals false.
 *  'autoload': Whether to autoload the option. If not set, equals true.
 *
 * @package SpeedSearch
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

if (
    class_exists( 'WooCommerce' ) &&
    defined( 'WC_ABSPATH' )
) { // To declare "wc_get_default_products_per_row()" function.
    include_once WC_ABSPATH . 'includes/wc-template-functions.php';
}

return [

    /*
     * If speedsearch activation was handled.
     */
    'speedsearch-activation-handled'                      => [
        'default' => '1',
    ],

    /*
     * If speedsearch post-activation was handled.
     */
    'speedsearch-post-activation-handled'                 => [
        'default' => '1',
    ],

    /*
     * Array of excluded filters where value is "data-name" attribute value.
     */
    'speedsearch-setting-hidden-filters'                  => [
        'default' => [],
    ],

    /*
     * Display toggles as checkboxes.
     */
    'speedsearch-setting-display-toggles-as-checkboxes'   => [
        'default' => '',
    ],

    /*
     * Array of filters in their order
     */
    'speedsearch-setting-filters-order'                   => [
        'default' => [],
    ],

    /*
     * Prefix before fancy URLs
     */
    'speedsearch-setting-prefix-before-fancy-urls'        => [
        'default' => 'q',
    ],

    /*
     * Last handled version update.
     */
    'speedsearch-last-handled-version-update'             => [],

    /*
     * How many posts per page.
     */
    'speedsearch-setting-posts-per-page'                  => [
        'default' => function_exists( 'wc_get_default_products_per_row' ) ?
            apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() ) :
            get_option( 'posts_per_page' ),
    ],

    /*
     * Single select (option) filters.
     */
    'speedsearch-setting-single-select-filters'           => [
        'default' => [],
    ],

    /*
     * Whether to use multiselect for categories.
     */
    'speedsearch-setting-categories-support-multi-select' => [
        'default' => '',
    ],

    /*
     * Whether the Infinite scroll is enabled (or basic pagination instead).
     */
    'speedsearch-setting-is-infinite-scroll-enabled'      => [
        'default' => '',
    ],

    /*
     * How to treat empty categories and attributes.
     */
    'speedsearch-setting-how-to-treat-empty'              => [
        'default' => 'hide',
    ],

    /*
     * Show only images without text.
     */
    'speedsearch-setting-only-swatches-show-filters'      => [
        'default' => [],
    ],

    /*
     * Filters to hide when no filters selected.
     */
    'speedsearch-setting-filters-to-hide-when-no-filters-selected' => [
        'default' => [],
    ],

    /*
     * Array of active toggles.
     */
    'speedsearch-setting-active-toggles'                  => [
        'default' => [],
    ],

    /*
     * Array of toggles in their order.
     */
    'speedsearch-setting-toggles-order'                   => [
        'default' => [],
    ],

    /*
     * Array of filters with their toggles. Where key is filter name, and value is array of its toggle names.
     */
    'speedsearch-setting-filters-toggles'                 => [
        'default' => [],
    ],

    /*
     * Which posts fields to display (and which text is before them).
     */
    'speedsearch-setting-posts-fields'                    => [
        'default' => [
            '1' => [ 'type' => 'categories' ],
            '2' => [ 'type' => 'title' ],
            '3' => [ 'type' => 'price' ],
        ],
    ],

    /*
     * Whether to display tags.
     */
    'speedsearch-setting-display-tags'                    => [
        'default' => '1',
    ],

    /*
     * Whether to hide unavailable tags.
     */
    'speedsearch-setting-hide-unavailable-tags'           => [
        'default' => '1',
    ],

    /*
     * Whether the tags support multi-select.
     */
    'speedsearch-setting-tags-support-multiselect'        => [
        'default' => '1',
    ],

    /*
     * Autocomplete: Whether to preserve all currently selected filters on autocomplete select.
     */
    'speedsearch-setting-autocomplete-select-preserve-all-filters' => [
        'default' => '',
    ],

    /*
     * Autocomplete: Whether to redirect to attribute archive from non-shop pages.
     */
    'speedsearch-setting-autocomplete-redirect-to-attribute-archive' => [
        'default' => '1',
    ],

    /*
     * Autocomplete: Delete search blocks and instead show singular results with the labels below.
     */
    'speedsearch-setting-autocomplete-delete-search-blocks-and-instead-show-singular-results-with-labels-below' => [
        'default' => '',
    ],

    /*
     * Categories structure. Where `type` key is for type, and `categories-prefix` is categories prefix.
     */
    'speedsearch-setting-categories-structure'            => [
        'default' => [
            'type'              => 'last-with-shop-page',
            'categories-prefix' => array_key_exists( 'category_base', (array) get_option( 'woocommerce_permalinks', [] ) ) ?
                ( (array) get_option( 'woocommerce_permalinks', [] ) )['category_base'] : '/product-category/',
        ],
    ],

    /*
     * Categories order is by their order rather than alphabetically.
     */
    'speedsearch-setting-categories-order-by-their-order' => [
        'default' => '',
    ],

    /*
     * Current theme data, including theme name and all its options.
     */
    'speedsearch-setting-current-theme-data'              => [
        'default' => [ 'name' => 'default' ],
    ],

    /*
     * Attribute filters heading for autocomplete window.
     */
    'speedsearch-setting-attribute-filters-autocomplete-headings' => [
        'default' => [],
    ],

    /*
     * Cache flush interval (in minutes).
     */
    'speedsearch-setting-cache-flush-interval'            => [
        'default' => 60,
    ],

    /*
     * Add autocomplete input field to search inside of searches.
     */
    'speedsearch-setting-add-search-field-inside-of-search-result-for-shop-page' => [
        'default' => 1,
    ],

    /*
     * Autocomplete - open products in the new window.
     */
    'speedsearch-setting-autocomplete-open-products-in-new-window' => [
        'default' => '1',
    ],

    /*
     * Autocomplete - show right panel tabs on the page also.
     */
    'speedsearch-setting-autocomplete-show-tabs-on-page'  => [
        'default' => '',
    ],

    /*
     * Autocomplete - Show blocks (words, categories, tags, attributes) with the fixed order rather than blocks with the more results higher than the blocks with the fewer results.
     */
    'speedsearch-setting-autocomplete-blocks-fixed-order' => [
        'default' => '',
    ],

    /*
     * Autocomplete - Show chosen attributes in autocomplete field.
     */
    'speedsearch-setting-autocomplete-automatic-filtering-based-on-search-terms' => [
        'default' => '',
    ],

    /*
     * Autocomplete - Show the current selected autocomplete option in the search as selected text.
     */
    'speedsearch-setting-autocomplete-show-selected-option-text-in-the-search-as-selected-text' => [
        'default' => '',
    ],

    /*
     * Autocomplete - Automatically select the first result.
     */
    'speedsearch-setting-autocomplete-automatically-preselect-the-first-result' => [
        'default' => '1',
    ],

    /*
     * Attributes URL params prefix.
     */
    'speedsearch-setting-attributes-url-params-prefix'    => [
        'default' => 'pa_',
    ],

    /*
     * Whether the current category can be deselected on click on it.
     */
    'speedsearch-setting-current-category-can-be-deselected-on-click' => [
        'default' => '',
    ],

    /*
     * Last JSON cache flush time.
     */
    'speedsearch-json-cache-last-flush-time'              => [
        'default' => 0,
    ],

    /*
     * Products hashes generation status. `1` for scheduled. `2` for going on. `3` for finished for all products.
     */
    'speedsearch-product-hashes-generation-status'        => [
        'autoload' => false,
        'default'  => 0,
    ],

    /*
     * How many times the hash has been generated since the last hash flush (plugin deactivation or via plugin settings).
     */
    'speedsearch-product-hashes-one-generation-counter'   => [
        'autoload' => false,
        'default'  => 0,
    ],

    /*
     * For many batches the hash has been generated since the last hash flush (plugin deactivation or via plugin settings).
     */
    'speedsearch-product-hashes-batches-counter'          => [
        'autoload' => false,
        'default'  => 0,
    ],

    /*
     * Action Scheduler's last action ID planned/used for hashes generation.
     */
    'speedsearch-product-hashes-generation-last-as-id'    => [
        'autoload' => false,
    ],

    /*
     * ID of the most recent post for which hash has been generated.
     */
    'speedsearch-product-hashes-generation-last-post-id'  => [
        'autoload' => false,
    ],

    /*
     * Posts IDs for the last batch of hash generation, separated by ', '.
     */
    'speedsearch-product-hashes-last-batch-post-ids'      => [
        'autoload' => false,
    ],

    /*
     * The last ID of the post in the batch for which the hash generation was initiated.
     */
    'speedsearch-product-hashes-last-batch-post-id'       => [
        'autoload' => false,
    ],

    /*
     * ID of the post for which rewrites rules were saved - used to update rewrite rules and run `flush_rewrite_rules()` only when `speedsearch-setting-main-page-id` is updated.
     */
    'speedsearch-post-id-last-rewrite-rules-save'         => [],

    /*
     * Categories prefix for which the last rewrite rules save was.
     */
    'speedsearch-categories-prefix-last-rewrite-rules-save' => [
        'default' => '',
    ],

    /*
     * Categories type for white last rewrite rules save was.
     */
    'speedsearch-categories-type-last-rewrite-rules-save' => [],

    /*
     * The list of taxonomies IDs to update the hash of the products within.
     */
    'speedsearch-taxonomies-to-update-products-hash-for'  => [
        'autoload' => false,
        'default'  => [],
    ],

    /*
     * Additional JSON Cache lastCacheFlushFields - for product attributes, attribute-terms, tags, cats.
     */
    'speedsearch-json-cache-last-cache-flush-fields'      => [
        'autoload' => false,
        'default'  => [
            'attributes' => [],
            'tag'        => [],
            'cat'        => [],
            'hashes'     => [],
        ],
    ],

    /*
     * Debug counter.
     */
    'speedsearch-products-html-object-cache-delete-counter' => [
        'autoload' => false,
        'default'  => '',
    ],

    /*
     * Debug counter.
     */
    'speedsearch-products-html-object-cache-delete-all-counter' => [
        'autoload' => false,
        'default'  => 0,
    ],

    /*
     * Debug counter.
     */
    'speedsearch-products-html-object-cache-validations-counter' => [
        'autoload' => false,
        'default'  => 0,
    ],

    /*
     * Debug counter.
     */
    'speedsearch-products-html-object-cache-validations-flush-counter' => [
        'autoload' => false,
        'default'  => 0,
    ],

    /*
     * Webhooks secret.
     */
    'speedsearch-webhooks-secret'                         => [
        'default' => null,
    ],

    /*
     * Array that stores all SpeedSearch webhooks IDs. Keys are webhook topics - 'product.updated', etc.
     */
    'speedsearch-webhooks-ids'                            => [
        'default' => [],
    ],

    /*
     * If the demo mode is enabled.
     */
    'speedsearch-setting-demo-mode-enabled'               => [
        'default' => '',
    ],

    /*
     * When the product image has no alt, use product title as the alt.
     */
    'speedsearch-setting-when-no-image-alt-use-product-title' => [
        'default' => '1',
    ],

    /*
     * Do not wait for the sync to finish.
     */
    'speedsearch-setting-do-not-wait-for-sync-to-finish'  => [
        'default' => '0',
    ],

    /*
     * When the backend is synced.
     */
    'speedsearch-synced'                                  => [
        'default' => '',
    ],

    /*
     * Whether the plugin introduction completed.
     */
    'speedsearch-introduction-completed'                  => [
        'default' => '',
    ],

    /*
     * Time when the settings were changed for the last time.
     */
    'speedsearch-last-settings-update-time'               => [
        'default' => 0,
    ],

    /*
     * When the backend is synced.
     */
    'speedsearch-wc-rest-auth-key'                        => [
        'default' => '',
    ],

    /*
     * Last cache flush time.
     */
    'speedsearch-cache-last-flush-time'                   => [
        'default' => '',
    ],

    /*
     * Array of excluded filters where value is "data-name" attribute value.
     */
    'speedsearch-setting-do-not-use-webhooks'             => [
        'default' => '',
    ],

    /*
     * Whether the admin credentials were sent.
     */
    'speedsearch-were-admin-credentials-sent'             => [
        'default' => '',
    ],

    /*
     * The data of the user that activated the plugin.
     */
    'speedsearch-user-data-that-activated-the-plugin'     => [
        'default' => '',
    ],

    /*
     * User ID of speedsearch plugin.
     */
    'speedsearch-plugin-user-id'                          => [
        'default' => '',
    ],

    /*
     * Whether the store was authorised, ever.
     */
    'speedsearch-store-was-authorised'                    => [
        'default' => '',
    ],

    /*
     * Last plugin error.
     */
    'speedsearch-last-plugin-error'                       => [
        'default' => [],
    ],

    /*
     * Post IDs for which HTML cache was created.
     */
    'speedsearch-post-ids-for-which-html-cache-was-created' => [
        'default' => [],
    ],

    /*
     * Feed generation progress.
     */
    'speedsearch-feed-generation-progress'                => [
        'default' => [],
    ],

    /*
     * Enable theme integration for posts.
     */
    'speedsearch-setting-posts-enable-theme-integration'  => [
        'default' => '1',
    ],

    /*
     * Heading for toggles.
     */
    'speedsearch-setting-toggles-heading'                 => [
        'default' => __( 'Toggles', 'speedsearch' ),
    ],

    /*
     * Whether to enable analytics ageing.
     */
    'speedsearch-setting-enable-analytics-ageing'         => [
        'default' => '',
    ],

    /*
     * Half-life of analytics ageing.
     */
    'speedsearch-setting-analytics-ageing-half-life'      => [
        'default' => 60,
    ],

    /*
     * Analytics data buffer (to send) - views, carts, buys.
     */
    'speedsearch-analytics-data-buffer'                   => [
        'default' => [],
    ],

    /*
     * Sync data feed - last file index.
     */
    'speedsearch-feed-last-file-index'                    => [
        'default'  => null,
        'autoload' => false,
    ],

    /*
     * Sync data feed - last item index.
     */
    'speedsearch-feed-last-item-index'                    => [
        'default'  => null,
        'autoload' => false,
    ],

    /*
     * Settings sharing buffer.
     */
    'speedsearch-settings-sharing-buffer'                 => [
        'default' => [],
    ],

    /*
     * Settings sharing debug data.
     */
    'speedsearch-settings-sharing-debug-data'             => [
        'default' => [],
    ],

    /*
     * Settings: Allow to deselect category on category archive pages.
     */
    'speedsearch-setting-allow-to-deselect-category-on-category-archive-pages' => [
        'default' => '1',
    ],

    /*
     * Settings sharing debug data.
     */
    'speedsearch-setting-archive-pages-hide-tags'         => [
        'default' => '',
    ],

    /*
     * Settings sharing debug data.
     */
    'speedsearch-setting-archive-pages-hide-categories'   => [
        'default' => '',
    ],

    /*
     * Settings sharing debug data.
     */
    'speedsearch-setting-archive-pages-hide-filters'      => [
        'default' => '',
    ],

    /*
     * Setting for fancy URLs (Instead of /shop?tags=123&pa_color=red will be /shop/tags/123/pa_color/red).
     */
    'speedsearch-setting-fancy-urls'                      => [
        'default' => '1',
    ],

    /*
     * Setting for debug mode (if enabled or not).
     */
    'speedsearch-setting-debug-mode'                      => [
        'default' => '',
    ],

    /*
     * The list of selected products for debug mode.
     */
    'speedsearch-setting-debug-mode-products'             => [
        'default' => [],
    ],

    /*
     * Patching - the list of patches files.
     *
     * Where key is a date, and values is an array of the file paths.
     */
    'speedsearch-dynamically-patched-scripts'             => [
        'default' => [],
    ],

    /*
     * Ordering options.
     */
    'speedsearch-setting-ordering-options'                => [
        'default' => [
            'default'       => [
                'text'     => __( 'Standard sorting', 'speedsearch' ),
                'enabled'  => true,
                'standard' => true,
                'default'  => true,
            ],
            'newest'        => [
                'text'     => __( 'Newest', 'speedsearch' ),
                'enabled'  => true,
                'standard' => true,
            ],
            'oldest'        => [
                'text'     => __( 'Oldest', 'speedsearch' ),
                'enabled'  => false,
                'standard' => true,
            ],
            'lowestPrice'   => [
                'text'     => __( 'Lowest price', 'speedsearch' ),
                'enabled'  => true,
                'standard' => true,
            ],
            'highestPrice'  => [
                'text'     => __( 'Highest price', 'speedsearch' ),
                'enabled'  => true,
                'standard' => true,
            ],
            'highestRating' => [
                'text'     => __( 'Highest rating', 'speedsearch' ),
                'enabled'  => true,
                'standard' => true,
            ],
            'mostPopular'   => [
                'text'     => __( 'Most popular', 'speedsearch' ),
                'enabled'  => true,
                'standard' => true,
            ],
        ],
    ],

    /*
     * Last DB successful migration number.
     */
    'speedsearch-last-db-success-migration-number'        => [
        'default' => 0,
    ],

    /*
     * Whether the last db init was successful, for all migrations.
     */
    'speedsearch-last-db-migration-success'               => [
        'default' => true,
    ],

    /*
     * DB migration log.
     */
    'speedsearch-db-migration-log'                        => [
        'autoload' => false,
        'default'  => [],
    ],

    /*
     * When initial feed generation was complete.
     */
    'speedsearch-initial-feed-generation-complete'        => [
        'autoload' => false,
        'default'  => '',
    ],

    /*
     * Index when the initial feed generation was complete.
     */
    'speedsearch-initial-feed-generation-complete-on-index' => [
        'autoload' => false,
        'default'  => '',
    ],
];
