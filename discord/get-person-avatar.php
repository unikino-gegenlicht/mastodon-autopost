<?php



function map_get_person_avatar_url( MAP_Movie $movie ): string {
	$personName = $movie->proposedBy;

	$filter = array(
		'post_type' => 'team',
		'title'      => $personName
	);

	$posts = new WP_Query( $filter );

	while ($posts->have_posts()) {
		if ($posts->post->post_title == $personName) {
			return get_the_post_thumbnail_url($posts->post);

		}
		$posts->next_post();
	}

	return get_option(OptionsDiscordFallbackAvatarUrl);


}
