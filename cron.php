<?php


function cron() {
	$movies = get_movies();
	foreach ( $movies as $movie ) {
		$movie->postToDiscord();
		$movie->postToMastodon();
	}
}