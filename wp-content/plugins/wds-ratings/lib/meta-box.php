<?php
if( ! class_exists( 'WDS_Ratings_Meta_Box' ) ):
class WDS_Ratings_Meta_Box {
	/**
	 * Setup our class
	 * @since  0.1.0
	 * @access public
	 */
	public function __construct( $wds_ratings ) {
		$this->wds_ratings = $wds_ratings;
	}
	
	/**
	 * Add the metabox
	 * @since  0.1.0
	 * @access public
	 */
	public function meta_box_add() {
		add_meta_box( 'wds-ratings-meta-box', 'WDS Ratings', array( $this, 'meta_box_cb' ), 'post', 'side', 'high' );
	}
	
	/**
	 * Callback for our meta box
	 * @since  0.1.0
	 * @access public
	 */
	public function meta_box_cb() {
	    global $post;
	    $values = get_post_custom( $post->ID );
	    $check = isset( $values['_wds_ratings_added_to_filter'][0] ) ? esc_attr( $values['_wds_ratings_added_to_filter'][0] ) : '';
	    
	    wp_nonce_field( 'wds_ratings_meta_box_nonce', 'meta_box_nonce' );
	    ?>     
	    <p>
	        <input type="checkbox" id="_wds_ratings_added_to_filter" name="_wds_ratings_added_to_filter" <?php checked( $check, 'on' ); ?> />
	        <label for="_wds_ratings_added_to_filter">Add to filter?</label>
	        <p><small>inclusive or exclusive based on what is chosen in the plugin's settings. (default: exclusive)</small></p>
	    </p>
	    <?php  
	}
	
	/**
	 * Save the custom metabox data
	 * @since  0.1.0
	 * @access public
	 */
	public function meta_box_save( $post_id ) {
		// bail if we're autosaving
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		// bail if our nounce if not verified
		if( ! isset( $_POST['meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['meta_box_nonce'], 'wds_ratings_meta_box_nonce' ) ) {
		    return;
		}
		
		// bail if our current user can't edit this post
		if( ! current_user_can( 'edit_post' ) ) {
			return;
		}
		
		// save the post meta
		$chk = isset( $_POST['_wds_ratings_added_to_filter'] ) && $_POST['_wds_ratings_added_to_filter'] ? 'on' : 'off';
		update_post_meta( $post_id, '_wds_ratings_added_to_filter', $chk );
	}
}
endif;