<?php


require plugin_dir_path( __FILE__ ) . '../MAP_Movie.php';

/**
 * @return MAP_Movie[]
 */
function map_get_test_movies(): array {
	$filter = array(
		'post_type'      => 'film',
		'p'              => get_option( OptionsTestPostID, '929' ),
		'posts_per_page' => '1'
	);


	$posts = new WP_Query( $filter );

	if ( ! $posts->have_posts() || get_theme_mod( "sepa_checkbox" ) ) {
		return array();
	}

	if ( $posts->found_posts != 1 ) {
		return array();
	}
	$movies = array();
	while ( $posts->have_posts() ) {
		$movie              = new MAP_Movie();
		$post               = $posts->post;
		$movieDate          = get_post_meta( $post->ID, 'hauptfilm_date', single: true );
		$movieStartTime         = get_post_meta( $post->ID, 'hauptfilm_time', single: true );
		$movie->start       = DateTimeImmutable::createFromFormat( "Y-m-d H:i", "$movieDate $movieStartTime", new DateTimeZone('Europe/Berlin') );
		$movie->name        = get_post_meta( $post->ID, 'hauptfilm_title', single: true );
		$movie->description = sanitize_text_field( get_post_meta( $post->ID, 'hauptfilm_filmtext', single: true ) );
		$movie->genre       = get_post_meta( $post->ID, 'hauptfilm_shown_genre', single: true );
		$movie->proposedBy  = get_post_meta( $post->ID, 'weiteres_selected_by_name', single: true );
		$movie->licensed    = get_post_meta( $post->ID, 'hauptfilm_license_ok', true );
		$movie->wp_post_id  = $post->ID;
		$movies[]           = $movie;
		$posts->next_post();
	}
	return $movies;
}