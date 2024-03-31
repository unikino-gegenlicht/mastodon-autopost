<?php

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/discord/get-person-avatar.php';

use PhpChannels\DiscordWebhook\Discord;

const eol = "\r\n";

$utc     = new DateTimeZone( 'UTC' );
$germany = new DateTimeZone( 'Europe/Berlin' );

$threeDaysFromNow = DateTimeImmutable::createFromFormat( "Y-m-d", date( "Y-m-d", strtotime( '+3 day ' ) ), $utc );
$threeDaysFromNow = $threeDaysFromNow->setTimezone( $germany );

$twoDaysFromNow = DateTimeImmutable::createFromFormat( "Y-m-d", date( "Y-m-d", strtotime( '+3 day ' ) ), $utc );
$twoDaysFromNow = $twoDaysFromNow->setTimezone( $germany );

$oneDayFromNow = DateTimeImmutable::createFromFormat( "Y-m-d", date( "Y-m-d", strtotime( '+3 day ' ) ), $utc );
$oneDayFromNow = $oneDayFromNow->setTimezone( $germany );

$today = DateTimeImmutable::createFromFormat( "Y-m-d", date( "Y-m-d", strtotime( '+3 day ' ) ), $utc );
$today = $today->setTimezone( $germany );

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
	 * @var DateTimeImmutable $start The starting point of the movie
	 */
	public DateTimeImmutable $start;

	/**
	 * @var bool $licensed Whether the content is licensed or not
	 */
	public bool $licensed;

	/**
	 * Retrieves the post prefix based on the start date of the movie.
	 *
	 * @return string The post prefix.
	 * @throws Exception If an error occurs while formatting the start date.
	 */
	private function getPostPrefix(): string {
		global $threeDaysFromNow, $twoDaysFromNow, $oneDayFromNow, $today;
		$starting_time = $this->start->format( "H:i" );
		$weekday       = $this->start->format( "l" );
		$status_prefix = '';

		switch ( $this->start->format( "d.m.Y" ) ) {
			case $threeDaysFromNow->format( "d.m.Y" ):
				$status_prefix = "In drei Tagen zeigen wir euch um $starting_time bei uns '$this->name'. Ausgesucht wurde der Film von $this->proposedBy.";
				break;
			case $twoDaysFromNow->format( "d.m.Y" ):
				$status_prefix = "Der $weekday rückt näher und wir freuen uns, euch in zwei Tagen '$this->name' präsentieren zu dürfen.";
				break;
			case $oneDayFromNow->format( "d.m.Y" ):
				$status_prefix = "Morgen ist $weekday und das heißt für euch, dass ihr euch '$this->name' bei uns im Unikino nicht entgehen lassen dürft.";
				break;
			case $today->format( "d.m.Y" ):
				$status_prefix = "Es ist endlich wieder $weekday und damit Zeit für einen Abend im GEGENLICHT. Wir und $this->proposedBy freuen uns, euch heute um $starting_time Uhr '$this->name' präsentieren zu dürfen.";
				break;
		}

		return $status_prefix;

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

		$content = $this->getPostPrefix() . eol . eol;
		$content .= $this->description;
		$content .= eol;
		$content .= eol;
		$content .= "Mehr Infos und Reservierungen unter [gegenlicht.net](" . get_permalink( $this->wp_post_id ) . ")";

		$message = Discord::message( $webhook_url );
		$message->setUsername( $this->proposedBy );
		$message->setContent( $content );
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

		$status_message = $this->getPostPrefix() .
		                  eol .
		                  eol .
		                  wp_trim_words( $this->description, 55, '...' ) .
		                  eol .
		                  "Mehr Infos unter " . get_permalink( $this->wp_post_id ) .
		                  eol .
		                  eol .
		                  "#kino #uni #oldenburg #$this->gerne #unikinos #uni_oldenburg #gegenlicht";

		$words = 55;
		while ( strlen( $status_message ) > 500 ) {
			$status_message = $this->getPostPrefix() .
			                  eol .
			                  eol .
			                  wp_trim_words( $this->description, $words, '...' ) .
			                  eol .
			                  "Mehr Infos unter " . get_permalink( $this->wp_post_id ) .
			                  eol .
			                  eol .
			                  "#kino #uni #oldenburg #$this->gerne #unikinos #uni_oldenburg #gegenlicht";
			$words          = $words - 1;
		}

		$status_data = array(
			"status"     => $status_message,
			"language"   => "de",
			"visibility" => "public",
		);

		if ( $media_id != null ) {
			$status_data["media_ids"] = array( $media_id );
		}


		$headers = [
			"Authorization: Bearer $token",
			"Content-Type: application/json",
		];

		$ch_status = curl_init();
		curl_setopt( $ch_status, CURLOPT_URL, "$instanceUrl/api/v1/statuses" );
		curl_setopt( $ch_status, CURLOPT_POST, 1 );
		curl_setopt( $ch_status, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch_status, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch_status, CURLOPT_POSTFIELDS, json_encode( $status_data ) );
		$response      = curl_exec( $ch_status );
		$response_info = curl_getinfo( $ch_status );

		if ( $response_info['http_code'] != 200 ) {
			throw new ErrorException( "Unable to post status update:" . eol . $response );
		}

		curl_close( $ch_status );
	}
}