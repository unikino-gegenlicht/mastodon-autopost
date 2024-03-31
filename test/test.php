<?php

require_once 'common.php';

function map_run_discord_test() {
	$movies       = map_get_test_movies();
	$return_value = array();
	foreach ( $movies as $movie ) {
		try {
			$movie->postToDiscord();
		} catch ( ErrorException $e ) {
			$return_value['message'] = $e->getMessage();
			echo json_encode( $return_value );
			wp_die();
		}
	}
	$return_value['message'] = "success";
	echo json_encode( $return_value );
	wp_die();
}

function map_run_mastodon_test() {
	$movies       = map_get_test_movies();
	$return_value = array();
	foreach ( $movies as $movie ) {
		try {
			$movie->postToMastodon();
		} catch ( ErrorException $e ) {
			$return_value['message'] = $e->getMessage();
			echo json_encode( $return_value );
			wp_die();
		}
	}
	$return_value['message'] = "success";
	echo json_encode( $return_value );
	wp_die();
}