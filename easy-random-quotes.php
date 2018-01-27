<?php
/*
 * Plugin Name: Easy Random Quotes
 * Plugin URI: http://trepmal.com/plugins/easy-random-quotes/
 * Description: Insert quotes and pull them randomly into your pages and posts (via shortcodes) or your template (via template tags).
 * Author: Kailey Lampert
 * Version: 2.0-dev
 * Author URI: http://kaileylampert.com/
 * License: GPLv2 or later
 * TextDomain: easy-random-quotes
 * DomainPath: lang/
 */

$easy_random_quotes = new Easy_Random_Quotes();

class Easy_Random_Quotes {

	/**
	 *
	 */
	private $page_name;

	/**
	 *
	 */
	function __construct() {
		add_action( 'widgets_init',    array( $this, 'widgets_init' ) );

		add_action( 'admin_menu',      array( $this, 'menu' ) );
		add_action( 'contextual_help', array( $this, 'help'), 10, 3 );

		add_shortcode( 'erq', 'erq_shortcode' );
	}

	/**
	 *
	 */
	function widgets_init() {
		register_widget( 'Easy_Random_Quotes_Widget' );
	}

	/**
	 *
	 */
	function menu() {
		$this->page_name = add_options_page(
			__( 'Easy Random Quotes', 'easy-random-quotes' ),
			__( 'Easy Random Quotes', 'easy-random-quotes' ),
			'manage_options',
			__CLASS__,
			array( $this, 'page' )
		);

		add_action( 'admin_head-' . $this->page_name, array( $this, 'verify_nonce' ) );

	}

	function verify_nonce() {
		if ( isset( $_POST['erq_add'] ) || isset( $_POST['erq_import'] ) ) {
			check_admin_referer( 'easyrandomquotes-update_add' );
		}
		if ( isset( $_POST['erq_quote'] ) ) {
			check_admin_referer( 'easyrandomquotes-update_edit' );
		}
	}
	/**
	 *
	 */
	function update() {

		if ( isset( $_POST['erq_add'] ) ) {
			$newquote = wp_filter_post_kses( $_POST['erq_newquote'] );

			if ( ! empty( $newquote ) ) {

				$theQuotes = get_option( 'kl-easyrandomquotes', array() ); //get existing

				$theQuotes[] = $newquote; //add new

				check_admin_referer( 'easyrandomquotes-update_add' );

				update_option( 'kl-easyrandomquotes', $theQuotes ); //successfully updated

				$this->alert_message( __( 'New quote was added', 'easy-random-quotes' ) );

			} else {
				$this->alert_message( __( 'Nothing added', 'easy-random-quotes' ) );
			}

		} // end if add

		if ( isset( $_POST['erq_import'] ) ) {

			$newquotes = array_filter( explode( "\n", $_POST['erq_newquote'] ) );
			$newquotes = array_map( 'wp_filter_post_kses', $newquotes );

			$erq_count = count( $newquotes );

			$theQuotes = get_option( 'kl-easyrandomquotes', array() );

			// array_merge messes up the keys, and using the '+' method will skip certain items
			foreach ( $newquotes as $newquote ) {
				$theQuotes[] = $newquote;
			}

			check_admin_referer( 'easyrandomquotes-update_add' );

			update_option( 'kl-easyrandomquotes', $theQuotes ); //successfully updated

			$this->alert_message( sprintf( __( '%d new quotes were added', 'easy-random-quotes' ), $erq_count ) );

		} // end if add

		if ( isset( $_POST[ 'erq_quote' ] ) ) {

			$ids       = $_POST[ 'erq_quote' ];
			$dels      = isset( $_POST[ 'erq_del' ] ) ? $_POST[ 'erq_del' ] : array();
			$theQuotes =  get_option( 'kl-easyrandomquotes', array() );

			// update each quote
			foreach ( $ids as $id => $quote ) {
				$theQuotes[ $id ] = wp_filter_post_kses( $quote ); // update each part with new quote
			}

			// delete all checked quotes
			foreach ( $dels as $id => $quote ) {
				unset( $theQuotes[ $id ] );
			}

			check_admin_referer( 'easyrandomquotes-update_edit' );

			if ( update_option( 'kl-easyrandomquotes',$theQuotes ) ) {
				$this->alert_message( __( 'Quote was edited/deleted', 'easy-random-quotes' ) );
			} else {
				$this->alert_message( __( 'Nothing changed', 'easy-random-quotes' ) );
			}

		} // end if edit

		if ( isset( $_POST[ 'clear' ] ) ) {

			if ( ! isset( $_POST[ 'confirm' ] ) ) {

				$this->alert_message( __( 'You must confirm for a reset', 'easy-random-quotes' ) );

			} elseif ( delete_option( 'kl-easyrandomquotes' ) ) {

				$this->alert_message( __( 'All quotes deleted', 'easy-random-quotes' ) );

			}

		} // end if clear

	} // end update()


	function alert_message( $message, $type='updated' ) {
		printf(
			'<div class="%s"><p>%s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}

	/**
	 *
	 */
	function page() {
		$this->update();
		echo '<div class="wrap">';

		echo '<h2>' . esc_html__( 'Easy Random Quotes', 'easy-random-quotes' ) . '</h2>';

		echo '<h3>' . esc_html__( 'Add Quote', 'easy-random-quotes' ) . '</h3>';

			echo '<form method="post">';

			wp_nonce_field( 'easyrandomquotes-update_add' );
			echo '<table class="widefat page"><thead><tr><th class="manage-column" colspan = "2">' . esc_html__( 'Add New', 'easy-random-quotes' ) . '</th></tr></thead><tbody><tr>';
			echo '<td><textarea name="erq_newquote" rows="6" cols="60"></textarea></td>';

			printf(
				"<td><p>%s %s</p><p>%s</p></td>",
				get_submit_button( esc_html__( 'Add', 'easy-random-quotes' ), 'small', 'erq_add', false ),
				get_submit_button( esc_html__( 'Import', 'easy-random-quotes' ), 'small', 'erq_import', false ),
				esc_html__( 'With Import, each new line will be treated as the start of a new quote', 'easy-random-quotes' )
			);

			echo '</tr></tbody></table></form>';

		echo '<h3>' . esc_html__( 'Edit Quotes' ) . '</h3>';
			echo '<form method="post">';
			wp_nonce_field( 'easyrandomquotes-update_edit' );

			$escaped_tblrows = '<tr><td class="manage-column column-cb check-column" id="cb"><input type="checkbox" /></th>
							<th scope="col" id="quote" class="manage-column column-quote">' . esc_html__( 'The quote', 'easy-random-quotes' ) . '</th>
							<th scope="col" id="shortcode" class="manage-column column-shortcode">' . esc_html__( 'Short code (for posts/pages)', 'easy-random-quotes' ) . '</th></tr>';

			echo '<table class="widefat page"><thead>' . $escaped_tblrows . '</thead><tfoot>' . $escaped_tblrows . '</tfoot><tbody>';

			$all_quotes =  get_option( 'kl-easyrandomquotes', array() ) ;

			if ( ! empty( $all_quotes ) ) {
				foreach ( $all_quotes as $id => $quote ) {
					echo '<tr>';
					echo '<th class="check-column"><input type="checkbox" name="erq_del[' . esc_attr( $id ) . ']" /></th>';
					echo '<td><textarea name="erq_quote[' . esc_attr( $id ) . ']" rows="6" cols="60">' . esc_textarea( stripslashes( $quote ) ) . '</textarea></td>';
					echo '<td>[erq id=' . esc_attr( $id ) . ']</td>';
					echo '</tr>';
				}
			} else {
				echo '<tr><th colspan="3">' . esc_html__( 'No quotes', 'easy-random-quotes' ) . '</th></tr>';
			}

			echo '</tbody></table>';

			echo '<p>';
			submit_button( esc_html__( 'Save Changes', 'easy-random-quotes' ), 'primary', 'submit', false );
			echo ' <span class="description">' . esc_html__( 'Checked items will be deleted', 'easy-random-quotes' ) . '</span>';
			echo '</p>';
			echo '</form>';

		echo '</div>';

	}// end page()

	/**
	 *
	 */
	function help( $contextual_help, $screen_id, $screen ) {

		if ( $screen_id != $this->page_name ) return;

		$eg_specific = sprintf( esc_html__( 'Specific quote: %s', 'easy-random-quotes' ), '<code>[erq id=2]</code>' );
		$eg_random   = sprintf( esc_html__( 'Random quote: %s', 'easy-random-quotes' ), '<code>[erq]</code>' );
		$content = sprintf(
			'<p><strong>%s</strong><br />%s<br />%s</p>',
			esc_html__( 'Shortcode', 'easy-random-quotes' ),
			$eg_specific,
			$eg_random
		);

		$eg_specific = sprintf( esc_html__( 'Specific quote: %s', 'easy-random-quotes' ), '<code>' . htmlspecialchars( '<?php echo erq_shortcode(array(\'id\' => \'2\')); ?>' ) . '</code>' );
		$eg_random   = sprintf( __( 'Random quote: %s', 'easy-random-quotes' ), '<code>' . htmlspecialchars( '<?php echo erq_shortcode(); ?>' ) . '</code>' );
		$content .= sprintf(
			'<p><strong>%s</strong><br />%s<br />%s</p>',
			esc_html__( 'Template tag', 'easy-random-quotes' ),
			$eg_specific,
			$eg_random
		);

		$content .= sprintf(
			'<p>%s</p>',
			esc_html__( 'Quotes retained when plugin deactivated. Quotes deleted when plugin removed.', 'easy-random-quotes' )
		);

		$content .= sprintf(
			'<form method="post">%s <label><input type="checkbox" name="confirm" value="true" />%s</label></form>',
			get_submit_button( esc_html__( 'Delete All Quotes', 'easy-random-quotes' ), 'secondary', 'clear', false ),
			esc_html__( 'Check to confirm', 'easy-random-quotes' )
		);


		$screen->add_help_tab( array(
			'id'      => 'erq_help',
			'title'   => 'Help',
			'content' => $content,
		) );

	}

}

/**
 * shortcode/template tag
 * outside of class to make it more accessible
 */
function erq_shortcode( $atts=array() ) {
	$atts = shortcode_atts( array(
		'id' => 'rand',
	), $atts );

	$id = $atts['id'];

	$id = explode( ',', $id );
	shuffle( $id );
	$id = array_pop( $id );

	$all_quotes = get_option( 'kl-easyrandomquotes', array() ); //get exsisting
	$use = ( 'rand' === $id ) ? array_rand( $all_quotes ) : $id;
	if ( isset( $all_quotes[ $use ] ) ) {
		return stripslashes( $all_quotes[ $use ] );
	}
}

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
		$after_widget = $args['after_widget'];

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