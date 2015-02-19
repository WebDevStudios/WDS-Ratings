<?php

if ( ! function_exists( 'wds_post_ratings' ) ) {
	function wds_post_ratings( $echo = true ) {
		if ( is_object( wds_ratings() ) && method_exists( wds_ratings(), 'fetch_ratings_template' ) ) {
			$ratings = wds_ratings()->fetch_ratings_template();
			
			if ( $echo ) {
				echo $ratings;
			}
			
			return $ratings;
		}
	}
	// add action for template calls
	add_action( 'wds_post_ratings', 'wds_post_ratings', 1, 1 );
}