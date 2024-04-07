<?php

require_once plugin_dir_path(__FILE__).'../common/get-movies.php';
require_once plugin_dir_path( __FILE__ ) . '../discord/post.php';
require_once plugin_dir_path( __FILE__ ) . '../mastodon/post.php';

function map_run_discord_test() {
	$movies       = map_get_movies();
	$return_value = array();
	try {
		postToDiscord( $movies, true );
	} catch ( Exception $e ) {
		$return_value['message'] = $e->getMessage();
		echo json_encode( $return_value );
		wp_die();
	}
	$return_value['message'] = "success";
	echo json_encode( $return_value );
	wp_die();
}

function map_run_mastodon_test() {
	$movies       = map_get_movies();
	$return_value = array();
	try {
		map_post_movies_to_mastodon( $movies, true );
	} catch ( Exception $e ) {
		$return_value['message'] = $e->getMessage();
		echo json_encode( $return_value );
		wp_die();
	}
	$return_value['message'] = "success";
	echo json_encode( $return_value );
	wp_die();
}