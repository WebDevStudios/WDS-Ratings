<?php
if ( ! class_exists( 'WDS_Ratings_Ajax' ) ) :

class WDS_Ratings_Ajax {
	/**
	 * Setup our class
	 * @since  0.1.0
	 * @access public
	 */
	public function __construct( $wds_ratings ) {
		$this->wds_ratings = $wds_ratings;
	}

	/**
	 * Handle a user's rating
	 * @since  0.1.0
	 * @access public
	 */
	public function post_user_rating() {
		$security_check_passes = (
			! empty($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest'
			&& isset( $_POST['nonce'], $_POST['post_id'], $_POST['user_id'], $_POST['rating'] )
			&& wp_verify_nonce( $_POST['nonce'],  'wds-ratings-nonce' )
		);

		if ( ! $security_check_passes ) {
			wp_send_json_error( $_POST );
		}

		global $wpdb;
		$ratings_table = $wpdb->prefix . $this->wds_ratings->ratings_table;

		$post_id = $_POST['post_id'];
		$user_id = $_POST['user_id'];
		$rating  = $_POST['rating'];

		$post_ratings_users   = get_post_meta( $post_id, '_wds_ratings_users', true );
		$post_ratings_score   = get_post_meta( $post_id, '_wds_ratings_score', true );
		$post_ratings_average = get_post_meta( $post_id, '_wds_ratings_average', true );

		// default to zero if not available
		$post_ratings_users   = $post_ratings_users ? $post_ratings_users : 0;
		$post_ratings_score   = $post_ratings_score ? $post_ratings_score : 0;
		$post_ratings_average = $post_ratings_average ? $post_ratings_average : 0;

		$post = get_post( $post_id );

		$old_user_rating = $this->wds_ratings->get_user_rating( $user_id, $post_id );

		// Is this a new rating for this user?
		if ( ! $old_user_rating ) {
			$user_ip = self::get_user_ip();
			// log rating into ratings table
			$rate_log = $wpdb->query(
				$wpdb->prepare( "INSERT INTO {$ratings_table} VALUES (%d, %d, %d, %d, %s, %s, %d )",
				0,
				$post_id,
				$rating,
				current_time( 'timestamp' ),
				$user_ip,
				@gethostbyaddr( $user_ip ),
				$user_id )
			);

			// update with new rating data
			$post_ratings_users = ( intval( $post_ratings_users ) + 1);
			$post_ratings_score = ( $post_ratings_score + intval( $rating ) );
			$post_ratings_average = round( $post_ratings_score / $post_ratings_users, 2 );

			update_post_meta( $post_id, '_wds_ratings_users', $post_ratings_users );
			update_post_meta( $post_id, '_wds_ratings_score', $post_ratings_score );
			update_post_meta( $post_id, '_wds_ratings_average', $post_ratings_average );

		} else {
			// We're updating the user's rating - so we need the post meta
			$query = $wpdb->prepare(
				"
				UPDATE
					{$ratings_table}
				SET
					rating = %d
				WHERE
					userid = %d
				AND
					postid = %d
				", $rating, $user_id, $post_id );


			# Do the query and check for errors
			if ( false === $wpdb->query( $query ) ) {
				if( isset( $wp_error ) )
					return new WP_Error(
						'db_query_error',
						__( 'Could not execute query' ),
						$wpdb->last_error
					);
			}

			// update the post's rating data
			$post_ratings_score = ( $post_ratings_score + intval( $rating ) - intval( $old_user_rating ) );
			$post_ratings_average = round( $post_ratings_score / $post_ratings_users, 2 );
			update_post_meta( $post_id, '_wds_ratings_users', $post_ratings_users );
			update_post_meta( $post_id, '_wds_ratings_score', $post_ratings_score );
			update_post_meta( $post_id, '_wds_ratings_average', $post_ratings_average );
		}

		// Allow other plugins to hook in
		do_action( 'wds_rate_post', $user_id, $post_id, $rating );

		wp_send_json_success( $_POST );
	}

	/**
	 * Get the user's IP address
	 * @since  0.1.0
	 * @access private static
	 */
	private static function get_user_ip() {
		if ( empty( $_SERVER["HTTP_X_FORWARDED_FOR"] ) ) {
			$ip_address = $_SERVER["REMOTE_ADDR"];
		} else {
			$ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}

		if ( false !== strpos( $ip_address, ',' ) ) {
			$ip_address = explode( ',', $ip_address );
			$ip_address = $ip_address[0];
		}

		return esc_attr( $ip_address );
	}

}

endif;
