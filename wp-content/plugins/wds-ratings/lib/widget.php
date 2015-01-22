<?php
 // Prevent direct file access
if ( ! defined ( 'ABSPATH' ) ) {
	exit;
}

class WDS_Ratings_Widget extends WP_Widget {
    protected $widget_slug = 'wds-ratings-widget';

	public function __construct() {
		// load plugin text domain
		add_action( 'init', array( $this, 'widget_textdomain' ) );
		// Hooks fired when the Widget is activated and deactivated
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		// TODO: update description
		parent::__construct(
			$this->get_widget_slug(),
			__( 'WDS Ratings', $this->get_widget_slug() ),
			array(
				'classname'  => $this->get_widget_slug().'-class',
				'description' => __( 'Displays post ratings', $this->get_widget_slug() )
			)
		);
	} 
	
    /**
     * Return the widget slug.
     *
     * @since    0.2.0
     *
     * @return    Plugin slug variable.
     */
    public function get_widget_slug() {
        return $this->widget_slug;
    }

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array args  The array of form elements
	 * @param array instance The current instance of the widget
	 */
	public function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		
		$title = apply_filters( 'widget_title', $instance['title'] );
		
		$widget_string = $before_widget . $title;

		if ( function_exists( 'wds_post_ratings' ) ) {
			$widget_string .= wds_post_ratings( false );
		}
		
		$widget_string .= $after_widget;
		echo  $widget_string;
		
	}
	
	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param array new_instance The new instance of values to be generated via the update.
	 * @param array old_instance The previous instance of values before the update.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}
	
	/**
	 * Generates the administration form for the widget.
	 *
	 * @param array instance The array of keys and values for the widget.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			(array) $instance
		);
		
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = __( 'New title', 'text_domain' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	} 
	
	/**
	 * Loads the Widget's text domain for localization and translation.
	 */
	public function widget_textdomain() {
		load_plugin_textdomain( $this->get_widget_slug(), false, plugin_dir_path( __FILE__ ) . 'lang/' );
	}
	
	/**
	 * Fired when the plugin is activated.
	 *
	 * @param  boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public function activate( $network_wide ) { } 
	
	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	public function deactivate( $network_wide ) { }
	
} //end WDS_Ratings_Widget

add_action( 'widgets_init', create_function( '', 'register_widget( "WDS_Ratings_Widget" );' ) );