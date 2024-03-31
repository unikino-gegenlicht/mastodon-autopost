<?php

require_once 'common.php';

function map_run_discord_test() {
	echo "STARTING";
	$movies = map_get_test_movies();
	echo "GOT MOVIES";
	foreach ( $movies as $movie ) {
		$movie->postToDiscord();
	}
	echo "DONE";
	wp_die();
}

function map_run_mastodon_test() {
	echo "STARTING";
	$movies = map_get_test_movies();
	echo "GOT_MOVIES";
	foreach ( $movies as $movie ) {
		$movie->postToMastodon();
	}
	echo "DONE";
	wp_die();
}