<?php
/**
 * Plugin Name: CPT Staff
 * Plugin URI: https://github.com/VLSLgithub/cpt-staff
 * Description: Creates the Staff CPT
 * Version: 1.0.0
 * Text Domain: cpt-staff
 * Author: Eric Defore
 * Author URI: https://realbigmarketing.com/
 * Contributors: d4mation
 * GitHub Plugin URI: VLSLgithub/cpt-staff
 * GitHub Branch: master
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'CPT_Staff' ) ) {

	/**
	 * Main CPT_Staff class
	 *
	 * @since	  1.0.0
	 */
	class CPT_Staff {
		
		/**
		 * @var			CPT_Staff $plugin_data Holds Plugin Header Info
		 * @since		1.0.0
		 */
		public $plugin_data;
		
		/**
		 * @var			CPT_Staff $admin_errors Stores all our Admin Errors to fire at once
		 * @since		1.0.0
		 */
		private $admin_errors;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  object self::$instance The one true CPT_Staff
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
				
				$this->admin_errors[] = sprintf( _x( '%s requires v%s of %s or higher to be installed!', 'Outdated Dependency Error', 'cpt-staff' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>WordPress</strong></a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}
			
			if ( ! class_exists( 'RBM_CPTS' ) ||
				! class_exists( 'RBM_FieldHelpers' ) ) {
				
				$this->admin_errors[] = sprintf( _x( 'To use the %s Plugin, both %s and %s must be active as either a Plugin or a Must Use Plugin!', 'Missing Dependency Error', 'cpt-staff' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '<a href="//github.com/realbig/rbm-field-helpers-wrapper/" target="_blank">' . __( 'RBM Field Helpers', 'cpt-staff' ) . '</a>', '<a href="//github.com/realbig/rbm-cpts/" target="_blank">' . __( 'RBM Custom Post Types', 'cpt-staff' ) . '</a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );

			if ( ! defined( 'CPT_Staff_VER' ) ) {
				// Plugin version
				define( 'CPT_Staff_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'CPT_Staff_DIR' ) ) {
				// Plugin path
				define( 'CPT_Staff_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'CPT_Staff_URL' ) ) {
				// Plugin URL
				define( 'CPT_Staff_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'CPT_Staff_FILE' ) ) {
				// Plugin File
				define( 'CPT_Staff_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = CPT_Staff_DIR . '/languages/';
			$lang_dir = apply_filters( 'cpt_staff_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'cpt-staff' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'cpt-staff', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/cpt-staff/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/cpt-staff/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( 'cpt-staff', $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/cpt-staff/languages/ folder
				load_textdomain( 'cpt-staff', $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( 'cpt-staff', false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function require_necessities() {
			
			require_once CPT_Staff_DIR . '/core/cpt/class-cpt-staff.php';
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				'cpt-staff',
				CPT_Staff_URL . 'assets/css/style.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : CPT_Staff_VER
			);
			
			wp_register_script(
				'cpt-staff',
				CPT_Staff_URL . 'assets/js/script.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : CPT_Staff_VER,
				true
			);
			
			wp_localize_script( 
				'cpt-staff',
				'cPTStaff',
				apply_filters( 'cpt_staff_localize_script', array() )
			);
			
			wp_register_style(
				'cpt-staff-admin',
				CPT_Staff_URL . 'assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : CPT_Staff_VER
			);
			
			wp_register_script(
				'cpt-staff-admin',
				CPT_Staff_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : CPT_Staff_VER,
				true
			);
			
			wp_localize_script( 
				'cpt-staff-admin',
				'cPTStaff',
				apply_filters( 'cpt_staff_localize_admin_script', array() )
			);
			
		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true CPT_Staff
 * instance to functions everywhere
 *
 * @since	  1.0.0
 * @return	  \CPT_Staff The one true CPT_Staff
 */
add_action( 'plugins_loaded', 'cpt_staff_load', 11 );
function cpt_staff_load() {

	require_once __DIR__ . '/core/cpt-staff-functions.php';
	CPTSTAFF();

}
