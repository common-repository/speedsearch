<?php
/**
 * AJAX handlers.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use ActionScheduler_Store;
use Exception;
use SpeedSearch\Demo_Mode as Demo;
use SpeedSearch\Initial_Elements_Rendering\Elements_Rendering_Data;
use SpeedSearch\Initial_Elements_Rendering\Parse_Url_For_Request_Params;
use SpeedSearch\Sync_Data_Feed\Sync_Data_Feed;
use WP_Plugins_Core\Sanitize;

/**
 * Manages AJAX.
 */
final class AJAX {


    /**
     * Whether to add debug data.
     *
     * @var bool
     */
    private $add_debug_data;


    /**
     * Adds the menu and inits assets loading for it.
     */
    public function __construct() {
        $this->add_debug_data = SpeedSearch::$options->get( 'setting-debug-mode' ) &&
            is_user_logged_in() &&
            current_user_can( 'edit_theme_options' );

        $this->add_ajax_events();
    }


    /**
     * Loads AJAX handlers.
     */
    private function add_ajax_events() {
        $admin_ajax_events = [
            'save',
            'reset_filters_order',
            'reset_toggles_order',
            'cache_flush',
            'pause_sync',
            'products_object_cache_flush',
            'import_settings',
            'reset_settings',
            'force_sync',
            'remove_all_products_hash',
            'reset_feed',
            'get_debug_data',
            'update_demo_mode',
            'reset_to_fresh_state',
            'activate_the_license',
            'introduction_completed',
            'get_initial_sync_status',
            'get_analytics_data',
            'get_post_debug_data',
            'get_debug_products',
        ];

        foreach ( $admin_ajax_events as $ajax_event ) {
            add_action( 'wp_ajax_speedsearch_' . $ajax_event, [ $this, $ajax_event ] );
        }

        $get_actions = [
            'search',
            'autocomplete',
            'autocomplete_search',
            'get_date_min',
            'get_date_max',
            'get_price_min',
            'get_price_max',
            'filter_tags',
            'filter_attribute_terms',
            'filter_categories',
            'get_recently_viewed_products_data',
            'get_server_time',
            'get_public_settings',
            'get_tags',
            'get_categories',
            'get_filters',
        ];

        if (
            array_key_exists( 'plugin', $_REQUEST ) && 'speedsearch' === sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) &&
            array_key_exists( 'action', $_REQUEST ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ), $get_actions, true )
        ) {
            call_user_func( [ $this, sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) ] );
        }
    }


    /**
     * Maybe JSON decode, depending on whether the method data is passed from the backend, or client-side.
     *
     * @param bool  $backend_side Whether the method data was passed from the backend, or client-side.
     * @param mixed $data         Data to maybe json_decode.
     *
     * @return array Data.
     */
    public function maybe_json_decode( $backend_side, $data ) {
        return (array) ( $backend_side ? $data : json_decode( $data ) );
    }


    /**
     * Save menu settings.
     *
     * @throws Exception Exception.
     */
    public function save() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        if ( array_key_exists( 'hiddenFilters', $_REQUEST ) ) {
            $hidden_filters = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['hiddenFilters'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-hidden-filters', $hidden_filters );
        }

        if (
            array_key_exists( 'orderingOptions', $_REQUEST ) &&
            array_key_exists( 'defaultOrderingOption', $_REQUEST )
        ) {
            $ordering_options_initial = SpeedSearch::$options->get( 'setting-ordering-options' );

            $ordering_options_data = [];

            $ordering_options        = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['orderingOptions'] ) ), true ) );
            $default_ordering_option = sanitize_text_field( wp_unslash( $_REQUEST['defaultOrderingOption'] ) );

            foreach ( $ordering_options as $ordering_option_slug => $ordering_option ) {
                $ordering_options_data[ $ordering_option_slug ]['text']     = $ordering_option['name'];
                $ordering_options_data[ $ordering_option_slug ]['enabled']  = $ordering_option['enabled'];
                $ordering_options_data[ $ordering_option_slug ]['default']  = $ordering_option_slug === $default_ordering_option;
                $ordering_options_data[ $ordering_option_slug ]['standard'] = isset( $ordering_options_initial[ $ordering_option_slug ]['standard'] ) &&
                                                                            $ordering_options_initial[ $ordering_option_slug ]['standard'];

                if ( isset( $ordering_option['sortBy'] ) ) {
                    $ordering_options_data[ $ordering_option_slug ]['sort_by'] = $ordering_option['sortBy'];
                }

                if ( // Do not save a custom option without sorting params.
                    ! $ordering_options_data[ $ordering_option_slug ]['standard'] &&
                    (
                        ! isset( $ordering_option['sortBy'] ) || ! $ordering_option['sortBy']
                    )
                ) {
                    if ( $ordering_options_data[ $ordering_option_slug ]['default'] ) { // If the option is default, select the first option as the default.
                        $ordering_options_data['default']['default'] = true;
                    }

                    unset( $ordering_options_data[ $ordering_option_slug ] );
                }
            }

            SpeedSearch::$options->set( 'setting-ordering-options', $ordering_options_data );
        }

        if ( array_key_exists( 'singleSelectFilters', $_REQUEST ) ) {
            $single_select_filters = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['singleSelectFilters'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-single-select-filters', $single_select_filters );
        }

        if ( array_key_exists( 'onlyShowSwatchesFilters', $_REQUEST ) ) {
            $only_show_swatches_filters = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['onlyShowSwatchesFilters'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-only-swatches-show-filters', $only_show_swatches_filters );
        }

        if ( array_key_exists( 'filtersToHideWhenNoFiltersSelected', $_REQUEST ) ) {
            $filters_to_hide_when_no_filters_selected = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['filtersToHideWhenNoFiltersSelected'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-filters-to-hide-when-no-filters-selected', $filters_to_hide_when_no_filters_selected );
        }

        if ( array_key_exists( 'archivePagesHideTags', $_REQUEST ) ) {
            $archive_pages_hide_tags = sanitize_text_field( sanitize_text_field( wp_unslash( $_REQUEST['archivePagesHideTags'] ) ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-archive-pages-hide-tags', $archive_pages_hide_tags );
        }

        if ( array_key_exists( 'archivePagesHideFilters', $_REQUEST ) ) {
            $archive_pages_hide_filters = sanitize_text_field( sanitize_text_field( wp_unslash( $_REQUEST['archivePagesHideFilters'] ) ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-archive-pages-hide-filters', $archive_pages_hide_filters );
        }

        if ( array_key_exists( 'archivePagesHideCategories', $_REQUEST ) ) {
            $archive_pages_hide_categories = sanitize_text_field( sanitize_text_field( wp_unslash( $_REQUEST['archivePagesHideCategories'] ) ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-archive-pages-hide-categories', $archive_pages_hide_categories );
        }

        if ( array_key_exists( 'filtersOrder', $_REQUEST ) ) {
            $filters_order = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['filtersOrder'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-filters-order', $filters_order );
        }

        if ( array_key_exists( 'postsPerPage', $_REQUEST ) ) {
            $posts_per_page = (int) sanitize_text_field( wp_unslash( $_REQUEST['postsPerPage'] ) );
            SpeedSearch::$options->set( 'setting-posts-per-page', $posts_per_page );
        }

        if ( array_key_exists( 'categoriesSupportMultiSelect', $_REQUEST ) ) {
            $categories_support_multiselect = sanitize_text_field( wp_unslash( $_REQUEST['categoriesSupportMultiSelect'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-categories-support-multi-select', $categories_support_multiselect );
        }

        if ( array_key_exists( 'isInfiniteScrollEnabled', $_REQUEST ) ) {
            $is_infinite_scroll_enabled = sanitize_text_field( wp_unslash( $_REQUEST['isInfiniteScrollEnabled'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-is-infinite-scroll-enabled', $is_infinite_scroll_enabled );
        }

        if ( array_key_exists( 'displayTogglesAsCheckboxes', $_REQUEST ) ) {
            $is_infinite_scroll_enabled = sanitize_text_field( wp_unslash( $_REQUEST['displayTogglesAsCheckboxes'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-display-toggles-as-checkboxes', $is_infinite_scroll_enabled );
        }

        if ( array_key_exists( 'howToTreatEmpty', $_REQUEST ) ) {
            $how_to_treat_empty_tags_and_categories = sanitize_text_field( wp_unslash( $_REQUEST['howToTreatEmpty'] ) );
            SpeedSearch::$options->set( 'setting-how-to-treat-empty', $how_to_treat_empty_tags_and_categories );
        }

        if ( array_key_exists( 'attributesURLParamsPrefix', $_REQUEST ) ) {
            $attributes_url_params_prefix = sanitize_text_field( wp_unslash( $_REQUEST['attributesURLParamsPrefix'] ) );
            SpeedSearch::$options->set( 'setting-attributes-url-params-prefix', $attributes_url_params_prefix );
        }

        if ( array_key_exists( 'activeToggles', $_REQUEST ) ) {
            $active_toggles = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['activeToggles'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-active-toggles', $active_toggles );
        }

        if ( array_key_exists( 'togglesOrder', $_REQUEST ) ) {
            $toggles_order = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['togglesOrder'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-toggles-order', $toggles_order );
        }

        if ( array_key_exists( 'filtersToggles', $_REQUEST ) ) {
            $filters_toggles = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['filtersToggles'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-filters-toggles', $filters_toggles );
        }

        if ( array_key_exists( 'theme', $_REQUEST ) ) {
            $theme = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['theme'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-current-theme-data', $theme );
        }

        if ( array_key_exists( 'autocompleteAutomaticallyPreselectTheFirstResult', $_REQUEST ) ) {
            $autocomplete_automatically_select_the_first_result =
                sanitize_text_field( wp_unslash( $_REQUEST['autocompleteAutomaticallyPreselectTheFirstResult'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-autocomplete-automatically-preselect-the-first-result', $autocomplete_automatically_select_the_first_result );
        }

        if ( array_key_exists( 'postsFields', $_REQUEST ) ) {
            $posts_fields = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['postsFields'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-posts-fields', $posts_fields );
        }

        if ( array_key_exists( 'categoriesStructure', $_REQUEST ) ) {
            $categories_structure = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['categoriesStructure'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-categories-structure', $categories_structure );
        }

        if ( array_key_exists( 'displayTags', $_REQUEST ) ) {
            $display_tags = sanitize_text_field( wp_unslash( $_REQUEST['displayTags'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-display-tags', $display_tags );
        }

        if ( array_key_exists( 'hideUnavailableTags', $_REQUEST ) ) {
            $display_tags = sanitize_text_field( wp_unslash( $_REQUEST['hideUnavailableTags'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-hide-unavailable-tags', $display_tags );
        }

        if ( array_key_exists( 'tagsSupportMultiselect', $_REQUEST ) ) {
            $tags_support_multiselect = sanitize_text_field( wp_unslash( $_REQUEST['tagsSupportMultiselect'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-tags-support-multiselect', $tags_support_multiselect );
        }

        if ( array_key_exists( 'doNotUseWebhooks', $_REQUEST ) ) {
            $do_not_use_webhooks = sanitize_text_field( wp_unslash( $_REQUEST['doNotUseWebhooks'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-do-not-use-webhooks', $do_not_use_webhooks );
        }

        if ( array_key_exists( 'autocompletePreserveAllFilters', $_REQUEST ) ) {
            $autocomplete_preserve_all_filters = sanitize_text_field( wp_unslash( $_REQUEST['autocompletePreserveAllFilters'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-autocomplete-select-preserve-all-filters', $autocomplete_preserve_all_filters );
        }

        if ( array_key_exists( 'allowToDeselectCategoryOnCategoryArchivePages', $_REQUEST ) ) {
            $allow_to_deselect_category_on_category_archive_pages = sanitize_text_field( wp_unslash( $_REQUEST['allowToDeselectCategoryOnCategoryArchivePages'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-allow-to-deselect-category-on-category-archive-pages', $allow_to_deselect_category_on_category_archive_pages );
        }

        if ( array_key_exists( 'autocompleteRedirectToAttributeArchive', $_REQUEST ) ) {
            $autocomplete_redirect_to_attribute_archive = sanitize_text_field( wp_unslash( $_REQUEST['autocompleteRedirectToAttributeArchive'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-autocomplete-redirect-to-attribute-archive', $autocomplete_redirect_to_attribute_archive );
        }

        if ( array_key_exists( 'autocompleteBlocksFixedOrder', $_REQUEST ) ) {
            $autocomplete_blocks_fixed_order = sanitize_text_field( wp_unslash( $_REQUEST['autocompleteBlocksFixedOrder'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-autocomplete-blocks-fixed-order', $autocomplete_blocks_fixed_order );
        }

        if ( array_key_exists( 'autocompleteShowTabsOnPage', $_REQUEST ) ) {
            $autocomplete_show_tabs_on_page = sanitize_text_field( wp_unslash( $_REQUEST['autocompleteShowTabsOnPage'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-autocomplete-show-tabs-on-page', $autocomplete_show_tabs_on_page );
        }

        if ( array_key_exists( 'autocompleteOpenProductsInNewWindow', $_REQUEST ) ) {
            $autocomplete_open_products_in_new_window = sanitize_text_field( wp_unslash( $_REQUEST['autocompleteOpenProductsInNewWindow'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-autocomplete-open-products-in-new-window', $autocomplete_open_products_in_new_window );
        }

        if ( array_key_exists( 'autocompleteHeadings', $_REQUEST ) ) {
            $autocomplete_headings = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['autocompleteHeadings'] ) ), true ) );
            SpeedSearch::$options->set( 'setting-attribute-filters-autocomplete-headings', $autocomplete_headings );
        }

        if ( array_key_exists( 'togglesHeading', $_REQUEST ) ) {
            $toggles_heading = sanitize_text_field( wp_unslash( $_REQUEST['togglesHeading'] ) );
            SpeedSearch::$options->set( 'setting-toggles-heading', $toggles_heading );
        }

        if ( array_key_exists( 'automaticFilteringBasedOnSelectedFilters', $_REQUEST ) ) {
            $show_chosen_attributes_in_autocomplete_field = sanitize_text_field( sanitize_text_field( wp_unslash( $_REQUEST['automaticFilteringBasedOnSelectedFilters'] ) ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-autocomplete-automatic-filtering-based-on-search-terms', $show_chosen_attributes_in_autocomplete_field );
        }

        if ( array_key_exists( 'analyticsAgeing', $_REQUEST ) ) {
            $analytics_ageing_enabled = sanitize_text_field( wp_unslash( $_REQUEST['analyticsAgeing'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-enable-analytics-ageing', $analytics_ageing_enabled );
        }

        if ( array_key_exists( 'analyticsAgeingHalfLife', $_REQUEST ) ) {
            $analytics_ageing_half_life = max( 1, (int) sanitize_text_field( wp_unslash( $_REQUEST['analyticsAgeingHalfLife'] ) ) );
            SpeedSearch::$options->set( 'setting-analytics-ageing-half-life', $analytics_ageing_half_life );
        }

        if ( array_key_exists( 'categoriesOrderByTheirOrder', $_REQUEST ) ) {
            $categories_order_by_their_order = sanitize_text_field( wp_unslash( $_REQUEST['categoriesOrderByTheirOrder'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-categories-order-by-their-order', $categories_order_by_their_order );
        }

        if ( array_key_exists( 'currentCategoryCanBeDeselectedOnClick', $_REQUEST ) ) {
            $current_category_can_be_deselected_on_click = sanitize_text_field( wp_unslash( $_REQUEST['currentCategoryCanBeDeselectedOnClick'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-current-category-can-be-deselected-on-click', $current_category_can_be_deselected_on_click );
        }

        if ( array_key_exists( 'whenNoImageAltUseProductTitle', $_REQUEST ) ) {
            $when_no_image_alt_use_product_title = sanitize_text_field( wp_unslash( $_REQUEST['whenNoImageAltUseProductTitle'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-when-no-image-alt-use-product-title', $when_no_image_alt_use_product_title );
        }

        if ( array_key_exists( 'doNotWaitForSyncToFinish', $_REQUEST ) ) {
            $do_not_wait_for_sync_to_finish = sanitize_text_field( wp_unslash( $_REQUEST['doNotWaitForSyncToFinish'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-do-not-wait-for-sync-to-finish', $do_not_wait_for_sync_to_finish );
        }

        if ( array_key_exists( 'autocompleteDeleteSearchBlocksAndInsteadShowSingularResultsWithLabelsBelow', $_REQUEST ) ) {
            $autocomplete_delete_search_blocks_and_instead_show_singular_results_with_labels_below = sanitize_text_field( wp_unslash( $_REQUEST['autocompleteDeleteSearchBlocksAndInsteadShowSingularResultsWithLabelsBelow'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-autocomplete-delete-search-blocks-and-instead-show-singular-results-with-labels-below', $autocomplete_delete_search_blocks_and_instead_show_singular_results_with_labels_below );
        }

        if ( array_key_exists( 'postsEnableThemeIntegration', $_REQUEST ) ) {
            $enable_theme_integration = sanitize_text_field( wp_unslash( $_REQUEST['postsEnableThemeIntegration'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-posts-enable-theme-integration', $enable_theme_integration );
        }

        if ( array_key_exists( 'fancyUrls', $_REQUEST ) ) {
            $fancy_urls = sanitize_text_field( wp_unslash( $_REQUEST['fancyUrls'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-fancy-urls', $fancy_urls );
        }

        if ( array_key_exists( 'deubgMode', $_REQUEST ) ) {
            $debug_mode = sanitize_text_field( wp_unslash( $_REQUEST['deubgMode'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set( 'setting-debug-mode', $debug_mode );
        }

        if ( array_key_exists( 'prefixBeforeFancyUrls', $_REQUEST ) ) {
            $prefix_before_fancy_urls = sanitize_text_field( wp_unslash( $_REQUEST['prefixBeforeFancyUrls'] ) );
            if ( $prefix_before_fancy_urls ) {
                SpeedSearch::$options->set( 'setting-prefix-before-fancy-urls', $prefix_before_fancy_urls );
            } else {
                SpeedSearch::$options->delete( 'setting-prefix-before-fancy-urls' );
            }
        }

        if ( array_key_exists( 'debugModeProducts', $_REQUEST ) ) {
            $debug_mode_products = array_map( 'intval', array_filter( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['debugModeProducts'] ) ), true ) ) );

            $debug_mode_products_list = array_map( 'intval', SpeedSearch::$options->get( 'setting-debug-mode-products' ) );

            if ( $debug_mode_products_list !== $debug_mode_products ) { // Debug mode products were modified.
                $response = Backend_Requests::set_debug_products( $debug_mode_products );

                $response_code = wp_remote_retrieve_response_code( $response );
                if (
                    ! is_wp_error( $response ) &&
                    $response_code >= 200 &&
                    $response_code < 300
                ) {
                    SpeedSearch::$options->set( 'setting-debug-mode-products', $debug_mode_products );
                } else {
                    SpeedSearch::$options->delete( 'setting-debug-mode-products' );
                }
            }
        }

        if ( array_key_exists( 'autocompleteShowSelectedOptionTextInTheSearchAsSelectedText', $_REQUEST ) ) {
            $autocomplete_show_selected_option_text_in_the_search_as_selected_text
                = sanitize_text_field( wp_unslash( $_REQUEST['autocompleteShowSelectedOptionTextInTheSearchAsSelectedText'] ) ) === 'on' ? '1' : '';
            SpeedSearch::$options->set(
                'speedsearch-setting-autocomplete-show-selected-option-text-in-the-search-as-selected-text',
                $autocomplete_show_selected_option_text_in_the_search_as_selected_text
            );
        }

        if ( array_key_exists( 'autocompleteSettingAddSearchFieldInsideOfSearchResultForShopPage', $_REQUEST ) ) {
            $autocomplete_add_search_field_inside_of_search_result_for_shop_page
                = sanitize_text_field( wp_unslash( $_REQUEST['autocompleteSettingAddSearchFieldInsideOfSearchResultForShopPage'] ) ) === 'on' ? '1' : '';

            SpeedSearch::$options->set(
                'speedsearch-setting-add-search-field-inside-of-search-result-for-shop-page',
                $autocomplete_add_search_field_inside_of_search_result_for_shop_page
            );
        }

        if ( array_key_exists( 'cacheFlushInterval', $_REQUEST ) ) {
            $json_cache_flush_interval = (int) sanitize_text_field( wp_unslash( $_REQUEST['cacheFlushInterval'] ) );
            if ( $json_cache_flush_interval < 1 ) {
                SpeedSearch::$options->set( 'setting-cache-flush-interval', 1 );
            } else {
                SpeedSearch::$options->set( 'setting-cache-flush-interval', $json_cache_flush_interval );
            }
        }

        if ( array_key_exists( 'newImageSwatches', $_REQUEST ) ) {
            $new_image_swatches = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['newImageSwatches'] ) ), true ) );
            foreach ( $new_image_swatches as $name => $fields ) {
                foreach ( $fields as $field_name => $val ) {
                    $term_ids = get_terms(
                        [
                            'taxonomy' => "pa_$name",
                            'slug'     => $field_name,
                            'fields'   => 'ids',
                        ]
                    );
                    if ( $term_ids && ! is_wp_error( $term_ids ) ) {
                        if ( 'del' === $val ) { // Remove.
                            delete_term_meta( $term_ids[0], 'speedsearch-swatch-image' );
                        } else {
                            $swatch_image = get_term_meta( $term_ids[0], 'speedsearch-swatch-image', true );

                            if (
                                is_array( $swatch_image ) &&
                                ( array_key_exists( '1', $swatch_image ) || array_key_exists( '2', $swatch_image ) ) && // Replacing swatch image with swatch image.
                                ( array_key_exists( '1', $val ) || array_key_exists( '2', $val ) )
                            ) {

                                // Adds colors from the original image.

                                if ( array_key_exists( '1', $swatch_image ) && ! array_key_exists( '1', $val ) ) {
                                    $val['1'] = $swatch_image['1'];
                                }

                                if ( array_key_exists( '2', $swatch_image ) && ! array_key_exists( '2', $val ) ) {
                                    $val['2'] = $swatch_image['2'];
                                }

                                // Deletion for single colors.

                                if ( array_key_exists( '1', $val ) && 'del' === $val['1'] ) {
                                    unset( $val['1'] );
                                }

                                if ( array_key_exists( '2', $val ) && 'del' === $val['2'] ) {
                                    unset( $val['2'] );
                                }
                            }

                            update_term_meta( $term_ids[0], 'speedsearch-swatch-image', $val );
                        }
                    }
                }
            }
        }

        // Flushes the cache.
        SpeedSearch::$json_ajax_cache->flush();

        // Update last settings update time.
        SpeedSearch::$options->set( 'last-settings-update-time', time() );

        wp_send_json_success();
    }


    /**
     * Updates demo mode setting.
     *
     * @throws Exception Exception.
     */
    public function update_demo_mode() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        if ( array_key_exists( 'demoModeEnabled', $_REQUEST ) ) {
            $demo_mode_enabled = sanitize_text_field( wp_unslash( $_REQUEST['demoModeEnabled'] ) ) === '1';
            SpeedSearch::$options->set( 'setting-demo-mode-enabled', $demo_mode_enabled );
        }

        // Flushes the cache.
        SpeedSearch::$json_ajax_cache->flush();

        wp_send_json_success();
    }


    /**
     * Updates demo mode setting.
     *
     * @throws Exception Exception.
     */
    public function activate_the_license() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );


        wp_send_json_success();
    }


    /**
     * Plugin introduction completed.
     */
    public function introduction_completed() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        SpeedSearch::$options->set( 'introduction-completed', true );

        wp_send_json_success();
    }


    /**
     * Get post debug data.
     */
    public function get_post_debug_data() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        if ( ! isset( $_REQUEST['postId'] ) ) {
            wp_send_json_error( 'No postId specified.' );

        }

        WC()->frontend_includes(); // Add WC hooks, to have an approximate HTML parity with the original SpeedSearch HTML.

        $post_id = sanitize_text_field( wp_unslash( $_REQUEST['postId'] ) );

        $data = [];

        if ( SpeedSearch::$options->get( 'setting-posts-enable-theme-integration' ) ) {
            $data = Posts_Data_Final_Output::try_to_inject_html( $data, [ $post_id ] );
        }

        if ( ! array_key_exists( 'html', $data ) ) { // If posts HTML wasn't added by the integration, then returns raw data.
            $data['posts'] = Posts_Data_Final_Output::get_posts_data( [ $post_id ] );
        }

        wp_send_json_success( $data );
    }


    /**
     * Return initial sync status data.
     */
    public function get_initial_sync_status() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        $initial_sync_progress = Initial_Setup::get_initial_sync_generation_progress();

        wp_send_json_success(
            [
                'progress'        => $initial_sync_progress,
                'progressBarHTML' => 100 !== $initial_sync_progress ? Admin_Menu::get_initial_sync_data_progress_bar_block( $initial_sync_progress ) : '',
            ]
        );
    }


    /**
     * Get analytics data.
     */
    public function get_analytics_data() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        $response = Backend_Requests::get_analytics_data();

        ob_start();

        if (
            ! is_wp_error( $response ) &&
            200 === $response['response']['code']
        ) {
            $data = (array) json_decode( wp_remote_retrieve_body( $response ), true );
            ?>
                <div class="speedsearch-row mb-40">
                    <div class="speedsearch-analytics-block" data-type="most_searched_words">
                        <h4 class="search-analytics-block-heading">
                            <?php esc_html_e( 'Most searched words', 'speedsearch' ); ?>
                        </h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>
                                    </th>
                                    <th>
                                        <?php esc_html_e( 'Word', 'speedsearch' ); ?>
                                    </th>
                                    <th>
                                        <?php esc_html_e( 'Searches', 'speedsearch' ); ?>
                                    </th>
                                    <th>
                                        <?php esc_html_e( '% Searches', 'speedsearch' ); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            Analytics\Rendering::print_tbody_trows_standard( $data['most_searched_words'] );
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="speedsearch-analytics-block" data-type="most_searched_sentences">
                        <h4 class="search-analytics-block-heading">
                            <?php esc_html_e( 'Most searched sentences', 'speedsearch' ); ?>
                        </h4>
                        <table>
                            <thead>
                            <tr>
                                <th>
                                </th>
                                <th>
                                    <?php esc_html_e( 'Word', 'speedsearch' ); ?>
                                </th>
                                <th>
                                    <?php esc_html_e( 'Searches', 'speedsearch' ); ?>
                                </th>
                                <th>
                                    <?php esc_html_e( '% Searches', 'speedsearch' ); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            Analytics\Rendering::print_tbody_trows_standard( $data['most_searched_sentences'] );
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="speedsearch-row">
                    <div class="speedsearch-analytics-block" data-type="most_searched_words_without_result">
                        <h4 class="search-analytics-block-heading">
                            <?php esc_html_e( 'Search words without results', 'speedsearch' ); ?>
                        </h4>
                        <table>
                            <thead>
                            <tr>
                                <th>
                                </th>
                                <th>
                                    <?php esc_html_e( 'Word', 'speedsearch' ); ?>
                                </th>
                                <th>
                                    <?php esc_html_e( 'Searches', 'speedsearch' ); ?>
                                </th>
                                <th>
                                    <?php esc_html_e( '% Searches', 'speedsearch' ); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            Analytics\Rendering::print_tbody_trows_standard( $data['most_searched_words_without_result'] );
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="speedsearch-analytics-block" data-type="most_popular_results">
                        <h4 class="search-analytics-block-heading">
                            <?php esc_html_e( 'Most viewed products', 'speedsearch' ); ?>
                        </h4>
                        <table>
                            <thead>
                            <tr>
                                <th>
                                </th>
                                <th>
                                    <?php esc_html_e( 'Product', 'speedsearch' ); ?>
                                </th>
                                <th>
                                    <?php esc_html_e( 'Views', 'speedsearch' ); ?>
                                </th>
                                <th>
                                    <?php esc_html_e( '% Views', 'speedsearch' ); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            Analytics\Rendering::print_tbody_trows_with_post_id( $data['most_popular_results'] );
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php

            wp_send_json_success(
                [
                    'html' => ob_get_clean(),
                ]
            );
        } else {
            ?>
                <div><?php echo esc_html__( "Couldn't fetch the data, something went wrong. Please send this code the plugin developer:", 'speedsearch' ); ?></div>
                <pre class="speedsearch-debug-block"><?php echo wp_json_encode( $response ); ?></pre>
            <?php

            wp_send_json_error(
                [
                    'html' => ob_get_clean(),
                ]
            );
        }
    }


    /**
     * Resets the plugin to the fresh state.
     */
    public function reset_to_fresh_state() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        SpeedSearch::delete_on_deactivation_data();

        // Delete all swatches.
        Swatches::delete_all_attribute_swatches();

        // Removes transients.

        delete_transient( 'speedsearch_store_authorized' );

        // Delete all options.

        foreach ( SpeedSearch::$options->all_options as $option_name => $option_data ) {
            SpeedSearch::$options->delete( $option_name );
        }

        delete_option( 'speedsearch-updating' );

        // Set them to defaults to trigger all event listeners.
        foreach ( SpeedSearch::$options->all_options as $option_name => $option_data ) {
            if ( array_key_exists( 'default', $option_data ) ) {
                SpeedSearch::$options->set( $option_name, $option_data['default'] );
            }
        }


        wp_send_json_success();
    }


    /**
     * Resets filters order.
     *
     * @throws Exception Exception.
     */
    public function reset_filters_order() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        SpeedSearch::$options->delete( 'setting-filters-order' );

        // Flushes the cache.
        SpeedSearch::$json_ajax_cache->flush();

        wp_send_json_success();
    }


    /**
     * Resets toggles order.
     *
     * @throws Exception Exception.
     */
    public function reset_toggles_order() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        SpeedSearch::$options->delete( 'setting-toggles-order' );

        // Flushes the cache.
        SpeedSearch::$json_ajax_cache->flush();

        wp_send_json_success();
    }


    /**
     * Flush caches.
     *
     * - JSON Cache.
     * - General cache (currently affects IDB only).
     */
    public function cache_flush() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        // Flush JSON (AJAX) cache (Sets JSON Cache flush time to the current time).

        SpeedSearch::$json_ajax_cache->flush();

        // General cache flush time (currently affects IDB only).

        SpeedSearch::$options->set( 'cache-last-flush-time', round( microtime( true ) * 1000 ) );

        wp_send_json_success();
    }


    /**
     * Pause sync.
     */
    public function pause_sync() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        if ( array_key_exists( 'pauseFor', $_REQUEST ) ) {
            $pause_for = (int) sanitize_text_field( wp_unslash( $_REQUEST['pauseFor'] ) );
            if ( $pause_for ) {
                $response = Backend_Requests::pause_sync( $pause_for );

                if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
                    wp_send_json_success();
                }
            }
        }

        wp_send_json_error();
    }


    /**
     * Products object cache HTML flush
     *
     * @throws Exception Exception.
     */
    public function products_object_cache_flush() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        Products_HTML_Cache::flush();

        wp_send_json_success();
    }


    /**
     * Imports settings from the file.
     *
     * Accepts: $_REQUEST['file']
     *          $_REQUEST['type']
     *
     * @throws Exception Exception.
     */
    public function import_settings() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        if (
            array_key_exists( 'file', $_REQUEST ) &&
            array_key_exists( 'type', $_REQUEST )
        ) {
            Settings_Import::import( sanitize_text_field( wp_unslash( $_REQUEST['file'] ) ), sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) );

            // Flushes the cache.
            SpeedSearch::$json_ajax_cache->flush();

            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }


    /**
     * Resets all plugin settings.
     *
     * @throws Exception Exception.
     */
    public function reset_settings() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        // Delete all settings options.
        foreach ( SpeedSearch::$options->settings_options as $option ) {
            SpeedSearch::$options->delete( $option );
        }

        // Set them to defaults to trigger all event listeners.
        foreach ( SpeedSearch::$options->settings_options as $option ) {
            $option_data = SpeedSearch::$options->all_options[ $option ];
            if ( array_key_exists( 'default', $option_data ) ) {
                SpeedSearch::$options->set( $option, $option_data['default'] );
            }
        }

        // Delete all swatches.
        Swatches::delete_all_attribute_swatches();

        // Flushes the cache.
        SpeedSearch::$json_ajax_cache->flush();

        wp_send_json_success();
    }


    /**
     * Force sync.
     */
    public static function force_sync() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        $sync_response = Backend_Requests::force_sync();
        wp_send_json( $sync_response );
    }


    /**
     * Removes all products hash.
     */
    public static function remove_all_products_hash() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        Products_Hash\Base::remove_all_products_hash();

        wp_send_json_success();
    }


    /**
     * Resets all feed data.
     */
    public static function reset_feed() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        Sync_Data_Feed::reset_feed();

        wp_send_json_success();
    }


    /**
     * Returns debug data.
     *
     * @throws Exception Exception.
     */
    public static function get_debug_data() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-menu', 'nonceToken' );

        $hash_block_size             = esc_html( Products_Hash\Init_Generation::BATCH_SIZE );
        $action_scheduler_timeout    = esc_html( Products_Hash\Base::get_new_action_scheduler_timeout() );
        $hashes_generation_status    = (int) SpeedSearch::$options->get( 'product-hashes-generation-status' );
        $hashes_generation_counter   = (int) SpeedSearch::$options->get( 'product-hashes-one-generation-counter' );
        $hashes_batches_counter      = (int) SpeedSearch::$options->get( 'product-hashes-batches-counter' );
        $last_as_action_id           = esc_html( SpeedSearch::$options->get( 'product-hashes-generation-last-as-id' ) );
        $last_post_id                = esc_html( SpeedSearch::$options->get( 'product-hashes-generation-last-post-id' ) );
        $last_batch_post_id          = esc_html( SpeedSearch::$options->get( 'product-hashes-last-batch-post-id' ) );
        $last_batch_post_ids         = esc_html( SpeedSearch::$options->get( 'product-hashes-last-batch-post-ids' ) );
        $scheduled_actions_ids       = esc_html(
            implode(
                ', ',
                as_get_scheduled_actions(
                    [
                        'per_page' => '-1',
                        'status'   => ActionScheduler_Store::STATUS_PENDING,
                    ],
                    'ids'
                )
            )
        );
        $analytics_data_buffer       = esc_html( wp_json_encode( SpeedSearch::$options->get( 'analytics-data-buffer' ) ) );
        $settings_sharing_buffer     = esc_html( wp_json_encode( SpeedSearch::$options->get( 'settings-sharing-buffer' ) ) );
        $settings_sharing_debug_data = esc_html( wp_json_encode( SpeedSearch::$options->get( 'settings-sharing-debug-data' ) ) );

        $html_object_cache_delete_counter           = (int) SpeedSearch::$options->get( 'products-html-object-cache-delete-counter' );
        $html_object_cache_all_counter              = (int) SpeedSearch::$options->get( 'products-html-object-cache-delete-all-counter' );
        $html_object_cache_validations_counter      = (int) SpeedSearch::$options->get( 'products-html-object-cache-validations-counter' );
        $html_object_cache_flush_counter            = (int) SpeedSearch::$options->get( 'products-html-object-cache-validations-flush-counter' );
        $html_object_cache_set_counter              = (int) esc_html( wp_cache_get( 'products_html_object_cache_set_counter', 'speedsearch' ) );
        $how_many_times_product_html_cache_was_used = (int) esc_html( wp_cache_get( 'how_many_times_product_html_cache_was_used', 'speedsearch' ) );
        $post_ids_for_which_html_cache_was_created  = esc_html( implode( ', ', (array) SpeedSearch::$options->get( 'post-ids-for-which-html-cache-was-created' ) ) );
        $do_fallback_cache_to_files                 = (int) File_Fallbacked_Cache::do_fallback_cache_to_files();

        $feed_generation_progress                  = esc_html( wp_json_encode( SpeedSearch::$options->get( 'feed-generation-progress' ) ) );
        $initial_feed_generation_complete          = esc_html( SpeedSearch::$options->get( 'initial-feed-generation-complete' ) );
        $initial_feed_generation_complete_on_index = esc_html( SpeedSearch::$options->get( 'initial-feed-generation-complete-on-index' ) );
        $feed_last_file_index                      = esc_html( SpeedSearch::$options->get( 'feed-last-file-index' ) );
        $feed_last_item_index                      = esc_html( SpeedSearch::$options->get( 'feed-last-item-index' ) );
        $feed_buffer_rows                          = DB::get_feed_buffer_count();

        $speedsearch_server_env = isset( $_ENV['SPEEDSEARCH_SERVER'] ) ? esc_html( untrailingslashit( sanitize_text_field( $_ENV['SPEEDSEARCH_SERVER'] ) ) ) : '';
        $speedsearch_origin_env = isset( $_ENV['SPEEDSEARCH_ORIGIN'] ) ? esc_html( untrailingslashit( sanitize_text_field( $_ENV['SPEEDSEARCH_ORIGIN'] ) ) ) : '';

        $html = "
            <div class=\"speedsearch-debug-data-container\">
                <h3>Options</h3>
                <pre>analytics-data-buffer       = $analytics_data_buffer</pre>
                <pre>settings-sharing-buffer     = $settings_sharing_buffer</pre>
                <pre>settings-sharing-debug-data = $settings_sharing_debug_data</pre>
                <h3>Feed Generation</h3>
                <pre>feed-generation-progress                  = $feed_generation_progress</pre>
                <pre>initial-feed-generation-complete          = $initial_feed_generation_complete</pre>
                <pre>initial-feed-generation-complete-on-index = $initial_feed_generation_complete_on_index</pre>
                <pre>feed-last-file-index                      = $feed_last_file_index</pre>
                <pre>feed-last-item-index                      = $feed_last_item_index</pre>
                <pre>feed-buffer-rows                          = $feed_buffer_rows</pre>
                <h3>Hashes</h3>
                <pre>BATCH_SIZE = $hash_block_size</pre>
                <pre>Action scheduler timeout (sec per batch) = $action_scheduler_timeout</pre>
                <pre>product-hashes-generation-status         = $hashes_generation_status</pre>
                <pre>product-hashes-one-generation-counter    = $hashes_generation_counter</pre>
                <pre>product-hashes-batches-counter           = $hashes_batches_counter</pre>
                <pre-product-hashes-generation-last-as-id     = $last_as_action_id</pre>
                <pre>product-hashes-generation-last-post-id   = $last_post_id</pre>
                <pre>product-hashes-last-batch-post-id        = $last_batch_post_id</pre>
                <pre>product-hashes-last-batch-post-ids       = $last_batch_post_ids</pre>
                <h3>Products HTML Cache</h3>
                <pre>File_Fallbacked_Cache::do_fallback_cache_to_files() = $do_fallback_cache_to_files</pre>
                <pre>products-html-object-cache-delete-counter      = $html_object_cache_delete_counter</pre>
                <pre>products-html-object-cache-delete-all-counter  = $html_object_cache_all_counter</pre>
                <pre>products-html-object-cache-validations-counter = $html_object_cache_validations_counter</pre>
                <pre>products-html-object-cache-validations-flush-counter = $html_object_cache_flush_counter</pre>
                <pre>post-ids-for-which-html-cache-was-created      = $post_ids_for_which_html_cache_was_created</pre>
                <pre>(from cache) products_html_object_cache_set_counter = $html_object_cache_set_counter</pre>
                <pre>(from cache) how_many_times_product_html_cache_was_used = $how_many_times_product_html_cache_was_used</pre>
                <h3>Action Scheduler</h3>
                <pre>Scheduled Actions IDs: $scheduled_actions_ids</pre>
                <h3>ENVs</h3>
                <pre>SPEEDSEARCH_SERVER: $speedsearch_server_env</pre>
                <pre>SPEEDSEARCH_ORIGIN: $speedsearch_origin_env</pre>
            </div>
";

        wp_send_json( [ 'html' => $html ] );
    }


    /**
     * Whether doing real products search request (and not placeholder posts, demo mode, or something else).
     *
     * @var bool
     */
    public static $doing_search = false;


    /**
     * Search.
     *
     * Accepts: $_REQUEST['text']
     *          $_REQUEST['categories']
     *          $_REQUEST['attributes']
     *          $_REQUEST['page']
     *          etc.
     *
     * @param bool       $to_return                           Whether to return the output, or just wp_send_json().
     * @param bool       $return_empty_string_if_not_in_cache Return empty string if the result is not in cache (so do not make any backend request).
     * @param bool|array $custom_product_ids                  The list of custom product IDs. So not backend request will be done, but simply data for those custom IDs will be returned.
     * @param bool|array $custom_request_params               The list of custom request params.
     * @param bool       $just_get_ids                        Just get product IDs, without HTML.
     *
     * @noinspection PhpUndefinedVariableInspection
     * @throws Exception Exception.
     */
    public function search( $to_return = false, $return_empty_string_if_not_in_cache = false, $custom_product_ids = false, $custom_request_params = false, $just_get_ids = false ) {
        static $data;
        if ( $to_return && null !== $data ) { // Cache.
            return $data;
        }

        $demo_mode = false;

        if ( false !== $custom_request_params ) {
            $request_params = $custom_request_params;
        } else {
            if ( $to_return ) {
                $request_params = Initial_Elements_Rendering\Parse_Url_For_Request_Params::get( true, true );

                if ( false === $request_params ) {
                    if ( $return_empty_string_if_not_in_cache ) {
                        return '';
                    } else {
                        $request_params = [];
                    }
                }
            } else {
                $request_params = Sanitize::sanitize_array( $_REQUEST );
            }
        }

        if ( false === $custom_request_params && ! $to_return && isset( $_REQUEST['currentPageAddress'] ) ) { // Save request params for BE-side URL-based fetching.
            Parse_Url_For_Request_Params::save( $request_params, sanitize_text_field( wp_unslash( $_REQUEST['currentPageAddress'] ) ) );
        }

        $args = $this->get_filter_request_args( $to_return, $request_params );

        $page_num = array_key_exists( 'page', $request_params ) ? (int) wp_unslash( $request_params['page'] ) : 1;

        $in_cache    = false;
        $search_time = null;

        if ( false === $custom_product_ids ) {
            if (
                array_key_exists( 'forDemoMode', $request_params ) &&
                '1' === wp_unslash( $request_params['forDemoMode'] )
            ) {
                $demo_mode = true;

                $response    = Demo::get_posts_ids();
                $product_ids = Posts::paginate( $response, $page_num );
            } else {
                $posts_per_page = isset( $args['postsPerPage'] ) ? $args['postsPerPage'] : Posts::get_posts_per_page_number();

                $args['offset'] = ( $page_num - 1 ) * $posts_per_page;
                $args['limit']  = $posts_per_page;

                $in_cache = '' !== Backend_Requests::get(
                    'search',
                    $args,
                    true
                );

                $response = Backend_Requests::get(
                    'search',
                    $args,
                    $return_empty_string_if_not_in_cache
                );

                if ( $return_empty_string_if_not_in_cache && '' === $response ) {
                    return '';
                }
            }

            if ( array_key_exists( 'error', $response ) ) {
                if ( $to_return ) {
                    return $response;
                } else {
                    wp_send_json( $response );
                }
            } elseif ( ! $demo_mode ) {
                $product_ids = array_keys( $response['products'] );

                if ( ! $in_cache ) {
                    $search_time = $response['searchTime'];
                }

                $less_products_from_be_returned_than_should_be =
                    Posts::how_many_products_should_be_on_the_current_page( $response['totalProducts'], $page_num ) - count( $product_ids );
            }
        } else {
            $product_ids = $custom_product_ids;
        }

        $data = [
            'searched'   => isset( $response ) && array_key_exists( 'searched', $response ) ? $response['searched'] : '',
            'searchTime' => null !== $search_time ?
                /* translators: %s is the number of seconds it took for the search request to complete. */
                sprintf( '%s seconds', number_format( $search_time / 1000, 3, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) ) :
                null,
        ];

        $all_product_ids = $product_ids;

        // Retain only valid posts.

        $product_ids = [];
        foreach ( $all_product_ids as $product_id ) {
            if ( ! get_transient( "speedsearch-invalid-product-id-$product_id" ) ) {
                $product_ids[] = $product_id;
            }
        }

        $initial_invalid_products = count( $all_product_ids ) - count( $product_ids );

        // Post nums.

        if (
            array_key_exists( 'postNums', $request_params ) &&
            '1' !== SpeedSearch::$options->get( 'setting-is-infinite-scroll-enabled' ) // Custom posts positioning doesn't work for infinite scroll.
        ) {
            $post_nums = $this->maybe_json_decode( $to_return, wp_unslash( $request_params['postNums'] ) );

            $product_ids_old = $product_ids;
            $product_ids     = [];

            foreach ( $post_nums as $post_num ) {
                $i = $post_num - 1;

                if ( array_key_exists( $i, $product_ids_old ) ) {
                    $product_ids[] = array_slice( $product_ids_old, $i, 1 )[0];
                }
            }
        }

        if ( $just_get_ids ) {
            return $product_ids;
        }

        $valid_products = [];
        if ( $product_ids ) {

            /**
             * Populate valid products list.
             *
             * @param int|null $post_id ID of the post.
             */
            $populate_valid_products = function( $post_id = null ) use ( &$valid_products ) {
                if ( null === $post_id ) {
                    $post_id = get_the_ID();
                }

                if ( ! in_array( $post_id, $valid_products, true ) ) {
                    $valid_products[] = $post_id;
                }
            };

            /**
             * Populate valid products list.
             *
             * @param string   $permalink The post's permalink.
             * @param \WP_Post $post      The post.
             */
            $populate_valid_products_for_filter = function( $permalink, $post ) use ( &$valid_products ) {
                if ( ! in_array( $post->ID, $valid_products, true ) ) {
                    $valid_products[] = $post->ID;
                }
                return $permalink;
            };

            add_filter( // Post permalink filter is not needed, but just adds a bit more precision.
                'post_type_link',
                $populate_valid_products_for_filter,
                10,
                2
            );
            $product_print_actions = [
                'speedsearch_before_product_print',
                'speedsearch_before_product_cache_print',
                'woocommerce_before_shop_loop_item',
            ];

            foreach ( $product_print_actions as $action ) {
                add_action( $action, $populate_valid_products );
            }

            self::$doing_search = ! $to_return || false === $custom_product_ids; // Not doing search (no HTML cache) when getting placeholder posts.

            // Get the posts' data.

            if ( SpeedSearch::$options->get( 'setting-posts-enable-theme-integration' ) ) {
                $data = Posts_Data_Final_Output::try_to_inject_html( $data, $product_ids );
            }

            if ( ! array_key_exists( 'html', $data ) ) { // If posts HTML wasn't added by the integration, then returns raw data.
                $data['posts'] = Posts_Data_Final_Output::get_posts_data( $product_ids );
            }

            self::$doing_search = false;

            remove_filter(
                'post_type_link',
                $populate_valid_products_for_filter
            );
            foreach ( $product_print_actions as $action ) {
                remove_action( $action, $populate_valid_products );
            }

            $invalid_products = array_diff( $product_ids, $valid_products );

            foreach ( $invalid_products as $invalid_product ) {
                set_transient( "speedsearch-invalid-product-id-$invalid_product", true, MINUTE_IN_SECONDS * 30 );
            }
        }

        if ( isset( $response ) ) {
            $data['pagination'] = [
                'totalPosts' => isset( $request_params['fake_posts_counter'] ) ?
                    (int) $request_params['fake_posts_counter'] :
                    (
                        $demo_mode ?
                            count( $response ) :
                            $response['totalProducts']
                                - ( isset( $less_products_from_be_returned_than_should_be ) ? $less_products_from_be_returned_than_should_be : 0 )
                                - $initial_invalid_products
                                - ( isset( $invalid_products ) ? count( $invalid_products ) : 0 )
                    ),
            ];
        }

        if ( ! $valid_products ) {
            $data['noPosts'] = true;
        }

        if ( $to_return ) {
            return $data;
        } else {
            $data = [
                'data'       => $data,
                'cacheUntil' => time() + (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60,
            ];

            if ( $this->add_debug_data ) {
                $data['debugData'] = [
                    'requestParams'  => Backend_Requests::convert_raw_args_to_request_params( $args ),
                    'requestArgs'    => $args,
                    'response'       => $response,
                    'requestUrl'     => Backend_Requests::$last_request_url,
                    'rawRequestBody' => Backend_Requests::$last_request_args['body'] ?? '',
                ];
            }

            wp_send_json( $data );
        }
    }


    /**
     * Autocomplete search.
     *
     * Accepts: $_REQUEST['text']
     *          $_REQUEST['categories']
     *          $_REQUEST['attributes']
     *          $_REQUEST['page']
     *          etc.
     *
     * @noinspection PhpUndefinedVariableInspection
     *
     * @throws Exception Exception.
     */
    public function autocomplete_search() {
        static $data;
        if ( null !== $data ) { // Cache.
            return $data;
        }

        $request_params = Sanitize::sanitize_array( $_REQUEST );

        $args = [
            'limit' => Posts::AUTOCOMPLETE_PRODUCTS_LIMIT,
        ]; // Request args.

        if ( array_key_exists( 'text', $request_params ) ) {
            $text         = wp_unslash( $request_params['text'] );
            $args['text'] = $text;
        }

        if ( array_key_exists( 'categories', $request_params ) ) {
            $categories_slugs = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $request_params['categories'] ) ), true ) );
            foreach ( $categories_slugs as $category_slug ) {
                $category = get_term_by( 'slug', $category_slug, 'product_cat' );
                if ( $category ) {
                    $args['categories'][] = $category->term_id;
                }
            }
        }

        if ( array_key_exists( 'attributes', $request_params ) ) {
            $attributes = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $request_params['attributes'] ) ), true ) );

            // Adds pa_ to attributes keys.
            $attributes = array_combine(
                array_map(
                    function( $key ) {
                        return 'pa_' . $key;
                    },
                    array_keys( $attributes ),
                    $attributes
                ),
                $attributes
            );

            $args['attributes'] = $attributes;
        }

        if ( array_key_exists( 'tags', $request_params ) ) {
            $tags         = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $request_params['tags'] ) ), true ) );
            $args['tags'] = $tags;
        }

        if ( array_key_exists( 'hash', $request_params ) ) {
            $args['hash'] = wp_unslash( $request_params['hash'] );
        }

        $response = Backend_Requests::get( 'autocomplete_search', $args );

        if ( array_key_exists( 'error', $response ) ) {
            wp_send_json( $response );
        }

        $data = [];

        foreach ( $response['products'] as $product ) {
            $post_data = [
                'title'     => $product['name'],
                'permalink' => $product['permalink'],
            ];

            if (
                array_key_exists( 'images', $product ) &&
                is_array( $product['images'] ) &&
                array_key_exists( 0, $product['images'] )
            ) {
                $post_data['image'] = $product['images'][0];
                unset( $post_data['image']['id'] );

                if (
                    ! $post_data['image']['alt'] &&
                    '1' === SpeedSearch::$options->get( 'setting-when-no-image-alt-use-product-title' )
                ) {
                    $post_data['image']['alt'] = $product['name'];
                }
            }

            $data[] = $post_data;
        }

        $data = [
            'data'       => $data,
            'cacheUntil' => time() + (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60,
        ];

        if ( $this->add_debug_data ) {
            $data['debugData'] = [
                'requestUrl'     => Backend_Requests::$last_request_url,
                'rawRequestBody' => Backend_Requests::$last_request_args['body'] ?? '',
            ];
        }

        wp_send_json( $data );
    }


    /**
     * Autocomplete.
     *
     * Accepts: $_REQUEST['text']
     *
     * @throws Exception Exception.
     */
    public function autocomplete() {
        static $data;
        if ( null !== $data ) { // Cache.
            return $data;
        }

        $args = [
            'limit' => Posts::AUTOCOMPLETE_LINKS_LIMIT_PER_BLOCK,
        ]; // Request args.

        if ( array_key_exists( 'search', $_REQUEST ) ) {
            $text           = sanitize_text_field( wp_unslash( $_REQUEST['search'] ) );
            $args['search'] = $text;
        }

        if ( array_key_exists( 'categories', $_REQUEST ) ) {
            $categories_slugs = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['categories'] ) ), true ) );
            foreach ( $categories_slugs as $category_slug ) {
                $category = get_term_by( 'slug', $category_slug, 'product_cat' );
                if ( $category ) {
                    $args['categories'][] = $category->term_id;
                }
            }
        }

        if ( array_key_exists( 'attributes', $_REQUEST ) ) {
            $attributes = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['attributes'] ) ), true ) );

            // Adds pa_ to attributes keys.
            $attributes = array_combine(
                array_map(
                    function( $key ) {
                        return 'pa_' . $key;
                    },
                    array_keys( $attributes ),
                    $attributes
                ),
                $attributes
            );

            $args['attributes'] = $attributes;
        }

        if ( array_key_exists( 'tags', $_REQUEST ) ) {
            $tags         = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['tags'] ) ), true ) );
            $args['tags'] = $tags;
        }

        if ( array_key_exists( 'hash', $_REQUEST ) ) {
            $args['hash'] = sanitize_text_field( wp_unslash( $_REQUEST['hash'] ) );
        }

        $autocomplete_data = Backend_Requests::get( 'autocomplete', $args );

        $words_with_posts = isset( $autocomplete_data['products'] ) ? $autocomplete_data['products'] : [];
        $categories       = isset( $autocomplete_data['categories'] ) ? $autocomplete_data['categories'] : [];
        $tags             = isset( $autocomplete_data['tags'] ) ? $autocomplete_data['tags'] : [];
        $attributes       = isset( $autocomplete_data['attributes'] ) ? $autocomplete_data['attributes'] : [];

        $data['products']   = [];
        $data['categories'] = [];
        $data['tags']       = [];
        $data['attributes'] = [];

        $how_to_treat_empty = SpeedSearch::$options->get( 'setting-how-to-treat-empty' );
        $fixed_blocks_order = '1' === SpeedSearch::$options->get( 'setting-autocomplete-blocks-fixed-order' );

        $words_count      = 0;
        $categories_count = 0;
        $tags_count       = 0;
        $attributes_count = 0;

        foreach ( $words_with_posts as $word => $word_count ) {
            if ( 'hide' === $how_to_treat_empty && 0 === $word_count ) {
                continue;
            }
            $data['products'][ $word ] = $word_count;

            $words_count += $word_count;
        }

        // Add "parent" to category data only for the duplicate categories.

        $category_counts          = array_count_values( array_column( $categories, 'name' ) );
        $duplicate_category_names = []; // Categories with the same name that have multiple occurrences.

        foreach ( $category_counts as $category_name => $category_count ) {
            if ( $category_count > 1 ) {
                $duplicate_category_names[] = $category_name;
            }
        }

        foreach ( $categories as $category_data ) {
            // Skip empty if setting to hide them is set.
            if ( 'hide' === $how_to_treat_empty && 0 === $category_data['count'] ) {
                continue;
            }

            $cat_data = [
                'name'  => $category_data['name'],
                'slug'  => $category_data['slug'],
                'count' => $category_data['count'],
            ];

            $term_link = get_term_link( $category_data['slug'], 'product_cat' );
            if ( ! is_wp_error( $term_link ) ) {
                $cat_data['archiveLink'] = $term_link;
            }

            if ( in_array( $category_data['name'], $duplicate_category_names, true ) ) {
                /* translators: %s is a parent category name. For example: "Category Shirts in Clothing" */
                $cat_data['parent'] = sprintf( __( 'In %s', 'speedsearch' ), $category_data['parent'] );
            }

            $data['categories'][] = $cat_data;

            $categories_count += $category_data['count'];
        }

        if (
            SpeedSearch::$options->get( 'setting-display-tags' ) && // When tags are hidden, hide them from autocomplete window also.
            count( $tags )
        ) {
            foreach ( $tags as $tag_data ) {
                // Skip empty if setting to hide them is set.
                if ( 'hide' === $how_to_treat_empty && 0 === $tag_data['count'] ) {
                    continue;
                }

                $tag_data = [
                    'name'  => $tag_data['name'],
                    'id'    => $tag_data['id'],
                    'count' => $tag_data['count'],
                ];

                $term_link = get_term_link( (int) $tag_data['id'], 'product_tag' );
                if ( ! is_wp_error( $term_link ) ) {
                    $tag_data['archiveLink'] = $term_link;
                }

                $data['tags'][] = $tag_data;

                $tags_count += $tag_data['count'];
            }
        }

        $hidden_filters = SpeedSearch::$options->get( 'setting-hidden-filters' );
        foreach ( $attributes as $attribute_data ) {
            $attribute_data['attributeSlug'] = substr( $attribute_data['attributeSlug'], 3 );

            // Skip empty if setting to hide them is set.
            if ( 'hide' === $how_to_treat_empty && 0 === $attribute_data['count'] ) {
                continue;
            }

            // Don't return attributes for filters that are hidden.
            if ( in_array( $attribute_data['attributeSlug'], $hidden_filters, true ) ) {
                continue;
            }

            $the_attribute_data = [
                'name'         => $attribute_data['name'],
                'val'          => $attribute_data['slug'],
                'slug'         => $attribute_data['attributeSlug'],
                'taxonomyName' => $attribute_data['attributeName'],
                'count'        => $attribute_data['count'],
            ];

            if ( $attribute_data['hasArchives'] ) {
                $term_link = get_term_link( $attribute_data['slug'], 'pa_' . $attribute_data['attributeSlug'] );
                if ( ! is_wp_error( $term_link ) ) {
                    $the_attribute_data['archiveLink'] = $term_link;
                }
            }

            // Don't return attributes for filters that are hidden.
            $term_meta = $attribute_data['termMeta'];
            if ( $term_meta ) {
                $term_meta = (array) json_decode( $term_meta, true );
                if ( array_key_exists( 'speedsearch-swatch-image', $term_meta ) ) {
                    $the_attribute_data['img'] = unserialize( $term_meta['speedsearch-swatch-image'][0] );
                }
            }

            $data['attributes'][] = $the_attribute_data;

            $attributes_count += $attribute_data['count'];
        }

        if ( $fixed_blocks_order ) {
            $data['order'] = [
                'text',
                'categories',
                'tags',
                'attributes',
            ];
        } else { // Otherwise (if not fixed), show the blocks with more total products higher.
            $count_per_type = [
                'text'       => $words_count,
                'categories' => $categories_count,
                'tags'       => $tags_count,
                'attributes' => $attributes_count,
            ];

            uasort(
                $count_per_type,
                function( $a, $b ) {
                    return $b - $a;
                }
            );

            $data['order'] = array_keys( $count_per_type );
        }

        $data = [
            'data'       => $data,
            'cacheUntil' => time() + (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60,
        ];

        if ( $this->add_debug_data ) {
            $data['debugData'] = [
                'requestUrl'     => Backend_Requests::$last_request_url,
                'rawRequestBody' => Backend_Requests::$last_request_args['body'] ?? '',
            ];
        }

        wp_send_json( $data );
    }


    /**
     * Returns filter request args.
     *
     * @param bool       $to_return      Whether to return the output, or just wp_send_json().
     * @param array|null $request_params Request parameters.
     *
     * @return array
     */
    public function get_filter_request_args( $to_return = false, array $request_params = null ) {
        if ( is_null( $request_params ) ) {
            $request_params = Sanitize::sanitize_array( $_REQUEST );
        }

        $args = []; // Request args.

        if ( array_key_exists( 'text', $request_params ) ) {
            $text         = wp_unslash( $request_params['text'] );
            $args['text'] = $text;
        }

        if ( array_key_exists( 'categories', $request_params ) ) {
            $categories_slugs = $this->maybe_json_decode( $to_return, wp_unslash( $request_params['categories'] ) );
            foreach ( $categories_slugs as $category_slug ) {
                $category = get_term_by( 'slug', $category_slug, 'product_cat' );
                if ( $category ) {
                    $args['categories'][] = $category->term_id;
                }
            }
        }

        if ( array_key_exists( 'attributes', $request_params ) ) {
            $attributes = $this->maybe_json_decode( $to_return, wp_unslash( $request_params['attributes'] ) );

            // Adds pa_ to attributes keys.
            $attributes = array_combine(
                array_map(
                    function( $key ) {
                        return 'pa_' . $key;
                    },
                    array_keys( $attributes ),
                    $attributes
                ),
                $attributes
            );

            $args['attributes'] = $attributes;
        }

        if ( array_key_exists( 'price', $request_params ) ) {
            $price         = $this->maybe_json_decode( $to_return, wp_unslash( $request_params['price'] ) );
            $args['price'] = $price;
        }

        if ( array_key_exists( 'tags', $request_params ) ) {
            $tags         = $this->maybe_json_decode( $to_return, wp_unslash( $request_params['tags'] ) );
            $args['tags'] = $tags;
        }

        if ( array_key_exists( 'toggles', $request_params ) ) {
            $toggles         = $this->maybe_json_decode( $to_return, wp_unslash( $request_params['toggles'] ) );
            $args['toggles'] = $toggles;
        }

        if ( array_key_exists( 'sortBy', $request_params ) ) {
            $sort_by        = ( $this->maybe_json_decode( $to_return, wp_unslash( $request_params['sortBy'] ) ) )[0];
            $args['sortBy'] = $sort_by;
        }

        if ( array_key_exists( 'date', $request_params ) ) {
            $date         = $this->maybe_json_decode( $to_return, wp_unslash( $request_params['date'] ) );
            $args['date'] = $date;
        }

        if ( array_key_exists( 'postsPerPage', $request_params ) ) {
            $args['postsPerPage'] = $request_params['postsPerPage'];
        }

        if ( array_key_exists( 'hash', $request_params ) ) {
            $text         = wp_unslash( $request_params['hash'] );
            $args['hash'] = $text;
        }

        return $args;
    }


    /**
     * Filters tags.
     *
     * @param bool       $to_return                           Whether to return the output, or just wp_send_json().
     * @param array|null $request_params                      Request parameters.
     * @param bool       $return_empty_string_if_not_in_cache Return empty string if the result is not in cache (so do not make any backend request).
     *
     * @throws Exception Exception.
     */
    public function filter_tags( $to_return = false, array $request_params = null, $return_empty_string_if_not_in_cache = false ) {
        $args = $this->get_filter_request_args( $to_return, $request_params );

        $tags_ids = Backend_Requests::get( 'filter_tags', $args, $return_empty_string_if_not_in_cache );

        if ( $return_empty_string_if_not_in_cache && '' === $tags_ids ) {
            return '';
        }

        if ( $to_return ) {
            return $tags_ids;
        } else {
            $data = $tags_ids;

            $data = [
                'data'       => $data,
                'cacheUntil' => time() + (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60,
            ];

            if ( $this->add_debug_data ) {
                $data['debugData'] = [
                    'requestUrl'     => Backend_Requests::$last_request_url,
                    'rawRequestBody' => Backend_Requests::$last_request_args['body'] ?? '',
                ];
            }

            wp_send_json( $data );
        }
    }


    /**
     * Filters attribute terms.
     *
     * @param bool       $to_return                           Whether to return the output, or just wp_send_json().
     * @param array|null $request_params                      Request parameters.
     * @param bool       $return_empty_string_if_not_in_cache Return empty string if the result is not in cache (so do not make any backend request).
     *
     * @throws Exception Exception.
     */
    public function filter_attribute_terms( $to_return = false, array $request_params = null, $return_empty_string_if_not_in_cache = false ) {
        if ( is_null( $request_params ) ) {
            $request_params = Sanitize::sanitize_array( $_REQUEST );
        }

        $args = []; // Request args.

        if ( ! array_key_exists( 'attributeSlug', $request_params ) ) {
            if ( $to_return ) {
                return [ 'error' => 'No attributeSlug provided.' ];
            } else {
                wp_send_json( [ 'error' => 'No attributeSlug provided.' ] );
            }
        }

        $args['attributeSlug'] = wp_unslash( $request_params['attributeSlug'] );
        $args                  = array_merge( $args, $this->get_filter_request_args( $to_return, $request_params ) );

        $attribute_terms_ids = Backend_Requests::get( 'filter_attribute_terms', $args, $return_empty_string_if_not_in_cache );

        if ( $return_empty_string_if_not_in_cache && '' === $attribute_terms_ids ) {
            return '';
        }

        if ( $to_return ) {
            return $attribute_terms_ids;
        } else {
            $data = $attribute_terms_ids;

            $data = [
                'data'       => $data,
                'cacheUntil' => time() + (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60,
            ];

            if ( $this->add_debug_data ) {
                $data['debugData'] = [
                    'requestUrl'     => Backend_Requests::$last_request_url,
                    'rawRequestBody' => Backend_Requests::$last_request_args['body'] ?? '',
                ];
            }

            wp_send_json( $data );
        }
    }


    /**
     * Filters categories
     *
     * @param bool       $to_return                           Whether to return the output, or just wp_send_json().
     * @param array|null $request_params                      Request parameters.
     * @param bool       $return_empty_string_if_not_in_cache Return empty string if the result is not in cache (so do not make any backend request).
     *
     * @throws Exception Exception.
     */
    public function filter_categories( $to_return = false, array $request_params = null, $return_empty_string_if_not_in_cache = false ) {
        $args = $this->get_filter_request_args( $to_return, $request_params );

        $categories_ids = Backend_Requests::get( 'filter_categories', $args, $return_empty_string_if_not_in_cache );

        if ( $return_empty_string_if_not_in_cache && '' === $categories_ids ) {
            return '';
        }

        if ( $to_return ) {
            return $categories_ids;
        } else {
            $data = $categories_ids;

            $data = [
                'data'       => $data,
                'cacheUntil' => time() + (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60,
            ];

            if ( $this->add_debug_data ) {
                $data['debugData'] = [
                    'requestUrl'     => Backend_Requests::$last_request_url,
                    'rawRequestBody' => Backend_Requests::$last_request_args['body'] ?? '',
                ];
            }

            wp_send_json( $data );
        }
    }


    /**
     * Returns max date (The newest product).
     *
     * Format: {min: val} or empty array if failed to retrieve from the backend.
     *
     * @param bool $to_return                           Whether to return the output, or just wp_send_json().
     * @param bool $return_empty_string_if_not_in_cache Return empty string if the result is not in cache (so do not make any backend request).
     *
     * @throws Exception Exception.
     */
    public function get_date_min( $to_return = false, $return_empty_string_if_not_in_cache = false ) {
        if ( $to_return ) {
            $request_params = Initial_Elements_Rendering\Parse_Url_For_Request_Params::get( true, true );

            if ( false === $request_params ) {
                if ( $return_empty_string_if_not_in_cache ) {
                    return '';
                } else {
                    $request_params = [];
                }
            }
        } else {
            $request_params = Sanitize::sanitize_array( $_REQUEST );
        }

        $min_date = false;

        $args = [
            'property' => 'dateCreatedGmt',
            'sortBy'   => 'oldest',
            'limit'    => '1',
        ];

        $args = array_merge( $args, $this->get_filter_request_args( $to_return, $request_params ) );

        // Oldest product.

        $response = Backend_Requests::get(
            'properties',
            $args,
            $return_empty_string_if_not_in_cache
        );

        if ( $return_empty_string_if_not_in_cache && '' === $response ) {
            return '';
        }

        if ( array_key_exists( 'error', $response ) ) {
            if ( $to_return ) {
                return $response;
            } else {
                wp_send_json( $response );
            }
        }

        if (
            array_key_exists( 'values', $response ) &&
            count( $response['values'] )
        ) {
            $min_date = reset( $response['values'] );
        }

        if ( $min_date ) {
            $data = [
                'min' => explode( ' ', reset( $min_date ) )[0], // We don't need the time.
            ];
        } else {
            $data = [];
        }

        if ( $to_return ) {
            return $data;
        } else {
            $data = [
                'data'       => $data,
                'cacheUntil' => time() + (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60,
            ];

            if ( $this->add_debug_data ) {
                $data['debugData'] = [
                    'requestUrl'     => Backend_Requests::$last_request_url,
                    'rawRequestBody' => Backend_Requests::$last_request_args['body'] ?? '',
                ];
            }

            wp_send_json( $data );
        }
    }


    /**
     * Returns max date (The oldest product).
     *
     * Format: {max: val} or empty array if failed to retrieve from the backend.
     *
     * @param bool $to_return                           Whether to return the output, or just wp_send_json().
     * @param bool $return_empty_string_if_not_in_cache Return empty string if the result is not in cache (so do not make any backend request).
     *
     * @throws Exception Exception.
     */
    public function get_date_max( $to_return = false, $return_empty_string_if_not_in_cache = false ) {
        if ( $to_return ) {
            $request_params = Initial_Elements_Rendering\Parse_Url_For_Request_Params::get( true, true );

            if ( false === $request_params ) {
                if ( $return_empty_string_if_not_in_cache ) {
                    return '';
                } else {
                    $request_params = [];
                }
            }
        } else {
            $request_params = Sanitize::sanitize_array( $_REQUEST );
        }

        $max_date = false;

        $args = [
            'property' => 'dateCreatedGmt',
            'sortBy'   => 'newest',
            'limit'    => '1',
        ];

        $args = array_merge( $args, $this->get_filter_request_args( $to_return, $request_params ) );

        // Newest product.

        $response = Backend_Requests::get(
            'properties',
            $args,
            $return_empty_string_if_not_in_cache
        );

        if ( $return_empty_string_if_not_in_cache && '' === $response ) {
            return '';
        }

        if ( array_key_exists( 'error', $response ) ) {
            if ( $to_return ) {
                return $response;
            } else {
                wp_send_json( $response );
            }
        }

        if (
            array_key_exists( 'values', $response ) &&
            count( $response['values'] )
        ) {
            $max_date = reset( $response['values'] );
        }

        if ( $max_date ) {
            $data = [
                'max' => explode( ' ', reset( $max_date ) )[0], // We don't need the time.
            ];
        } else {
            $data = [];
        }

        if ( $to_return ) {
            return $data;
        } else {
            $data = [
                'data'       => $data,
                'cacheUntil' => time() + (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60,
            ];

            if ( $this->add_debug_data ) {
                $data['debugData'] = [
                    'requestUrl'     => Backend_Requests::$last_request_url,
                    'rawRequestBody' => Backend_Requests::$last_request_args['body'] ?? '',
                ];
            }

            wp_send_json( $data );
        }
    }


    /**
     * Returns min price (the cheapest product).
     *
     * Format: {min: val} or empty array if failed to retrieve from the backend.
     *
     * @param bool $to_return                           Whether to return the output, or just wp_send_json().
     * @param bool $return_empty_string_if_not_in_cache Return empty string if the result is not in cache (so do not make any backend request).
     *
     * @throws Exception Exception.
     */
    public function get_price_min( $to_return = false, $return_empty_string_if_not_in_cache = false ) {
        if ( $to_return ) {
            $request_params = Initial_Elements_Rendering\Parse_Url_For_Request_Params::get( true, true );

            if ( false === $request_params ) {
                if ( $return_empty_string_if_not_in_cache ) {
                    return '';
                } else {
                    $request_params = [];
                }
            }
        } else {
            $request_params = Sanitize::sanitize_array( $_REQUEST );
        }

        $min_price = false;

        $args = [
            'property' => 'minPrice',
            'sortBy'   => 'lowestPrice',
            'limit'    => '1',
        ];

        $args = array_merge( $args, $this->get_filter_request_args( $to_return, $request_params ) );

        // Cheapest product.

        $response = Backend_Requests::get(
            'properties',
            $args,
            $return_empty_string_if_not_in_cache
        );

        if ( $return_empty_string_if_not_in_cache && '' === $response ) {
            return '';
        }

        if ( array_key_exists( 'error', $response ) ) {
            if ( $to_return ) {
                return $response;
            } else {
                wp_send_json( $response );
            }
        }

        if (
            array_key_exists( 'values', $response ) &&
            count( $response['values'] )
        ) {
            $min_price = reset( $response['values'] );
        }

        if ( $min_price ) {
            $data = [
                'min' => (string) (int) reset( $min_price ),
            ];
        } else {
            $data = [];
        }

        if ( $to_return ) {
            return $data;
        } else {
            $data = [
                'data'       => $data,
                'cacheUntil' => time() + (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60,
            ];

            if ( $this->add_debug_data ) {
                $data['debugData'] = [
                    'requestUrl'     => Backend_Requests::$last_request_url,
                    'rawRequestBody' => Backend_Requests::$last_request_args['body'] ?? '',
                ];
            }

            wp_send_json( $data );
        }
    }


    /**
     * Returns max price (the most expensive product).
     *
     * Format: {max: val} or empty array if failed to retrieve from the backend.
     *
     * @param bool $to_return                           Whether to return the output, or just wp_send_json().
     * @param bool $return_empty_string_if_not_in_cache Return empty string if the result is not in cache (so do not make any backend request).
     *
     * @throws Exception Exception.
     */
    public function get_price_max( $to_return = false, $return_empty_string_if_not_in_cache = false ) {
        if ( $to_return ) {
            $request_params = Initial_Elements_Rendering\Parse_Url_For_Request_Params::get( true, true );

            if ( false === $request_params ) {
                if ( $return_empty_string_if_not_in_cache ) {
                    return '';
                } else {
                    $request_params = [];
                }
            }
        } else {
            $request_params = Sanitize::sanitize_array( $_REQUEST );
        }

        $max_price = false;

        $args = [
            'property' => 'maxPrice',
            'sortBy'   => 'highestPrice',
            'limit'    => '1',
        ];

        $args = array_merge( $args, $this->get_filter_request_args( $to_return, $request_params ) );

        // Most expensive product.

        $response = Backend_Requests::get(
            'properties',
            $args,
            $return_empty_string_if_not_in_cache
        );

        if ( $return_empty_string_if_not_in_cache && '' === $response ) {
            return '';
        }

        if ( array_key_exists( 'error', $response ) ) {
            if ( $to_return ) {
                return $response;
            } else {
                wp_send_json( $response );
            }
        }

        if (
            array_key_exists( 'values', $response ) &&
            count( $response['values'] )
        ) {
            $max_price = reset( $response['values'] );
        }

        if ( $max_price ) {
            $data = [
                'max' => (string) (int) reset( $max_price ),
            ];
        } else {
            $data = [];
        }

        if ( $to_return ) {
            return $data;
        } else {
            $data = [
                'data'       => $data,
                'cacheUntil' => time() + (int) SpeedSearch::$options->get( 'setting-cache-flush-interval' ) * 60,
            ];

            if ( $this->add_debug_data ) {
                $data['debugData'] = [
                    'requestUrl'     => Backend_Requests::$last_request_url,
                    'rawRequestBody' => Backend_Requests::$last_request_args['body'] ?? '',
                ];
            }

            wp_send_json( $data );
        }
    }


    /**
     * Returns recently viewed products data.
     *
     * @throws Exception Exception.
     * @see WC_Widget_Recently_Viewed
     */
    public function get_recently_viewed_products_data() {
        $viewed_products           = array_map(
            'absint',
            array_key_exists( 'products', $_REQUEST ) ?
                Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['products'] ) ), true ) ) : []
        );
        $add_most_popular_products = array_key_exists( 'addMostPopularProductsIfLimitIsNotHit', $_REQUEST ) &&
                                    'true' === sanitize_text_field( wp_unslash( $_REQUEST['addMostPopularProductsIfLimitIsNotHit'] ) );
        $thumbnail_image_size      = array_key_exists( 'thumbnailImageSize', $_REQUEST ) ? sanitize_text_field( wp_unslash( $_REQUEST['thumbnailImageSize'] ) ) : '';

        $data = [];
        if ( $viewed_products ) {

            /**
             * Fires before recently viewed products data is rendered.
             *
             * @param array $viewed_products List of viewed product IDs.
             */
            do_action( 'speedsearch_before_render_recently_viewed_products_data', $viewed_products );

            if ( $add_most_popular_products ) {
                $num_of_most_popular_products_to_add = SPEEDSEARCH_RECENTLY_VIEWED_PRODUCTS_PRODUCTS_SHOW_LIMIT - count( $viewed_products );

                if ( $num_of_most_popular_products_to_add ) {
                    $popular_products = self::search(
                        true,
                        false,
                        false,
                        [
                            'sortBy'       => [ 'mostPopular' ],
                            'postsPerPage' => SPEEDSEARCH_RECENTLY_VIEWED_PRODUCTS_PRODUCTS_SHOW_LIMIT * 2,
                        ],
                        true
                    );

                    $viewed_products = array_slice(
                        array_merge(
                            $viewed_products,
                            $popular_products
                        ),
                        0,
                        SPEEDSEARCH_RECENTLY_VIEWED_PRODUCTS_PRODUCTS_SHOW_LIMIT
                    );
                }
            }

            if ( $thumbnail_image_size && 'woocommerce_thumbnail' !== $thumbnail_image_size ) {
                add_filter(
                    'single_product_archive_thumbnail_size',
                    function() use ( $thumbnail_image_size ) {
                        return $thumbnail_image_size;
                    }
                );
            }

            $data = Posts_Data_Final_Output::try_to_inject_html( $data, $viewed_products, 'recently_viewed_products' );
            if ( ! array_key_exists( 'html', $data ) ) { // If posts HTML wasn't added by the integration, then returns raw data.
                $data = Posts_Data_Final_Output::get_posts_data( $viewed_products );
            }
        }

        wp_send_json( $data );
    }


    /**
     * Returns public settings.
     */
    public function get_public_settings() {
        wp_send_json( Elements_Rendering_Data::get_public_settings() );
    }


    /**
     * Returns tags data.
     */
    public function get_tags() {
        wp_send_json( Elements_Rendering_Data::get_tags() );
    }


    /**
     * Returns categories data.
     */
    public function get_categories() {
        wp_send_json( Elements_Rendering_Data::get_categories() );
    }


    /**
     * Returns filters data.
     */
    public function get_filters() {
        wp_send_json( Elements_Rendering_Data::get_filters() );
    }


    /**
     * Returns server time.
     *
     * It's used by client-side for higher JSON files sync accuracy.
     */
    public static function get_server_time() {
        wp_send_json( [ 'server_time' => time() ] );
    }


    /**
     * Returns the products debug data HTML.
     */
    public function get_debug_products() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'speedsearch-debug-mode', 'nonceToken' );

        $response = [
            'error' => [
                'text' => '"Filters" param is not specified.',
            ],
        ];
        if ( isset( $_REQUEST['filters'] ) ) {
            $raw_args = Sanitize::sanitize_array( (array) json_decode( sanitize_text_field( wp_unslash( $_REQUEST['filters'] ) ), true ) );
            $args     = $this->get_filter_request_args( true, $raw_args );
            $response = Backend_Requests::get( 'debug_products', $args );
        }

        $data = [];

        /**
         * Wrap HTML.
         *
         * @param string $html HTML to wrap.
         *
         * @return string Wrapped HTML.
         */
        $wrap = function( $html ) {
            return '<div class="speedsearch-debug-data-container"><pre>' . $html . '</pre></div>';
        };

        /**
         * Converts array to rows.
         *
         * @param array $rows Array.
         *
         * @return array Rows.
         */
        $convert_array_to_rows = function( array $array ) use ( $wrap ) {
            $rows = [];

            foreach ( $array as $row_name => $row_value ) {
                if ( null === $row_value ) {
                    $row_value = 'null';
                }

                $rows[] = [
                    $row_name,
                    is_array( $row_value ) ? $wrap( json_encode( $row_value, JSON_PRETTY_PRINT ) ) : $row_value,
                ];
            }

            return $rows;
        };

        $filters = [];

        if ( isset( $response['error'] ) ) {
            $data[] = [
                'title' => __( 'Failed to fetch the debug products list' ),
                'rows'  => $convert_array_to_rows( $response['error'] ),
                'type'  => 'error',
            ];
        } elseif ( $response ) { // Some products are returned.
            foreach ( $response as $product ) {
                $post_id = $product['id'];

                $a_product = wc_get_product( $post_id );
                $date      = $a_product->get_date_modified();

                $date_modified = null;
                if ( ! is_null( $date ) ) {
                    $timestamp = $date->getTimestamp();

                    $date_modified = wp_date( 'Y-m-d H:i:s', $timestamp );
                }

                $synced_time = get_date_from_gmt( $product['fully_synced_at'] );
                if ( $date_modified && strtotime( $synced_time ) < strtotime( $date_modified ) ) {
                    $synced_time = '<span class="speedsearch-warning">' . $synced_time . '<span>';
                }

                if ( ! $date_modified ) {
                    $date_modified = '<span class="speedsearch-warning">' . __( "Couldn't fetch from WP", 'speedsearch' ) . '<span>';
                }

                $in_results_text = '<span class="speedsearch-warning">' . __( 'No', 'speedsearch' ) . '</span>';
                if ( $product['found'] ) {
                    $posts_per_page = Posts::get_posts_per_page_number();
                    $position       = $product['position'];

                    $page_number = ceil( $position / $posts_per_page );

                    $mod              = $position % $posts_per_page;
                    $position_on_page = 0 === $mod ? $posts_per_page : $mod;

                    $in_results_text = sprintf(
                        /* translators: %1$d is page number %2$d is position on that page. */
                        __( 'Yes (page %1$d position %2$d)', 'speedsearch' ),
                        $page_number,
                        $position_on_page
                    );
                }

                $rows = [
                    [
                        __( 'Last modified', 'speedsearch' ),
                        $date_modified,
                    ],
                    [
                        __( 'Last synced', 'speedsearch' ),
                        $synced_time,
                    ],
                    [
                        __( 'In results', 'speedsearch' ),
                        $in_results_text,
                    ],
                ];

                if ( ! $product['found'] ) {
                    $reasons = $product['reasons'];

                    // Tags.

                    if ( isset( $reasons['tag'] ) ) {
                        $filters['tags'] = [
                            'match' => $reasons['tag']['match'],
                            'has'   => array_values( $reasons['tag']['active'] ),
                        ];
                    }

                    // Categories.

                    if ( isset( $reasons['category'] ) ) {
                        $filters['categories'] = [
                            'match' => $reasons['category']['match'],
                            'has'   => array_values( $reasons['category']['active'] ),
                        ];
                    }

                    // Attributes.

                    if ( isset( $reasons['attributes'] ) ) {
                        foreach ( $reasons['attributes'] as $attribute_slug => $attribute_filter_data ) {
                            if ( ! isset( $mismatches['attributes'] ) ) {
                                $mismatches['attributes'] = [];
                            }

                            $filters['attributes'][ $attribute_slug ] = [
                                'match' => $attribute_filter_data['match'],
                                'has'   => array_values( $attribute_filter_data['active'] ),
                            ];
                        }
                    }

                    // Search text.

                    if ( isset( $reasons['fullText'] ) ) {
                        $filters['text'] = [
                            'match' => $reasons['fullText']['match'],
                            'has'   => (array) $reasons['fullText']['active'],
                        ];
                    }

                    // Toggles.

                    if ( isset( $reasons['properties']['eq'] ) ) {
                        foreach ( $reasons['properties']['eq'] as $toggle_slug => $toggle_data ) {
                            if ( ! isset( $filters['toggles'] ) ) {
                                $filters['toggles'] = [];
                            }

                            $filters['toggles'][ $toggle_slug ] = [
                                'match' => $toggle_data['match'],
                            ];
                        }
                    }

                    // Date.

                    if ( isset( $reasons['properties']['range']['date_created_gmt'] ) ) {
                        $filters['date'] = [
                            'match' => $reasons['properties']['range']['date_created_gmt']['match'],
                            'has'   => [ explode( ' ', $reasons['properties']['range']['date_created_gmt']['active'] )[0] ],
                        ];
                    }

                    // Price.

                    if ( isset( $reasons['properties']['range']['price'] ) ) {
                        $filters['price'] = [
                            'match' => $reasons['properties']['range']['price']['match'],
                            'has'   => [ $reasons['properties']['range']['price']['active'] ],
                        ];
                    }
                }

                $data[] = [
                    'title'   => isset( $product['name'] ) ? $product['name'] : '',
                    'rows'    => $rows,
                    'filters' => $filters,
                ];
            }
        }

        $last_plugin_error = SpeedSearch::$options->get( 'last-plugin-error' );
        if ( $last_plugin_error ) {
            $last_plugin_error_at      = array_key_last( $last_plugin_error );
            $last_plugin_error_message = end( $last_plugin_error );

            $data[] = [
                'title' => __( 'Last plugin error', 'speedsearch' ),
                'rows'  => [
                    [
                        __( 'Occurred at', 'speedsearch' ),
                        '<span title="' . $last_plugin_error_at . '">' . wp_date( 'Y-m-d H:i:s', $last_plugin_error_at ) . '</span>',
                    ],
                    [
                        __( 'Error message', 'speedsearch' ),
                        $last_plugin_error_message,
                    ],
                ],
                'type'  => 'error',
            ];
        }

        wp_send_json( $data );
    }
}
