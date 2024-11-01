<?php
/**
 * Sort by filter template.
 *
 * Accepts: $is_filter_hidden
 *          $display_for_settings
 *          $filter_name
 *
 * This template can be overridden by copying it to yourtheme/speedsearch/themes/speedsearch_theme/templates/parts/filters/sort-by.php.
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
use SpeedSearch\Initial_Elements_Rendering\Parse_Url_For_Request_Params;
use SpeedSearch\SpeedSearch;

$real_filter_name = $filter_name;
$filter_data      = Elements_Rendering_Data::get_filters()['filters'][ $real_filter_name ];
$filters_text     = Elements_Rendering_Data::get_filters()['text'];

$filter_classes = [];
if ( $is_filter_hidden ) {
    $filter_classes[] = 'speedsearch-hidden-filter';
}
if ( $filter_data['isHiddenWhenNoFiltersSelected'] ) {
    $filter_classes[] = 'speedsearch-hide-when-no-filters-selected';
}

if ( $display_for_settings ) {
    $filter_classes[] = 'speedsearch-settings-filter';
    $filter_name      = 'speedsearch-setting-' . $filter_name;
}

$filters_text = array_map(
    function ( $x ) {
        return esc_attr( $x );
    },
    $filters_text
);

$filter_data['text'] = array_map(
    function ( $x ) {
        return esc_attr( $x );
    },
    $filter_data['text']
);

?>

<div class="speedsearch-filter <?php echo esc_attr( implode( ' ', $filter_classes ) ); ?>" data-name="<?php echo esc_attr( $filter_name ); ?>">
    <?php
    echo sanitize_user_field(
        'speedsearch',
        Filters::get_filter_wrap_start_html( $real_filter_name, $display_for_settings ),
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
        }

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
    } else {
        /**
         * Returns 'active' class if the passed sorting name matches the default value.
         *
         * @param string $sorting_name
         *
         * @return string
         */
        $get_is_active_class = function( $sorting_name ) use ( $filter_data ) {
            $attr = '';

            if ( $filter_data['defaultValue'] === $sorting_name ) {
                $attr .= 'default-value';

                if ( ! Parse_Url_For_Request_Params::get_url_param( 'sort-by' ) ) {
                    $attr .= ' active';
                }
            }

            return $attr;
        };

        foreach ( SpeedSearch::$options->get( 'setting-ordering-options' ) as $ordering_option_slug => $ordering_option ) {
            if ( ! $ordering_option['enabled'] ) {
                continue;
            }

            ?>
            <span data-val="<?php echo esc_attr( $ordering_option_slug ); ?>" class="speedsearch-filter-field <?php echo esc_attr( $get_is_active_class( $ordering_option_slug ) ); ?>">
                <?php echo esc_attr( $ordering_option['text'] ); ?>
            </span>
            <?php
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
