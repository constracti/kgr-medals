<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'plugin_action_links_kgr-medals/kgr-medals.php', function( array $links ): array {
	$links[] = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=kgr-medals' ), 'Settings' );
	return $links;
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
	echo sprintf( '<h1>%s</h1>', 'KGR Medals' ) . "\n";
	$posts = get_posts( [
		's' => 'kgr-medals',
		'post_type' => ['post', 'page'],
		'post_status' => ['publish', 'private'],
		'nopaging' => TRUE,
	] );
	foreach ( $posts as $post ) {
		$content = $post->post_content;
		$end = 0;
		$stop = FALSE;
		while ( TRUE ) {
			$beg = mb_strpos( $content, '[kgr-medals', $end );
			if ( $beg === FALSE ) {
				$stop = TRUE;
				break;
			}
			$end = mb_strpos( $content, ']', $beg );
			if ( $end === FALSE ) {
				$stop = TRUE;
				break;
			}
			$shortcode = mb_substr( $content, $beg + 1, $end - $beg - 1 );
			$shortcode = shortcode_parse_atts( $shortcode );
			$term_id = intval( $shortcode['id'] );
			kgr_medals_settings_term_div( $term_id );
		}
		if ( $stop )
			continue;
	}
	echo '</div>' . "\n";
}

function kgr_medals_settings_term_div( int $term_id ) {
	global $kgr_medals;
	$term = get_term( $term_id );
	$users = get_users( [
		'meta_key' => sprintf( 'kgr_medals_%d', $term_id ),
		'orderby' => 'email',
		'order' => 'ASC',
	] );
	$posts = get_posts( [
		'tax_query' => [ [ 'taxonomy' => $term->taxonomy, 'terms' => $term_id ] ],
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
	echo '<div class="kgr_medals_settings_term">' . "\n";
	echo sprintf( '<h2><a href="%s" title="%s">%s</a></h2>', get_term_link( $term ), $term->name, $term->name ) . "\n";
	// tabs
	echo '<h2 class="nav-tab-wrapper">' . "\n";
	echo sprintf( '<a class="nav-tab" href="#users">users (%d)</a>', count( $users ) ) . "\n";
	echo sprintf( '<a class="nav-tab" href="#posts">posts (%d)</a>', count( $posts ) ) . "\n";
	echo '</h2>' . "\n";
	// users
	echo '<table class="widefat striped kgr_medals_settings_term_users">' . "\n";
	echo '<thead>' . "\n";
	echo '<tr>' . "\n";
	echo '<th>user</th>' . "\n";
	echo '<th>email</th>' . "\n";
	foreach ( $kgr_medals as $medal )
		echo sprintf( '<th>%s</th>', $medal ) . "\n";
	echo '</tr>' . "\n";
	echo '</thead>' . "\n";
	echo '<tbody>' . "\n";
	foreach ( $users as $user ) {
		$vote = kgr_medals_get_user_vote( $user->ID, $term_id );
		echo '<tr>' . "\n";
		echo sprintf( '<td><a href="%suser-edit.php?user_id=%d">%s</a></td>', admin_url(), $user->ID, $user->user_login ) . "\n";
		echo sprintf( '<td><a href="mailto:%s">%s</a></td>', $user->user_email, $user->user_email ) . "\n";
		foreach ( $kgr_medals as $medal ) {
			$id = $vote[ $medal ];
			if ( $id !== 0 ) {
				echo sprintf( '<td><a href="%s?p=%d">%d</a></td>', home_url(), $id, $id ) . "\n";
				$posts[ $id ]->$medal++;
			} else {
				echo '<td></td>' . "\n";
			}
		}
		echo '</tr>' . "\n";
	}
	echo '</tbody>' . "\n";
	echo '</table>' . "\n";
	// posts
	echo '<table class="widefat striped kgr_medals_settings_term_posts">' . "\n";
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
	echo '</div>' . "\n";
}
add_action( 'admin_enqueue_scripts', function( string $hook ) {
	if ( !current_user_can( 'administrator' ) )
		return;
	if ( $hook !== 'settings_page_kgr-medals' )
		return;
	wp_enqueue_style( 'kgr-medals-settings', plugins_url( 'settings.css', __FILE__ ) );
	wp_enqueue_script( 'kgr-medals-settings', plugins_url( 'settings.js', __FILE__ ), ['jquery'] );
} );
