<?php
/**
 * A class for sitelinks search box integration.
 *
 * @see https://developers.google.com/search/docs/appearance/structured-data/sitelinks-searchbox
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * Class Sitelinks_Search_Box.
 */
final class Sitelinks_Search_Box {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action(
            'wp',
            function() {
                if ( is_front_page() && ! filter_var( wp_parse_url( home_url(), PHP_URL_HOST ), FILTER_VALIDATE_IP ) ) { // Home URL is not IP.
                    add_action( 'wp_head', [ $this, 'add_json_ld_to_head' ] );
                }
            }
        );
    }

    /**
     * Adds JSON-LD to the head of site homepage.
     */
    public function add_json_ld_to_head() {
        $shop_page  = untrailingslashit( get_permalink( wc_get_page_id( 'shop' ) ) );
        $shop_page .= ( wp_parse_url( $shop_page, PHP_URL_QUERY ) ? '&' : '?' ) . 'text={search_term_string}';

        ?>
        <script
        <?php
        ?>
        type='application/ld+json'>
        {
          "@context": "https://schema.org",
          "@type": "WebSite",
          "url": "<?php echo esc_js( home_url() ); ?>",
          "potentialAction": {
            "@type": "SearchAction",
            "target": {
              "@type": "EntryPoint",
              "urlTemplate": "<?php echo esc_js( $shop_page ); ?>"
            },
            "query-input": "required name=search_term_string"
          }
        }
        </script>
        <?php
    }
}
