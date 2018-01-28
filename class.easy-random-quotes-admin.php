<?php
/**
 *
 */
class Easy_Random_Quotes_Admin {

	function __construct() {
		add_action( 'init',                                         array( $this, 'init' ) );
		add_filter( 'enter_title_here',                             array( $this, 'title_prompt' ) );

		// @todo On post save, create post title from content

		add_filter( 'default_hidden_columns',                    array( $this, 'hidden_columns' ), 10, 2 );
		add_filter( 'manage_edit-easy_random_quote_columns',        array( $this, 'columns' ) );
		add_action( 'manage_easy_random_quote_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'post_updated_messages',                        array( $this, 'messages' ) );
	}

	/**
	 * Register post type
	 */
	function init() {
		register_post_type( 'easy_random_quote', array(
			'labels'            => array(
				'name'                  => __( 'Quotes', 'easy-random-quote' ),
				'singular_name'         => __( 'Quote', 'easy-random-quote' ),
				'all_items'             => __( 'All Quotes', 'easy-random-quote' ),
				'archives'              => __( 'Quote Archives', 'easy-random-quote' ),
				'attributes'            => __( 'Quote Attributes', 'easy-random-quote' ),
				'insert_into_item'      => __( 'Insert into Quote', 'easy-random-quote' ),
				'uploaded_to_this_item' => __( 'Uploaded to this Quote', 'easy-random-quote' ),
				'featured_image'        => _x( 'Featured Image', 'easy-random-quote', 'easy-random-quote' ),
				'set_featured_image'    => _x( 'Set featured image', 'easy-random-quote', 'easy-random-quote' ),
				'remove_featured_image' => _x( 'Remove featured image', 'easy-random-quote', 'easy-random-quote' ),
				'use_featured_image'    => _x( 'Use as featured image', 'easy-random-quote', 'easy-random-quote' ),
				'filter_items_list'     => __( 'Filter Quotes list', 'easy-random-quote' ),
				'items_list_navigation' => __( 'Quotes list navigation', 'easy-random-quote' ),
				'items_list'            => __( 'Quotes list', 'easy-random-quote' ),
				'new_item'              => __( 'New Quote', 'easy-random-quote' ),
				'add_new'               => __( 'Add New', 'easy-random-quote' ),
				'add_new_item'          => __( 'Add New Quote', 'easy-random-quote' ),
				'edit_item'             => __( 'Edit Quote', 'easy-random-quote' ),
				'view_item'             => __( 'View Quote', 'easy-random-quote' ),
				'view_items'            => __( 'View Quotes', 'easy-random-quote' ),
				'search_items'          => __( 'Search Quotes', 'easy-random-quote' ),
				'not_found'             => __( 'No Quotes found', 'easy-random-quote' ),
				'not_found_in_trash'    => __( 'No Quotes found in trash', 'easy-random-quote' ),
				'parent_item_colon'     => __( 'Parent Quote:', 'easy-random-quote' ),
				'menu_name'             => __( 'Quotes', 'easy-random-quote' ),
			),
			'public'            => false,
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_in_nav_menus' => false,
			'supports'          => array( 'editor' ),

			'has_archive'       => false,
			'rewrite'           => false,
			'query_var'         => false,
			'menu_icon'         => 'dashicons-format-quote',
			'show_in_rest'      => true,
			'rest_base'         => 'quotes',
			'rest_controller_class' => 'WP_REST_Posts_Controller',

			// [Gutenberg Support]
			'template' => array(
				array( 'core/paragraph', array(
					'placeholder' => __( 'Add quote...', 'easy-random-quotes' ),
				) ),
			),
			'template_lock' => 'all',
		) );

	}

	/**
	 * [Gutenberg Support]
	 * Change title placeholder/prompt on edit screen
	 *
	 * Gutenberg doesn't yet support our lack of Title support
	 * https://github.com/WordPress/gutenberg/issues/1914
	 */
	function title_prompt( $prompt ) {
		if ( 'easy_random_quote' === get_post_type() ) {
			return __( 'Ignore this for now', 'easy-random-quotes' );
		}
		return $prompt;
	}

	/**
	 * Hide ID column by default
	 */
	function hidden_columns( $hidden, $screen ) {
		if ( 'easy_random_quote' !== $screen->post_type ) {
			return $hidden;
		}

		$hidden[] = 'quote_id';
		return $hidden;
	}

	/**
	 * Define columns for edit screen
	 */
	function columns( $columns ) {

		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Quote', 'easy-random-quotes' ),
			'quote' => __( 'Full Quote', 'easy-random-quotes' ),
			'quote_id' => __( 'ID', 'easy-random-quotes' ),
			// 'original_id' => __( 'Original ID', 'easy-random-quotes' ),
		);

		return $columns;
	}

	/**
	 * Populate columns for edit screen
	 */
	function column_content( $column, $post_id ) {

		global $post;
		switch ( $column ) {
			case 'quote':
				the_content();
				break;
			case 'quote_id':
				the_ID();
				$original_id = get_post_meta( $post_id, 'original_id', true );
				echo ( '' !== $original_id ) ? ' (' . intval( $original_id ) . ')' : '';
				break;
			case 'original_id':
				break;
		}
	}

	/**
	 * Define default messages
	 */
	function messages( $messages ) {
		global $post;

		$permalink = get_permalink( $post );

		$messages['easy-random-quote'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __('Quote updated. <a target="_blank" href="%s">View Quote</a>', 'easy-random-quote'), esc_url( $permalink ) ),
			2 => __('Custom field updated.', 'easy-random-quote'),
			3 => __('Custom field deleted.', 'easy-random-quote'),
			4 => __('Quote updated.', 'easy-random-quote'),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __('Quote restored to revision from %s', 'easy-random-quote'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Quote published. <a href="%s">View Quote</a>', 'easy-random-quote'), esc_url( $permalink ) ),
			7 => __('Quote saved.', 'easy-random-quote'),
			8 => sprintf( __('Quote submitted. <a target="_blank" href="%s">Preview Quote</a>', 'easy-random-quote'), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
			9 => sprintf( __('Quote scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Quote</a>', 'easy-random-quote'),
			// translators: Publish box date format, see https://secure.php.net/manual/en/function.date.php
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $permalink ) ),
			10 => sprintf( __('Quote draft updated. <a target="_blank" href="%s">Preview Quote</a>', 'easy-random-quote'), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		);

		return $messages;
	}


}