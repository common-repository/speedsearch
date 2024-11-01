<?php
/**
 * A class to add post info to the post publish box.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use WP_Post;

/**
 * Class Publish_Box.
 */
final class Publish_Box {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_custom_box' ] );
    }

    /**
     * Adds custom box.
     */
    public function add_custom_box() {
        if (
            isset( $_SERVER['REQUEST_URI'] ) &&
            ! str_starts_with( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), wp_parse_url( admin_url( 'post-new.php' ), PHP_URL_PATH ) ) && // @codingStandardsIgnoreLine
            isset( $_GET['post'] ) && // @codingStandardsIgnoreLine
            'product' === get_post_type( (int) $_GET['post'] ) // @codingStandardsIgnoreLine
        ) { // A meta-box for when editing a post.
            add_meta_box(
                'speedsearch-standard-editor',
                __( 'SpeedSearch', 'speedsearch' ),
                [ $this, 'print_meta_box_content' ],
                [ 'product' ],
                'side',
                'high',
                [ '__back_compat_meta_box' => true ]
            );
        }
    }

    /**
     * Adds publish-box text.
     *
     * @param WP_Post $post The current post object.
     */
    public function print_meta_box_content( WP_Post $post ) {
        ?>

        <div class="misc-pub-section speedsearch-publish-box" id="speedsearch-search-words-per-product-and-search-result-position">
            <div class="speedsearch-publish-box-content">
                <?php
                $response = Backend_Requests::get_analytics_data( $post->ID );

                if (
                    ! is_wp_error( $response ) &&
                    200 === $response['response']['code']
                ) :
                    $data = (array) json_decode( wp_remote_retrieve_body( $response ), true );

                    ?>
                    <div class="speedsearch-analytics-block">
                        <h4 class="search-analytics-block-heading">
                            <?php esc_html_e( 'Search words:', 'speedsearch' ); ?>
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
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            Analytics\Rendering::print_tbody_trows_for_search_words_for_a_product( $data );
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                else :
                    ?>
                    <?php esc_html_e( "Couldn't fetch the data. Something went wrong.", 'speedsearch' ); ?>
                    <?php
                endif;
                ?>
            </div>
        </div>
        <hr>
        <div class="misc-pub-section speedsearch-publish-box">
            <div class="speedsearch-publish-box-image"></div>
            <div class="speedsearch-publish-box-content">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=speedsearch-settings' ) ); ?>">
                    <?php esc_attr_e( 'Plugin page', 'speedsearch' ); ?>
                </a>
            </div>
        </div>
        <?php
    }
}
