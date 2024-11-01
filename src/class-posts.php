<?php
/**
 * Class to Get Posts.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;
use SpeedSearch\Initial_Elements_Rendering\Elements_Rendering_Data;
use WP_Query;

/**
 * Class Posts.
 */
final class Posts {

    /**
     * Maximum number of products that could be shown in an autocomplete window.
     */
    const AUTOCOMPLETE_PRODUCTS_LIMIT = 12;

    /**
     * Limit per block (text, tags, categories, attributes).
     */
    const AUTOCOMPLETE_LINKS_LIMIT_PER_BLOCK = 10;

    /**
     * Posts block for infinite scroll.
     */
    const INFINITE_SCROLL_POSTS_BLOCK = 12;

    /**
     * Returns number of posts per page.
     *
     * If infinite scroll is enabled, returns the predefined block value,
     * otherwise speedsearch-setting-posts-per-page option, or global posts_per_page option.
     *
     * @return int Number of posts per page.
     *
     * @throws Exception Exception.
     */
    public static function get_posts_per_page_number() {
        if ( '1' === SpeedSearch::$options->get( 'setting-is-infinite-scroll-enabled' ) ) {
            $posts_per_page = self::INFINITE_SCROLL_POSTS_BLOCK;
        } else {
            $posts_per_page_from_theme_integration = apply_filters( 'loop_shop_per_page', 0 );
            $posts_per_page                        = (int) ( $posts_per_page_from_theme_integration ? $posts_per_page_from_theme_integration : SpeedSearch::$options->get( 'setting-posts-per-page' ) );
        }

        return $posts_per_page;
    }

    /**
     * Paginate posts. If infinite scroll is enabled, returns more posts for single request.
     *
     * @param array $posts_ids IDs of posts to paginate.
     * @param int   $page      Page.
     *
     * @return array IDs of posts.
     *
     * @throws Exception Exception.
     */
    public static function paginate( array $posts_ids, $page ) {
        $posts_per_page = self::get_posts_per_page_number();
        return array_slice( $posts_ids, ( $page - 1 ) * $posts_per_page, $posts_per_page );
    }

    /**
     * Returns post classes.
     *
     * @param int $product_id Post ID.
     *
     * @return array The list of post classes.
     */
    public static function get_post_classes( $product_id ) {
        $args    = [
            'p'         => $product_id,
            'post_type' => 'product',
        ];
        $product = new WP_Query( $args );

        if ( $product->have_posts() ) {
            $product->the_post();

            ob_start();
            wc_get_template_part( 'content', 'product' );
            $product_html = ob_get_clean();

            // Get .speedsearch-single-post from HTML.

            if ( $product_html ) { // In case there is no product HTML for some reason (like some filter from some plugin is mangling the template path).
                $doc = new \DOMDocument();
                libxml_use_internal_errors( true ); // Suppress HTML parsing errors.
                $doc->loadHTML( $product_html );
                $xpath = new \DOMXPath( $doc );
                $nodes = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' speedsearch-single-post ')]" );

                wp_reset_postdata();

                /**
                 * Usually, extra 'wc_get_product_class' is not needed,
                 * but on Ocean WP, for example, it's necessary, as it has span_1_of_3 missing for some yet-do-discover reason.
                 *
                 * But as a temp. hotfix, it's ok.
                 */
                if ( $nodes->length > 0 ) {
                    return array_values(
                        array_unique(
                            array_merge(
                                explode( ' ', $nodes[0]->getAttribute( 'class' ) ),
                                wc_get_product_class( 'speedsearch-single-post', $product_id )
                            )
                        )
                    );
                }
            }
        }

        return wc_get_product_class( 'speedsearch-single-post', $product_id ); // Just a fallback in case something goes wrong.
    }

    /**
     * Returns post data.
     *
     * @param int $post_id ID of the post.
     *
     * @return array Post data, or empty array if couldn't get the data.
     *
     * @throws Exception Exception.
     */
    public static function get_post_data( $post_id ) {
        /**
         * Set global product to make the SpeedSearch filters to work correctly.
         *
         * @see Posts_Data_Final_Output::FILTERS_TO_USE
         */
        global $product;

        $data    = [];
        $product = wc_get_product( $post_id );
        if ( $product ) {
            do_action( 'speedsearch_before_product_print', $post_id );

            // Post fields list.

            $posts_fields_list = [];
            $posts_fields      = SpeedSearch::$options->get( 'setting-posts-fields' );
            foreach ( $posts_fields as $field ) {
                if ( array_key_exists( 'type', $field ) ) {
                    $posts_fields_list[] = $field['type'];
                }
            }

            $post_data = [
                'permalink' => $product->get_permalink(),
                'id'        => $product->get_id(),
                'type'      => $product->get_type(),
                'classes'   => implode( ' ', self::get_post_classes( $product->get_id() ) ),
            ];

            if ( in_array( 'author', $posts_fields_list, true ) ) {
                $author_id           = get_post_field( 'post_author', $post_data['id'] );
                $author_name         = get_the_author_meta( 'display_name', $author_id );
                $post_data['author'] = $author_name;
            }

            if ( in_array( 'categories', $posts_fields_list, true ) ) {
                $category_names = [];
                $category_ids   = $product->get_category_ids();
                foreach ( $category_ids as $category_id ) {
                    $category_names[] = get_term( $category_id )->name;
                }
                $post_data['categories'] = $category_names;
            }

            if ( in_array( 'price', $posts_fields_list, true ) ) {
                $price = [];
                $b     = preg_split( '@</del>@', $product->get_price_html() );
                if ( count( $b ) > 1 ) {
                    $price['sale']    = wp_strip_all_tags( array_shift( $b ) );
                    $price['regular'] = wp_strip_all_tags( implode( '', $b ) );
                } else {
                    $price['regular'] = wp_strip_all_tags( array_shift( $b ) );
                }
                $post_data['price'] = $price;
            }

            if ( in_array( 'sku', $posts_fields_list, true ) ) {
                $post_data['sku'] = $product->get_sku();
            }

            if ( in_array( 'title', $posts_fields_list, true ) ) {
                $post_data['title'] = $product->get_title();
            }

            $post_data['image'] = $product->get_image();

            // Variable product data.
            // Variable products "view" is shown only where is image for the post.

            if ( $product->is_type( 'variable' ) ) {
                $variations       = $product->get_available_variations( 'objects' );
                $variations_count = count( $variations );
                if ( $variations_count ) {
                    $variations_to_display       = array_values(
                        array_filter(
                            $variations,
                            function( $variation ) {
                                $variation_data = $variation->get_data();
                                return isset( $variation_data['image_id'] ) && $variation->get_data()['image_id'];
                            }
                        )
                    );
                    $variations_to_display_count = count( $variations_to_display );

                    $variable_products_show_limit                   = 3;
                    $is_more_variations_than_limit                  = $variations_to_display_count > $variable_products_show_limit;
                    $post_data['variations']                        = [];
                    $post_data['variations']['variationsOverLimit'] = $variations_count > $variable_products_show_limit ? $variations_count + 1 - $variable_products_show_limit : 0;
                    $post_data['variations']['entities']            = [];

                    // Send only 2 products when there is more variations than limit, so decrement by one to send only 2 instead of default 3 limit.
                    for ( $i = 0; $i < $variations_to_display_count && $i < $variable_products_show_limit - $is_more_variations_than_limit ? 1 : 0; $i ++ ) {
                        $variation = $variations_to_display[ $i ];

                        $product = $variation; // Set global product to make the SpeedSearch filters to work correctly.

                        $variation_data = [
                            'permalink' => $variation->get_permalink(),
                            'id'        => $variation->get_id(),
                        ];

                        if ( in_array( 'price', $posts_fields_list, true ) ) {
                            $b = preg_split( '@</del>@', $variation->get_price_html() );
                            if ( count( $b ) > 1 ) {
                                $price['sale']    = wp_strip_all_tags( array_shift( $b ) );
                                $price['regular'] = wp_strip_all_tags( implode( '', $b ) );
                            } else {
                                $price['regular'] = wp_strip_all_tags( array_shift( $b ) );
                            }
                            $variation_data['price'] = $price;
                        }

                        if ( in_array( 'sku', $posts_fields_list, true ) ) {
                            $variation_data['sku'] = $variation->get_sku();
                        }

                        if ( in_array( 'title', $posts_fields_list, true ) ) {
                            $variation_data['title'] = $variation->get_name();
                        }

                        $variation_data['image'] = $variation->get_image();

                        $post_data['variations']['entities'][] = $variation_data;
                    }
                }
            }
            $data = $post_data;
        }
        return $data;
    }

    /**
     * Returns a placeholder image URL.
     */
    public static function get_placeholder_image_url() {
        return SPEEDSEARCH_URL . 'media/woocommerce-placeholder-400x400.png';
    }

    /**
     * Returns a placeholder image URL.
     *
     * @param array|false $image {
     *     Array of image data, or boolean false if no image is available.
     *
     *     @type string $0 Image source URL.
     *     @type int    $1 Image width in pixels.
     *     @type int    $2 Image height in pixels.
     *     @type bool   $3 Whether the image is a resized image.
     * }
     */
    public static function return_image_placeholder_for_img_src( $image ) {
        if ( is_array( $image ) ) {
            $image[0] = self::get_placeholder_image_url();
        }
        return $image;
    }

    /**
     * Adds 'speedsearch-placeholder-post' product classes.
     *
     * @param array $classes Product classes.
     *
     * @return array
     */
    public static function add_placeholder_product_classes( $classes ) {
        $classes[] = 'speedsearch-placeholder-post';
        return $classes;
    }

    /**
     * Changes the permalink for placeholder posts.
     *
     * @param string   $permalink The original permalink.
     * @param \WP_Post $post      The post object.
     *
     * @return string Modified permalink.
     */
    public static function change_placeholder_post_permalink( $permalink, $post ) {
        return '#';
    }

    /**
     * Returns a placeholder image URL.
     */
    public static function get_something_went_wrong_image_url() {
        return SPEEDSEARCH_URL . 'media/something-went-wrong.png';
    }

    /**
     * Returns post Element HTML from its data
     *
     * Should be the same as assets/common/posts.js -> speedsearch_getPostElement
     *
     * @see speedsearch.getPostElement
     *
     * @param array $post Post data object.
     *
     * @return string Post element HTML.
     */
    public static function get_post_element_html( array $post ) {

        /**
         * Gets the price HTML for the Post data.
         *
         * @param array $price Price object.
         */
        $get_price_html = function( array $price ) {
            $price_html = '';
            if ( array_key_exists( 'sale', $price ) ) {
                $sale_price    = esc_attr( $price['sale'] );
                $regular_price = esc_attr( $price['regular'] );

                $price_html .= "
                    <del>$sale_price</del><ins>$regular_price</ins>
                ";
            } else {
                $price_html .= $price['regular'];
            }
            return $price_html;
        };

        /**
         * Get variations HTML.
         *
         * @param array $post Post data.
         *
         * @return string HTML
         */
        $get_variations_html = function( array $post ) {
            /**
             * Returns variations counter (for one and two variations (for styling)).
             *
             * @return string
             */
            $get_variations_counter_class = function() use ( $post ) {
                $a_class = '';

                if ( ! $post['variations']['variationsOverLimit'] ) {
                    if ( 2 === count( $post['variations']['entities'] ) ) {
                        $a_class .= 'speedsearch-two-variations';
                    } elseif ( 1 === count( $post['variations']['entities'] ) ) {
                        $a_class .= 'speedsearch-one-variation';
                    }
                }

                return esc_attr( $a_class );
            };

            $html = '';
            if ( array_key_exists( 'variations', $post ) ) {
                $html .= "
                    <span class=\"speedsearch-variations-block {$get_variations_counter_class()}\">
                ";

                foreach ( $post['variations']['entities'] as $variation ) {
                    $permalink = esc_attr( $variation['permalink'] );

                    $html .= "
                        <div class=\"speedsearch-single-variation\">
                            <div class=\"speedsearch-variation-image\">
                                <a href=\"$permalink\" target=\"_blank\">
                                    {$variation['image']}
                                </a>
                            </div>
                        </div>
                    ";
                }

                if ( array_key_exists( 'variationsOverLimit', $post['variations'] ) && $post['variations']['variationsOverLimit'] ) {
                    $html .= "
                        <div class=\"speedsearch-single-variation speedsearch-variation-excess-count-container\">
                            <a href={$post['permalink']} target=\"_blank\" class=\"speedsearch-variation-excess-count\">
                                <span>+{$post['variations']['variationsOverLimit']}</span>
                            </a>
                        </div>
                    ";
                }
            }
            $html .= '</span>';

            return $html;
        };

        $permalink = esc_attr( $post['permalink'] );

        $post_data_str = esc_attr( wp_json_encode( $post ) ); // data-post-data is added for variations event listeners.

        $post_id   = esc_attr( $post['id'] );
        $post_type = esc_attr( $post['type'] );

        $product_classes = $post['classes'];

        $product_tag = HTML::get_product_element_tag();

        $post_html = "
            <$product_tag class=\"$product_classes\" 
                    data-post-data=\"$post_data_str\"
                    data-product-id=\"$post_id\"
                    data-product-type=\"$post_type\">
                <div class=\"speedsearch-post-image-block\">
                    <a href=\"$permalink\" target=\"_blank\">
                        {$post['image']}
                    </a>
                    {$get_variations_html( $post )}
                </div>
        ";

        $settings = Elements_Rendering_Data::get_public_settings();
        foreach ( $settings['postsFields'] as $field ) {
            $field_type  = $field['type'];
            $text_before =
                array_key_exists( 'textBefore', $field ) ?
                '<span class="speedsearch-before-text">' . esc_html( $field['textBefore'] ) . '</span>' : '';

            if ( 'categories' === $field_type ) {
                $categories = esc_html( implode( ', ', $post['categories'] ) );
                $post_html .= "
                    <div class=\"speedsearch-post-field speedsearch-post-category\">
                        $text_before
                        <span class=\"speedsearch-main-text\">$categories</span>
                    </div>
                ";
            } elseif ( 'title' === $field_type ) {
                $title      = esc_html( $post['title'] );
                $post_html .= "
                    <div class=\"speedsearch-post-field speedsearch-post-title\">
                        <a href=\"$permalink\" target=\"_blank\">
                            $text_before
                            <span class=\"speedsearch-main-text\">$title</span>
                        </a>
                    </div>
                ";
            } elseif ( 'price' === $field_type ) {
                $price      = $post['price'];
                $post_html .= "
                    <div class=\"speedsearch-post-field speedsearch-post-price\">
                        <a href=\"$permalink\" target=\"_blank\">
                            $text_before
                            <span class=\"speedsearch-main-text\">{$get_price_html( $price )}</span>
                        </a>
                    </div>
                ";
            } else {
                $field_val = $post[ $field_type ];
                if ( is_string( $field_val ) ) {
                    $field_type = esc_html( $field_type );
                    $field_val  = esc_html( $field_val );

                    $post_html .= "
                        <div class=\"speedsearch-post-field speedsearch-post-$field_type\">
                            $text_before
                            <span class=\"speedsearch-main-text\">$field_val</span>
                        </div>
                    ";
                } elseif ( is_array( $field_val ) && array_key_exists( 'url', $field_val ) ) {
                    $field_type     = esc_attr( $field_type );
                    $field_val_url  = esc_attr( $field_val['url'] );
                    $field_val_name = esc_html( $field_val['name'] );

                    $post_html .= "
                        <div class=\"speedsearch-post-field speedsearch-post-$field_type\">
                            <a href=\"$field_val_url\" target=\"_blank\">
                                $text_before
                                <span class=\"speedsearch-main-text\">$field_val_name</span>
                            </a>
                        </div>
                    ";
                }
            }
        }

        $post_html .= "</$product_tag>";

        return $post_html;
    }

    /**
     * Cache key for posts IDs.
     */
    const IDS_CACHE_KEY = [ 'SpeedSearch\Posts', 'get_ids' ];

    /**
     * Product IDs.
     *
     * @param bool|int $posts_per_page How many IDs to get.
     *
     * @return int[]
     *
     * @throws Exception Exception.
     */
    public static function get_ids( $posts_per_page = false ) {
        $cached_data = Cache::get( wp_json_encode( self::IDS_CACHE_KEY ), $posts_per_page, false );

        if ( false !== $cached_data ) {
            return $cached_data;
        }

        $posts_ids = get_posts(
            [
                'post_type'      => 'product',
                'posts_per_page' => false === $posts_per_page ? self::get_posts_per_page_number() : $posts_per_page, // @codingStandardsIgnoreLine
                'fields'         => 'ids',
                'post_status'    => 'publish',
                'tax_query'      => [
                    [
                        'taxonomy' => 'product_visibility',
                        'field'    => 'name',
                        'terms'    => 'exclude-from-catalog',
                        'operator' => 'NOT IN',
                    ],
                ],
            ]
        );

        Cache::save( wp_json_encode( self::IDS_CACHE_KEY ), $posts_per_page, $posts_ids, false );

        return $posts_ids;
    }

    /**
     * How many products should be on the current page.
     *
     * @param int $total_posts  How many total posts.
     * @param int $current_page Current page.
     *
     * @return int Number of products expected to see on the current page.
     */
    public static function how_many_products_should_be_on_the_current_page( $total_posts, $current_page ) {
        return min(
            self::get_posts_per_page_number(),
            $total_posts - ( self::get_posts_per_page_number() * ( $current_page - 1 ) )
        );
    }

    /**
     * Returns posts settings.
     *
     * @return array
     */
    public static function get_posts_settings() {
        return [
            'postsFields' => SpeedSearch::$options->get( 'setting-posts-fields' ),
            'fieldsTypes' => apply_filters(
                'speedsearch_post_field_content_types',
                [
                    'title'      => __( 'Title', 'speedsearch' ),
                    'price'      => __( 'Price', 'speedsearch' ),
                    'author'     => __( 'Author', 'speedsearch' ),
                    'categories' => __( 'Categories', 'speedsearch' ),
                    'sku'        => __( 'SKU', 'speedsearch' ),
                ]
            ),
        ];
    }
}
