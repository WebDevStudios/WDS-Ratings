<?php
/**
 * WDS Ratings Options
 * @version 0.1.0
 */
class WDS_Ratings_Admin {

	/**
 	 * Option key, and option page slug
 	 * @var string
 	 */
	private $key = 'wds_ratings';

	/**
 	 * Options page metabox id
 	 * @var string
 	 */
	private $metabox_id = 'wds-ratings';

	/**
	 * Array of metaboxes/fields
	 * @var array
	 */
	protected $option_metabox = array();

	/**
	 * Options Page title
	 * @var string
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Constructor
	 * @since 0.1.0
	 */
	public function __construct() {
		// Set our title
		$this->title = __( 'WDS Ratings Options', 'wds_ratings' );

		// Set our CMB2 fields
		$this->fields = array(
			array(
				'name' => __( 'Filter Type', 'wds_ratings' ),
				'desc' => __( 'If exclusive, the ratings will not available on posts added to the WDS Ratings filter. <br /> If inclusive, ratings will not available on posts added to the WDS Ratings filter.', 'wds_ratings' ),
				'id'   => 'filter_type',
				'type' => 'select',
				'options' => array(
					'off' => __( 'Off', 'wds_ratings' ),
					'exclusive'   => __( 'Exclusive', 'wds_ratings' ),
					'inclusive'     => __( 'Inclusive', 'wds_ratings' ),
				),
			),
			array(
				'name' => __( 'Enable Content Filter', 'cmb2' ),
				'desc' => __( 'Add ratings before post content', 'wds_ratings' ),
				'id'   => 'enable_content_filter',
				'type' => 'checkbox',
			),
			array(
				'name' => __( 'Enable Widget', 'cmb2' ),
				//'desc' => __( 'field description (optional)', 'wds_ratings' ),
				'id'   => 'enable_widget',
				'type' => 'checkbox',
			),
		);
	}

	/**
	 * Initiate our hooks
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_filter( 'cmb2_meta_boxes', array( $this, 'add_options_page_metabox' ) );
	}


	/**
	 * Register our setting to WP
	 * @since  0.1.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Add menu options page
	 * @since 0.1.0
	 */
	public function add_options_page() {
		$this->options_page = add_menu_page( 
			$this->title, 
			$this->title, 
			'manage_options', 
			$this->key, 
			array( $this, 'admin_page_display' ) 
		);
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 * @since  0.1.0
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2_options_page <?php echo $this->key; ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
		</div>
		<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 * @since  0.1.0
	 * @param  array $meta_boxes
	 * @return array $meta_boxes
	 */
	function add_options_page_metabox( array $meta_boxes ) {
		$meta_boxes[] = $this->option_metabox();

		return $meta_boxes;
	}

	/**
	 * Defines the theme option metabox and field configuration
	 * @since  0.1.0
	 * @return array
	 */
	public function option_metabox() {
		return array(
			'id'      => $this->metabox_id,
			'fields'  => $this->fields,
			'hookup'  => false,
			'show_on' => array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( $this->key, )
			),
		);
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'fields', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		if ( 'option_metabox' === $field ) {
			return $this->option_metabox();
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

}

$GLOBALS['WDS_Ratings_Admin'] = new WDS_Ratings_Admin;
$GLOBALS['WDS_Ratings_Admin']->hooks();

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 * @param  string  $key Options array key
 * @return mixed        Option value
 */
function wds_ratings_get_option( $key = '' ) {
	global $WDS_Ratings_Admin;
	return cmb2_get_option( $WDS_Ratings_Admin->key, $key );
}