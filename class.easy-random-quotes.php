<?php
/**
 *
 */
class Easy_Random_Quotes {

	/**
	 *
	 */
	private $page_name;

	/**
	 *
	 */
	function __construct() {

		add_action( 'admin_init',      array( $this, 'migrate' ), 11 );
		add_action( 'widgets_init',    array( $this, 'widgets_init' ) );

		add_action( 'admin_menu',      array( $this, 'menu' ) );
		add_action( 'contextual_help', array( $this, 'help'), 10, 3 );

	}

	/**
	 * Migrate data
	 */
	function migrate() {

		// if we've already migrated, bail
		if ( get_option( 'erq-has-migrated', false ) ) {
			return;
		}

		// if there are not old options-data, bail
		if ( ! ( $all_quotes = get_option( 'kl-easyrandomquotes', false ) ) ) {
			return;
		}

		$count_old_quotes = count( $all_quotes );

		$all_quotes = array_map( 'stripslashes', $all_quotes );

		$successes = self::bulk_import( $all_quotes, true );

		update_option( 'erq-has-migrated', 'erq-has-migrated' );

		add_action( 'admin_notices', function() use( $count_old_quotes, $successes ) {
			echo '<div class="updated"><p>';
			printf(
				esc_html__( '%d of %d quotes migrated. Original data not harmed.', 'easy-random-quotes' ),
				intval( $successes ),
				intval( $count_old_quotes )
			);
			echo '</p></div>';
		} );


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
		$this->page_name = add_submenu_page(
			'edit.php?post_type=easy_random_quote',
			__( 'Bulk Import Quotes', 'easy-random-quotes' ),
			__( 'Bulk Import Quotes', 'easy-random-quotes' ),
			'manage_options',
			__CLASS__,
			array( $this, 'page' )
		);

		add_action( 'admin_head-' . $this->page_name, array( $this, 'handle_submission' ) );

	}

	function handle_submission() {

		if ( isset( $_POST['erq_import'] ) && isset( $_POST['erq_newquote'] ) ) {

			check_admin_referer( 'easy-random-quotes-bulk-import' );

			$new_quotes = sanitize_textarea_field( wp_unslash( $_POST['erq_newquote'] ) );
			$new_quotes = array_filter( explode( "\n", $new_quotes ) );

			$successes = self::bulk_import( $new_quotes );

			add_action( 'admin_notices', function() use( $successes ) {
				echo '<div class="updated"><p>';
				printf(
					esc_html__( '%d new quotes were added', 'easy-random-quotes' ),
					intval( $successes )
				);
				echo '</p></div>';
			} );

		}

	}

	function bulk_import( $quotes, $preserve_keys=false ) {
		$successes = 0;

		foreach( $quotes as $id => $quote ) {

			$title = wp_trim_words( $quote, 10 );

			$post_id = wp_insert_post( [
				'post_type'    => 'easy_random_quote',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $quote,
			] );

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				$successes++;
				if ( $preserve_keys ) {
					update_post_meta( $post_id, 'original_id', $id );
				}
			}

		}

		return $successes;
	}

	/**
	 *
	 */
	function page() {
		echo '<div class="wrap">';

		echo '<h2>' . esc_html__( 'Easy Random Quotes - Bulk Import', 'easy-random-quotes' ) . '</h2>';

		echo '<p>' . esc_html__( 'Each new line will be treated as the start of a new quote.', 'easy-random-quotes' ) . '</p>';

			echo '<form method="post">';

			wp_nonce_field( 'easy-random-quotes-bulk-import' );

			echo '<div><textarea name="erq_newquote" rows="6" cols="60"></textarea></div>';

			submit_button( __( 'Import', 'easy-random-quotes' ), 'primary', 'erq_import' );

			echo '</form>';


		echo '</div>';

	}// end page()

	/**
	 *
	 */
	function help( $contextual_help, $screen_id, $screen ) {

		if ( $screen_id != $this->page_name ) {
			return;
		}

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
			esc_html__( 'Quotes retained when plugin deactivated.', 'easy-random-quotes' )
		);

		$screen->add_help_tab( array(
			'id'      => 'erq_help',
			'title'   => 'Help',
			'content' => $content,
		) );

	}

}

