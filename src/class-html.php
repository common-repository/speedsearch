<?php
/**
 * Class for HTML.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;
use SpeedSearch\Initial_Elements_Rendering\Elements_Rendering_Data;
use SpeedSearch\Initial_Elements_Rendering\Parse_Url_For_Request_Params;

/**
 * Class HTML.
 */
final class HTML {

    /**
     * Whether mobile filters block container was added.
     *
     * @var bool
     */
    public static $mobile_filters_block_container_was_added = false;

    /**
     * Whether the first layer filtering data was added.
     *
     * @var bool
     */
    public static $was_first_layer_filtering_added = false;

    /**
     * Whether the second layer filtering data was added.
     *
     * @var bool
     */
    public static $was_second_layer_filtering_added = false;

    /**
     * Whether total posts counter was added.
     *
     * @var bool
     */
    public static $was_html_total_posts_counter_inited = false;

    /**
     * Get filter attribute terms and tags by the desired request params (e.g. [ 'categories' ] ).
     *
     * 1. If the value is not in cache, returns empty strings.
     * 2. If not a shop page, returns empty strings.
     *
     * @param array $for_request_params Array of request params for which to get the data.
     *
     * @return array [ $attribute_term_names_to_show, $tags_ids_to_show ]
     * @throws Exception Exception.
     */
    public static function get_from_cache_filter_attribute_terms_and_tags( array $for_request_params ) {
        $is_shop_page = is_shop() || is_product_taxonomy();

        $request_params             = Parse_Url_For_Request_Params::get();
        $desired_request_params_arr = [];

        foreach ( $for_request_params as $param ) {
            if ( array_key_exists( $param, $request_params ) ) {
                $desired_request_params_arr[ $param ] = $request_params[ $param ];
            }
        }

        $attributes_slugs = array_diff(
            array_keys( Elements_Rendering_Data::get_filters()['filters'] ),
            [ 'sort-by', 'date', 'price' ]
        );

        // Attribute-terms.

        /**
         * Combines requests for single attribute (for many attributes) into one.
         *
         * If at least one of them is not in the cache, returns an empty string.
         *
         * @return string|array Empty string if at least one of them is not in the cache. An array otherwise.
         * @throws Exception Exception.
         */
        $get_attribute_term_names_to_show = function() use ( $attributes_slugs, $desired_request_params_arr ) {
            $attribute_term_names_to_show = [];
            foreach ( $attributes_slugs as $attribute_slug ) {
                $attribute_slug_request_params                  = $desired_request_params_arr;
                $attribute_slug_request_params['attributeSlug'] = $attribute_slug;

                $response = SpeedSearch::$ajax->filter_attribute_terms( true, $attribute_slug_request_params, true );

                if ( '' === $response ) {
                    return '';
                } else {
                    $attribute_term_names_to_show[ $attribute_slug ] = $response;
                }
            }
            return $attribute_term_names_to_show;
        };

        $attribute_term_names_to_show = $is_shop_page ? $get_attribute_term_names_to_show() : '';

        // Tags.

        $tags_ids_to_show = $is_shop_page ?
            SpeedSearch::$ajax->filter_tags( true, $desired_request_params_arr, true ) : '';

        return [ $attribute_term_names_to_show, $tags_ids_to_show ];
    }

    /**
     * Pre-filters HTML print.
     *
     * @throws Exception Exception.
     */
    public static function pre_tags_print() {
        if ( ! self::$was_second_layer_filtering_added ) {
            self::$was_second_layer_filtering_added = true;

            list( $attribute_term_names_to_show, $tags_ids_to_show ) = self::get_from_cache_filter_attribute_terms_and_tags( [ 'tags', 'attributes', 'toggles' ] );

            ob_start();

            ?>
            <script
            <?php
            ?>
            type='text/javascript'>
            /* <![CDATA[ */
            speedsearch.filterByTagsAttributesAndToggles_attributeTermNamesToShow = <?php echo wp_json_encode( $attribute_term_names_to_show ); ?>;
            speedsearch.filterByTagsAttributesAndToggles_tagsIdsToShow = <?php echo wp_json_encode( $tags_ids_to_show ); ?>;
            /* ]]> */
            </script>
            <?php

            echo sanitize_user_field(
                'speedsearch',
                ( new \MatthiasMullie\Minify\JS( ob_get_clean() ) )->minify(),
                0,
                'display'
            );
        }
    }

    /**
     * Pre-categories HTML print.
     *
     * @throws Exception Exception.
     */
    public static function pre_categories_print() {
        if ( ! self::$was_first_layer_filtering_added ) {
            self::$was_first_layer_filtering_added = true;

            $is_shop_page = is_shop() || is_product_taxonomy();

            list( $attribute_term_names_to_show, $tags_ids_to_show ) = self::get_from_cache_filter_attribute_terms_and_tags( [ 'categories' ] );

            $request_params = Initial_Elements_Rendering\Parse_Url_For_Request_Params::get();

            if ( array_key_exists( 'taxArchivePage', $request_params ) ) { // This param is added on "wp" hook, but of course print always happens later.
                $desired_request_params_arr = [
                    'attributes' => $request_params['taxArchivePage'],
                ];
                $categories_ids_to_show     = $is_shop_page ?
                    SpeedSearch::$ajax->filter_categories( true, $desired_request_params_arr, true ) : '';
            } else {
                $categories_ids_to_show = '';
            }

            ob_start();

            ?>
            <script
            <?php
            ?>
            type='text/javascript'>
            /* <![CDATA[ */
            speedsearch.filterByCategories_attributeTermNamesToShow = <?php echo wp_json_encode( $attribute_term_names_to_show ); ?>;
            speedsearch.filterByCategories_tagsIdsToShow = <?php echo wp_json_encode( $tags_ids_to_show ); ?>;
            speedsearch.filterCategories_idsToShow = <?php echo wp_json_encode( $categories_ids_to_show ); ?>;
            /* ]]> */
            </script>
            <?php

            echo sanitize_user_field(
                'speedsearch',
                ( new \MatthiasMullie\Minify\JS( ob_get_clean() ) )->minify(),
                0,
                'display'
            );
        }
    }

    /**
     * Pre-filters HTML print.
     *
     * @throws Exception Exception.
     */
    public static function pre_filters_print() {
        if ( ! self::$was_second_layer_filtering_added ) {
            self::$was_second_layer_filtering_added = true;

            list( $attribute_term_names_to_show, $tags_ids_to_show ) = self::get_from_cache_filter_attribute_terms_and_tags( [ 'tags', 'attributes', 'toggles' ] );

            ob_start();

            ?>
            <script
            <?php
            ?>
            type='text/javascript'>
            /* <![CDATA[ */
            speedsearch.filterByTagsAttributesAndToggles_attributeTermNamesToShow = <?php echo wp_json_encode( $attribute_term_names_to_show ); ?>;
            speedsearch.filterByTagsAttributesAndToggles_tagsIdsToShow = <?php echo wp_json_encode( $tags_ids_to_show ); ?>;
            /* ]]> */
            </script>
            <?php

            echo sanitize_user_field(
                'speedsearch',
                ( new \MatthiasMullie\Minify\JS( ob_get_clean() ) )->minify(),
                0,
                'display'
            );
        }
    }

    /**
     * Pre-posts HTML print.
     *
     * @throws Exception Exception.
     */
    public static function pre_posts_print() {
        if ( ! self::$was_html_total_posts_counter_inited ) {
            $is_shop_page = is_shop() || is_product_taxonomy();
            $posts_data   = $is_shop_page ? SpeedSearch::$ajax->search( true, true ) : '';

            if (
                '' !== $posts_data &&
                ! array_key_exists( 'error', $posts_data ) &&
                array_key_exists( 'pagination', $posts_data )
            ) {
                self::$was_html_total_posts_counter_inited = true;

                $total_posts = $posts_data['pagination']['totalPosts'];
                $searched    = array_key_exists( 'searched', $posts_data ) ? $posts_data['searched'] : '';

                ob_start();

                ?>
                <script
                <?php
                ?>
                type='text/javascript'>
                /* <![CDATA[ */
                speedsearch.totalPosts = <?php echo esc_html( $total_posts ); ?>;
                speedsearch.searched   = "<?php echo esc_html( $searched ); ?>";
                /* ]]> */
                </script>
                <?php

                echo sanitize_user_field(
                    'speedsearch',
                    ( new \MatthiasMullie\Minify\JS( ob_get_clean() ) )->minify(),
                    0,
                    'display'
                );
            }
        }
    }

    /**
     * Post (after)-posts HTML print.
     *
     * @throws Exception Exception.
     */
    public static function post_posts_print() {
        if ( ! self::$was_html_total_posts_counter_inited ) { // Posts were embedded to the page HTML.
            ob_start();

            ?>
            <script
            <?php
            ?>
            type='text/javascript'>
            /* <![CDATA[ */
            speedsearch.updatePosts( false, false, 'afterPosts' );
            speedsearch.startRequestsEnqueue();
            /* ]]> */
            </script>
            <?php

            echo sanitize_user_field(
                'speedsearch',
                ( new \MatthiasMullie\Minify\JS( ob_get_clean() ) )->minify(),
                0,
                'display'
            );
        }
    }

    /**
     * Get template override file theme path for the current SpeedSearch theme.
     *
     * @param string $template_file_relative_path Template to load. Path relative to the /src/templates/.
     *
     * @return string|bool Template file relative path for the current theme, or false if no current theme or such a file.
     */
    public static function get_current_theme_template_override_file( $template_file_relative_path ) {
        $template_file_relative_path = ltrim( $template_file_relative_path, '/' );

        // Loads theme-specific template files.
        $current_theme_slug = SpeedSearch::$options->get( 'setting-current-theme-data' )['name'];

        $theme_template_file = locate_template( [ "speedsearch/themes/$current_theme_slug/templates/$template_file_relative_path" ] );

        return $theme_template_file;
    }

    /**
     * Locate template.
     *
     * Searches in themes first, then in plugin's currently selected theme, then in the plugin itself. Search Order:
     *
     * speedsearch/themes/speedsearch_theme
     *
     * 1. If custom theme is chosen: /themes/theme/speedsearch/themes/speedsearch_theme/$template_file
     * 2. If theme integration is found: /plugins/speedsearch/src/integrations/themes/theme_name/templates/$template_file
     * 3. /plugins/speedsearch/src/templates/$template_file
     *
     * @param string $template_file_relative_path Template to load. Path relative to the /src/templates/.
     *
     * @return string Path to the template file.
     *
     * @throws Exception Exception.
     */
    public static function locate_template( $template_file_relative_path ) {
        $template_file_relative_path = ltrim( $template_file_relative_path, '/' );

        $plugin_template_path = SPEEDSEARCH_DIR . "src/templates/$template_file_relative_path";

        $theme_template_file = self::get_current_theme_template_override_file( $template_file_relative_path );

        if ( $theme_template_file ) {
            return $theme_template_file;
        } elseif ( isset( SpeedSearch::$integrations->template_overrides[ $template_file_relative_path ] ) ) {
            return SpeedSearch::$integrations->template_overrides[ $template_file_relative_path ];
        } else {
            return $plugin_template_path;
        }
    }

    /**
     * Returns template's HTML as a string.
     *
     * @param string $template_file_relative_path Path of template file relative to SPEEDSEARCH_DIR/src/templates/.
     * @param array  $args                        Arguments to pass to the template.
     *
     * @return string
     *
     * @throws Exception Exception.
     */
    private static function return_template_html( $template_file_relative_path, array $args = [] ) {
        ob_start();
        extract( $args ); // @codingStandardsIgnoreLine
        require self::locate_template( $template_file_relative_path );
        $html = ob_get_clean();

        // Removes spaces between the tags to minimize the wpautotop effect.
        return preg_replace( '@(?<=>)\s*(?=<)@', '', $html );
    }

    /**
     * Renders (displays) template file.
     *
     * @param string $template_file_relative_path Path of template file relative to SPEEDSEARCH_DIR/src/templates/.
     * @param array  $args                        Arguments to pass to the template.
     * @param bool   $return                      Whether to return the HTML or just echo it.
     *
     * @throws Exception Exception.
     */
    public static function render_template( $template_file_relative_path, array $args = [], $return = false ) {
        if ( $return ) {
            ob_start();
        }

        if ( str_contains( $template_file_relative_path, 'parts/posts.php' ) ) {
            self::pre_posts_print();
        } elseif ( str_contains( $template_file_relative_path, 'parts/categories.php' ) ) {
            self::pre_categories_print();
        } elseif ( str_contains( $template_file_relative_path, 'parts/filters.php' ) ) {
            self::pre_filters_print();
        } elseif ( str_contains( $template_file_relative_path, 'parts/tags.php' ) ) {
            self::pre_tags_print();
        }

        echo sanitize_user_field(
            'speedsearch',
            self::return_template_html( $template_file_relative_path, $args ),
            0,
            'display'
        );

        if ( str_contains( $template_file_relative_path, 'parts/posts.php' ) ) {
            self::post_posts_print();
        }

        if ( $return ) {
            return ob_get_clean();
        }
    }

    /**
     * Returns categories HTML.
     *
     * @return string
     * @throws Exception Exception.
     */
    public static function get_categories_html() {
        $html              = '';
        $get_category_html = '';

        /**
         * Returns a disabled category class.
         *
         * @param array $category_data Category data.
         *
         * @return null|string 'disabled' class or empty string.
         */
        $get_is_disabled_class = function( array $category_data ) {
            $a_class = '';
            if ( $category_data['isDisabled'] ) {
                $a_class .= 'disabled';
            }
            return $a_class;
        };

        /**
         * Returns children categories HTML.
         *
         * @param array $category_data Category data.
         *
         * @return null|string HTML.
         */
        $get_children_categories_html = function( array $category_data ) use ( &$get_category_html ) {
            $html = '';
            if ( array_key_exists( 'children', $category_data ) ) {
                foreach ( $category_data['children'] as $category_slug => $category_data ) {
                    $html .= $get_category_html( $category_data, $category_slug );
                }
            }
            return $html;
        };

        /**
         * Adds category HTML.
         *
         * @param array  $category_data Category data.
         * @param string $category_slug Category slug.
         */
        $get_category_html = function( $category_data, $category_slug ) use ( &$html, $get_is_disabled_class, $get_children_categories_html ) {
            $category_slug = esc_attr( $category_slug );
            $category_id   = esc_attr( $category_data['id'] );
            $category_name = esc_html( $category_data['name'] );

            $link = "?categories=$category_slug";

            /**
             *  Category links should have archive URL.
             */
            if (
                (
                    is_product_category() &&
                    ! SpeedSearch::$options->get( 'setting-categories-support-multi-select' )
                ) ||
                (
                    // If the visitor is on shop page (not on an archive page).
                    is_shop() &&
                    // And the setting Hide category filters on archive pages is not enabled.
                    ! SpeedSearch::$options->get( 'setting-archive-pages-hide-categories' ) &&
                    // And the visitor did not select a category yet, or multiple categories is off.
                    (
                        ! SpeedSearch::$options->get( 'setting-categories-support-multi-select' ) ||
                        false === Parse_Url_For_Request_Params::get_url_param( 'categories' )
                    )
                )
            ) {
                $category_archive_link = get_term_link( (int) $category_id, 'product_cat' );

                if ( ! is_wp_error( $category_archive_link ) ) {
                    $link = esc_attr( $category_archive_link );
                }
            }

            return "
                <li class=\"speedsearch-ul-container\">
                    <ul class=\"speedsearch-child-categories\">
                        <a href=\"$link\" class=\"speedsearch-category {$get_is_disabled_class( $category_data )}\">
                            <span data-id=\"$category_id\" data-slug=\"$category_slug\">
                                {$category_name}
                            </span>
                        </a>
                        {$get_children_categories_html( $category_data )}
                    </ul>
                </li>
            ";
        };

        $categories_data = Elements_Rendering_Data::get_categories();
        foreach ( $categories_data as $category_slug => $category_data ) {
            $html .= $get_category_html( $category_data, $category_slug );
        }

        return $html;
    }

    /**
     * Whether doing placeholder posts.
     *
     * @var bool
     */
    public static $doing_placeholder_posts = false;

    /**
     * Returns posts HTML from their data.
     *
     * If no posts' data, adds placeholder posts HTML.
     *
     * @see speedsearch_updatePosts
     *
     * @param array|string $posts_data Posts data, or empty string if posts data is not in cache or this is not a shop page.
     *
     * @return string Posts HTML.
     *
     * @throws Exception Exception.
     */
    public static function get_posts_html( $posts_data ) {
        $html = '';

        // Add categories HTML to the products.
        if ( is_shop() || is_product_category() ) {
            $html .= woocommerce_maybe_show_product_subcategories();
        }

        if ( 'subcategories' !== woocommerce_get_loop_display_mode() ) {
            if ( '' === $posts_data ) { // If no posts, then returns placeholder posts.
                self::$doing_placeholder_posts = true;

                $cache = wp_cache_get( 'placeholder-posts-html', 'speedsearch' );
                if ( false !== $cache ) {
                    return $cache;
                }

                $posts_ids = Posts::get_ids();

                add_filter( 'woocommerce_post_class', [ 'SpeedSearch\Posts', 'add_placeholder_product_classes' ] );
                add_filter( 'wp_get_attachment_image_src', [ 'SpeedSearch\Posts', 'return_image_placeholder_for_img_src' ], 999 );
                add_filter( 'post_type_link', [ 'SpeedSearch\Posts', 'change_placeholder_post_permalink' ], 999, 2 );
                $posts_data = SpeedSearch::$ajax->search( true, false, $posts_ids );
                remove_filter( 'post_type_link', [ 'SpeedSearch\Posts', 'change_placeholder_post_permalink' ], 999 );
                remove_filter( 'wp_get_attachment_image_src', [ 'SpeedSearch\Posts', 'return_image_placeholder_for_img_src' ], 999 );
                remove_filter( 'woocommerce_post_class', [ 'SpeedSearch\Posts', 'add_placeholder_product_classes' ] );
            }

            if ( array_key_exists( 'html', $posts_data ) || ( array_key_exists( 'posts', $posts_data ) && count( $posts_data['posts'] ) ) ) {

                // Adds posts HTML.

                if ( array_key_exists( 'html', $posts_data ) ) {

                    // Get .speedsearch-single-post from HTML.

                    $doc = new \DOMDocument();
                    libxml_use_internal_errors( true ); // Suppress HTML parsing errors.
                    $doc->loadHTML( $posts_data['html'] );
                    $xpath = new \DOMXPath( $doc );
                    $nodes = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' speedsearch-single-post ')]" );

                    foreach ( $nodes as $node ) {
                        $html .= $doc->saveHTML( $node );
                    }
                } elseif ( array_key_exists( 'posts', $posts_data ) ) {
                    $posts = $posts_data['posts'];

                    foreach ( $posts as $post ) {
                        $html .= Posts::get_post_element_html( $post );
                    }
                }

                // Saves to cache.

                if ( self::$doing_placeholder_posts ) {
                    self::$doing_placeholder_posts = false;
                    wp_cache_set( 'placeholder-posts-html', $html, 'speedsearch' );
                }
            } else {
                $translations = Elements_Rendering_Data::get_public_translations();

                $translations = array_map(
                    function ( $x ) {
                        return esc_attr( $x );
                    },
                    $translations
                );

                if ( array_key_exists( 'error', $posts_data ) ) {
                    $something_went_wrong_url = esc_attr( Posts::get_something_went_wrong_image_url() );
                    $html                    .= "
                        <span class=\"speedsearch-error-container\">
                            <img src=\"$something_went_wrong_url\" alt=\"{$translations['somethingWentWrong']}\" loading=\"lazy\">
                            <span class=\"speedsearch-error-heading\">{$translations['somethingWentWrong']}</span>
                            <span class=\"speedsearch-error-text\">{$translations['somethingWentWrong2']}</span>
                            <button class=\"speedsearch-error-reload-btn\" onClick=\"window.location.reload();\">{$translations['reloadThisPage']}</button>
                        </span>
                    ";
                } else {
                    $html .= "
                        <span class=\"speedsearch-no-posts-found-container\">
                            <span class=\"speedsearch-no-posts-found\">{$translations['noFound']}</span>
                            <span class=\"speedsearch-reset-all-filters\">{$translations['resetAllFilters']}</span>
                        </span>
                    ";
                }
            }
        }

        return $html;
    }

    /**
     * Returns posts pagination HTML from their data
     *
     * Should return the same HTML as assets/common/posts.js -> speedsearch_updatePosts -> maybeAddPaginationHTML
     *
     * @param array|string $posts_data Posts data, or empty string if posts data is not in cache or this is not a shop page.
     *
     * @return string Pagination HTML.
     *
     * @throws Exception Exception.
     *
     * @see maybeAddPaginationHTML
     */
    public static function get_pagination_html( $posts_data ) {
        if (
            'subcategories' !== woocommerce_get_loop_display_mode() || // No pagination when showing only categories.
            '' === $posts_data
        ) {
            return '';
        }

        $settings = Elements_Rendering_Data::get_public_settings();
        $html     = '';

        // Adds pagination HTML.

        if ( ! $settings['pagination']['infiniteScroll']['isEnabled'] && array_key_exists( 'pagination', $posts_data ) ) {
            $current_page = (int) Parse_Url_For_Request_Params::get_url_param( 'page' );
            if ( ! $current_page ) {
                $current_page = 1;
            }
            $max_page = (int) ( floor( $posts_data['pagination']['totalPosts'] / $settings['pagination']['postsPerPage'] )
                        // If there is a reminder from the division, it's treated like another page (obviously).
                        + ( $posts_data['pagination']['totalPosts'] % $settings['pagination']['postsPerPage'] ? 1 : 0 ) );
            $previous_page = 1 === $current_page ? 1 : $current_page - 1;
            $next_page     = $current_page === $max_page ? $max_page : $current_page + 1;

            $pages_view_range = 3;

            if ( $posts_data['pagination']['totalPosts'] > $settings['pagination']['postsPerPage'] ) {
                $translations = Elements_Rendering_Data::get_public_translations();

                $translations = array_map(
                    function ( $x ) {
                        return esc_attr( $x );
                    },
                    $translations
                );

                // Adds a new one.

                $previous_page_attr = esc_attr( $previous_page );

                $html .= "
                    <div class=\"speedsearch-pagination\">
                        <a data-page=\"$previous_page_attr\" href=\"?page=$previous_page_attr\" rel=\"prev\" class=\"speedsearch-pagination-element\">{$translations['paginationPrevious']}</a>
                ";

                $page                        = 1;
                $were_three_dots_added_start = false;
                $were_three_dots_added_end   = false;

                $print_page = function() use ( &$page, &$current_page, &$html ) {
                    if ( $page === $current_page ) {
                        $page_attr = esc_attr( $page );
                        $html     .= "
                            <a data-page=\"$page_attr\" href=\"?page=$page_attr\" class=\"speedsearch-pagination-element speedsearch-pagination-digit active\">$page_attr</a>
                        ";
                    } else {
                        $page_attr = esc_attr( $page );
                        $html     .= "
                            <a data-page=\"$page_attr\" href=\"?page=$page_attr\" class=\"speedsearch-pagination-element speedsearch-pagination-digit\">$page_attr</a>
                        ";
                    }
                };

                $print_three_dots = function() use ( &$html ) {
                    $html .= '
                        <span class="speedsearch-pagination-dots">...</span>
                    ';
                };

                for ( $remained_posts = $posts_data['pagination']['totalPosts']; $remained_posts > 0; $remained_posts -= $settings['pagination']['postsPerPage'] ) {
                    $is_page_in_start_range      = $page <= $pages_view_range;
                    $is_page_around_current_page = $page >= $current_page - $pages_view_range && $page <= $current_page + $pages_view_range;
                    $is_page_in_end_range        = $page > $max_page - $pages_view_range;

                    if ( $is_page_in_start_range || $is_page_around_current_page || $is_page_in_end_range ) {
                        $print_page();
                    } else {
                        if ( $page < $current_page && ! $were_three_dots_added_start ) {
                            $print_three_dots();
                            $were_three_dots_added_start = true;
                        }
                        if ( $page > $current_page && ! $were_three_dots_added_end ) {
                            $print_three_dots();
                            $were_three_dots_added_end = true;
                        }
                    }

                    $page++;
                }

                $next_page_attr = esc_attr( $next_page );

                $html .= "
                            <a data-page=\"$next_page_attr\" href=\"?page=$next_page_attr\" rel=\"next\" class=\"speedsearch-pagination-element\">{$translations['paginationNext']}</a>
                        </div>
                ";
            }
        }

        return $html;
    }

    /**
     * Sends no cache headers for shop pages if HTML is not complete (not all data-entities are present in the cache).
     *
     * Should be called on "WP" hook.
     *
     * @throws Exception Exception.
     */
    public static function send_no_cache_headers_for_shop_pages_if_html_is_not_complete() {
        $is_shop_page = is_shop() || is_product_taxonomy();

        if ( $is_shop_page ) {
            // Run the check (and cleanup after).

            ob_start();
            self::pre_categories_print();
            self::$was_first_layer_filtering_added = false;
            self::pre_filters_print();
            self::$was_second_layer_filtering_added = false;
            self::pre_posts_print();
            self::$was_html_total_posts_counter_inited = false;
            ob_end_clean();

            if ( Backend_Requests::$at_least_one_no_cache_request ) {
                Misc::send_no_cache_headers();
            }
        }
    }

    /**
     * Returns products container classes.
     *
     * @return string Product container classes.
     */
    public static function get_products_container_classes() {
        ob_start();

        do_action( 'woocommerce_before_shop_loop' );

        wc_set_loop_prop( 'loop', 0 );

        wc_get_template( 'loop/loop-start.php' );

        $html = ob_get_clean();

        // Regex pattern to match opening tags with a class and all closing tags.
        $pattern = '/<(\w+)([^>]*class=["\'][^"\']*["\'][^>]*)>|<\/(\w+)>/';

        preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER );

        $open_tags = [];

        foreach ( $matches as $match ) {
            if ( isset( $match[3] ) ) {
                // This is a closing tag.
                // Remove the last opening tag from the stack if they match.
                if ( end( $open_tags )['tag'] === $match[3] ) {
                    array_pop( $open_tags );
                }
            } else {
                // This is an opening tag.
                $open_tags[] = [
                    'tag'   => $match[1],
                    'class' => self::get_class_from_tag( $match[0] ),
                ];
            }
        }

        // The last unclosed tag.
        $last_unclosed_tag = end( $open_tags );

        // Get classes of the last unclosed tag.
        $classes = $last_unclosed_tag ? $last_unclosed_tag['class'] : '';

        /**
         * TODO: Grep the classes from the container.
         *
         * @see render_block_core_post_template
         */

        if (
            function_exists( 'use_block_editor_for_post_type' ) &&
            use_block_editor_for_post_type( 'page' ) &&
            ! (
                SpeedSearch::$options->get( 'setting-posts-enable-theme-integration' ) &&
                SpeedSearch::$integrations->is_current_theme_products_integration_present
            )
        ) {
            $classes = str_replace(
                ' products ',
                ' ',
                'speedsearch-gutenberg-styles-added is-flex-container wp-block-post-template is-layout-flow ' . trim( $classes )
            );
        }

        return $classes;
    }

    /**
     * Returns products container tag name.
     *
     * @return string Product container tag name.
     */
    public static function get_products_container_tag() {
        ob_start();

        do_action( 'woocommerce_before_shop_loop' );

        woocommerce_product_loop_start();

        $html = ob_get_clean();

        // Regex pattern to match opening tags with a class and all closing tags.
        $pattern = '/<(\w+)([^>]*class="[^"]*"[^>]*)>|<\/(\w+)>/';

        preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER );

        $open_tags = [];

        foreach ( $matches as $match ) {
            if ( isset( $match[2] ) ) {
                // This is a closing tag.
                // Remove the last opening tag from the stack if they match.
                if ( end( $open_tags ) === $match[2] ) {
                    array_pop( $open_tags );
                }
            } else {
                // This is an opening tag.
                $open_tags[] = $match[1];
            }
        }

        // The last unclosed tag.
        $last_unclosed_tag = end( $open_tags );

        // Return tag name of the last unclosed tag.
        return $last_unclosed_tag ? $last_unclosed_tag : 'ul'; // WC uses <ul> by default, so let's fallback to it.
    }

    /**
     * Returns products element tag, calculating it from the products container tag.
     *
     * @return string
     */
    public static function get_product_element_tag() {
        return in_array( self::get_products_container_tag(), [ 'ul', 'ol' ], true ) ? 'li' : 'div';
    }

    /**
     * Extracts class attribute from an HTML tag.
     *
     * @param string $tag The HTML tag.
     *
     * @return string The class attribute value.
     */
    private static function get_class_from_tag( $tag ) {
        if ( preg_match( '/class="([^"]*)"/', $tag, $match ) ) {
            return $match[1];
        }

        return '';
    }
}
