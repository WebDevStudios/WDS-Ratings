<?php
/**
 * Plugin Name: WDS Ratings
 * Description: Allow users to rate posts
 * Version:     0.1.0
 * Author:      WebDevStudios
 * Author URL:  http://webdevstudios.com
 * Text Domain: wds_ratings
 * Domain Path: /languages
 */

if( ! class_exists( 'WDS_Ratings' ) ):
class WDS_Ratings {

	const VERSION = '0.4.0';
	
	private
		$wpdb;
	
	public static 
		$url,
		$path,
		$name;
		
	private static
		$options;

	/**
	 * Sets up our plugin
	 * @since  0.1.0
	 * @access public
	 */
	public function __construct() {
		// add ratings table to $wpdb
		global $wpdb;
		$wpdb->ratings = $wpdb->prefix . 'ratings';
		$this->wpdb = $wpdb;
		
		// Useful variables
		self::$url  = trailingslashit( plugin_dir_url( __FILE__ ) );
		self::$path = trailingslashit( dirname( __FILE__ ) );
		self::$name = __( 'WDS Ratings', 'wds_ratings' );
		
		// Set the options
		self::$options = get_option( 'wds_ratings_settings' );
	}
	
	/**
	 * Hook in where we need to
	 * @since  0.1.0
	 * @access public
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activation' ) );

		// Add JS and CSS to head
		add_action( 'wp_head', array( $this, 'do_wds_ratings' ), 1 );
		
		// Options
		add_action( 'admin_menu', array( $this->settings(), 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this->settings(), 'settings_init' ) );
		
		// create meta box for posts
		if ( 'off' !== self::fetch_option( 'filter' ) ) {
			add_action( 'add_meta_boxes', array( $this->meta_box(), 'meta_box_add' ) );
			add_action( 'save_post', array( $this->meta_box(), 'meta_box_save' ) );
		}
		
		// AJAX
		add_action( 'wp_ajax_wds_ratings_post_user_rating', array( $this->ajax(), 'post_user_rating' ) );
		add_action( 'wp_ajax_nopriv_wds_ratings_post_user_rating', array( $this->ajax(), 'post_user_rating' ) );
		
		// add content filter if enabled
		if ( 1 === self::fetch_option( 'enable_content_filter' ) ) {
			add_filter( 'the_content', array( $this, 'content_filter' ) );
		}
	}

	/**
	 * Init hooks
	 * @since  0.1.0
	 * @access public
	 * @return null
	 */
	public function init() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wds_ratings' );
		load_textdomain( 'wds_ratings', WP_LANG_DIR . '/wds-ratings/wds-ratings-' . $locale . '.mo' );
		load_plugin_textdomain( 'wds_ratings', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Do the ratings thing
	 * @since  0.1.0
	 * @access public
	 * @return bool
	 */
	public function do_wds_ratings() {
		if ( $this->is_allowed_on_post() ) {
			// CSS
			wp_enqueue_style( 'wds-ratings', self::$url . 'wds-ratings.css' );
			
			// JS
			wp_enqueue_script( 'wds-ratings', self::$url . 'wds-ratings.js', array( 'jquery' ) );
			wp_localize_script( 'wds-ratings', 'wds_ratings_config', $this->compile_js_data() );
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * JS data we want to localize
	 * @since  0.1.0
	 * @access private
	 * @return array $js_data
	 */
	private function compile_js_data() {
		global $post;
		$js_data = array(
			'ajaxurl'	=> admin_url( 'admin-ajax.php' ),
			'post_id' => $post->ID,
			'user_id' => get_current_user_id(),
			'nonce' => wp_create_nonce( 'wds-ratings-nonce' ),
		);
		
		return $js_data;
	}
	
	/**
	 * Add ratings before post content
	 * @since  0.1.0
	 * @access public
	 * @return string $content
	 */
	public function content_filter( $content ) {
		if ( $this->is_allowed_on_post() ) {
			$ratings = $this->fetch_ratings_template();
			$content = $ratings . $content;
		}
		
		return $content;
	}
	
	/**
	 * Fetch the ratings template
	 * @since  0.1.0
	 * @access public
	 * @return string $ratings_template
	 */
	public function fetch_ratings_template() {
		$post_id = get_the_ID();
		$user_id = get_current_user_id();
		
		$post_rating = self::get_post_average( $post_id );
		$user_rating = $this->get_user_rating( $user_id, $post_id );
	
		$data = array(
			'post_rating' => $post_rating,
			'post_id' => $post_id,
		);
		
		if ( $user_rating && $user_rating > 0 ) {
			$data['user_rating'] = $user_rating;
		}
		
		ob_start();
		extract( $data );
		require_once( self::$path . 'lib/stars-template.php' );
		$ratings_template = ob_get_clean();
		//ob_end_flush();
		
		return $ratings_template;
	}
	
	/**
	 * Find out if ratings are allowed on the post
	 * @since  0.1.0
	 * @access private
	 * @param int $post_id=null
	 * @return bool
	 */
	private function is_allowed_on_post( $post_id = null ) {
		if ( 'off' == self::fetch_option( 'filter' ) ) {
			return true;
		}
		
		// fallback
		if ( is_null( $post_id ) ) {
			$post_id = get_the_ID();
		}
		
		$is_added = 'on' === get_post_meta( $post_id, '_wds_ratings_added_to_filter', true ) ? true : false;
		$filter = self::fetch_option( 'filter' );
		
		// Are ratings allowed on this post?
		if ( 
			( 'exclusive' === $filter && ! $is_added )
			|| ( 'inclusive' === $filter && $is_added )
		) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Get a user's rating for the post
	 * @since  0.1.0
	 * @access private
	 * @return int $rating
	 */
	public function get_user_rating( $user_id, $post_id ) {
		$sql = "SELECT * FROM `{$this->wpdb->ratings}` WHERE `userid` = $user_id AND `postid` = $post_id";
		$results = $this->wpdb->get_results( $sql );
		
		if ( ! empty( $results ) && is_array( $results ) ) {
			return intval( $results[0]->rating );
		}
		
		// no rating found
		return false;
	}
	
	/**
	 * Activation hook
	 * @since  0.1.0
	 * @access public
	 * @return null
	 */
	public function activation( $network_wide ) {
		if ( is_multisite() && $network_wide ) {
			$ms_sites = wp_get_sites();
	
			if( 0 < sizeof( $ms_sites ) ) {
				foreach ( $ms_sites as $ms_site ) {
					switch_to_blog( $ms_site['blog_id'] );
					$this->ratings_activate();
				}
			}
	
			restore_current_blog();
		} else {
			$this->ratings_activate();
		}
	}
	
	/**
	 * Create the ratings table
	 * @since  0.1.0
	 * @access protected
	 * @return null
	 */
	protected function ratings_activate() {
		$create_sql = "CREATE TABLE `{$this->wpdb->ratings}` (".
				"rating_id INT(11) NOT NULL auto_increment,".
				"postid INT(11) NOT NULL ,".
				"rating INT(2) NOT NULL ,".
				"timestamp VARCHAR(15) NOT NULL ,".
				"ip VARCHAR(40) NOT NULL ,".
				"host VARCHAR(200) NOT NULL,".
				"userid int(10) NOT NULL default '0',".
				"PRIMARY KEY (rating_id));";
				
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $create_sql );
	}
	
	/**
	 * Get a posts average rating
	 * @since  0.1.0
	 * @access private static
	 * @return int $average
	 */
	private static function get_post_average( $post_id ) {
		$average = get_post_meta( $post_id, 'ratings_average', true );
		
		return $average ? $average : 0;
	}
	
	/**
	* Get options from the wds_ratings_settings option array.
	*
	* @since 1.0.0
	* @access public
	*
	* @param  string $key Key to get from the wds_ratings_settings option array.
	* @return string Returns the value of the key or false on failure.
	*/
	public static function fetch_option( $key ) {
		// Are options already set?
		if ( empty( self::$options ) ) {
			self::$options = get_option( 'wds_ratings_settings' );
		}
		
		// Does the key exist?
		if ( isset( self::$options[$key] ) ) {
			return self::$options[$key];
		}
		
		// If nothing has been returned yet, return false
		return false;
	}
	
	/**
	 * Ajax class
	 * @since  0.1.0
	 * @access public
	 * @return object WDS_Ratings_Ajax
	 */
	public function ajax() {
		if ( isset( $this->ajax ) ) {
			return $this->ajax;
		}
		
		require_once( 'lib/ajax.php' );
		$this->ajax = new WDS_Ratings_Ajax( $this );
		return $this->ajax;
	}
	
	/**
	 * Meta Box class
	 * @since  0.1.0
	 * @access public
	 * @return object WDS_Ratings_Meta_Box
	 */
	public function meta_box() {
		if ( isset( $this->meta_box ) ) {
			return $this->meta_box;
		}
		
		require_once( 'lib/meta-box.php' );
		$this->meta_box = new WDS_Ratings_Meta_Box( $this );
		return $this->meta_box;
	}
	
	/**
	 * Settings class
	 * @since  0.1.0
	 * @access public
	 * @return object WDS_Ratings_Options
	 */
	public function settings() {
		if ( isset( $this->settings ) ) {
			return $this->settings;
		}
		require_once( 'lib/options.php' );
		$this->settings = new WDS_Ratings_Options( $this );
		return $this->settings;
	}
}

// init our class
$GLOBALS['wds_ratings'] = new WDS_Ratings();
$GLOBALS['wds_ratings']->hooks();

function post_ratings( $echo = true ) {
	global $wds_ratings;
	
	$ratings = $wds_ratings->fetch_ratings_template();
	
	if ( $echo ) {
		echo $ratings;
	}
	
	return $ratings;
}

endif;