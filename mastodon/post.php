<?php

require plugin_dir_path( __FILE__ ) . '../common/table.php';
require plugin_dir_path( __FILE__ ) . 'MAP_Mastodon_API.php';
require plugin_dir_path( __FILE__ ) . 'upload-media.php';
require_once plugin_dir_path( __FILE__ ) . '../consts.php';
require_once plugin_dir_path( __FILE__ ) . 'announcement-openers.php';
require_once plugin_dir_path( __FILE__ ) . 'singe-movie-openers.php';


$tz = new DateTimeZone( 'Europe/Berlin' );


/**
 * Retrieves an opener line for posting a status update on Mastodon.
 *
 * @param bool $for_single_movie Optional. Indicates if the opener line is for a single movie. Default false.
 *
 * @return string The selected opener line.
 */
function get_opener_line( bool $for_single_movie = false ): string {
	global $announcement_openers, $single_movie_openers;
	if ( $for_single_movie ) {
		$line = rand( 0, sizeof( $single_movie_openers ) - 1 );

		return $single_movie_openers[ $line ];
	}
	$line = rand( 0, sizeof( $announcement_openers ) - 1 );

	return $announcement_openers[ $line ];
}

/**
 * Maps a movie to Mastodon by posting a status update.
 *
 * @param MAP_Movie[] $movies The movie objects to post to mastodon.
 * @param bool $testing Indicates if this is a test run. It will change the audience to directly mentioned accounts only
 *
 * @return void
 * @throws ErrorException If unable to post the status update.
 */
function map_post_movies_to_mastodon( array $movies, bool $testing = false ): void {
	global $tz;
	$api = new MAP_Mastodon_API();

	$token       = get_option( OptionsMastodonToken );
	$instanceUrl = get_option( OptionsMastodonInstance );
	if ( ! $token || ! $instanceUrl ) {
		return;
	}

	$collectedMovies = array();
	$moviesToday     = array();
	$now             = new DateTimeImmutable( null, timezone: $tz );
	$unlicensedMovie = false;
	foreach ( $movies as $movie ) {
		if ( $testing ) {
			$moviesToday[] = $movie;
			continue;
		}
		switch ( $movie->start->diff( $now )->days ) {
			case 3:
			case 2:
			case 1:
				$collectedMovies[] = $movie;
				break;
			case 0:
				$moviesToday[] = $movie;

		}
		if ( ! $unlicensedMovie ) {
			$unlicensedMovie = $movie->licensed;
		}

	}

	// sort the collected movies by their date
	usort( $collectedMovies, function ( $a, $b ) {
		return $a->start <=> $b->start;
	} );

	// check if the movies are the same to stop spam posting
	if ( ! $testing ) {
		$oldCollectedMovies = get_option( 'map_movie_collected_movies_last_mastodon' );
		if ( $oldCollectedMovies == $collectedMovies ) {
			return;
		}
		update_option( 'map_movie_collected_movies_last_mastodon', $collectedMovies );

	}

	$updates = array();


	if ( sizeof( $collectedMovies ) > 0 ) {
		$table_data = array();
		foreach ( $collectedMovies as $collected_movie ) {
			$movieDate    = $collected_movie->start->format( "d.m.Y" );
			$movieTime    = $collected_movie->start->format( "H:i" ) . " Uhr";
			$movieTitle   = $collected_movie->name;
			$movieGenre   = '#' . preg_replace( '/([\s\-\+]+)/', '_', $collected_movie->genre );
			$table_row    = array( $movieDate, $movieTime, $movieTitle, $movieGenre );
			$table_data[] = $table_row;
		}

		// now build the post depending on the movies returned by the query and the day filtering
		$status_message = get_opener_line() . eol . eol;
		$status_message .= table( $table_data ) . eol . eol;
		$status_message .= 'Mehr Infos zu den Vorführungen und Reservierungen unter: https://gegenlicht.net/programm' . eol . eol;
		$status_message .= '#kino #unikino #oldenburg #uni_oldenburg #gegenlicht #kommunales_kino';


		$status_data = array(
			"status"     => $status_message,
			"language"   => "de",
			"visibility" => $testing ? 'direct' : 'public',
		);
		$updates[]   = $status_data;

	}


	// now handle the movies that are shown today
	foreach ( $moviesToday as $movie ) {
		$status_message = get_opener_line( true ) . eol . eol;
		$status_message .= "Heute um " . $movie->start->format( " H:i" ) . " Uhr haben wir für euch $movie->name im Angebot." . eol . eol;
		$status_message .= wp_trim_words( $movie->description, more: '...' ) . eol . eol;
		$status_message .= "Reservierungen und mehr Infos unter: " . get_the_permalink( $movie->wp_post_id ) . eol . eol;
		$status_message .= '#' . strtolower( preg_replace( '/([\s\-\+]+)/', '_', $movie->genre ) ) . ' ';
		$status_message .= '#kino #unikino #oldenburg #uni_oldenburg #gegenlicht #kommunales_kino';

		// now trim the status message to be below the max amount of characters of 500
		$max_excerpt_words = 55;
		while ( strlen( $status_message ) > 500 ) {
			$max_excerpt_words --;
			$status_message = get_opener_line( true ) . eol . eol;
			$status_message .= "Heute um " . $movie->start->format( "H:i" ) . " Uhr haben wir für euch $movie->name im Angebot." . eol . eol;
			$status_message .= wp_trim_words( $movie->description, $max_excerpt_words, more: '...' ) . eol . eol;
			$status_message .= "Reservierungen und mehr Infos unter: " . get_the_permalink( $movie->wp_post_id ) . eol . eol;
			$status_message .= '#' . strtolower( preg_replace( '/([\s\-\+]+)/', '_', $movie->genre ) ) . ' ';
			$status_message .= '#kino #unikino #oldenburg #uni_oldenburg #gegenlicht #kommunales_kino';
		}

		$status_data = array(
			"status"     => $status_message,
			"language"   => "de",
			"visibility" => $testing ? 'direct' : 'public',
		);

		$media_id = map_upload_movie_thumbnail_to_mastodon( $movie );
		if ( $media_id != null ) {
			$status_data["media_ids"] = array( $media_id );
		}


		$updates[] = $status_data;
	}

	foreach ( $updates as $status ) {
		$result = $api->postJSON( "/api/v1/statuses", $status );
		if ( $result['http_code'] != 200 ) {
			throw new ErrorException( "Unable to post status update:" . eol . eol . $result['response'] );
		}
	}


}