<?php
/**
 * Class for Filters.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;
use SpeedSearch\Initial_Elements_Rendering\Elements_Rendering_Data;

/**
 * Class Filters.
 */
class Filters {

    /**
     * Returns filter top HTML.
     *
     * @param string  $real_filter_name    Filter slug.
     * @param boolean $is_settings_filter  Whether it is settings filter.
     * @param boolean $is_attribute_filter Whether it is an attribute filter.
     *
     * @return string
     * @throws Exception Exception.
     */
    public static function get_filter_wrap_start_html( $real_filter_name, $is_settings_filter, $is_attribute_filter = false ) {
        $top_html    = self::get_filter_top_html( $is_settings_filter, $is_attribute_filter );
        $filter_data = Elements_Rendering_Data::get_filters()['filters'][ $real_filter_name ];

        $filter_label = esc_attr( $filter_data['label'] );

        return "
            <div class=\"speedsearch-filter-btn-container\">
                <button class=\"speedsearch-filter-btn\"><span>$filter_label</span></button>
            </div>
            <div class=\"speedsearch-filter-fields-container\">
                $top_html
                <div class=\"speedsearch-filter-fields\">
";
    }

    /**
     * Returns filter top HTML.
     *
     * @param string  $real_filter_name   Filter slug.
     * @param boolean $is_settings_filter Whether it is settings filter.
     *
     * @return string
     * @throws Exception Exception.
     */
    public static function get_filter_wrap_end_html( $real_filter_name, $is_settings_filter ) {
        $toggles_html = self::get_toggles_html( $real_filter_name, $is_settings_filter );
        $filters_text = Elements_Rendering_Data::get_filters()['text'];

        $filters_text = array_map(
            function ( $x ) {
                return esc_attr( $x );
            },
            $filters_text
        );

        return "
                $toggles_html
                </div>
                <div class=\"speedsearch-filter-bottom\">
                    <span class=\"speedsearch-filter-button-save\">{$filters_text['save']}</span>
                    <span class=\"speedsearch-filter-button-reset\">{$filters_text['reset']}</span>
                </div>
            </div>
";
    }

    /**
     * Returns filter top HTML.
     *
     * @param boolean $is_settings_filter  Whether it is a settings filter.
     * @param boolean $is_attribute_filter Whether it is an attribute filter.
     *
     * @return string
     * @throws Exception Exception.
     */
    public static function get_filter_top_html( $is_settings_filter, $is_attribute_filter ) {
        $text = Elements_Rendering_Data::get_filters()['text'];

        $search_text = esc_attr( $text['search'] );

        $html = '';
        if ( ! $is_settings_filter && $is_attribute_filter ) {
            $html .= "
                <div class=\"speedsearch-filter-top\">
                    <input
                        class=\"speedsearch-fields-search-input\"
                        type=\"search\"
                        placeholder=\"$search_text\">
                    <span class=\"speedsearch-filter-clear-btn\">
                        <svg xmlns='http://www.w3.org/2000/svg' height='23px' width='23px' focusable='false' fill='currentColor' viewBox='0 0 24 24'>
                            <path d='M16.2 7.8c-.3-.3-.8-.3-1.1 0L12 10.9 8.8 7.8c-.3-.3-.8-.3-1 0-.3.2-.3.7 0 1L11 12l-3.2 3.2c-.3.3-.3.8 0 1.1.1.1.3.2.5.2s.4-.1.5-.2l3.2-3.2 3.2 3.2c.1.1.3.2.5.2s.4-.1.5-.2c.3-.3.3-.8 0-1.1L13.1 12l3.2-3.2c.2-.3.2-.8-.1-1z'></path>
                            <path d='M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.6 0 12 0zm0 22.5C6.2 22.5 1.5 17.8 1.5 12S6.2 1.5 12 1.5 22.5 6.2 22.5 12 17.8 22.5 12 22.5z'></path>
                        </svg>
                    </span>
                </div>
";
        }
        return $html;
    }

    /**
     * Returns filter top HTML.
     *
     * @param bool|string $real_filter_name   Filter slug.
     * @param boolean     $is_settings_filter Whether it is settings filter.
     * @param array       $toggles            Toggles list.
     *
     * @return string
     * @throws Exception Exception.
     */
    public static function get_toggles_html( $real_filter_name = null, $is_settings_filter = false, $toggles = [] ) {
        if ( $is_settings_filter ) {
            return '';
        }

        if ( null !== $real_filter_name ) {
            if ( $real_filter_name ) {
                $filters = Elements_Rendering_Data::get_filters()['filters'];

                if (
                    array_key_exists( $real_filter_name, $filters ) &&
                    array_key_exists( 'toggles', $filters[ $real_filter_name ] )
                ) {
                    $toggles = $filters[ $real_filter_name ]['toggles'];
                }
            } else {
                $toggles = Elements_Rendering_Data::get_filters()['toggles']['active'];
            }
        }

        $html = '';

        foreach (
            $toggles as
            $toggle_field_name => $toggle_name
        ) {
            $class             = SpeedSearch::$options->get( 'setting-display-toggles-as-checkboxes' )
                ? "class='speedsearch-checkbox-like-toggle'" : '';
            $toggle_field_name = esc_attr( $toggle_field_name );
            $toggle_name       = esc_html( $toggle_name );
            $html             .= "
                <div class=\"speedsearch-toggle-container\" $class data-field=\"$toggle_field_name\">
                    <span class=\"toggle-name\">$toggle_name</span>
                    <div class=\"speedsearch-toggle\">
                        <span class=\"speedsearch-a-toggle\"></span>
                    </div>
                </div>
";
        }

        return $html;
    }

    /**
     * Returns all filters HTML in an array.
     *
     * @param bool $display_for_settings Whether to display filters for settings or for real use.
     *
     * @return array Where key is filter name (slug).
     *
     * @throws Exception Exception.
     */
    public static function get_filters_html( $display_for_settings = false ) {
        $filters = []; // All filters HTML (For further sorting).

        $hidden_filters = SpeedSearch::$options->get( 'setting-hidden-filters' );

        $common_args = [
            'display_for_settings' => $display_for_settings,
        ];

        // Sort by.

        $filter_name      = 'sort-by';
        $is_filter_hidden = in_array( $filter_name, $hidden_filters, true );

        $filter_args = [
            'filter_name'      => $filter_name,
            'is_filter_hidden' => $is_filter_hidden,
        ];

        $filters[ $filter_name ] = HTML::render_template(
            'parts/filters/sort-by.php',
            array_merge( $common_args, $filter_args ),
            true
        );

        // Date.

        $filter_name      = 'date';
        $is_filter_hidden = in_array( $filter_name, $hidden_filters, true );

        $filter_args = [
            'filter_name'      => $filter_name,
            'is_filter_hidden' => $is_filter_hidden,
        ];

        $filters[ $filter_name ] = HTML::render_template(
            'parts/filters/date.php',
            array_merge( $common_args, $filter_args ),
            true
        );

        // Price.

        $filter_name      = 'price';
        $is_filter_hidden = in_array( $filter_name, $hidden_filters, true );

        $filter_args = [
            'filter_name'      => $filter_name,
            'is_filter_hidden' => $is_filter_hidden,
        ];

        $filters[ $filter_name ] = HTML::render_template(
            'parts/filters/price.php',
            array_merge( $common_args, $filter_args ),
            true
        );

        // Attributes.

        $attributes = wc_get_attribute_taxonomies();
        foreach ( $attributes as $attribute ) {
            $attribute_name   = $attribute->attribute_name; // Filter name.
            $is_filter_hidden = in_array( $attribute_name, $hidden_filters, true );

            $filter_args = [
                'attribute_name'   => $attribute_name,
                'is_filter_hidden' => $is_filter_hidden,
            ];

            $filters[ $attribute_name ] = HTML::render_template(
                'parts/filters/attribute.php',
                array_merge( $common_args, $filter_args ),
                true
            );
        }

        return $filters;
    }

    /**
     * Displays filters HTML.
     *
     * @param bool $display_for_settings Whether to display all filters (for settings).
     *
     * @throws Exception Exception.
     */
    public static function render( $display_for_settings = false ) {
        $filters_html = self::get_filters_html( $display_for_settings );

        // Prints filters according to their order.

        $filters_order = SpeedSearch::$options->get( 'setting-filters-order' );
        foreach ( $filters_order as $filter_order ) {
            if ( array_key_exists( $filter_order, $filters_html ) ) {
                echo sanitize_user_field(
                    'speedsearch',
                    $filters_html[ $filter_order ],
                    0,
                    'display'
                );

                unset( $filters_html[ $filter_order ] );
            }
        }

        // Prints leftovers (unsorted filters).

        foreach ( $filters_html as $filter_name => $filter_html ) {
            echo sanitize_user_field(
                'speedsearch',
                $filter_html,
                0,
                'display'
            );
        }
    }

    /**
     * Returns all toggles.
     *
     * @return array Where key is field value, and value is the name.
     */
    public static function get_all_toggles() {
        return [
            'featured'           => __( 'Featured', 'speedsearch' ),
            'on_sale'            => __( 'On Sale', 'speedsearch' ),
            'purchasable'        => __( 'Purchasable', 'speedsearch' ),
            'virtual'            => __( 'Virtual', 'speedsearch' ),
            'downloadable'       => __( 'Downloadable', 'speedsearch' ),
            'manage_stock'       => __( 'Manage Stock', 'speedsearch' ),
            'backorders_allowed' => __( 'Backorders Allowed', 'speedsearch' ),
            'backordered'        => __( 'Backordered', 'speedsearch' ),
            'sold_individually'  => __( 'Sold Individually', 'speedsearch' ),
            'shipping_required'  => __( 'Shipping Required', 'speedsearch' ),
            'shipping_taxable'   => __( 'Shipping Taxable', 'speedsearch' ),
            'reviews_allowed'    => __( 'Reviews Allowed', 'speedsearch' ),
        ];
    }
}
