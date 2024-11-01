<?php
/**
 * Attributes filter template.
 *
 * Accepts: $is_filter_hidden
 *          $display_for_settings
 *          $attribute_name
 *
 * This template can be overridden by copying it to yourtheme/speedsearch/themes/speedsearch_theme/templates/parts/filters/attribute.php.
 *
 * HOWEVER, on occasion SpeedSearch will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package SpeedSearch
 * @version 1.6.20
 */

/**
 * Suppress inspection warning for potentially undefined variables.
 *
 * @noinspection PhpUndefinedVariableInspection
 */

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

use SpeedSearch\Filters;
use SpeedSearch\Initial_Elements_Rendering\Elements_Rendering_Data;
use SpeedSearch\SpeedSearch;

$real_filter_name = $attribute_name;
$filter_data      = Elements_Rendering_Data::get_filters()['filters'][ $real_filter_name ];
$filters_text     = Elements_Rendering_Data::get_filters()['text'];
$settings         = Elements_Rendering_Data::get_public_settings();

$filter_classes = [];
if ( $is_filter_hidden ) {
    $filter_classes[] = 'speedsearch-hidden-filter';
}
if ( $filter_data['isSingleSelect'] ) {
    $filter_classes[] = 'speedsearch-single-select-filter';
}
if ( $filter_data['isOnlySwatchesShow'] ) {
    $filter_classes[] = 'speedsearch-only-swatches-filter';
}
if ( $filter_data['isHiddenWhenNoFiltersSelected'] ) {
    $filter_classes[] = 'speedsearch-hide-when-no-filters-selected';
}

if ( $display_for_settings ) {
    $filter_classes[] = 'speedsearch-settings-filter';
    $attribute_name   = 'speedsearch-setting-' . $attribute_name;
}

$filters_text = array_map(
    function ( $x ) {
        return esc_attr( $x );
    },
    $filters_text
);

?>

<div class="speedsearch-filter speedsearch-attribute-filter <?php echo esc_attr( implode( ' ', $filter_classes ) ); ?>"
        data-name="<?php echo esc_attr( SpeedSearch::$options->get( 'setting-attributes-url-params-prefix' ) ); ?><?php echo esc_attr( $attribute_name ); ?>"
        data-attribute-filter-name="<?php echo esc_attr( $filter_data['label'] ); ?>">
    <?php
    echo sanitize_user_field(
        'speedsearch',
        Filters::get_filter_wrap_start_html( $real_filter_name, $display_for_settings, true ),
        0,
        'display'
    );

    if ( $display_for_settings ) {
        if ( $filter_data['isHidden'] ) {
            echo sanitize_user_field(
                'speedsearch',
                "<span data-val=\"show-filter\" class=\"speedsearch-filter-field\">{$filters_text['showFilter']}</span>",
                0,
                'display'
            );
        } else {
            echo sanitize_user_field(
                'speedsearch',
                "<span data-val=\"hide-filter\" class=\"speedsearch-filter-field\">{$filters_text['hideFilter']}</span>",
                0,
                'display'
            );

            if ( $filter_data['isHiddenWhenNoFiltersSelected'] ) {
                echo sanitize_user_field(
                    'speedsearch',
                    "<span data-val=\"show-filter-when-no-filters-selected\" class=\"speedsearch-filter-field\">{$filters_text['showWhenNoFiltersSelected']}</span>",
                    0,
                    'display'
                );
            } else {
                echo sanitize_user_field(
                    'speedsearch',
                    "<span data-val=\"hide-filter-when-no-filters-selected\" class=\"speedsearch-filter-field\">{$filters_text['hideWhenNoFiltersSelected']}</span>",
                    0,
                    'display'
                );
            }

            if ( $filter_data['isSingleSelect'] ) {
                echo sanitize_user_field(
                    'speedsearch',
                    "<span data-val=\"deactivate-single-select\" class=\"speedsearch-filter-field\">{$filters_text['multipleOptionsFilter']}</span>",
                    0,
                    'display'
                );
            } else {
                echo sanitize_user_field(
                    'speedsearch',
                    "<span data-val=\"activate-single-select\" class=\"speedsearch-filter-field\">{$filters_text['singleOptionFilter']}</span>",
                    0,
                    'display'
                );
            }

            if ( $filter_data['isOnlySwatchesShow'] ) {
                echo sanitize_user_field(
                    'speedsearch',
                    "<span data-val=\"show-swatches-with-text\" class=\"speedsearch-filter-field\">{$filters_text['showSwatchesWithText']}</span>",
                    0,
                    'display'
                );
            } else {
                echo sanitize_user_field(
                    'speedsearch',
                    "<span data-val=\"show-only-swatches\" class=\"speedsearch-filter-field\">{$filters_text['showOnlySwatches']}</span>",
                    0,
                    'display'
                );
            }
        }
    } else {
        /**
         * Returns a disabled attribute class.
         *
         * @param array $attribute Attribute object.
         *
         * @return string
         */
        $get_is_disabled_class = function( array $attribute ) use ( $display_for_settings, $settings ) {
            $a_class = '';
            if ( ! $display_for_settings && 'show-disabled' === $settings['howToTreatEmpty'] && 0 === $attribute['count'] ) {
                $a_class .= 'disabled';
            }
            return $a_class;
        };

        /**
         * Returns whether the filter has some swatches, and of which type
         *
         * @param array $attribute Attribute object.
         *
         * @return string "", or "has-swatch-image" or "has-swatch-color", or "has-swatch-colors".
         */
        $get_has_some_swatches_class = function( array $attribute ) {
            $html = '';
            if ( array_key_exists( 'swatch', $attribute ) ) {
                $swatch = $attribute['swatch'];
                if ( array_key_exists( 'url', $swatch ) ) {
                    $html .= 'has-swatch-image';
                } else {
                    if ( array_key_exists( '1', $swatch ) && array_key_exists( '2', $swatch ) ) {
                        $html .= 'has-swatch-colors';
                    } else {
                        $html .= 'has-swatch-color';
                    }
                }
            }
            return $html;
        };

        /**
         * Returns swatch image HTML.
         *
         * @param array $attribute Attribute object.
         *
         * @return string
         */
        $get_attribute_swatch_image_html = function( array $attribute ) {
            /**
             * Wraps in IMG container.
             *
             * @param string html
             *
             * @return string HTML.
             */
            $wrap_img_container = function( $html ) {
                return "<span class=\"speedsearch-swatch-image\">$html</span>";
            };

            $html = '';
            if ( array_key_exists( 'swatch', $attribute ) ) {
                $swatch = $attribute['swatch'];

                $swatch = array_map(
                    function ( $x ) {
                        return esc_attr( $x );
                    },
                    $swatch
                );

                if ( array_key_exists( 'url', $swatch ) ) { // Image.
                    $html .= $wrap_img_container( "<img alt=\"{$swatch['alt']}\" src=\"{$swatch['url']}\">" );
                } else { // Color(s).
                    if ( is_array( $swatch ) && array_key_exists( '1', $swatch ) ) {
                        $html .= $wrap_img_container( "<span class=\"color\" data-color-num=\"1\" style=\"background-color: {$swatch['1']}\"></span>");
                    }
                    if ( is_array( $swatch ) && array_key_exists( '2', $swatch ) ) {
                        $html .= $wrap_img_container( "<span class=\"color\" data-color-num=\"2\" style=\"background-color: {$swatch['2']}\"></span>" );
                    }
                }
            }
            return $html;
        };

        if ( array_key_exists( 'attributes', $filter_data ) ) {
            foreach ( $filter_data['attributes'] as $slug => $attribute ) {
                if ( $display_for_settings || ! ( 'hide' === $settings['howToTreatEmpty'] && 0 === $attribute['count'] ) ) {
                    $slug           = esc_attr( $slug );
                    $attribute_name = esc_attr( $attribute['name'] );
                    echo sanitize_user_field(
                        'speedsearch',
                        "<span data-val=\"$slug\" data-attribute-term-name=\"$attribute_name\"
                            class=\"speedsearch-filter-field speedsearch-attribute-term-field {$get_is_disabled_class( $attribute )} {$get_has_some_swatches_class( $attribute )}\">
                            <span class=\"speedsearch-field-text\">$attribute_name</span>
                                {$get_attribute_swatch_image_html( $attribute )}
                            </span>",
                        0,
                        'display'
                    );
                }
            }
        }
    }

    echo sanitize_user_field(
        'speedsearch',
        Filters::get_filter_wrap_end_html( $real_filter_name, $display_for_settings ),
        0,
        'display'
    );
    ?>
</div>
