<?php

if ( ! function_exists( 'wds_post_ratings' ) ) {
	function wds_post_ratings( $echo = true ) {
		$ratings = wds_ratings()->fetch_ratings_template();

		if ( $echo ) {
			echo $ratings;
		}

		return $ratings;
	}
	// add action for template calls
	add_action( 'wds_post_ratings', 'wds_post_ratings' );
}
