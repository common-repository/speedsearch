<?php
/**
 * A class for custom orderings.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * Class Ordering.
 */
final class Ordering {

    /**
     * SpeedSearch orderings mapping into WC mapping.
     */
    const SPEEDSEARCH_ORDERINGS_MAPPING_INTO_WC_MAPPING = [
        'mostPopular'   => 'popularity',
        'highestRating' => 'rating',
        'newest'        => 'date',
        'lowestPrice'   => 'price',
        'highestPrice'  => 'price-desc',
        'default'       => 'menu_order',
    ];

    /**
     * The list of the properties that can be used for ordering.
     */
    const ORDERING_PROPERTIES = [
        'id',
        'name',
        'minPrice',
        'maxPrice',
        'dateCreated',
        'dateCreatedGmt',
        'dateModified',
        'dateModifiedGmt',
        'status',
        'featured',
        'sku',
        'price',
        'regularPrice',
        'salePrice',
        'onSale',
        'totalSales',
        'stockQuantity',
        'backorders',
        'weight',
        'dimensions',
        'averageRating',
        'ratingCount',
        'categories',
        'attributes',
        'menuOrder',
    ];

    /**
     * Constructor.
     */
    public function __construct() {

        // Ordering options.

        add_filter(
            'woocommerce_default_catalog_orderby_options',
            [ $this, 'woocommerce_catalog_orderby_options' ],
            0
        );

        // Default orderby param.

        add_filter(
            'woocommerce_default_catalog_orderby',
            [ $this, 'get_wc_compliant_default_ordering_name' ],
            0
        );

        // Default customizer value.

        add_filter(
            'customize_dynamic_setting_args',
            [ $this, 'customizer_products_sorting_default_value' ],
            0,
            2
        );

        // Default sorting value (affects the value in the customizer).

        add_filter(
            'pre_option_woocommerce_default_catalog_orderby',
            [ $this, 'change_default_product_sorting_option' ]
        );

        // Detect when the customizer option was saved.

        add_action(
            'pre_update_option_woocommerce_default_catalog_orderby',
            [ $this, 'customizer_default_product_sorting_changed' ],
            10,
            2
        );
    }

    /**
     * WooCommerce catalog orderBy options.
     *
     * @param array $sorting_options Sorting options.
     *
     * @return array Sorting options.
     */
    public function woocommerce_catalog_orderby_options( $sorting_options ) {
        $sorting_options = [];

        foreach ( SpeedSearch::$options->get( 'setting-ordering-options' ) as $ordering_option_slug => $ordering_option ) {
            if ( $ordering_option['enabled'] ) {
                $is_default_wc_option = array_key_exists( $ordering_option_slug, self::SPEEDSEARCH_ORDERINGS_MAPPING_INTO_WC_MAPPING );

                if ( $is_default_wc_option ) {
                    $sorting_options[ self::SPEEDSEARCH_ORDERINGS_MAPPING_INTO_WC_MAPPING[ $ordering_option_slug ] ] = $ordering_option['text'];
                } else {
                    $sorting_options[ $ordering_option_slug ] = $ordering_option['text'];
                }
            }
        }

        return $sorting_options;
    }

    /**
     * Returns the default ordering name.
     *
     * @return string Ordering name.
     */
    public static function get_default_ordering_name() {
        $ordering_options = SpeedSearch::$options->get( 'setting-ordering-options' );

        foreach ( $ordering_options as $ordering_option_slug => $ordering_option ) {
            if (
                isset( $ordering_option['default'] ) &&
                $ordering_option['default']
            ) {
                return $ordering_option_slug;
            }
        }
        return array_key_first( $ordering_options );
    }

    /**
     * Returns WC-compliant default ordering name (i.e. when the sorting name comes from WC, use its slug; otherwise (a custom one), use it).
     *
     * @return string Ordering name.
     */
    public static function get_wc_compliant_default_ordering_name() {
        $default_ordering_by_option = self::get_default_ordering_name();

        return array_key_exists( $default_ordering_by_option, self::SPEEDSEARCH_ORDERINGS_MAPPING_INTO_WC_MAPPING ) ?
            self::SPEEDSEARCH_ORDERINGS_MAPPING_INTO_WC_MAPPING[ $default_ordering_by_option ] : $default_ordering_by_option;
    }

    /**
     * Modify the arguments of the 'woocommerce_default_catalog_orderby' setting.
     *
     * @param array  $setting_args The arguments for the dynamic setting.
     * @param string $setting_id   ID for dynamic setting, usually coming from the 'option_name' or 'theme_mod'.
     *
     * @return array The modified setting arguments.
     */
    public function customizer_products_sorting_default_value( $setting_args, $setting_id ) {
        if ( 'woocommerce_default_catalog_orderby' === $setting_id ) {
            $setting_args['default'] = self::get_wc_compliant_default_ordering_name();

        }
        return $setting_args;
    }

    /**
     * Updates the default ordering option when 'Default product sorting' customizer option was changed.
     *
     * @param mixed $new_value The new value of the option.
     * @param mixed $old_value The old value of the option.
     */
    public function customizer_default_product_sorting_changed( $new_value, $old_value ) {
        if ( $old_value !== $new_value ) {
            $ordering_options_data = SpeedSearch::$options->get( 'setting-ordering-options' );
            foreach ( $ordering_options_data as $ordering_option_slug => &$ordering_option ) {
                $wc_compliant_option_slug = self::get_wc_compliant_option_slug( $ordering_option_slug );

                if ( $new_value === $wc_compliant_option_slug ) {
                    $ordering_option['enabled'] = true; // Just in case it was disabled during the customizing by someone.
                    $ordering_option['default'] = true;
                } else {
                    $ordering_option['default'] = false;
                }
            }
            SpeedSearch::$options->set( 'setting-ordering-options', $ordering_options_data );
        }
    }

    /**
     * Returns WC-compliant option slug.
     *
     * @param string $option_slug Option slug, which can potentially be not WC-compliant.
     *
     * @return string
     */
    public static function get_wc_compliant_option_slug( $option_slug ) {
        return array_key_exists( $option_slug, self::SPEEDSEARCH_ORDERINGS_MAPPING_INTO_WC_MAPPING ) ?
            self::SPEEDSEARCH_ORDERINGS_MAPPING_INTO_WC_MAPPING[ $option_slug ] : $option_slug;
    }

    /**
     * Changes the value of the 'Default product sorting' option.
     *
     * @param mixed $value The current value of the option.
     *
     * @return string The updated value for the option.
     */
    public function change_default_product_sorting_option( $value ) {
        return self::get_wc_compliant_default_ordering_name();
    }
}
