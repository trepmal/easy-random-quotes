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

require( plugin_dir_path( __FILE__ ) . '/class.easy-random-quotes.php' );
require( plugin_dir_path( __FILE__ ) . '/class.easy-random-quotes-admin.php' );
require( plugin_dir_path( __FILE__ ) . '/class.easy-random-quotes-widget.php' );

$easy_random_quotes = new Easy_Random_Quotes();
$ERQ_Admin = new Easy_Random_Quotes_Admin();

/**
 * Template tag
 *
 * @param array $atts {
 *     Optional. Array of parameters
 *
 *     @type int|string $id Quote ID, or comma-separated list of ids, or "rand".
 * }
 */
function erq_shortcode( $atts=array() ) {
	$atts = shortcode_atts( array(
		'id' => 'rand',
	), $atts );

	if ( 'rand' !== $atts['id'] )  {
		$ids = wp_parse_id_list( $atts['id'] );
		shuffle( $ids );
		$id = array_pop( $ids );

		$quote_post = get_post( $id );

	} else {

		$cache_key = md5( 'erq-rand-query' . serialize( $atts ) );

		// orderby=rand queries are expensive, so let's cache 10 posts
		// for an hour, then randomly select from those
		if ( ! $quotes = wp_cache_get( $cache_key, 'erq' ) ) {
			$quotes = get_posts( [
				'suppress_filters' => false,
				'post_type'        => 'easy_random_quote',
				'orderby'          => 'rand', // rand is sub-optimal, but kinda in the name. Cache it.
				'posts_per_page'   => 10,
			] );
			wp_cache_set( $cache_key, $quotes, 'erq', HOUR_IN_SECONDS );
		}
		shuffle( $quotes );
		$quote_post = array_pop( $quotes );
	}

	if ( is_a( $quote_post, 'WP_Post' ) ) {
		$quote = $quote_post->post_content;
		return apply_filters( 'the_content', $quote );
	}
	return '';

}
add_shortcode( 'erq', 'erq_shortcode' );
