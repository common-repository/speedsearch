<?php
/**
 * Backend Requests.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

use Exception;
use SpeedSearch\Webhooks\Webhooks;
use WP_Error;

/**
 * Class Backend Requests.
 */
final class Backend_Requests {

    /**
     * Requests timeout.
     *
     * @var int
     */
    const TIMEOUT = 60;

    /**
     * Whether at least one no-cache request was done.
     *
     * @var bool
     */
    public static $at_least_one_no_cache_request = false;

    /**
     * Last request args.
     *
     * @var array
     */
    public static $last_request_args;

    /**
     * Last request URL.
     *
     * @var string
     */
    public static $last_request_url;

    /**
     * Capture last request args and URL.
     *
     * @param array  $parsed_args Parsed request args.
     * @param string $url         Request URL.
     */
    public function capture_last_request_args_and_url( array $parsed_args, string $url ) {
        self::$last_request_args = $parsed_args;
        self::$last_request_url  = $url;

        return $parsed_args;
    }

    /**
     * Init.
     */
    public function __construct() {
        // Capture last request args and URL.
        add_action( 'http_request_args', [ $this, 'capture_last_request_args_and_url' ], 10, 2 );
    }

    /**
     * Gets a data from the backend. Main function - an entry point.
     *
     * @param string $endpoint                            Name of the backend endpoint. Can be "search", "autocomplete", "filter-attributes".
     * @param array  $args                                Arguments (categories, filters, attributes etc).
     * @param bool   $return_empty_string_if_not_in_cache Return empty string if the result is not in cache (so do not make any backend request).
     * @param bool   $bypass_cache                        Whether to bypass the cache.
     *
     * @return string|array
     */
    public static function get(
        $endpoint,
        array $args = [],
        $return_empty_string_if_not_in_cache = false,
        $bypass_cache = false
    ) {

        // Try to get the data from the cache first.

        $cached_data = ! $bypass_cache ? Cache::get( $endpoint, $args, false ) : false;

        if ( ! self::$at_least_one_no_cache_request && false === $cached_data ) {
            self::$at_least_one_no_cache_request = true;
        }

        if ( false !== $cached_data ) {
            return $cached_data;
        } elseif (
            $return_empty_string_if_not_in_cache &&
            // For warmer. Even if data is not in cache, we still render everything in HTML after get it from the BE. Thus, page loading can take up to 20 seconds with this param.
            ! isset( $_GET['speedsearch-warmer'] ) // @codingStandardsIgnoreLine
        ) {
            return '';
        }

        $request_start_timestamp = time();

        // Make a request.

        if ( 'autocomplete' === $endpoint ) {
            $response = self::autocomplete( $args );
        } elseif ( 'search' === $endpoint ) {
            $response = self::search( $args );
        } elseif ( 'filter_tags' === $endpoint ) {
            $response = self::filter_tags( $args );
        } elseif ( 'filter_attribute_terms' === $endpoint ) {
            $response = self::filter_attribute_terms( $args );
        } elseif ( 'filter_categories' === $endpoint ) {
            $response = self::filter_categories( $args );
        } elseif ( 'properties' === $endpoint ) {
            $response = self::properties( $args );
        } elseif ( 'sync_status' === $endpoint ) {
            $response = self::sync_status();
        } elseif ( 'autocomplete_search' === $endpoint ) {
            $response = self::autocomplete_search( $args );
        } elseif ( 'stores' === $endpoint ) {
            $response = self::stores();
        } elseif ( 'store_details' === $endpoint ) {
            $response = self::store_details();
        } elseif ( 'process' === $endpoint ) {
            $response = self::get_process( $args );
        } elseif ( 'auth' === $endpoint ) {
            $response = self::auth();
        } elseif ( 'debug_products' === $endpoint ) {
            $response = self::get_debug_products( $args );
        } else {
            return [];
        }

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            $error_object  = [
                'Description'                  => "Can't connect to the backend server",
                'Server'                       => SPEEDSEARCH_SERVER,
                'Timestamp before the request' => $request_start_timestamp,
                'Origin'                       => self::get_request_origin_domain(),
                'Speedsearch-Version'          => SPEEDSEARCH_VERSION,
                'X-License-Key'                => Initial_Setup::get_license_key(),
                'Endpoint'                     => $endpoint,
                'Request Params'               => $args,
                'Error message'                => $error_message,
            ];

            if ( isset( $args['hash'] ) ) {
                SpeedSearch::$json_ajax_cache->flush_for_request_hash( $args['hash'] );
            }

            return [ 'error' => $error_object ];
        } elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            $error_object  = [
                'Description'                  => 'SpeedSearch Backend Response Code is not 200.',
                'Server'                       => SPEEDSEARCH_SERVER,
                'Timestamp before the request' => $request_start_timestamp,
                'Origin'                       => self::get_request_origin_domain(),
                'Speedsearch-Version'          => SPEEDSEARCH_VERSION,
                'X-License-Key'                => Initial_Setup::get_license_key(),
                'Endpoint'                     => $endpoint,
                'Request Params'               => $args,
                'Response Code'                => $response_code,
                'Response Body'                => $response_body,
            ];

            if ( isset( $args['hash'] ) ) {
                SpeedSearch::$json_ajax_cache->flush_for_request_hash( $args['hash'] );
            }

            return [ 'error' => $error_object ];
        } elseif ( array_key_exists( 'body', $response ) ) {
            $response_body = (array) json_decode( wp_remote_retrieve_body( $response ), true );

            // Cache the response.
            unset( $args['hash'] );
            Cache::save( $endpoint, $args, $response_body, false );

            return $response_body;
        }

        return [];
    }

    /**
     * Get the request origin domain.
     *
     * @return string Request origin domain.
     */
    private static function get_request_origin_domain() {
        return isset( $_ENV['SPEEDSEARCH_ORIGIN'] ) ? untrailingslashit( sanitize_text_field( $_ENV['SPEEDSEARCH_ORIGIN'] ) ) : get_home_url();
    }

    /**
     * Converts raw arguments to request params, in the format acceptable by the backend.
     *
     * @param array $args Raw arguments.
     *
     * @return array Request params.
     */
    public static function convert_raw_args_to_request_params( array $args ) {

        // Offset.

        if ( array_key_exists( 'offset', $args ) ) {
            $params['offset'] = $args['offset'];
        }

        // Limit.

        if ( array_key_exists( 'limit', $args ) ) {
            $params['limit'] = $args['limit'];
        }

        // Text.

        if ( array_key_exists( 'text', $args ) ) {
            $params['fullText'] = $args['text'];
        }

        // Search (for autocomplete).

        if ( array_key_exists( 'search', $args ) ) {
            $params['search'] = $args['search'];
        }

        // Categories.

        if ( array_key_exists( 'categories', $args ) ) {
            $params['category'] = $args['categories'];
        }

        // Attributes.

        if ( array_key_exists( 'attributes', $args ) ) {
            $params['attributes'] = $args['attributes'];
        }

        // Tags.

        if ( array_key_exists( 'tags', $args ) ) {
            $params['tag'] = $args['tags'];
        }

        // Properties.

        if ( array_key_exists( 'price', $args ) || array_key_exists( 'date', $args ) || array_key_exists( 'toggles', $args ) ) {
            $params['properties'] = [];

            // Price.

            if ( array_key_exists( 'price', $args ) ) {
                $params['properties']['price'] = $args['price'];
            }

            // Date.

            if ( array_key_exists( 'date', $args ) ) {
                $params['properties']['dateCreatedGmt'] = $args['date'];
            }

            // Toggles.

            if ( array_key_exists( 'toggles', $args ) ) {
                foreach ( $args['toggles'] as $toggle ) {
                    $params['properties'][ $toggle ] = true;
                }
            }
        }

        // Order by.

        if ( array_key_exists( 'sortBy', $args ) ) {
            $params['orderBy'] = self::get_sort_by_property_params( $args['sortBy'] );
        } else {
            $params['orderBy'] = self::get_sort_by_property_params( Ordering::get_default_ordering_name() );
        }

        return $params;
    }

    /**
     * Returns sort property values by the sorting name.
     *
     * @param string $sorting_name Sorting name.
     *
     * @return array Sorting property values.
     */
    public static function get_sort_by_property_params( $sorting_name ) {
        $get_sorting_params = function( $sorting_name ) {
            if ( 'default' === $sorting_name ) {
                return [
                    'menuOrder' => 'asc',
                    'name'      => 'asc',
                ];
            } elseif ( 'newest' === $sorting_name ) {
                return [
                    'dateCreated' => 'desc',
                    'id'          => 'desc',
                ];
            } elseif ( 'oldest' === $sorting_name ) {
                return [
                    'dateCreated' => 'asc',
                    'id'          => 'asc',
                ];
            } elseif ( 'lowestPrice' === $sorting_name ) {
                return [
                    'minPrice' => 'asc',
                    'id'       => 'asc',
                ];
            } elseif ( 'highestPrice' === $sorting_name ) {
                return [
                    'maxPrice' => 'desc',
                    'id'       => 'desc',
                ];
            } elseif ( 'highestRating' === $sorting_name ) {
                return [
                    'averageRating' => 'desc',
                    'ratingCount'   => 'desc',
                    'id'            => 'desc',
                ];
            } elseif ( 'mostPopular' === $sorting_name ) {
                return [
                    'totalSales' => 'desc',
                    'id'         => 'desc',
                ];
            } else {
                $ordering_options = SpeedSearch::$options->get( 'setting-ordering-options' );

                foreach ( $ordering_options as $ordering_option_slug => $ordering_option ) {
                    if ( $sorting_name === $ordering_option_slug ) {
                        $order_by_params = [];

                        foreach ( $ordering_option['sort_by'] as $order_by => $direction ) {
                            if ( str_starts_with( $order_by, 'tag-' ) ) { // Tag.
                                if ( ! isset( $order_by_params['tags'] ) ) {
                                    $order_by_params['tags'] = [];
                                }

                                $order_by_params['tags'][] = [
                                    'id'    => str_replace( 'tag-', '', $order_by ),
                                    'order' => 'desc' === $direction ? 'first' : 'last',
                                ];
                            } else { // Property.
                                $order_by_params[ $order_by ] = $direction;
                            }
                        }

                        return $order_by_params;
                    }
                }

                // If such sorting name wasn't found in the options list, then it's likely was deleted, then fallback to "default".
                return self::get_sort_by_property_params( 'default' );
            }
        };
        return apply_filters( 'speedsearch_get_sort_by_property_params', $get_sorting_params( $sorting_name ), $sorting_name );
    }

    /**
     * Makes request to the backend to get the requested posts IDs.
     *
     * @param array $args Arguments (categories, filters, attributes etc).
     *
     * @return array|WP_Error Array of posts IDs or an error.
     */
    private static function autocomplete( array $args = [] ) {
        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/v3/autocomplete',
            [
                'headers' => $headers,
                'body'    => wp_json_encode( self::convert_raw_args_to_request_params( $args ) ),
                'timeout' => self::TIMEOUT,
            ]
        );
    }

    /**
     * Makes request to the backend to get the requested posts IDs.
     *
     * @param array $args Arguments (categories, filters, attributes etc).
     *
     * @return array|WP_Error Array of posts IDs or an error.
     */
    private static function search( array $args = [] ) {
        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/v2/search',
            [
                'headers' => $headers,
                'body'    => wp_json_encode(
                    array_merge(
                        [ 'showCount' => true ],
                        self::convert_raw_args_to_request_params( $args )
                    )
                ),
                'timeout' => self::TIMEOUT,
            ]
        );
    }

    /**
     * Makes request to the backend to get the products for autocomplete right panel.
     *
     * @param array $args Arguments (categories, filters, attributes etc).
     *
     * @return array|WP_Error Array of autocomplete products or an error.
     */
    private static function autocomplete_search( array $args = [] ) {
        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/v2/autocomplete-search',
            [
                'headers' => $headers,
                'body'    => wp_json_encode( self::convert_raw_args_to_request_params( $args ) ),
                'timeout' => self::TIMEOUT,
            ]
        );
    }

    /**
     * /stores GET endpoint request.
     */
    public static function stores() {
        $headers = [
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        return wp_remote_get(
            SPEEDSEARCH_SERVER . '/stores',
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
            ]
        );
    }

    /**
     * /wc/store/details POST endpoint request to get the store details.
     *
     * Returns the same data as "/stores" GET request but works for the disables stores also.
     */
    public static function store_details() {
        $headers = [
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Content-Type'        => 'application/json; charset=utf-8',
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/wc/store/details',
            [
                'headers' => $headers,
                'body'    => wp_json_encode(
                    [
                        'domain' => preg_replace( '(^https?://)', '', untrailingslashit( self::get_request_origin_domain() ) ),
                    ]
                ),
                'timeout' => self::TIMEOUT,
            ]
        );
    }

    /**
     * /process/{process} GET endpoint request.
     *
     * @param array $args Contains 'process_id'.
     *
     * @return WP_Error|array Process data.
     */
    public static function get_process( $args ) {
        $process_id = $args['process'];

        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
        ];

        return wp_remote_get(
            SPEEDSEARCH_SERVER . "/process/$process_id",
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
            ]
        );
    }

    /**
     * Send admin credentials to /stores PATCH endpoint.
     *
     * @return bool Success or failure.
     */
    public static function send_admin_credentials() {
        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        $user_data_that_activated_the_plugin = SpeedSearch::$options->get( 'user-data-that-activated-the-plugin' );

        $params = [
            'contact' => $user_data_that_activated_the_plugin ?
                $user_data_that_activated_the_plugin :
                Initial_Setup::get_current_user_data_to_send_to_be(),
        ];

        $response = wp_remote_post(
            SPEEDSEARCH_SERVER . '/stores',
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
                'body'    => wp_json_encode( $params ),
                'method'  => 'PATCH',
            ]
        );

        return ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response );
    }

    /**
     * Pause store sync.
     *
     * @param int $pause_for_days For how many days to pause the sync.
     */
    public static function pause_sync( $pause_for_days ) {
        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        $params = [
            'stopSyncUntil' => time() + ( DAY_IN_SECONDS * $pause_for_days ),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/stores',
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
                'body'    => wp_json_encode( $params ),
                'method'  => 'PATCH',
            ]
        );
    }

    /**
     * Store authorization request.
     */
    public static function auth() {
        $headers = [
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
        ];

        $plugin_settings_page = admin_url( 'admin.php?page=speedsearch-settings' );

        return wp_remote_get(
            SPEEDSEARCH_SERVER . "/wc/authorize?returnUrl=$plugin_settings_page",
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
            ]
        );
    }

    /**
     * Makes force sync request to the backend (to sync all the products).
     *
     * @return array|WP_Error Array of posts IDs or an error.
     */
    public static function force_sync() {
        $request_start_timestamp = time();

        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        $response = wp_remote_post(
            SPEEDSEARCH_SERVER . '/sync',
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
            ]
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            $server        = SPEEDSEARCH_SERVER;
            $error_text    = "
                SpeedSearch Error: Can't connect to the backend server
                Timestamp before the request done: $request_start_timestamp
                Endpoint: $server/wc/sync
                Error message: $error_message
            ";

            return [
                'success'  => false,
                'errorMsg' => $error_text,
            ];
        } else {
            return (array) json_decode( wp_remote_retrieve_body( $response ), true );
        }
    }

    /**
     * Makes request to the backend to get the requested posts IDs.
     *
     * @param array $args Arguments (categories, filters, attributes etc).
     *
     * @return array|WP_Error|void Array of posts IDs or an error.
     */
    private static function properties( array $args = [] ) {
        if ( array_key_exists( 'property', $args ) ) {
            $headers = [
                'Content-Type'        => 'application/json; charset=utf-8',
                'Origin'              => self::get_request_origin_domain(),
                'Speedsearch-Version' => SPEEDSEARCH_VERSION,
                'X-License-Key'       => Initial_Setup::get_license_key(),
            ];

            return wp_remote_post(
                SPEEDSEARCH_SERVER . "/properties/{$args['property']}",
                [
                    'headers' => $headers,
                    'body'    => wp_json_encode( self::convert_raw_args_to_request_params( $args ) ),
                    'timeout' => self::TIMEOUT,
                ]
            );
        }
    }

    /**
     * Filters for tags by categories.
     *
     * @param array $args Arguments (categories, filters, attributes etc).
     *
     * @return array|WP_Error Array of tags IDs to show or an error.
     */
    private static function filter_tags( array $args = [] ) {
        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/tags',
            [
                'headers' => $headers,
                'body'    => wp_json_encode( self::convert_raw_args_to_request_params( $args ) ),
                'timeout' => self::TIMEOUT,
            ]
        );
    }

    /**
     * Filters for attribute-terms by categories.
     *
     * @param array $args Arguments (categories, filters, attributes etc).
     *
     * @return array|WP_Error Array of attribute terms IDs to show or an error.

     * @throws Exception Exception.
     */
    private static function filter_attribute_terms( array $args = [] ) {
        if ( ! array_key_exists( 'attributeSlug', $args ) ) {
            throw new Exception( __( 'No attributeSlug provided.', 'speedsearch' ) );
        }

        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
        ];

        $attribute_slug = $args['attributeSlug'];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . "/attribute-terms/pa_$attribute_slug",
            [
                'headers' => $headers,
                'body'    => wp_json_encode( self::convert_raw_args_to_request_params( $args ) ),
                'timeout' => self::TIMEOUT,
            ]
        );
    }


    /**
     * Filters categories
     *
     * @param array $args Arguments (attributes, tags, toggles etc).
     *
     * @return array|WP_Error Array of categories IDs to show or an error.
     */
    private static function filter_categories( array $args = [] ) {
        $headers = [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/categories',
            [
                'headers' => $headers,
                'body'    => wp_json_encode( self::convert_raw_args_to_request_params( $args ) ),
                'timeout' => self::TIMEOUT,
            ]
        );
    }

    /**
     * Activate the store.
     *
     * @return bool Success or failure.
     */
    public static function activate() {
        $store = self::get( 'store_details' );

        if ( isset( $store['error']['Response Code'] ) && 404 === $store['error']['Response Code'] ) { // Store not authorised.
            delete_option( 'speedsearch-synced' );
            delete_option( 'speedsearch-store-was-authorised' );
            delete_option( 'speedsearch-setting-debug-mode-products' );
            delete_transient( 'speedsearch_store_authorized' );

            return true;
        }

        if ( isset( $store['id'] ) ) {
            $store_id = $store['id'];

            $headers = [
                'Speedsearch-Version' => SPEEDSEARCH_VERSION,
                'X-License-Key'       => Initial_Setup::get_license_key(),
            ];

            $response = wp_remote_post(
                SPEEDSEARCH_SERVER . "/wc/store/$store_id/activate",
                [
                    'headers' => $headers,
                    'timeout' => self::TIMEOUT / 2,
                    'method'  => 'PUT',
                ]
            );

            if (
                ! is_wp_error( $response ) &&
                200 === wp_remote_retrieve_response_code( $response )
            ) {
                $response_body = (array) json_decode( wp_remote_retrieve_body( $response ), true );

                if ( isset( $response_body['active'] ) && $response_body['active'] ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Disable store.
     *
     * @param int $store_id ID of the store.
     *
     * @return array|WP_Error The response or WP_Error on failure.
     */
    public static function disable_store( $store_id ) {
        $headers = [
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . "/wc/store/$store_id/disable",
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
                'method'  => 'PUT',
            ]
        );
    }

    /**
     * Delete the store.
     *
     *  @return array|WP_Error The response or WP_Error on failure.
     */
    public static function delete_store() {
        $headers = [
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/stores',
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
                'method'  => 'DELETE',
            ]
        );
    }

    /**
     * Returns sync status.
     *
     * @return array|WP_Error Array of posts IDs or an error.
     */
    public static function sync_status() {
        $headers = [
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        return wp_remote_get(
            SPEEDSEARCH_SERVER . '/sync/status',
            [
                'headers' => $headers,
                // Hard-coded because in some cases BE is slow due to these requests from products-hash, which causes both (site and BE) to hang.
                'timeout' => 15,
            ]
        );
    }

    /**
     * Sends analytics data.
     *
     * @param array $data The data to send.
     *
     * @return array|WP_Error Array of posts IDs or an error.
     */
    public static function send_analytics_data( $data ) {
        $headers = [
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Content-Type'        => 'application/json; charset=utf-8',
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/webhooks/product/stats',
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
                'method'  => 'PUT',
                'body'    => wp_json_encode( $data ),
            ]
        );
    }

    /**
     * Start settings sharing handshake.
     *
     * @param string $guid   GUID of the settings file.
     * @param string $secret Secret of the file.
     *
     * @return array|WP_Error Response or a error.
     */
    public static function start_settings_sharing_handshake( $guid, $secret ) {
        SpeedSearch::$options->set( Webhooks::WEBHOOK_SECRET_OPTION_NAME, '9675a16faccab47a674da16fe32ee306' );

        $headers = [
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Content-Type'        => 'application/json; charset=utf-8',
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/webhooks/settings/credentials',
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
                'body'    => wp_json_encode(
                    [
                        'guid'   => $guid,
                        'secret' => $secret,
                    ]
                ),
            ]
        );
    }

    /**
     * Returns analytics data.
     *
     * @param int|null $product_id  Product ID.
     *
     * @return array|WP_Error Response or a error.
     */
    public static function get_analytics_data( $product_id = null ) {
        $headers = [
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        return wp_remote_get(
            $product_id ? ( SPEEDSEARCH_SERVER . '/search/stats/' . $product_id ) : ( SPEEDSEARCH_SERVER . '/search/stats' ),
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
            ]
        );
    }

    /**
     * Set debug products.
     *
     * @param array $product_ids Product IDs.
     *
     * @return array|WP_Error Response or a error.
     */
    public static function set_debug_products( $product_ids ) {
        $headers = [
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/stores/debug-products',
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
                'method'  => 'PUT',
                'body'    => wp_json_encode(
                    [
                        'ids' => array_map( 'intval', $product_ids ),
                    ]
                ),
            ]
        );
    }

    /**
     * Get debug products.
     *
     * @param array $args Arguments (categories, filters, attributes etc).
     *
     * @return array|WP_Error Response or a error.
     */
    private static function get_debug_products( $args ) {
        $headers = [
            'Origin'              => self::get_request_origin_domain(),
            'Speedsearch-Version' => SPEEDSEARCH_VERSION,
            'X-License-Key'       => Initial_Setup::get_license_key(),
            'Authorization'       => 'Bearer ' . SpeedSearch::$options->get( Webhooks::WEBHOOK_SECRET_OPTION_NAME ),
        ];

        return wp_remote_post(
            SPEEDSEARCH_SERVER . '/stores/debug-products',
            [
                'headers' => $headers,
                'timeout' => self::TIMEOUT,
                'body'    => wp_json_encode(
                    self::convert_raw_args_to_request_params( $args )
                ),
            ]
        );
    }
}
