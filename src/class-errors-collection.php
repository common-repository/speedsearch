<?php
/**
 * A class for errors collection.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * Class Errors_Collection.
 */
final class Errors_Collection {

    /**
     * Previous error handler.
     *
     * That is called after the main error handler finished.
     *
     * @var callable|null
     */
    private $previous_handler;

    /**
     * Convert error number to error constant name.
     *
     * @param int $error_number Error number.
     *
     * @return string|int Error constant or error number if the constant is unknown.
     */
    private function convert_error_number_to_error_constant( $error_number ) {
        switch ( $error_number ) {
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
            case E_ALL:
                return 'E_ALL';
            default:
                return $error_number;
        }
    }

    /**
     * Errors handler.
     *
     * @param int    $errno   Error code.
     * @param string $errstr  Error description.
     * @param string $errfile The file on which the error was encountered.
     * @param int    $errline The line on which the error was encountered.
     *
     * @return false
     */
    public function errors_handler( $errno, $errstr, $errfile, $errline ) {
        if ( strpos( $errfile, SPEEDSEARCH_DIR ) !== false ) {
            update_option(
                'speedsearch-last-plugin-error',
                [ time() => $this->convert_error_number_to_error_constant( $errno ) . ' (' . $errstr . ') in file ' . $errfile . ' on line ' . $errline ]
            );
        }
        // Call the previous error handler, if it's present.
        return is_callable( $this->previous_handler ) ? call_user_func_array( $this->previous_handler, func_get_args() ) : false;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        $this->previous_handler = set_error_handler( function() {} ); // @codingStandardsIgnoreLine
        set_error_handler( [ $this, 'errors_handler' ], E_ALL & ~E_NOTICE ); // @codingStandardsIgnoreLine
    }
}
