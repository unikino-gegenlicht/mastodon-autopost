<?php

function get_person_avatar_url( Movie $movie ): string {
	$personName = $movie->proposedBy;

	$filter = array(
		'post_type' => 'team',
		'name'      => $personName
	);

	$posts = new WP_Query( $filter );
	if (!$posts->have_posts()) {
		return get_option(OptionsDiscordFallbackAvatarUrl);
	}

	while ($posts->have_posts()) {
		return get_the_post_thumbnail_url($posts->post);
	}

}
