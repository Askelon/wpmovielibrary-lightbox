<?php
/**
 * WPMovieLibrary-FancyBox
 *
 * @package   WPMovieLibrary-FancyBox
 * @author    Charlie MERLAND <charlie@caercam.org>
 * @license   GPL-3.0
 * @link      http://www.caercam.org/
 * @copyright 2014 Charlie MERLAND
 */

if ( ! class_exists( 'WPMovieLibrary-FancyBox' ) ) :

	/**
	* Plugin class
	*
	* @package WPMovieLibrary-FancyBox
	* @author  Charlie MERLAND <charlie@caercam.org>
	*/
	class WPMovieLibrary_FancyBox {

		/**
		 * Initialize the plugin by setting localization and loading public scripts
		 * and styles.
		 *
		 * @since     1.0
		 */
		public function __construct() {

			$this->register_hook_callbacks();
		}

		/**
		 * Register callbacks for actions and filters
		 * 
		 * @since    1.0
		 */
		public function register_hook_callbacks() {

			// Enqueue scripts and styles
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_filter( 'wpmoly_template_path', array( $this, 'add_images_fancybox' ), 10, 1 );
			add_filter( 'wpmoly_template_path', array( $this, 'add_posters_fancybox' ), 10, 1 );
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                     Plugin  Activate/Deactivate
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		/**
		 * Fired when the plugin is activated.
		 * 
		 * Restore previously converted contents. If WPMOLY was previously
		 * deactivated or uninstalled using the 'convert' option, Movies and
		 * Custom Taxonomies should still be in the database. If they are, we
		 * convert them back to WPMOLY contents.
		 * 
		 * Call Movie Custom Post Type and Collections, Genres and Actors custom
		 * Taxonomies' registering functions and flush rewrite rules to update
		 * the permalinks.
		 *
		 * @since    1.0
		 *
		 * @param    boolean    $network_wide    True if WPMU superadmin uses
		 *                                       "Network Activate" action, false if
		 *                                       WPMU is disabled or plugin is
		 *                                       activated on an individual blog.
		 */
		public function activate( $network_wide ) {

			global $wpdb;

			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				if ( $network_wide ) {
					$blogs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

					foreach ( $blogs as $blog ) {
						switch_to_blog( $blog );
						$this->single_activate( $network_wide );
					}

					restore_current_blog();
				} else {
					$this->single_activate( $network_wide );
				}
			} else {
				$this->single_activate( $network_wide );
			}

		}

		/**
		 * Prepares a single blog to use the plugin
		 *
		 * @since    1.0
		 *
		 * @param    bool    $network_wide
		 */
		protected function single_activate( $network_wide ) {

			$this->update_cache();
		}

		/**
		 * Fired when the plugin is deactivated.
		 * 
		 * When deactivatin/uninstalling WPMOLY, adopt different behaviors depending
		 * on user options. Movies and Taxonomies can be kept as they are,
		 * converted to WordPress standars or removed. Default is conserve on
		 * deactivation, convert on uninstall.
		 *
		 * @since    1.0
		 */
		public function deactivate() {

			$this->update_cache();
		}

		/**
		 * Runs activation code on a new WPMS site when it's created
		 *
		 * @since    1.0
		 *
		 * @param    int    $blog_id
		 */
		public function activate_new_site( $blog_id ) {
			switch_to_blog( $blog_id );
			$this->single_activate( true );
			restore_current_blog();
		}

		/**
		 * Register and enqueue public-facing style sheet.
		 *
		 * @since    1.0
		 */
		public function enqueue_styles() {

			wp_enqueue_style( WPMOLY_FBOX_SLUG, WPMOLY_FBOX_URL . '/vendor/css/jquery.fancybox.css', array(), WPMOLY_FBOX_VERSION );
		}

		/**
		 * Register and enqueue public-facing style sheet.
		 *
		 * @since    1.0
		 */
		public function enqueue_scripts() {

			wp_enqueue_script( WPMOLY_FBOX_SLUG . '-fancybox', WPMOLY_FBOX_URL . '/vendor/js/jquery.fancybox.pack.js', array( 'jquery' ), WPMOLY_FBOX_VERSION, true );
			wp_enqueue_script( WPMOLY_FBOX_SLUG, WPMOLY_FBOX_URL . '/assets/js/wpmoly-fancybox.js', array( 'jquery', WPMOLY_FBOX_SLUG . '-fancybox' ), WPMOLY_FBOX_VERSION, true );
		}

		/**
		 * Add FancyBox to Movie Images Shortcode
		 *
		 * @since    1.0
		 * 
		 * @param    string    Shortcode's template path
		 * 
		 * @return   string    Edited template path
		 */
		public function add_images_fancybox( $template_path ) {

			if ( is_admin() || false === strpos( $template_path, 'shortcodes/images.php' ) )
				return $template_path;

			$template_path = WPMOLY_FBOX_PATH . 'views/shortcodes/images.php';

			return $template_path;
		}

		/**
		 * Add FancyBox to Movie Posters Shortcode
		 *
		 * @since    1.0
		 * 
		 * @param    string    Shortcode's template path
		 * 
		 * @return   string    Edited template path
		 */
		public function add_posters_fancybox( $template_path ) {

			if ( is_admin() || false === strpos( $template_path, 'shortcodes/poster.php' ) )
				return $template_path;

			$template_path = WPMOLY_FBOX_PATH . 'views/shortcodes/poster.php';

			return $template_path;
		}

		/**
		 * Update the cache: remove all shortcodes transients so that
		 * the new templates can be used.
		 *
		 * @since    1.1
		 */
		private function update_cache() {

			if ( method_exists( 'WPMOLY_Cache', 'clean_transient' ) )
				WPMOLY_Cache::clean_transient( $action = null, $force = true, $search = 'shortcode' );
		}

		/**
		 * Initializes variables
		 *
		 * @since    1.0
		 */
		public function init() {}

	}
endif;