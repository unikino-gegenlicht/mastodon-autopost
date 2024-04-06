<?php

/**
 * Uploads the movie thumbnail to Mastodon.
 *
 * @param MAP_Movie $movie The movie object.
 *
 * @return int|null The media ID on success, null on failure.
 * @throws ErrorException If unable to upload the post thumbnail.
 */
function map_upload_movie_thumbnail_to_mastodon( MAP_Movie $movie ): ?int {
	global $api;
	$token       = get_option( OptionsMastodonToken );
	$instanceUrl = get_option( OptionsMastodonInstance );
	if ( ! $token || ! $instanceUrl ) {
		return null;
	}

	$image_url_path = get_the_post_thumbnail_url( $movie->wp_post_id, size: '2048x2048' );
	$ch             = curl_init( $image_url_path );
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_BINARYTRANSFER, 1 );
	$raw_image = curl_exec( $ch );
	curl_close( $ch );


	$boundary  = uniqid();
	$delimiter = '-------------' . $boundary;
	$post_data = "";
	$eol       = eol;
	$fields    = array(
		"description" => "Ein Bild welches eine Szene aus dem Film '$movie->name' enthält"
	);


	foreach ( $fields as $name => $content ) {
		$post_data .= "--" . $delimiter . $eol . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol . $content . $eol;
	}


	$post_data .= "--" . $delimiter . $eol . 'Content-Disposition: form-data; name="file"; filename="post-image-' . $boundary . '"' . $eol . 'Content-Transfer-Encoding: binary' . $eol;
	$post_data .= $eol;
	$post_data .= $raw_image . $eol;
	$post_data .= "--" . $delimiter . "--";

	$result = $api->postFormData( "/api/v2/media", $post_data, $boundary );
	if ( $result['http_code'] == 200 ) {
		return json_decode( $result['response'] )['id'];
	}
	if ( $result['http_code'] == 202 ) {
		sleep( 2.5 );

		return json_decode( $result['response'] )['id'];
	}

	throw new ErrorException("Unable to upload media to mastodon:".eol.eol.$result['response']);
}