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
		) );

		$cmb->add_field( array(
			'name' => $this->filter_label(),
			'id'   => $prefix . 'filter',
			'type' => 'checkbox',
		) );

	}

	public function filter_label() {
		$type = WDS_Ratings::fetch_option( 'filter_type' );

		switch ( $type ) {
			case 'inclusive':
				$label = __( 'Allow Ratings', 'wds_ratings' );
				break;

			case 'exclusive':
				$label = __( 'Do NOT Allow Ratings', 'wds_ratings' );
				break;

			default:
				$label = __( 'No filter type selected.', 'wds_ratings' );
				break;
		}

		return $label;
	}

}

$GLOBALS['WDS_Ratings_Meta_Box'] = new WDS_Ratings_Meta_Box;

endif;
