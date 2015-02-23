<?php
if ( ! class_exists( 'WDS_Ratings_Meta_Box' ) ) :

class WDS_Ratings_Meta_Box {
	/**
	 * Setup our class
	 * @since  0.1.0
	 * @access public
	 */
	public function __construct() {
		add_filter( 'cmb2_meta_boxes', array( $this, 'add_metabox' ) );
	}

	/**
	 * Add the metabox
	 * @since  0.1.0
	 * @access public
	 */
	public function add_metabox( array $meta_boxes ) {
		// Start with an underscore to hide fields from custom fields list
		$prefix = '_wds_ratings_';

		/**
		 * Sample metabox to demonstrate each field type included
		 */
		$meta_boxes['wds_ratings_metabox'] = array(
			'id'            => 'wds_ratings_metabox',
			'title'         => 'WDS Ratings',
			'object_types'  => array( 'page', 'post' ), // Post type
			'context'       => 'side',
			'priority'      => 'high',
			'show_names'    => true,
			'fields'        => array(
				array(
					'name' => $this->filter_label(),
					'id'   => $prefix . 'filter',
					'type' => 'checkbox',
				),
			),
		);

		return $meta_boxes;
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
		}

		return $label;
	}

}

$GLOBALS['WDS_Ratings_Meta_Box'] = new WDS_Ratings_Meta_Box;

endif;
