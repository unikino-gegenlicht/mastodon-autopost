<?php

include __DIR__ . '/vendor/autoload.php';

use PhpChannels\DiscordWebhook\Discord;


/**
 * Represents a Movie entity.
 */
class Movie {
	/**
	 * @var int
	 */
	public int $wp_post_id;

	/**
	 * @var string $name The name of the movie
	 */
	public string $name;

	/**
	 * @var string $description The description of the movie
	 */
	public string $description;

	/**
	 * @var string $gerne The genre of the movie
	 */
	public string $gerne;

	/**
	 * @var string $proposedBy The name of the person who proposed the movie
	 */
	public string $proposedBy;

	/**
	 * @var DateTime $start The starting point of the movie
	 */
	public DateTime $start;

	/**
	 * @var bool $licensed Whether the content is licensed or not
	 */
	public bool $licensed;

	public function postToDiscord() {
		$webhook_url = get_option( OptionsDiscordWebhookUrl );
		if ( ! $webhook_url ) {
			return;
		}

		$message = Discord::message( $webhook_url );
		$message->setUsername( $this->name );
		$message->setContent( $this->description );
		$message->setImage( get_the_post_thumbnail_url( $this->wp_post_id, size: 'large' ) );
		$message->setAvatarUrl( get_person_avatar_url( $this ) );
		$message->send();
	}

	public function postToMastodon() {
		$token       = get_option( OptionsMastodonToken );
		$instanceUrl = get_option( OptionsMastodonInstance );
		if ( ! $token || ! $instanceUrl ) {
			return;
		}

		$image_url_path = get_the_post_thumbnail_url( $this->wp_post_id, size: 'large' );
		$ch             = curl_init( $image_url_path );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLPOT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_BINARYTRANSFER, 1 );
		$raw_image = curl_exec( $ch );
		curl_close( $ch );

		$boundary  = uniqid();
		$delimiter = '-------------' . $boundary;
		$post_data = "";
		$eol       = "\r\n";

		$post_data .= "--" . $delimiter . $eol . 'Content-Disposition: form-data; name="file"; filename="post-image-' . $boundary . '"' . $eol . 'Content-Transfer-Encoding: binary' . $eol;
		$post_data .= $eol;
		$post_data .= $raw_image . $eol;
		$post_data .= "--" . $delimiter . "--";

		$media_headers = [
			"Authorization: Bearer $token",
			"Content-Type: multipart/form-data; boundary=$delimiter",
			"Content-Length: " . strlen( $post_data )
		];

		$ch_media_status = curl_init();
		curl_setopt( $ch_media_status, CURLOPT_URL, "$instanceUrl/api/v2/media" );
		curl_setopt( $ch_media_status, CURLOPT_POST, 1 );
		curl_setopt( $ch_media_status, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch_media_status, CURLOPT_HTTPHEADER, $media_headers );
		curl_setopt( $ch_media_status, CURLOPT_POSTFIELDS, $post_data );
		$media_response = curl_exec( $ch_media_status );
		$media_status   = json_decode( $media_response );
		$media_info     = curl_getinfo( $ch_media_status );
		curl_close( $ch_media_status );

		$http_code = $media_info['http_code'];

		$post_with_media = false;

		if (($http_code == 200) || ($http_code == 202)) {
			$post_with_media = true;
		}

		$status_data = array(
			"status" => "$this->description $eol $eol #kino #uni #oldenburg #$this->gerne #unikinos #uni_oldenburg #gegenlicht",
			"language" => "de",
			"visibility" => "public"
		);

		if ($post_with_media) {
			$status_data["media_ids"] = [$media_status["id"]];
		}

		$status_update_body = json_encode($status_data);
		$headers = [
			"Authorization: Bearer $token",
			'Content-Type: application/json'
		];

		$ch_status = curl_init();
		curl_setopt($ch_status, CURLOPT_URL, "$instanceUrl/api/v1/statuses");
		curl_setopt($ch_status, CURLOPT_POST, 1);
		curl_setopt($ch_status, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch_status, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch_status, CURLOPT_POSTFIELDS, $status_update_body);
		curl_exec($ch_status);
		curl_close($ch_status);
	}
}