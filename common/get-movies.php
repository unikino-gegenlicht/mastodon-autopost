<?php


const metaQueryKey = 'hauptfilm_date';

$tz = new DateTimeZone( 'Europe/Berlin' );
/**
 * Retrieves an array of movies based on specified criteria.
 *
 * @return array An array of MAP_Movie objects.
 * @throws Exception If there is an error while retrieving the movies.
 */
function map_get_movies(): array {
	global $tz;

	$filter = array(
		'post_type'      => 'film',
		'meta_query'     => array(
			'key'     => metaQueryKey,
			'value'   => date( "Y-m-d", strtotime( date( "Y-m-d" ) ) ),
			'compare' => '>=',
			'type'    => 'date'
		),
		'post_status'    => 'publish',
		'orderby'        => 'meta_value date',
		'meta_key'       => metaQueryKey,
		'order'          => 'ASC',
		'posts_per_page' => - 1,
	);

	$posts = new WP_Query( $filter );

	if ( ! $posts->have_posts() || get_theme_mod( "sepa_checkbox" ) === true ) {
		error_log( 'no movies found or sepa_checkbox enabled' );

		return array();
	}
	$movies = array();
	foreach ( $posts->get_posts() as $post ) {
		$movieDate      = get_post_meta( $post->ID, 'hauptfilm_date', single: true );
		$movieStartTime = get_post_meta( $post->ID, 'hauptfilm_time', single: true );

		$movieStart = DateTimeImmutable::createFromFormat( "Y-m-d H:i", "$movieDate $movieStartTime", $tz );
		$now        = new DateTimeImmutable( null, timezone: $tz );

		$daysUntilScreening = $movieStart->diff( $now )->days;

		if ( $daysUntilScreening > 3 ) {
			continue;
		}

		$movie         = new MAP_Movie();
		$movie->start  = $movieStart;
		$movieLicensed = get_post_meta( $post->ID, 'hauptfilm_license_ok', single: true ) == 1;
		if ( ! $movieLicensed ) {
			$movie->name = $post->post_title;
		} else {
			$movie->name = get_post_meta( $post->ID, 'hauptfilm_title', single: true );
		}
		$movie->description = sanitize_text_field( get_post_meta( $post->ID, 'hauptfilm_filmtext', single: true ) );
		$movie->gerne       = get_post_meta( $post->ID, 'hauptfilm_shown_genre', single: true );
		$movie->proposedBy  = get_post_meta( $post->ID, 'weiteres_selected_by_name', single: true );
		$movie->licensed    = $movieLicensed;
		$movie->wp_post_id  = $post->ID;

		$movies[] = $movie;


	}

	return $movies;
}

