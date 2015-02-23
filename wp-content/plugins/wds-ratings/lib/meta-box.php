<?php
if ( ! class_exists( 'WDS_Ratings_Meta_Box' ) ) :

class WDS_Ratings_Meta_Box {

	/**
	 * Setup our class
	 * @since  0.1.0
	 */
	public function __construct() {
		add_action( 'cmb2_init', array( $this, 'register_metabox' ) );
	}

	/**
	 * Add the metabox
	 * @since  0.1.0
	 */
	public function register_metabox() {
		// Start with an underscore to hide fields from custom fields list
		$prefix = '_wds_ratings_';

		$cmb = new_cmb2_box( array(
			'id'            => $prefix . 'metabox',
			'title'         => __( 'WDS Ratings', 'wds_ratings' ),
			'object_types'  => array( 'page', 'post' ), // Post type
			'context'       => 'side',
			'show_names' => false,
		) );

		$cmb->add_field( array(
			'desc' => $this->filter_label(),
			'id'   => $prefix . 'filter',
			'type' => 'checkbox',
		) );

	}

	public function filter_label() {

		if ( ! isset( $_GET['post'] ) && ! isset( $_GET['post_type'] ) ) {
			$label = __( 'Post' );
		} else {
			$pt = isset( $_GET['post_type'] ) ? $_GET['post_type'] : get_post_type( $_GET['post'] );
			$pt_object = get_post_type_object( $pt );
			$label = $pt_object->labels->singular_name;
		}

		switch ( wds_ratings()->fetch_option( 'filter_type' ) ) {
			case 'inclusive':
				$label = sprintf( __( 'Show Ratings on this %s', 'wds_ratings' ), $label );
				break;

			default:
				$label = sprintf( __( 'Hide Ratings on this %s', 'wds_ratings' ), $label );
				break;
		}

		return $label;
	}

}

endif;
