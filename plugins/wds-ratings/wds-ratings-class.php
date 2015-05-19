<?php
/**
 * Plugin Name: WDS Ratings
 * Description: Allow users to rate posts
 * Version:     0.4.0
 * Author:      WebDevStudios
 * Author URL:  http://webdevstudios.com
 * Text Domain: wds_ratings
 * Domain Path: /languages
 */

class WDS_Ratings {

	/**
	 * Plugin version
	 */
	const VERSION = '0.4.0';

	/**
	 * Only incremenent if table structure needs to change in a future version
	 */
	const DB_VERSION = '0.1.0';

	public $ratings_table = 'wds_ratings';
	public $url;
	public $path;
	public $name;
	public $admin;
	public $metabox;
	public $ajax;

	protected $options;
	protected static $single_instance = null;

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
	 */
	protected function __construct() {
		// Useful variables
		$this->url  = trailingslashit( plugin_dir_url( __FILE__ ) );
		$this->path = trailingslashit( dirname( __FILE__ ) );
		$this->name = __( 'WDS Ratings', 'wds_ratings' );
	}

	/**
	 * Hook in where we need to
	 * @since  0.1.0
	 */
	public function hooks() {
		// CMB2 for metabox/options page
		require_once( $this->path  . 'lib/cmb2/init.php' );

		// Options
		require_once( $this->path . 'lib/options.php' );
		$this->admin = new WDS_Ratings_Admin;

		// create meta box for posts
		require_once( $this->path . 'lib/meta-box.php' );
		$this->metabox = new WDS_Ratings_Meta_Box();

		require_once( $this->path  . 'lib/ajax.php' );
		$this->ajax = new WDS_Ratings_Ajax();

		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activation' ) );

		// Add JS and CSS to head
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		// AJAX
		add_action( 'wp_ajax_wds_ratings_post_user_rating', array( $this->ajax, 'post_user_rating' ) );
		add_action( 'wp_ajax_nopriv_wds_ratings_post_user_rating', array( $this->ajax, 'post_user_rating' ) );

		// Cache
		add_action( 'wds_rate_post', array( $this, 'update_user_post_rating_cache' ), 10, 3 );

		// add content filter if enabled
		if ( 'on' === $this->fetch_option( 'enable_content_filter' ) ) {
			add_filter( 'the_content', array( $this, 'content_filter' ) );
		}

		$this->maybe_add_db_table();
	}

	/**
	 * Init hooks
	 * @since  0.1.0
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
	 * @return bool
	 */
	public function enqueue() {
		if ( ! $this->is_allowed_on_post() ) {
			return false;
		}

		if ( apply_filters( 'wds_ratings_css', true ) ) {
			wp_enqueue_style( 'wds-ratings', $this->url . 'wds-ratings.css' );
		}

		wp_enqueue_script( 'wds-ratings', $this->url . 'wds-ratings.js', array( 'jquery' ) );
		wp_localize_script( 'wds-ratings', 'wds_ratings_config', array(
			'ajaxurl'   => admin_url( 'admin-ajax.php' ),
			'user_id'   => get_current_user_id(),
			'nonce'     => wp_create_nonce( 'wds-ratings-nonce' ),
			'debug'     => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			'no_auth_alert' => __( 'You must be logged in to rate an article', 'wds_ratings' ),
		) );

		return true;
	}

	/**
	 * Add ratings before post content
	 * @since  0.1.0
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
	 * @return string $ratings_template
	 */
	public function fetch_ratings_template() {
		$post_id = get_the_ID();
		$user_id = get_current_user_id();

		$post_rating = $this->get_post_average( $post_id );
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

		$star_rows = '';
		for ( $number_stars = 5; $number_stars > 0; $number_stars-- ) {
			$star_rows .= '<div class="wds-ratings-stars" data-stars="'. $number_stars .'">';
			for ( $i = 1; $i <= $number_stars; $i++ ) {
				$star_rows .= '<span class="star-'. $i .'">&#x2605;</span>';
			}
			$star_rows .= '</div>';
		}

		$ratings_template = '
		<div id="star-rating-'. absint( $post_id ) .'" class="wds-ratings" data-rating="'. absint( $post_rating ) .'" data-userrating="'. absint( $user_rating ) .'" data-postid="'. absint( $post_id ) .'">
			<div class="wds-ratings-inner-wrap">
				<div>
					'. $star_rows .'
				</div>
			</div>
		</div>
		';

		// Make sure css/js is enqueued if not already
		$this->enqueue();

		return apply_filters( 'wds_ratings_template', $ratings_template, $post_id, $user_id, $post_rating, $user_rating );
	}

	/**
	 * Find out if ratings are allowed on the post
	 * @since  0.1.0
	 * @param int $post_id=null
	 * @return bool
	 */
	protected function is_allowed_on_post( $post_id = null ) {
		if ( ! $this->fetch_option( 'filter' ) || ( 'off' == $this->fetch_option( 'filter' ) ) ) {
			return true;
		}

		$post_id  = is_null( $post_id ) ? get_the_ID() : $post_id;

		$is_added = 'on' == get_post_meta( $post_id, '_wds_ratings_added_to_filter', true );
		$filter   = $this->fetch_option( 'filter_type' );

		// Are ratings allowed on this post?
		$allowed = ( 'exclusive' == $filter && ! $is_added ) || ( 'inclusive' == $filter && $is_added );

		return apply_filters( 'wds_ratings_allowed_on_post', $allowed, $post_id );
	}

	/**
	 * Get a user's rating for the post
	 * @since  0.1.0
	 * @return int $rating
	 */
	public function get_user_rating( $user_id, $post_id ) {
		global $wpdb;

		// Check if we've cached the user's rating for the day
		$rating = $this->get_user_post_rating_cache( $user_id, $post_id );

		if ( ! $rating ) {

			$ratings_table = $wpdb->prefix . $this->ratings_table;
			$sql = $wpdb->prepare( "SELECT * FROM `{$ratings_table}` WHERE `userid` = %d AND `postid` = %d", $user_id, $post_id );
			$results = $wpdb->get_results( $sql );

			$rating = ! empty( $results ) && is_array( $results )
			? intval( $results[0]->rating )
			: false;

			// Cache rating for a day
			$this->update_user_post_rating_cache( $user_id, $post_id, $rating );
		}

		return apply_filters( 'wds_rating_user_rating', $rating, $user_id, $post_id );
	}

	/**
	 * Get cached rating for a user/post combo
	 * @since  0.4.0
	 * @param  int    $user_id
	 * @param  int    $post_id
	 * @return mixed  Cache results
	 */
	public function update_user_post_rating_cache( $user_id, $post_id, $rating ) {
		return wp_cache_set( $this->get_user_post_cache_key( $user_id, $post_id ), $rating, 'wds_ratings', 8 * HOUR_IN_SECONDS );
	}

	/**
	 * Get cached rating for a user/post combo
	 * @since  0.4.0
	 * @param  int    $user_id
	 * @param  int    $post_id
	 * @return mixed  Cache results
	 */
	public function get_user_post_rating_cache( $user_id, $post_id ) {
		return wp_cache_get( $this->get_user_post_cache_key( $user_id, $post_id ), 'wds_ratings' );
	}

	/**
	 * Get a cache key for a user/post combo
	 * @since  0.4.0
	 * @param  int    $user_id
	 * @param  int    $post_id
	 * @return string Cache key
	 */
	public function get_user_post_cache_key( $user_id, $post_id ) {
		return sprintf( 'user_rating_%d_%d', $user_id, $post_id );
	}

	/**
	 * Activation hook
	 * @since  0.1.0
	 * @return null
	 */
	public function activation( $network_wide ) {
		if ( is_multisite() && $network_wide ) {
			/**
		 * this will return a max of 100 sites.
		 * The rest will have to depend on the db version check
		 */
			$ms_sites = wp_get_sites( array(
				'limit'      => 100,
			) );

			if ( ! empty( $ms_sites ) ) {
				foreach ( $ms_sites as $ms_site ) {
					$this->maybe_add_db_table( $ms_site['blog_id'] );
				}
			}
		} else {
			$this->maybe_add_db_table();
		}
	}

	/**
	 * Create the ratings table
	 * @since  0.1.0
	 * @return null
	 */
	protected function maybe_add_db_table( $blog_id = null ) {
		global $wpdb;

		$stored_db_version = $blog_id
		? get_blog_option( $blog_id, 'wds_ratings_db_version' )
		: get_option( 'wds_ratings_db_version' );

		if ( $stored_db_version && self::DB_VERSION == $stored_db_version ) {
			return;
		}

		$table = $wpdb->get_blog_prefix( $blog_id ) . $this->ratings_table;

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

		return $blog_id
		? update_blog_option( $blog_id, 'wds_ratings_db_version', self::DB_VERSION )
		: update_option( 'wds_ratings_db_version', self::DB_VERSION );
	}

	/**
	 * Get a posts average rating
	 * @since  0.1.0
	 * @return int $average
	 */
	public function get_post_average( $post_id ) {
		$average = get_post_meta( $post_id, '_wds_ratings_average', true );

		return $average ? $average : 0;
	}

	/**
	 * Get options from the wds_ratings_settings option array.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key Key to get from the wds_ratings_settings option array.
	 * @return string Returns the value of the key or false on failure.
	 */
	public function fetch_option( $key ) {
		// Are options already set?
		if ( empty( $this->options ) ) {
			$this->options = get_option( $this->admin->key );
		}

		// Does the key exist?
		if ( isset( $this->options[ $key ] ) ) {
			return $this->options[ $key ];
		}

		// If nothing has been returned yet, return false
		return false;
	}

}

function wds_ratings() {
	return WDS_Ratings::get_instance();
}
