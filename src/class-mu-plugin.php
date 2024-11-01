<?php
/**
 * A class responsible for MU-plugin setup and update.
 *
 * @package SpeedSearch
 */

namespace SpeedSearch;

/**
 * Class Mu_Plugin.
 */
final class Mu_Plugin {

    /**
     * MU plugin name.
     */
    const MU_PLUGIN_NAME = 'speedsearch-ajax-optimizer.php';

    /**
     * Path to the MU plugin dir.
     */
    const PATH_IN_PLUGIN = SPEEDSEARCH_DIR . 'src/mu-plugin/' . self::MU_PLUGIN_NAME;

    /**
     * SpeedSearch MU plugin path in MU plugins dir.
     */
    const PATH_IN_MU_DIR = WPMU_PLUGIN_DIR . '/' . self::MU_PLUGIN_NAME;

    /**
     * Constructor.
     */
    public function __construct() {
        if ( ! self::is_mu_plugin_installed() || self::is_installed_mu_plugin_outdated() ) {
            $this->copy_speedsearch_mu_plugin();
        }

        // Removes MU plugin on plugin deactivation.
        register_deactivation_hook( SPEEDSEARCH_FILE, [ $this, 'delete_speedsearch_mu_plugin' ] );
    }

    /**
     * Checks whether the MU plugin is installed.
     *
     * @return bool
     */
    public function is_mu_plugin_installed() {
        return SpeedSearch::$fs->is_file( self::PATH_IN_MU_DIR );
    }

    /**
     * Checks whether the currently installed MU installed plugin version is outdated.
     *
     * @return bool
     */
    public function is_installed_mu_plugin_outdated() {
        $version_in_mu_dir = get_file_data( self::PATH_IN_MU_DIR, [ 'Version' ] )[0]; // Do this to pass the CodeSniffer rules of kinda violation YODA conditions...

        return get_file_data( self::PATH_IN_PLUGIN, [ 'Version' ] )[0] !== $version_in_mu_dir;
    }

    /**
     * Plugin activation handler.
     *
     * Adds MU plugin on plugin activation.
     */
    public function copy_speedsearch_mu_plugin() {
        if ( ! SpeedSearch::$fs->is_dir( WPMU_PLUGIN_DIR ) ) {
            SpeedSearch::$fs->mkdir( WPMU_PLUGIN_DIR, 0755, true );
        }
        SpeedSearch::$fs->delete( self::PATH_IN_MU_DIR );
        SpeedSearch::$fs->copy( self::PATH_IN_PLUGIN, self::PATH_IN_MU_DIR );
    }

    /**
     * Plugin deactivation handler.
     *
     * Removes MU plugin on plugin deactivation.
     */
    public function delete_speedsearch_mu_plugin() {
        if ( SpeedSearch::$fs->is_file( self::PATH_IN_MU_DIR ) ) {
            SpeedSearch::$fs->delete( self::PATH_IN_MU_DIR );
        }
    }
}
