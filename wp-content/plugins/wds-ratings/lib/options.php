<?php
if( ! class_exists( 'WDS_Ratings_Options' ) ):
class WDS_Ratings_Options {
	/**
	 * Setup our class
	 * @since  0.1.0
	 * @access public
	 */
	public function __construct( $wds_ratings ) {
		$this->wds_ratings = $wds_ratings;
	}
	
	/**
	 * Add the admin menu
	 * @since  0.1.0
	 * @access public
	 */
	public function add_admin_menu() {
		add_options_page( 
			'WDS Ratings', 
			'WDS Ratings',
			'manage_options',
			'wds_ratings_plugin',
			array( $this, 'wds_ratings_plugin_options_page' )
		 );
	}
	
	/**
	 * Check to see if our settings exist
	 * @since  0.1.0
	 * @access public
	 */
	public function wds_ratings_settings_exist() {
		if( false == get_option( 'wds_ratings_plugin_settings' ) ) {
			add_option( 'wds_ratings_plugin_settings' );
		}
	}
	
	/**
	 * Compile our settings
	 * @since  0.1.0
	 * @access public
	 */
	public function settings_init() { 
		register_setting( 'WDS_Ratings_settingsPage', 'wds_ratings_settings' );
		
		// only add the js on the WDS Ratings admin page
		if( is_admin() 
			&& isset( $_GET['page'] ) 
			&& $_GET['page'] === 'wds_ratings_plugin' 
		) {
			// wp_enqueue_media();
			wp_enqueue_script( 
				'wp-easter-egg-admin', 
				plugins_url( '/wds-ratings-admin.js', __FILE__ ), array( 'jquery' ) 
			 );
		}
		
		add_settings_section(
			'wds_ratings_settingsPage_section', 
			__( '', 'wds_ratings' ), 
			array( $this, 'wds_ratings_settings_section_callback' ), 
			'WDS_Ratings_settingsPage'
		);
		
		add_settings_field( 
			'wds_ratings_filter_render', 
			__( 'WDS Ratings Filter', 'wds_ratings' ), 
			array( $this, 'wds_ratings_filter_render' ), 
			'WDS_Ratings_settingsPage', 
			'wds_ratings_settingsPage_section' 
		);
		
		add_settings_field( 
			'enable_content_filter_render', 
			__( 'Enable content filter', 'wds_ratings' ), 
			array( $this, 'enable_content_filter_render' ), 
			'WDS_Ratings_settingsPage', 
			'wds_ratings_settingsPage_section' 
		);
	}
	
	/**
	 * Filter for including/excluding posts
	 * @since  0.1.0
	 * @access public
	 */
	public function wds_ratings_filter_render() {
		?>
		<select name='wds_ratings_settings[filter]'>
			<option value='off' <?php selected( WDS_Ratings::fetch_option( 'filter' ), 'off' ); ?>>Off</option>
			<option value='exclusive' <?php selected( WDS_Ratings::fetch_option( 'filter' ), 'exclusive' ); ?>>Exclusive</option>
			<option value='inclusive' <?php selected( WDS_Ratings::fetch_option( 'filter' ), 'inclusive' ); ?>>Inclusive</option>
		</select>
		<?php
	}
	
	/**
	 * Checkbox for enabling the content filter
	 * @since  0.1.0
	 * @access public
	 */
	public function enable_content_filter_render() {
		?>
		<input type='checkbox' name='wds_ratings_settings[enable_content_filter]' <?php checked( WDS_Ratings::fetch_option( 'enable_content_filter' ), 1 ); ?> value='1'>
		<?php
	}
	
	/**
	 * Callback for our settings
	 * @since  0.1.0
	 * @access public
	 */
	public function wds_ratings_settings_section_callback() {
		echo __( '', 'wds_ratings' );
	}
	
	/**
	 * Create the options page
	 * @since  0.1.0
	 * @access public
	 */
	public function wds_ratings_plugin_options_page() {
		?>
		<form action='options.php' method='post'>
			<h2>WDS Ratings Plugin</h2>
			<?php
			settings_fields( 'WDS_Ratings_settingsPage' );
			do_settings_sections( 'WDS_Ratings_settingsPage' );
			submit_button();
			?>	
		</form>
		<?php
	}
}
endif;