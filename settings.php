<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'plugin_action_links_kgr-medals/kgr-medals.php', function( array $links ): array {
	$links[] = sprintf( '<a href="%s">%s</a>', menu_page_url( 'kgr-medals', FALSE ), 'Settings' );
	return $links;
} );

add_action( 'admin_init', function() {
	if ( !current_user_can( 'administrator' ) )
		return;
	$group = 'kgr-medals';
	$section = 'kgr-medals';
	add_settings_section( $section, '', '__return_null', $group );
	$name = 'kgr-medals';
	register_setting( $group, $name, function( $input ) {
		$n = count( $input['term'] ) - 1;
		$campaigns = [];
		for ( $i = 0; $i < $n; $i++ ) {
			$campaign = [
				'term' => intval( $input['term'][ $i ] ),
				'open' => $input['open'][ $i ] === 'on',
			];
			$campaigns[] = $campaign;
		}
		return $campaigns;
	} );
} );

add_action( 'admin_menu', function() {
	if ( !current_user_can( 'administrator' ) )
		return;
	$page_title = 'KGR Medals';
	$menu_title = 'KGR Medals';
	$menu_slug = 'kgr-medals';
	$function = 'kgr_medals_settings_page';
	add_submenu_page( 'options-general.php', $page_title, $menu_title, 'administrator', $menu_slug, $function );
} );

function kgr_medals_settings_page() {
	if ( !current_user_can( 'administrator' ) )
		return;
	echo '<div class="wrap">' . "\n";
	if ( array_key_exists( 'term', $_GET ) )
		kgr_medals_settings_term_page();
	else
		kgr_medals_settings_main_page();
	echo '</div>' . "\n";
}

function kgr_medals_settings_main_page() {
	$campaigns = get_option( 'kgr-medals', [] );
	echo sprintf( '<h1>%s</h1>', 'KGR Medals' ) . "\n";
	echo '<form method="post" action="options.php" class="kgr-medals-control-container">' . "\n";
	settings_fields( 'kgr-medals' );
	do_settings_sections( 'kgr-medals' );
	echo '<table class="widefat striped">' . "\n";
	echo '<thead>' . "\n";
	echo '<tr>' . "\n";
	echo sprintf( '<th>%s</td>', 'term' ) . "\n";
	echo sprintf( '<th>%s</td>', 'open' ) . "\n";
	echo sprintf( '<th>%s</td>', 'action' ) . "\n";
	echo '</tr>' . "\n";
	echo '</thead>' . "\n";
	echo '<tbody class="kgr-medals-control-items">' . "\n";
	foreach ( $campaigns as $campaign )
		kgr_medals_settings_main_page_tr( $campaign );
	echo '</tbody>' . "\n";
	echo '</table>' . "\n";
	echo '<table style="display: none;">' . "\n";
	echo '<tbody class="kgr-medals-control-item0">' . "\n";
	kgr_medals_settings_main_page_tr();
	echo '</tbody>' . "\n";
	echo '</table>' . "\n";
	echo '<div style="margin: 1em 0;">' . "\n";
	submit_button( 'save', 'primary', NULL, FALSE );
	echo sprintf( '<button type="button" class="button kgr-medals-control-add" style="float: right;">%s</button>', 'add' ) . "\n";
	echo '</div>' . "\n";
	echo '</form>' . "\n";
}

function kgr_medals_settings_main_page_tr( $campaign = NULL ) {
	$name = 'kgr-medals';
	if ( is_null( $campaign ) )
		$campaign = [
			'term' => 0,
			'open' => FALSE,
		];
	echo '<tr class="kgr-medals-control-item">' . "\n";
	// term
	$key = 'term';
	$value = $campaign[ $key ];
	echo '<td>' . "\n";
	if ( $value !== 0 ) {
		$term = get_term( $value );
		echo sprintf( '<input type="hidden" name="%s[%s][]" value="%d" />', $name, $key, $value ) . "\n";
		echo '<p>' . "\n";
		echo sprintf( '<a href="%s&term=%d">%s</a>', menu_page_url( 'kgr-medals', FALSE ), $term->term_id, $term->name ) . "\n";
		echo sprintf( '<span>(<a href="%s">%d</a>)</span>', get_term_link( $term ), $term->count ) . "\n";
		echo '</p>' . "\n";
	} else {
		$terms = get_terms( [
			'taxonomy' => 'post_tag',
			'hide_empty' => FALSE,
		] );
		echo sprintf( '<select name="%s[%s][]">', $name, $key ) . "\n";
		echo sprintf( '<option>%s</option>', 'none' ) . "\n";
		foreach ( $terms as $term )
			echo sprintf( '<option value="%d">%s (%d)</option>', $term->term_id, $term->name, $term->count ) . "\n";
		echo '</select>' . "\n";
	}
	echo '</td>' . "\n";
	// open
	$key = 'open';
	$value = $campaign[ $key ];
	echo '<td>' . "\n";
	echo sprintf( '<input type="hidden" name="%s[%s][]" />', $name, $key ) . "\n";
	echo sprintf( '<input type="checkbox" class="kgr-medals-settings-campaign-open-checkbox"%s />', checked( $value, TRUE, FALSE ) ) . "\n";
	echo '</td>' . "\n";
	echo '<td>' . "\n";
	echo sprintf( '<button type="button" class="button kgr-medals-control-delete">%s</button>', 'delete' ) . "\n";
	echo '</td>' . "\n";
	echo '</tr>' . "\n";
}

function kgr_medals_settings_term_page() {
	global $kgr_medals;
	$term = get_term( intval( $_GET['term'] ) );
	$campaigns = get_option( 'kgr-medals', [] );
	foreach ( $campaigns as $campaign )
		if ( $campaign['term'] === $term->term_id )
			break;
	$users = get_users( [
		'meta_key' => sprintf( 'kgr_medals_%d', $term->term_id ),
		'orderby' => 'email',
		'order' => 'ASC',
	] );
	$posts = get_posts( [
		'tax_query' => [ [ 'taxonomy' => $term->taxonomy, 'terms' => $term->term_id ] ],
		'post_type' => 'post',
		'nopaging' => TRUE,
		'order' => 'ASC',
		'orderby' => 'title',
	] );
	foreach ( $posts as $post )
		foreach ( $kgr_medals as $medal )
			$post->$medal = 0;
	$post_ids = array_map( function( $post ): int { return $post->ID; }, $posts );
	$posts = array_combine( $post_ids, $posts );
	echo '<h1>' . "\n";
	echo sprintf( '<span>%s</span>', 'KGR Medals' ) . "\n";
	echo sprintf( '<a href="%s" class="page-title-action">%s</a>', menu_page_url( 'kgr-medals', FALSE ), 'campaigns' ) . "\n";
	echo '</h1>' . "\n";
	echo '<h2>' . "\n";
	echo sprintf( '<span>%s</span>', $term->name ) . "\n";
	echo sprintf( '<span>(<a href="%s">%s</a>)</span>', get_term_link( $term ), $term->count ) . "\n";
	echo '</h2>' . "\n";
	echo '<table class="form-table">' . "\n";
	echo '<tbody>' . "\n";
	echo '<tr>' . "\n";
	echo sprintf( '<th scope="row">%s</th>', 'open' ) . "\n";
	echo sprintf( '<td>%s</td>', $campaign['open'] ? 'on' : 'off' ) . "\n";
	echo '</tr>' . "\n";
	echo '<tr>' . "\n";
	echo sprintf( '<th scope="row">%s</th>', 'users' ) . "\n";
	echo sprintf( '<td>%d</td>', count( $users ) ) . "\n";
	echo '</tr>' . "\n";
	echo '<tbody>' . "\n";
	echo '</table>' . "\n";
	// users
	$html = '';
	$html .= '<table class="widefat striped">' . "\n";
	$html .= '<thead>' . "\n";
	$html .= '<tr>' . "\n";
	$html .= '<th>user</th>' . "\n";
	$html .= '<th>email</th>' . "\n";
	foreach ( $kgr_medals as $medal )
		$html .= sprintf( '<th>%s</th>', $medal ) . "\n";
	$html .= '</tr>' . "\n";
	$html .= '</thead>' . "\n";
	$html .= '<tbody>' . "\n";
	foreach ( $users as $user ) {
		$vote = kgr_medals_get_user_vote( $user->ID, $term->term_id );
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<td><a href="%suser-edit.php?user_id=%d">%s</a></td>', admin_url(), $user->ID, $user->user_login ) . "\n";
		$html .= sprintf( '<td><a href="mailto:%s">%s</a></td>', $user->user_email, $user->user_email ) . "\n";
		foreach ( $kgr_medals as $medal ) {
			$id = $vote[ $medal ];
			if ( $id !== 0 ) {
				$html .= sprintf( '<td><a href="%s?p=%d">%d</a></td>', home_url(), $id, $id ) . "\n";
				$posts[ $id ]->$medal++;
			} else {
				$html .= '<td></td>' . "\n";
			}
		}
		$html .= '</tr>' . "\n";
	}
	$html .= '</tbody>' . "\n";
	$html .= '</table>' . "\n";
	// posts
	echo '<table class="widefat striped">' . "\n";
	echo '<thead>' . "\n";
	echo '<tr>' . "\n";
	echo '<th>post</th>' . "\n";
	foreach ( $kgr_medals as $medal )
		echo sprintf( '<th>%s</th>', $medal ) . "\n";
	echo '<th>points</th>' . "\n";
	echo '</tr>' . "\n";
	echo '</thead>' . "\n";
	echo '<tbody>' . "\n";
	foreach ( $posts as $post ) {
		echo '<tr>' . "\n";
		echo sprintf( '<td><a href="%s?p=%d">%s</a></td>', home_url(), $post->ID, $post->post_title ) . "\n";
		$points = 0;
		foreach ( $kgr_medals as $key => $medal ) {
			echo sprintf( '<td>%d</td>', $post->$medal ) . "\n";
			$points += $post->$medal * ( count( $kgr_medals ) - $key );
		}
		echo sprintf( '<td>%d</td>', $points ) . "\n";
		echo '</tr>' . "\n";
	}
	echo '</tbody>' . "\n";
	echo '</table>' . "\n";
	echo '<br />' . "\n";
	echo $html;
}

add_action( 'admin_enqueue_scripts', function( string $hook ) {
	if ( $hook !== 'settings_page_kgr-medals' )
		return;
	if ( array_key_exists( 'term', $_GET ) )
		return;
	wp_enqueue_script( 'kgr-medals-control', KGR_MEDALS_URL . 'control.js', ['jquery'] );
	wp_enqueue_script( 'kgr-medals-settings', KGR_MEDALS_URL . 'settings.js', ['jquery'] );
} );
