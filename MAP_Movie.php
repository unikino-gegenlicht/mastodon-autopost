<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/discord/get-person-avatar.php';

use PhpChannels\DiscordWebhook\Discord;

const eol = "\r\n";


/**
 * Represents a Movie entity.
 */
class MAP_Movie {
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
	 * @var int $start The starting point of the movie
	 */
	public int $start;

	/**
	 * @var bool $licensed Whether the content is licensed or not
	 */
	public bool $licensed;

	/**
	 * Retrieves the content for a post.
	 *
	 * This method generates the content for a post based on the given parameters.
	 * It includes a status prefix depending on the date of the post and concatenates it with the post description,
	 * permalink, and hashtags.
	 * The generated content is returned as a string.
	 *
	 * @return string The generated post content.
	 */
	private function getPostContent(): string {
		$old_locale = get_locale();
		setlocale( LC_ALL, "de_de" );
		$starting_time = date( 'H:i', $this->start );
		$weekday       = date( "l", $this->start );
		$status_prefix = '';
		if ( date( 'Y-m-d' ) == date( 'Y-m-d', strtotime( '-3 day', $this->start ) ) ) {
			$status_prefix = "In drei Tagen zeigen wir euch um $starting_time bei uns den Film $this->name. Ausgesucht wurde der Film von $this->proposedBy.";
		}
		if ( date( 'Y-m-d' ) == date( 'Y-m-d', strtotime( '-2 day', $this->start ) ) ) {
			$status_prefix = "Der $weekday rückt näher und wir freuen uns, euch in zwei Tagen den Film $this->name präsentieren zu dürfen.";
		}
		if ( date( 'Y-m-d' ) == date( 'Y-m-d', strtotime( '-1 day', $this->start ) ) ) {
			$status_prefix = "Morgen ist $weekday und das heißt für euch, dass ihr euch $this->name bei uns im Unikino nicht entgehen lassen dürft.";
		}
		if ( date( 'Y-m-d' ) == date( 'Y-m-d', strtotime( 'today', $this->start ) ) ) {
			$status_prefix = "Es ist endlich wieder $weekday und damit Zeit für einen neuen Film im Unikino. Wir freuen uns, euch heute um $starting_time den von $this->proposedBy ausgesuchten Film <i>$this->name</i> präsentieren zu dürfen.";
		}
		setlocale( LC_ALL, $old_locale );

		return $status_prefix . eol . eol . wp_trim_words( $this->description, 35, ' ...' ) . eol . eol . "Mehr Infos und Tickets unter: " . get_permalink( $this->wp_post_id ) . eol . eol . "#kino #uni #oldenburg #$this->gerne #unikinos #uni_oldenburg #gegenlicht";
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function postToDiscord(): void {
		$webhook_url = get_option( OptionsDiscordWebhookUrl );
		if ( ! $webhook_url ) {
			return;
		}


		$message = Discord::message( $webhook_url );
		$message->setUsername( $this->proposedBy );
		$message->setContent( $this->getPostContent() );
		$message->setImage( get_the_post_thumbnail_url( $this->wp_post_id, size: 'original' ) );
		$message->setAvatarUrl( map_get_person_avatar_url( $this ) );
		$message->send();
	}

	/**
	 * Uploads the post thumbnail image to Mastodon.
	 *
	 * @return int|null The ID of the uploaded media on Mastodon.
	 * @throws ErrorException
	 */
	private function uploadPostThumbnailToMastodon(): ?int {
		$token       = get_option( OptionsMastodonToken );
		$instanceUrl = get_option( OptionsMastodonInstance );
		if ( ! $token || ! $instanceUrl ) {
			return - 1;
		}

		$image_url_path = get_the_post_thumbnail_url( $this->wp_post_id, size: '2048x2048' );
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
			"description" => "Ein Bild welches eine Szene aus dem Film '$this->name' enthält"
		);


		foreach ( $fields as $name => $content ) {
			$post_data .= "--" . $delimiter . $eol . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol . $content . $eol;
		}


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
		$media_status   = json_decode( $media_response, true );
		$media_info     = curl_getinfo( $ch_media_status );
		curl_close( $ch_media_status );
		switch ( $media_info['http_code'] ) {
			case 200:
				return $media_status['id'];
			case 202:
				sleep( 2.5 );

				return $media_status['id'];
			default:
				error_log( "Unable to upload post thumbnail:$eol$media_response" );
				throw new ErrorException( "unable to upload post thumbnail: $eol$media_response" );
		}
	}

	/**
	 * @throws ErrorException
	 */
	public function postToMastodon(): void {
		$token       = get_option( OptionsMastodonToken );
		$instanceUrl = get_option( OptionsMastodonInstance );
		if ( ! $token || ! $instanceUrl ) {
			return;
		}

		$media_id = $this->uploadPostThumbnailToMastodon();

		$status_data = array(
			"status"     => $this->getPostContent(),
			"language"   => "de",
			"visibility" => "public",
		);

		if ( $media_id != null ) {
			$status_data["media_ids"] = array( $media_id );
		}

		$status  = json_encode( $status_data );
		$headers = [
			"Authorization: Bearer $token",
			'Content-Type: application/json'
		];

		$ch_status = curl_init();
		curl_setopt( $ch_status, CURLOPT_URL, "$instanceUrl/api/v1/statuses" );
		curl_setopt( $ch_status, CURLOPT_POST, 1 );
		curl_setopt( $ch_status, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch_status, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch_status, CURLOPT_POSTFIELDS, $status );
		$response      = curl_exec( $ch_status );
		$response_info = curl_getinfo( $ch_status );

		if ( $response_info['http_code'] != 200 ) {
			throw new ErrorException( "Unable to post status update:" . eol . $response );
		}

		curl_close( $ch_status );
	}
}