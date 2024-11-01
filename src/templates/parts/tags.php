<?php
/**
 * Tags template.
 *
 * Should be the same as "add" function under "renderTags" function.
 *
 * @see /assets/public/script.js -> renderTags -> add.
 *
 * Accepts: $html_id
 *
 * This template can be overridden by copying it to yourtheme/speedsearch/themes/speedsearch_theme/templates/parts/tags.php.
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

if ( ! defined( 'ABSPATH' ) ) { // Exit if accessed directly (for wordpress.org validations pass).
    exit;
}

use SpeedSearch\Initial_Elements_Rendering\Parse_Url_For_Request_Params;
use SpeedSearch\SpeedSearch;
use SpeedSearch\Initial_Elements_Rendering\Elements_Rendering_Data;

if ( '' === SpeedSearch::$options->get( 'setting-display-tags' ) ) {
    return;
}

if ( is_product_taxonomy() && SpeedSearch::$options->get( 'setting-archive-pages-hide-tags' ) ) {
    return;
}

?>

<div class="speedsearch-tags"
    <?php
    echo sanitize_user_field(
        'speedsearch',
        isset( $html_id ) && $html_id ? ' id="' . esc_attr( $html_id ) . '"' : '',
        0,
        'display'
    );
    ?>
>
<div class="speedsearch-tags-container">
    <?php

    $tags_all_data           = Elements_Rendering_Data::get_tags();
    $tags_data               = $tags_all_data['data'];
    $tags_initial_show_limit = Elements_Rendering_Data::TAGS_INITIAL_SHOW_LIMIT;
    $settings                = Elements_Rendering_Data::get_public_settings();
    $translations            = Elements_Rendering_Data::get_public_translations();

    /**
     * Sorts tags by count, and alphabetically if the count is equal.
     */
    $sort_tags_by_count = function() use ( &$tags_data ) {
        usort(
            $tags_data,
            function ( $a, $b ) {
                if ( $a['count'] > $b['count'] ) {
                    return -1;
                } elseif ( $a['count'] === $b['count'] ) {
                    if ( $a['name'] < $b['name'] ) {
                        return -1;
                    } elseif ( $a['name'] === $b['name'] ) {
                        return 0;
                    } else {
                        return 1;
                    }
                } else {
                    return 1;
                }
            }
        );
    };
    $sort_tags_by_count();

    /**
     * Returns a hidden attribute.
     *
     * @param int $i Current tag index.
     *
     * @return string
     */
    $get_hidden_attribute = function( $i ) use ( $tags_initial_show_limit ) {
        $attr = '';
        if ( $i >= $tags_initial_show_limit ) {
            $attr .= 'hidden';
        }
        return $attr;
    };

    /**
     * Returns a disabled tag class.
     *
     * @param array $tag
     *
     * @return string
     */
    $get_is_disabled_class = function( array $tag ) use ( $settings ) {
        $a_class = '';
        if (
            0 === $tag['count'] &&
            ! $settings['hideUnavailableTags']
        ) {
            $a_class .= 'disabled';
        }
        return $a_class;
    };

    foreach ( $tags_data as $i => $tag_data ) :
        $the_tag_link = '?tags=' . esc_attr( $tag_data['slug'] );

        /**
         *  Tags links should have archive URL.
         */
        if (
            // If the visitor is on shop page (not on an archive page).
            is_shop() &&
            // And the visitor did not select a tag yet.
            false === Parse_Url_For_Request_Params::get_url_param( 'tags' ) &&
            // And the setting Hide tag filters on archive pages is not enabled.
            '' === SpeedSearch::$options->get( 'setting-archive-pages-hide-tags' )
        ) {
            $the_tag_archive_link = get_term_link( (int) $tag_data['id'], 'product_tag' );

            if ( ! is_wp_error( $the_tag_archive_link ) ) {
                $the_tag_link = esc_attr( $the_tag_archive_link );
            }
        }

        ?>

        <a href="<?php echo esc_attr( $the_tag_link ); ?>" <?php echo esc_attr( $get_hidden_attribute( $i ) ); ?> data-id="<?php echo esc_attr( $tag_data['id'] ); ?>"
            rel="tag" class="speedsearch-tag <?php echo esc_attr( $get_is_disabled_class( $tag_data ) ); ?>">
                <?php echo esc_attr( $tag_data['name'] ); ?>
        </a>
        <?php
    endforeach;

    /**
     * Adds Expand and Collapse buttons to the tags container if necessary.
     */
    if ( count( $tags_data ) - 1 >= $tags_initial_show_limit ) :
        ?>
        <span class="speedsearch-tag speedsearch-expand-tags">
            <span class="text"><?php echo esc_attr( $translations['expand'] ); ?></span>
            <svg fill="#fff" xmlns="http://www.w3.org/2000/svg" width="10" height="10"><path d="M.8 3L5 6.4 9.2 3l.8.6-5 4-5-4z"/></svg>
        </span>

        <span hidden class="speedsearch-tag speedsearch-collapse-tags">
            <span class="text"><?php echo esc_attr( $translations['collapse'] ); ?></span>
            <svg fill="#fff" xmlns="http://www.w3.org/2000/svg" width="10" height="10"><path d="M9.2 7L5 3.6.8 7 0 6.4l5-4 5 4z"/></svg>
        </span>
        <?php
    endif;
    ?>
</div>
</div>
