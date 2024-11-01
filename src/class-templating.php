<?php
/**
 * A class with helper methods for templates printing.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * Class Templating.
 */
final class Templating {
    /**
     * Generates a <select> element with options and optgroups from an array and sets the selected value.
     *
     * @param string $name           The name attribute of the <select> element.
     * @param array  $options        The options and optgroups for the <select> element as a nested associative array.
     * @param string $selected_value The value of the selected option.
     * @param bool   $disabled       If the element is disabled.
     *
     * @return void
     */
    public static function print_dropdown( $name, $options, $selected_value, $disabled ) {
        $disabled = $disabled ? 'disabled' : '';

        echo sanitize_user_field(
            'speedsearch',
            "<select class='speedsearch-input speedsearch-select-input' name='$name' $disabled>",
            0,
            'display'
        );

        $optgroup_classes = isset( $options['optgroups'] ) ? $options['optgroups'] : [];
        $option_values    = isset( $options['options'] ) ? $options['options'] : $options;

        foreach ( $option_values as $optgroup_class => $optgroup_options ) {
            if ( isset( $optgroup_classes[ $optgroup_class ] ) ) {
                $optgroup_label = $optgroup_classes[ $optgroup_class ];
                echo sanitize_user_field(
                    'speedsearch',
                    "<optgroup label='$optgroup_label' class='$optgroup_class'>",
                    0,
                    'display'
                );
            }

            if ( is_array( $optgroup_options ) ) {
                foreach ( $optgroup_options as $value => $label ) {
                    $selected = ( $value === $selected_value ) ? 'selected' : '';
                    echo sanitize_user_field(
                        'speedsearch',
                        "<option value='$value' $selected>$label</option>",
                        0,
                        'display'
                    );
                }
            } else {
                $value    = $optgroup_class;
                $label    = $optgroup_options;
                $selected = ( $value === $selected_value ) ? 'selected' : '';
                echo sanitize_user_field(
                    'speedsearch',
                    "<option value='$value' $selected>$label</option>",
                    0,
                    'display'
                );
            }

            if ( isset( $optgroup_classes[ $optgroup_class ] ) ) {
                echo sanitize_user_field(
                    'speedsearch',
                    '</optgroup>',
                    0,
                    'display'
                );
            }
        }

        echo sanitize_user_field(
            'speedsearch',
            '</select>',
            0,
            'display'
        );
    }
}
