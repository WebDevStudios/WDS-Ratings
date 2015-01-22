<?php

if ( ! function_exists( 'post_ratings' ) ) {
	function post_ratings( $echo = true ) {
		global $wds_ratings;
		
		if ( is_object( $wds_ratings ) && method_exists( $wds_ratings, 'fetch_ratings_template' ) ) {
			$ratings = $wds_ratings->fetch_ratings_template();
			
			if ( $echo ) {
				echo $ratings;
			}
			
			return $ratings;
		}
	}
}