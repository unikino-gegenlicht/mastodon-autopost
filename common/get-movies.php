<?php

const metaQueryKey = 'hauptfilm_date';

/**
 * @return Movie[]
 */
function get_movies(): array {
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
		$movie      = new Movie();
		$post       = $posts->post;
		$movieDate  = get_post_meta( $post->ID, 'hauptfilm_date', single: true );
		$movieStart = get_post_meta( $post->ID, 'haptfilm_time', single: true );
		$movie->start       = DateTime::createFromFormat( "Y-m-d H:i", $movieDate . ' ' . $movieStart );
		$movie->name        = get_post_meta( $post->ID, 'hauptfilm_title', single: true );
		$movie->description = get_post_meta( $post->ID, 'hauptfilm_filmtext', single: true );
		$movie->gerne       = get_post_meta( $post->ID, 'hauptfilm_shown_genre', single: true );
		$movie->proposedBy  = get_post_meta( $post->ID, 'weiteres_selected_by_name', single: true );
		$movie->licensed    = get_post_meta( $post->ID, 'hauptfilm_license_ok', true );
		$movie->wp_post_id  = $post->ID;
		$movies[]           = $movie;
	}

	return $movies;
}

