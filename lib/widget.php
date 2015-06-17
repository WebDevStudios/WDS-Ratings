<?php
 // Prevent direct file access
if ( ! defined ( 'ABSPATH' ) ) {
	exit;
}

class WDS_Ratings_Widget extends WP_Widget {
	protected $widget_slug = 'wds-ratings-widget';

	public function __construct() {
		parent::__construct(
			$this->widget_slug,
			__( 'WDS Ratings', 'wds_ratings' ),
			array(
				'classname'  => $this->widget_slug.'-class',
				'description' => __( 'Displays post ratings', 'wds_ratings' )
			)
		);
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array args  The array of form elements
	 * @param array instance The current instance of the widget
	 */
	public function widget( $args, $instance ) {

		// This filter is documented in wp-includes/default-widgets.php
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		wds_post_ratings();

		echo $args['after_widget'];
	}

	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param array new_instance The new instance of values to be generated via the update.
	 * @param array old_instance The previous instance of values before the update.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Generates the administration form for the widget.
	 *
	 * @param array instance The array of keys and values for the widget.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array(
			'title' => __( 'Post Ratings', 'wds_ratings' )
		) );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wds_ratings' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<?php
	}

}

function wds_ratings_widget_register() {
	register_widget( 'WDS_Ratings_Widget' );
}
add_action( 'widgets_init', 'wds_ratings_widget_register' );
