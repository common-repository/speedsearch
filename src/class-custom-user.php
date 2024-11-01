<?php
/**
 * A class to create custom user.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * Class Custom_User.
 */
final class Custom_User {

    /**
     * The login of the user.
     */
    const USER_LOGIN = 'speedsearch-plugin';

    /**
     * The user role.
     */
    const USER_ROLE = 'administrator';

    /**
     * Constructor.
     */
    public function __construct() {

        // Create a custom user if it does not exist.

        if ( ! self::does_the_user_exist() ) {
            self::create_a_speedsearch_admin_user();
        }

        // Forbid the change of the speedsearch admin user role.
        add_action( 'set_user_role', [ $this, 'forbid_speedsearch_user_role_change' ], 10, 2 );

        // Forbid the user deletion.
        add_action( 'delete_user', [ $this, 'forbid_user_deletion' ] );

        // Admin user edit screen notice that not allowed to change the role.

        add_action(
            'current_screen',
            function () {
                $screen = get_current_screen();

                if (
                    'user-edit' === $screen->base &&
                    isset( $_GET['user_id'] ) && // @codingStandardsIgnoreLine
                    (int) SpeedSearch::$options->get( 'plugin-user-id' ) === (int) $_GET['user_id'] // @codingStandardsIgnoreLine
                ) {
                    $this->show_cant_modify_user_admin_notice();
                }
            }
        );
    }

    /**
     * Whether the SpeedSearch user exists.
     *
     * @return bool
     */
    public static function does_the_user_exist() {
        $speedsearch_user_id = (int) SpeedSearch::$options->get( 'plugin-user-id' );
        $speedsearch_user    = $speedsearch_user_id ? get_user_by( 'ID', $speedsearch_user_id ) : null;

        return $speedsearch_user_id && $speedsearch_user;
    }

    /**
     * Get the custom user ID.
     *
     * Also checks if it's created, and if not, creates it.
     *
     * @return int
     */
    public static function get_id() {
        if ( ! self::does_the_user_exist() ) {
            self::create_a_speedsearch_admin_user();
        }

        return (int) SpeedSearch::$options->get( 'plugin-user-id' );
    }

    /**
     * Creates a SpeedSearch admin user for the webhooks permissions.
     */
    private static function create_a_speedsearch_admin_user() {
        $user_id = wp_insert_user(
            [
                'user_login' => self::USER_LOGIN,
                'user_pass'  => wp_generate_password(),
                'role'       => self::USER_ROLE,
            ]
        );

        // If the user with this login already exists.
        if ( is_wp_error( $user_id ) && array_key_exists( 'existing_user_login', $user_id->errors ) ) {
            $user = get_user_by( 'login', self::USER_LOGIN );
            if ( $user ) {
                $user_id = $user->ID;
            }
        }

        if ( ! is_wp_error( $user_id ) && $user_id ) {
            SpeedSearch::$options->set( 'plugin-user-id', $user_id );
        }
    }

    /**
     * Forbid the speedsearch user role change.
     *
     * @param int    $user_id The user ID.
     * @param string $role    The new role.
     */
    public function forbid_speedsearch_user_role_change( $user_id, $role ) {
        if (
            self::USER_ROLE !== $role &&
            (int) SpeedSearch::$options->get( 'plugin-user-id' ) === $user_id
        ) {
            $speedsearch_user = get_user_by( 'ID', $user_id );
            $speedsearch_user->set_role( self::USER_ROLE );
        }
    }

    /**
     * Forbid the user deletion.
     *
     * @param int $id ID of the user to delete.
     */
    public function forbid_user_deletion( $id ) {
        if ( (int) SpeedSearch::$options->get( 'plugin-user-id' ) === $id ) {
            wp_die(
                esc_html(
                    sprintf(
                        /* translators: %s is a username. */
                        __( "You can't delete \"%s\" user. Otherwise, SpeedSearch will stop working.", 'speedsearch' ),
                        self::USER_LOGIN
                    )
                )
            );
        }
    }

    /**
     * Show admin notice that can't modify the current user.
     *
     * @param bool $error Whether error or just warning.
     */
    public function show_cant_modify_user_admin_notice( $error = false ) {
        $class = $error ? 'error is-dismissible' : 'warning';

        add_action(
            'admin_notices',
            function() use ( $class, $error ) {
                ?>
                <div class="notice notice-<?php echo esc_attr( $class ); ?>">
                    <p>
                    <b>
                    <?php
                            $error ?
                                esc_html_e( 'SpeedSearch error:', 'speedsearch' ) :
                                esc_html_e( 'SpeedSearch:', 'speedsearch' );
                    ?>
                    </b>
                    <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %s is a username. */
                                __( "You can't change the role of \"%s\" user or delete it. Otherwise, SpeedSearch will stop working.", 'speedsearch' ),
                                self::USER_LOGIN
                            )
                        )
                    ?>
                    </p>
                </div>
                <?php
            }
        );
    }

    /**
     * Delete a custom user.
     */
    public static function delete() {
        $speedsearch_user_id = (int) SpeedSearch::$options->get( 'plugin-user-id' );

        SpeedSearch::$options->delete( 'plugin-user-id' ); // Delete option first to unblock the deletion.

        if ( $speedsearch_user_id ) {
            if ( ! function_exists( 'wp_delete_user' ) ) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
            }
            wp_delete_user( $speedsearch_user_id );
        }
    }
}
