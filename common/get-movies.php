<?php


const metaQueryKey = 'hauptfilm_date';

/**
 * @return MAP_Movie[]
 */
function map_get_movies(): array {

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
		'meta_key'       => 'hauptfilm_date',
		'order'          => 'ASC',
		'posts_per_page' => '1'
	);

	$posts = new WP_Query( $filter );

	if ( ! $posts->have_posts() || get_theme_mod( "sepa_checkbox" ) ) {
		return array();
	}
	$movies = array();
	while ( $posts->have_posts() ) {
		$post       = $posts->post;
		$movieDate  = get_post_meta( $post->ID, 'hauptfilm_date', single: true );
		$movieStart = get_post_meta( $post->ID, 'hauptfilm_time', single: true );
		if ( date( 'Y-m-d' ) >= date( 'Y-m-d', strtotime( '-3 day', strtotime( "$movieDate $movieStart:00" ) ) ) ) {
			$posts->next_post();
			continue;
		}
		$movie              = new MAP_Movie();
		$movieDate          = get_post_meta( $post->ID, 'hauptfilm_date', single: true );
		$movieStart         = get_post_meta( $post->ID, 'hauptfilm_time', single: true );
		$movie->start       = strtotime( "$movieDate $movieStart:00" );
		$movie->name        = get_post_meta( $post->ID, 'hauptfilm_title', single: true );
		$movie->description = sanitize_text_field( get_post_meta( $post->ID, 'hauptfilm_filmtext', single: true ) );
		$movie->gerne       = get_post_meta( $post->ID, 'hauptfilm_shown_genre', single: true );
		$movie->proposedBy  = get_post_meta( $post->ID, 'weiteres_selected_by_name', single: true );
		$movie->licensed    = get_post_meta( $post->ID, 'hauptfilm_license_ok', true );
		$movie->wp_post_id  = $post->ID;
		$movies[]           = $movie;
		$posts->next_post();
	}

	return $movies;
}

