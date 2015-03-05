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
	private $key = 'wds_ratings_settings';

	/**
 	 * Options page metabox id
 	 * @var string
 	 */
	private $metabox_id = 'wds-ratings';

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
		$this->title = __( 'Ratings Options', 'wds_ratings' );

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_filter( 'cmb2_init', array( $this, 'add_options_page_metabox' ) );
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
		$this->options_page = add_submenu_page(
			'options-general.php',
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
	 */
	function add_options_page_metabox() {

		$cmb = new_cmb2_box( array(
			'id'      => $this->metabox_id,
			'hookup'  => false,
			'show_on' => array(
				'key'   => 'options-page',
				'value' => array( $this->key, )
			),
		) );

		// Set our CMB2 fields

		$cmb->add_field( array(
			'name' => __( 'Ratings Granular Control', 'wds_ratings' ),
			'id'   => 'filter_type',
			'type' => 'radio',
			'options' => array(
				'exclusive' => __( 'Option to Hide', 'wds_ratings' ) . '<p class="cmb2-metabox-description">' . __( 'Defaults to the checkbox on the post/page edit screen causing the rating to be <strong>removed</strong> from that post (while others are on by default).', 'wds_ratings' ) . '</p>',
				'inclusive' => __( 'Option to Show', 'wds_ratings' ) . '<p class="cmb2-metabox-description">' . __( 'Defaults to the checkbox on the post/page edit screen causing the rating to be <strong>added</strong> to that post (and others are off by default).', 'wds_ratings' ) . '</p>',
			),
			'default' => 'exclusive',
		) );

		$cmb->add_field( array(
			'name' => __( 'Enable Content Filter', 'cmb2' ),
			'desc' => __( 'Automatically add ratings before post content', 'wds_ratings' ),
			'id'   => 'enable_content_filter',
			'type' => 'checkbox',
		) );

		$cmb->add_field( array(
			'name' => __( 'Enable Widget', 'cmb2' ),
			'id'   => 'enable_widget',
			'type' => 'checkbox',
		) );

	}

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

}
