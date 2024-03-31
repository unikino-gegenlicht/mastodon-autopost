<?php


require_once 'common/get-movies.php';

function map_cron() {
	$movies = map_get_movies();
	foreach ( $movies as $movie ) {
		try {
			$movie->postToDiscord();
		} catch ( Exception $e ) {
			wp_mail(get_option( 'admin_email' ), '[Autopost] Unable to Post to Discord', $e->getMessage());
		}
		try {
			$movie->postToMastodon();
		} catch ( ErrorException $e ) {
			wp_mail(get_option( 'admin_email' ), '[Autopost] Unable to Post to Mastodon', $e->getMessage());

		}
	}
	update_option( OptionGroup . '-last-cron', date( 'd.m.Y H:i:s' ) );
}