<?php


require_once 'common/get-movies.php';

function map_cron() {
	$movies = map_get_movies();
	foreach ( $movies as $movie ) {
		$movie->postToDiscord();
		$movie->postToMastodon();
	}
	update_option( OptionGroup . '-last-cron', date( 'd.m.Y H:i:s' ) );
}