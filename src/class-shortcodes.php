<?php
/**
 * Shortcodes
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use SpeedSearch\Initial_Elements_Rendering\Elements_Rendering_Data;
use WP_Post;

/**
 * Class Shortcodes.
 */
class Shortcodes  {

    /**
     * Shortcodes data.
     *
     * Used by shortcode wrappers.
     *
     * @var array
     */
    public $data;

    /**
     * Populates the data.
     */
    private function populate_data() {
        WC()->add_image_sizes(); // Add WC image sizes for 'thumbnail_image_size'.

        $this->data = [
            'filters'                  => [
                'title'     => __( 'Filters', 'speedsearch' ),
                'shortcode' => 'speedsearch_part_filters',
                'callback'  => [ $this, 'part_filters' ],
            ],
            'main'                     => [
                'title'     => __( 'Main', 'speedsearch' ),
                'shortcode' => 'speedsearch',
                'callback'  => [ $this, 'main' ],
                'arguments' => [
                    'hide_search' => [
                        'type'    => 'bool',
                        'default' => 0,
                        'label'   => __( 'Hide search bar', 'speedsearch' ),
                    ],
                ],
            ],
            'search'                   => [
                'title'     => __( 'Search', 'speedsearch' ),
                'shortcode' => 'speedsearch_search',
                'callback'  => [ $this, 'search' ],
                'arguments' => [
                    'small_size'        => [
                        'type'    => 'bool',
                        'default' => 0,
                        'label'   => __( 'Small size', 'speedsearch' ),
                    ],
                    'align'             => [
                        'type'    => 'select',
                        'default' => 'right',
                        'label'   => __( 'Alignment', 'speedsearch' ),
                        'options' => [
                            'left'   => __( 'Left', 'speedsearch' ),
                            'center' => __( 'Center', 'speedsearch' ),
                            'right'  => __( 'Right', 'speedsearch' ),
                        ],
                    ],
                    'search_in_results' => [
                        'type'    => 'bool',
                        'default' => 0,
                        'label'   => __( 'Search in results', 'speedsearch' ),
                    ],
                ],
            ],
            'categories'               => [
                'title'     => __( 'Categories', 'speedsearch' ),
                'shortcode' => 'speedsearch_part_categories',
                'callback'  => [ $this, 'part_categories' ],
            ],
            'tags'                     => [
                'title'     => __( 'Tags', 'speedsearch' ),
                'shortcode' => 'speedsearch_part_tags',
                'callback'  => [ $this, 'part_tags' ],
            ],
            'posts'                    => [
                'title'     => __( 'Posts', 'speedsearch' ),
                'shortcode' => 'speedsearch_part_posts',
                'callback'  => [ $this, 'part_posts' ],
            ],
            'filter'                   => [
                'title'     => __( 'Filter', 'speedsearch' ),
                'shortcode' => 'speedsearch_part_filter',
                'callback'  => [ $this, 'part_filter' ],
                'arguments' => [
                    'name' => [
                        'type'    => 'select',
                        'default' => '',
                        'label'   => __( 'Filter to display', 'speedsearch' ),
                        'options' => call_user_func(
                            function() {
                                $options      = [
                                    '' => __( 'Please select a filter', 'speedsearch' ),
                                ];
                                $filters_data = Elements_Rendering_Data::get_filters();
                                $filters_raw  = $filters_data['filters'];
                                foreach ( $filters_raw as $filter_slug => $filter_data ) {
                                    $options[ $filter_slug ] = $filter_data['label'];
                                }
                                return $options;
                            }
                        ),
                    ],
                ],
            ],
            'active_filters'           => [
                'title'     => __( 'Active Filters', 'speedsearch' ),
                'shortcode' => 'speedsearch_part_active_filters',
                'callback'  => [ $this, 'part_active_filters' ],
            ],
            'toggle'                   => [
                'title'     => __( 'Toggle', 'speedsearch' ),
                'shortcode' => 'speedsearch_part_toggle',
                'callback'  => [ $this, 'part_toggle' ],
                'arguments' => [
                    'name' => [
                        'type'    => 'select',
                        'default' => '',
                        'label'   => __(
                            'Toggle to display. Note: If the toggle is not active (in general or at least for one of the filters), it will not be shown.',
                            'speedsearch'
                        ),
                        'options' => array_merge(
                            [
                                '' => __( 'Please select a toggle', 'speedsearch' ),
                            ],
                            Elements_Rendering_Data::get_filters()['toggles']['active']
                        ),
                    ],
                ],
            ],
            'recently_viewed_products' => [
                'title'     => __( 'Recently viewed products', 'speedsearch' ),
                'shortcode' => 'speedsearch_recently_viewed_products',
                'callback'  => [ $this, 'recently_viewed_products' ],
                'arguments' => [
                    'show_limit'           => [
                        'type'    => 'number',
                        'min'     => 1,
                        'max'     => SPEEDSEARCH_RECENTLY_VIEWED_PRODUCTS_PRODUCTS_SHOW_LIMIT,
                        'default' => SPEEDSEARCH_RECENTLY_VIEWED_PRODUCTS_PRODUCTS_SHOW_LIMIT,
                        'label'   => __( 'Maximum number of products to show.', 'speedsearch' ),
                    ],
                    'add_most_popular_products_if_limit_is_not_hit' => [
                        'type'    => 'bool',
                        'default' => 1,
                        'label'   => __( 'Add the most popular products if recently viewed products are less than the limit', 'speedsearch' ),
                    ],
                    'thumbnail_image_size' => [
                        'type'    => 'select',
                        'default' => 'woocommerce_thumbnail',
                        'options' => array_combine( get_intermediate_image_sizes(), get_intermediate_image_sizes() ),
                        'label'   => __( 'Thumbnail image size.', 'speedsearch' ),
                    ],
                ],
            ],
        ];

        // Adds default arguments to each shortcode.

        $default_arguments = [
            'html_id' => [
                'type'    => 'string',
                'default' => '',
                'label'   => __( 'HTML ID', 'speedsearch' ),
            ],
        ];
        foreach ( $this->data as $shortcode_name => &$shortcode_data ) {
            if ( ! array_key_exists( 'arguments', $shortcode_data ) ) {
                $shortcode_data['arguments'] = $default_arguments;
            } else {
                $shortcode_data['arguments'] = array_merge( $shortcode_data['arguments'], $default_arguments );
            }
        }
    }

    /**
     * Returns shortcode attribute.
     *
     * @param string $shortcode_name
     *
     * @return array Shortcode attributes.
     */
    private function get_shortcode_attributes( $shortcode_name ) {
        $data                 = $this->data[ $shortcode_name ];
        $shortcode_attributes = [];
        foreach ( $data['arguments'] as $argument_name => $argument_data ) {
            $shortcode_attributes[ $argument_name ] = $argument_data['default'];
        }
        return $shortcode_attributes;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        $this->populate_data();

        // It's impossible to use is_singular() before WP object is initialized.
        add_action( 'wp', [ $this, 'init_shortcodes' ] );
    }

    /**
     * Inits shortcodes.
     *
     * @global WP_Post $post
     */
    public function init_shortcodes() {

        // Adds shortcodes.

        add_shortcode( 'speedsearch', [ $this, 'main' ] );
        add_shortcode( 'saffy', [ $this, 'main' ] ); // Compatability for the old plugin versions.
        add_shortcode( 'woosearch', [ $this, 'main' ] );  // Compatability for the old plugin versions.

        // Autoloader basic shortcodes.

        $basic_shortcodes = [
            'search',
            'part_categories',
            'part_filters',
            'part_tags',
            'part_posts',
            'part_filter',
            'part_active_filters',
            'part_toggle',
            'recently_viewed_products',
        ];
        foreach ( $basic_shortcodes as $shortcode ) {
            add_shortcode( "speedsearch_$shortcode", [ $this, $shortcode ] );
            add_shortcode( "saffy_$shortcode", [ $this, $shortcode ] );     // Compatability for the old plugin versions.
            add_shortcode( "woosearch_$shortcode", [ $this, $shortcode ] ); // Compatability for the old plugin versions.
        }
    }

    /**
     * Displays posts block.
     *
     * @param array $attributes Attributes.
     *
     * @return string
     */
    public function main( $attributes ) {
        $name = 'main';

        $attributes = shortcode_atts(
            $this->get_shortcode_attributes( $name ),
            $attributes,
            'speedsearch'
        );

        return HTML::render_template( 'blocks/main.php', $attributes, true );
    }

    /**
     * Displays search box.
     *
     * @param array $attributes Attributes.
     *
     * @return string
     */
    public function search( $attributes ) {
        $name = 'search';

        $attributes = shortcode_atts(
            $this->get_shortcode_attributes( $name ),
            $attributes,
            'speedsearch_' . __FUNCTION__
        );

        return HTML::render_template( 'parts/search.php', $attributes, true );
    }

    /**
     * Displays all categories.
     *
     * @param array $attributes Attributes.
     *
     * @return string
     */
    public function part_categories( $attributes ) {
        $name = 'categories';

        $attributes = shortcode_atts(
            $this->get_shortcode_attributes( $name ),
            $attributes,
            'speedsearch_' . __FUNCTION__
        );

        return HTML::render_template( 'parts/categories.php', $attributes, true );
    }

    /**
     * Displays all filters.
     *
     * @param array $attributes Attributes.
     *
     * @return string
     */
    public function part_filters( $attributes ) {
        $name = 'filters';

        $attributes = shortcode_atts(
            $this->get_shortcode_attributes( $name ),
            $attributes,
            'speedsearch_' . __FUNCTION__
        );

        return HTML::render_template( 'parts/filters.php', $attributes, true );
    }

    /**
     * Displays all product tags.
     *
     * @param array $attributes Attributes.
     *
     * @return string
     */
    public function part_tags( $attributes ) {
        $name = 'tags';

        $attributes = shortcode_atts(
            $this->get_shortcode_attributes( $name ),
            $attributes,
            'speedsearch_' . __FUNCTION__
        );

        return HTML::render_template( 'parts/tags.php', $attributes, true );
    }

    /**
     * Displays posts.
     *
     * @param array $attributes Attributes.
     *
     * @return string
     */
    public function part_posts( $attributes ) {
        $name = 'posts';

        $attributes = shortcode_atts(
            $this->get_shortcode_attributes( $name ),
            $attributes,
            'speedsearch_' . __FUNCTION__
        );

        return HTML::render_template( 'parts/posts.php', $attributes, true );
    }

    /**
     * Displays a specific filter.
     *
     * @param array $attributes Attributes.
     *
     * @return string
     */
    public function part_filter( $attributes ) {
        $name = 'filter';

        $attributes = shortcode_atts(
            $this->get_shortcode_attributes( $name ),
            $attributes,
            'speedsearch_' . __FUNCTION__
        );

        // Converts filter_name from "Super Color" to "super-color" - a thing more likely to be a filter slug.
        $filter_name = str_replace( ' ', '-', strtolower( $attributes['name'] ) );

        $filters_html = Filters::get_filters_html();

        $out = '<div class="speedsearch-filters-block speedsearch-filters speedsearch-single-filter-block"' . ( $attributes['html_id'] ? ' id="' . $attributes['html_id'] . '"' : '' ) . '>';

        if ( array_key_exists( $filter_name, $filters_html ) ) {
            $out .= $filters_html[ $filter_name ];
        }

        $out .= '</div>';

        return $out;
    }

    /**
     * Displays filters reset button.
     *
     * @param array $attributes Attributes.
     *
     * @return string
     */
    public function part_active_filters( $attributes ) {
        $name = 'active_filters';

        $attributes = shortcode_atts(
            $this->get_shortcode_attributes( $name ),
            $attributes,
            'speedsearch_' . __FUNCTION__
        );

        return HTML::render_template( 'parts/active-filters.php', $attributes, true );
    }

    /**
     * Displays specific toggle.
     *
     * @param array $attributes Attributes.
     *
     * @return string
     */
    public function part_toggle( $attributes ) {
        $name = 'toggle';

        $attributes = shortcode_atts(
            $this->get_shortcode_attributes( $name ),
            $attributes,
            'speedsearch_' . __FUNCTION__
        );

        return HTML::render_template( 'parts/toggle.php', $attributes, true );
    }

    /**
     * Displays specific toggle.
     *
     * @param array $attributes Attributes.
     *
     * @return string
     */
    public function recently_viewed_products( $attributes ) {
        $name = 'recently_viewed_products';

        $attributes = shortcode_atts(
            $this->get_shortcode_attributes( $name ),
            $attributes,
            'speedsearch_' . __FUNCTION__
        );

        return HTML::render_template( 'other/recently-viewed-products.php', $attributes, true );
    }
}
