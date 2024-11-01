<?php
/**
 * Analytics rendering.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch\Analytics;

/**
 * A class for analytics rendering.
 */
final class Rendering {

    /**
     * Print the tbody trows for the standard table.
     *
     * @param array $the_data Array, where key is a word and the value is the number of searches.
     *
     * @return void
     */
    public static function print_tbody_trows_standard( $the_data ) {
        $total = array_sum( $the_data );
        $i     = 1;
        foreach ( $the_data as $word => $searches ) {
            ?>
            <tr>
                <td>
                    <?php echo esc_html( $i ) . '.'; ?>
                </td>
                <td>
                    <?php echo esc_html( $word ); ?>
                </td>
                <td>
                    <?php echo esc_html( $searches ); ?>
                </td>
                <?php
                $percent = round( $searches / $total * 100, 2 );
                ?>
                <td class="data-percent-td" data-percent="<?php echo esc_attr( $percent ); ?>">
                    <div class="progress-bar"></div>
                    <?php echo esc_html( $percent ) . '%'; ?>
                </td>
            </tr>
            <?php
            $i ++;
        }
    }

    /**
     * Print the tbody trows for search words for a product.
     *
     * @param array $the_data Data.
     *
     * @return void
     */
    public static function print_tbody_trows_for_search_words_for_a_product( $the_data ) {
        $i = 1;
        foreach ( $the_data as $word => $data ) {
            ?>
            <tr>
                <td>
                    <?php echo esc_html( $i ) . '.'; ?>
                </td>
                <td>
                    <?php echo esc_html( $word ); ?>
                </td>
                <td>
                    <?php echo esc_html( $data['count'] ); ?>
                </td>
            </tr>
            <?php
            $i ++;
        }
    }

    /**
     * Print the tbody trows for the table with post IDs.
     *
     * @param array  $the_data An array of arrays (products data).
     * @param string $column   Which column describes the data.
     *
     * @return void
     */
    public static function print_tbody_trows_with_post_id( $the_data, $column = 'views' ) {
        $total = array_sum( array_column( $the_data, $column ) );
        foreach ( $the_data as $i => $product ) {
            $value = (int) $product[ $column ];

            if ( $value ) {
                ?>
                <tr>
                    <td>
                        <?php echo esc_html( $i + 1 ) . '.'; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_attr( get_the_permalink( $product['id'] ) ); ?>">
                            <?php echo esc_html( $product['name'] ); ?>
                        </a>
                    </td>
                    <td>
                        <?php echo esc_html( $product[ $column ] ); ?>
                    </td>
                    <?php
                    $percent = round( $product[ $column ] / $total * 100, 2 );
                    ?>
                    <td class="data-percent-td" data-percent="<?php echo esc_attr( $percent ); ?>">
                        <div class="progress-bar"></div>
                        <?php echo esc_html( $percent ) . '%'; ?>
                    </td>
                </tr>
                <?php
            }
        }
    }
}
