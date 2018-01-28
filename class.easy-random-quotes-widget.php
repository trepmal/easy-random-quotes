<?php
/**
 *
 */
class Easy_Random_Quotes_Widget extends WP_Widget {

	/**
	 *
	 */
	function __construct() {
		$widget_ops = array(
			'classname'   => 'easy-random-quotes',
			'description' => __( 'Displays random quotes', 'easy-random-quotes' )
		);
		$control_ops = array();
		parent::__construct( 'easy-random-quotes', __( 'Easy Random Quotes', 'easy-random-quotes' ), $widget_ops, $control_ops );
	}

	/**
	 *
	 */
	function widget( $args, $instance ) {

		$before_widget = $args['before_widget'];
		$after_widget = $args['after_widget'];
		$before_title = $args['before_title'];
		$after_title = $args['after_title'];

		$output = '';
		$output .= $before_widget;

		if ( ! $instance[ 'hide_title' ] ) {
			$output .= $before_title . apply_filters( 'widget_title', $instance[ 'title' ] ) . $after_title;
		}

		$output .= '<p>' . wp_kses_post( erq_shortcode() ) . '</p>';
		$output .= $after_widget;

		echo wp_kses_post( $output );
	}

	/**
	 *
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']      = esc_attr( $new_instance['title'] );
		$instance['hide_title'] = (bool) $new_instance['hide_title'] ? 1 : 0;
		return $instance;
	}

	/**
	 *
	 */
	function form( $instance ) {
		$defaults = array(
			'title'      => __( 'A Random Thought', 'easy-random-quotes' ),
			'hide_title' => 0
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'easy-random-quotes' );?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
			</label>
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id('hide_title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('hide_title') ); ?>"<?php checked( $instance['hide_title'] ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'hide_title' ) ); ?>"><?php esc_html_e('Hide Title?', 'easy-random-quotes' );?></label>
		</p>
		<?php
	}

}
