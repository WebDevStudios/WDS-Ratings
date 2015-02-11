<?php
/**
 * Plugin Name: WDS Ratings
 * Description: Allow users to rate posts
 * Version:     0.2.0
 * Author:      WebDevStudios
 * Author URL:  http://webdevstudios.com
 * Text Domain: wds_ratings
 * Domain Path: /languages
 */

if( ! class_exists( 'WDS_Ratings' ) ):
class WDS_Ratings {

	const VERSION = '0.4.0';

	public static $url;
	public static $path;
	public static $name;

	private static $options;

	protected static $single_instance = null;
	public $ratings_table   = 'wds_ratings';

	/**
	 * Creates or returns an instance of this class.
	 * @since  0.1.0
	 * @return WDS_Ratings A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin
	 * @since  0.1.0
	 * @access public
	 */
	private function __construct() {
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
		$this->settings();
		
		//CMB2
		$this->include_cmb2();

		// create meta box for posts
		if ( 
			( 'off' !== self::fetch_option( 'filter_type' ) )
			&& ( false != self::fetch_option( 'filter_type' ) )
		) {
			$this->meta_box();
		}

		// AJAX
		add_action( 'wp_ajax_wds_ratings_post_user_rating', array( $this->ajax(), 'post_user_rating' ) );
		add_action( 'wp_ajax_nopriv_wds_ratings_post_user_rating', array( $this->ajax(), 'post_user_rating' ) );


		// add content filter if enabled
		if ( 'on' === self::fetch_option( 'enable_content_filter' ) ) {
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
			//wp_enqueue_style( 'wds-ratings', self::$url . 'wds-ratings.css' );

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
		return array(
			'ajaxurl'   => admin_url( 'admin-ajax.php' ),
			//'post_id' => $post->ID,
			'user_id'   => get_current_user_id(),
			'nonce'     => wp_create_nonce( 'wds-ratings-nonce' ),
			'debug'     => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			'no_auth_alert' => __( 'You must be logged in to rate an article', 'wds_ratings' ),
		);
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

		// round the rating to nearest .5
		$rounded_rating = round( $post_rating * 2, 0 ) / 2;

		$data = array(
			'post_rating' => $rounded_rating,
			'post_id' => $post_id,
		);

		if ( $user_rating && $user_rating > 0 ) {
			$data['user_rating'] = $user_rating;
		}

		ob_start();
		extract( $data );
		include( self::$path . 'lib/stars-template.php' );
		$ratings_template = ob_get_clean();

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
		if ( ! self::fetch_option( 'filter' ) || ( 'off' == self::fetch_option( 'filter' ) ) ) {
			return true;
		}

		// fallback
		if ( is_null( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$is_added = 'on' === get_post_meta( $post_id, '_wds_ratings_added_to_filter', true ) ? true : false;
		$filter = self::fetch_option( 'filter_type' );

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
		global $wpdb;

		$ratings_table = $wpdb->prefix . $this->ratings_table;
		$sql = "SELECT * FROM `{$ratings_table}` WHERE `userid` = $user_id AND `postid` = $post_id";
		$results = $wpdb->get_results( $sql );

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
			// TODO: this will not work w/ an extremely large network.
			$ms_sites = wp_get_sites();

			if ( ! empty( $ms_sites ) ) {
				foreach ( $ms_sites as $ms_site ) {
					switch_to_blog( $ms_site['blog_id'] );
					$this->ratings_activate( $ms_site['blog_id'] );
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
	protected function ratings_activate( $blog_id = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . $this->ratings_table;

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) == $table ) {
			return;
		}

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$create_sql = "CREATE TABLE $table (
		rating_id INT(11) NOT NULL auto_increment,
		postid INT(11) NOT NULL ,
		rating INT(2) NOT NULL ,
		timestamp VARCHAR(15) NOT NULL ,
		ip VARCHAR(40) NOT NULL ,
		host VARCHAR(200) NOT NULL,
		userid int(10) NOT NULL default '0',
		PRIMARY KEY (rating_id));";

		dbDelta( $create_sql );
	}

	/**
	 * Get a posts average rating
	 * @since  0.1.0
	 * @access private static
	 * @return int $average
	 */
	private static function get_post_average( $post_id ) {
		$average = get_post_meta( $post_id, '_wds_ratings_average', true );

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
			self::$options = get_option( 'wds_ratings' );
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

		require_once( self::$path  . 'lib/ajax.php' );
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
		require_once( self::$path . 'lib/meta-box.php' );
	}

	/**
	 * Settings class
	 * @since  0.1.0
	 * @access public
	 * @return object WDS_Ratings_Options
	 */
	public function settings() {
		require_once( self::$path . 'lib/options.php' );
	}
	
	/**
	 * Include CMB2
	 * @since  0.1.0
	 * @access public
	 */
	public function include_cmb2() {
		 if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
			require_once dirname( __FILE__ ) . '/cmb2/init.php';
		} elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
			require_once dirname( __FILE__ ) . '/CMB2/init.php';
		}
	}
}

function wds_ratings() {
	return WDS_Ratings::get_instance();
}
// init our class
wds_ratings()->hooks();

// include helpers
if ( file_exists( WDS_Ratings::$path . 'lib/helpers.php' ) ) {
	require_once( WDS_Ratings::$path . 'lib/helpers.php' );
}

// include widget if enabled
if (
	( 'on' === WDS_Ratings::fetch_option( 'enable_widget' ) )
	&& file_exists( WDS_Ratings::$path . 'lib/widget.php' )
) {
	require_once( WDS_Ratings::$path . 'lib/widget.php' );
}

endif;
