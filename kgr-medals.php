<?php

/*
 * Plugin Name: KGR Medals
 * Plugin URI: https://github.com/constracti/wp-medals
 * Author: constracti
 * Version: 1.1.1
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( !defined( 'ABSPATH' ) )
	exit;

define( 'KGR_MEDALS_DIR', plugin_dir_path( __FILE__ ) );
define( 'KGR_MEDALS_URL', plugin_dir_url( __FILE__ ) );

require_once( KGR_MEDALS_DIR . 'settings.php' );

$kgr_medals = [
	'golden',
	'silver',
	'bronze',
];

function kgr_medals_get_user_vote( int $user_id, int $term_id ): array {
	global $kgr_medals;
	$key = sprintf( 'kgr_medals_%d', $term_id );
	$value = get_user_meta( $user_id, $key, TRUE );
	if ( $value === '' )
		return array_fill_keys( $kgr_medals, 0 );
	return $value;
}

function kgr_medals_set_user_vote( int $user_id, int $term_id, array $value ) {
	$key = sprintf( 'kgr_medals_%d', $term_id );
	update_user_meta( $user_id, $key, $value );
}

function kgr_medals_clear_nonce( int $user_id, int $term_id ): string {
	return sprintf( 'kgr_medals_clear-u%d-t%d', $user_id, $term_id );
}

function kgr_medals_vote_nonce( int $user_id, int $term_id, string $medal, int $post_id ): string {
	return sprintf( 'kgr_medals_vote-u%d-t%d-%s-%p', $user_id, $term_id, $medal, $post_id );
}

function kgr_medals_get_campaign( int $term_id ) {
	$campaigns = get_option( 'kgr-medals', [] );
	foreach ( $campaigns as $campaign )
		if ( $campaign['term'] === $term_id )
			return $campaign;
	return NULL;
}

add_shortcode( 'kgr-medals', function( $atts ) {
	global $kgr_medals;
	$text = [];
	foreach ( array_merge( $kgr_medals, ['clear', 'total'] ) as $en )
		$text[ $en ] = array_key_exists( $en, $atts ) ? $atts[ $en ] : $en;
	$term_id = intval( $atts['id'] );
	$term = get_term( $term_id );
	$campaign = kgr_medals_get_campaign( $term_id );
	if ( is_null( $campaign ) )
		return '';
	$user_id = get_current_user_id();
	if ( $user_id !== 0 )
		$vote = kgr_medals_get_user_vote( $user_id, $term_id );
	$html = '';
	if ( $user_id === 0 && $campaign['open'] && array_key_exists( 'prompt', $atts ) )
		$html .= sprintf( '<p><a href="%s">%s</a></p>', wp_login_url( get_permalink() ), $atts['prompt'] ) . "\n";
	if ( !$campaign['open'] && array_key_exists( 'closed', $atts ) )
		$html .= sprintf( '<p>%s</p>', $atts['closed'] ) . "\n";
	// stand
	if ( $user_id !== 0 ) {
		$html .= '<table class="kgr_medals_stand">' . "\n";
		$html .= '<tbody>' . "\n";
		foreach ( $kgr_medals as $medal ) {
			$html .= '<tr class="kgr_medals_stand_step">' . "\n";
			$html .= sprintf( '<th data-kgr_medals_color="%s">%s</th>', $medal, $text[ $medal ] ) . "\n";
			$html .= '<td class="kgr_medals_stand_step_html"></td>' . "\n";
			$html .= '</tr>' . "\n";
		}
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
	}
	// clear
	if ( $user_id !== 0 && $campaign['open'] ) {
		$nonce = wp_create_nonce( kgr_medals_clear_nonce( $user_id, $term_id ) );
		$url = admin_url( sprintf( 'admin-ajax.php?action=kgr_medals_clear&term=%d&nonce=%s', $term_id, $nonce ) );
		$html .= sprintf( '<p><a class="kgr_medals_clear colormag-button" href="%s">%s</a></p>', $url, $text['clear'] ) . "\n";
	}
	// total
	$user_ids = get_users( [
		'meta_key' => sprintf( 'kgr_medals_%d', $term_id ),
		'orderby' => 'email',
		'order' => 'ASC',
		'fields' => 'ids',
	] );
	$html .= sprintf( '<p>%s: <span class="kgr_medals_total">%d</span></p>', $text['total'], count( $user_ids ) ) . "\n";
	// posts
	$posts = get_posts( [
		'tax_query' => [ [ 'taxonomy' => $term->taxonomy, 'terms' => $term_id ] ],
		'post_type' => 'post',
		'nopaging' => TRUE,
		'order' => 'ASC',
		'orderby' => 'title',
	] );
	foreach ( $posts as $post ) {
		$title = $post->post_title;
		$url = home_url( sprintf( '?p=%d', $post->ID ) );
		$html .= '<hr />' . "\n";
		$html .= '<div class="kgr_medals_post">' . "\n";
		$html .= sprintf( '<div class="kgr_medals_post_html"><a href="%s" title="%s">%s</a></div>', $url, $title, $title ) . "\n";
		$html .= sprintf( '<p><a href="%s" title="%s"><b>%s</b></a></p>', $url, $title, $title ) . "\n";
		if ( !empty( $post->post_excerpt ) )
			$html .= sprintf( '<p>%s</p>', $post->post_excerpt ) . "\n";
		if ( $user_id !== 0 ) {
			$html .= '<p class="kgr_medals_post_medals">' . "\n";
			$first = TRUE;
			foreach ( $kgr_medals as $medal ) {
				if ( $first )
					$first = FALSE;
				else
					$html .= '|' . "\n";
				$class = ['kgr_medals_post_medal'];
				if ( $vote[ $medal ] === $post->ID )
					$class[] = 'kgr_medals_post_medal_active';
				$nonce = wp_create_nonce( kgr_medals_vote_nonce( $user_id, $term_id, $medal, $post->ID ) );
				$url = admin_url( sprintf( 'admin-ajax.php?action=kgr_medals_vote&term=%d&medal=%s&post=%d&nonce=%s', $term_id, $medal, $post->ID, $nonce ) );
				if ( $campaign['open'] )
					$html .= sprintf( '<a class="%s" data-kgr_medals_color="%s" href="%s">%s</a>', implode( ' ', $class ), $medal, $url, $text[ $medal ] ) . "\n";
				else
					$html .= sprintf( '<span class="%s" data-kgr_medals_color="%s">%s</span>', implode( ' ', $class ), $medal, $text[ $medal ] ) . "\n";
			}
			$html .= '</p>' . "\n";
		}
		$html .= apply_filters( 'the_content', $post->post_content );
		$html .= '</div>' . "\n";
	}
	return $html;
} );

add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'kgr-medals-shortcode', plugins_url( 'shortcode.css', __FILE__ ), [], NULL );
	wp_enqueue_script( 'kgr-medals-shortcode', plugins_url( 'shortcode.js', __FILE__ ), ['jquery'], NULL );
} );

add_action( 'wp_ajax_kgr_medals_clear', function() {
	$user_id = get_current_user_id();
	if ( $user_id === 0 )
		exit( 'login' );
	$term_id = intval( $_GET['term'] );
	$campaign = kgr_medals_get_campaign( $term_id );
	if ( is_null( $campaign ) )
		exit( 'campaign' );
	if ( !$campaign['open'] )
		exit( 'closed' );
	if ( !wp_verify_nonce( $_GET['nonce'], kgr_medals_clear_nonce( $user_id, $term_id ) ) )
		exit( 'nonce' );
	$key = sprintf( 'kgr_medals_%d', $term_id );
	delete_user_meta( $user_id, $key );
	exit;
} );

add_action( 'wp_ajax_kgr_medals_vote', function() {
	$user_id = get_current_user_id();
	if ( $user_id === 0 )
		exit( 'login' );
	$term_id = intval( $_GET['term'] );
	$campaign = kgr_medals_get_campaign( $term_id );
	if ( is_null( $campaign ) )
		exit( 'campaign' );
	if ( !$campaign['open'] )
		exit( 'closed' );
	$medal = $_GET['medal'];
	$post_id = intval( $_GET['post'] );
	if ( !wp_verify_nonce( $_GET['nonce'], kgr_medals_vote_nonce( $user_id, $term_id, $medal, $post_id ) ) )
		exit( 'nonce' );
	$vote = kgr_medals_get_user_vote( $user_id, $term_id );
	$key = array_search( $post_id, $vote, TRUE );
	if ( $key === FALSE )
		$vote[ $medal ] = $post_id;
	elseif ( $key === $medal )
		$vote[ $medal ] = 0;
	else {
		$vote[ $key ] = 0;
		$vote[ $medal ] = $post_id;
	}
	kgr_medals_set_user_vote( $user_id, $term_id, $vote );
	exit;
} );
