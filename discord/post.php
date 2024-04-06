<?php
include __DIR__ . '../vendor/autoload.php';

use PhpChannels\DiscordWebhook\Discord;

$utc     = new DateTimeZone( 'UTC' );
$germany = new DateTimeZone( 'Europe/Berlin' );

$now = DateTimeImmutable::createFromFormat( "Y-m-d", date( "Y-m-d", strtotime( 'now' ) ), $utc );
$now = $now->setTimezone( $germany );

/**
 * Posts a movie to Discord.
 *
 * @param MAP_Movie[] $movies The movie object to post.
 *
 * @return void
 * @throws Exception
 */
function postToDiscord( array $movies ): void {
	global $now;
	$webhook_url = get_option( OptionsDiscordWebhookUrl );
	if ( ! $webhook_url ) {
		return;
	}

	foreach ( $movies as $movie ) {
		$day_difference = $movie->start->diff($now);
		

		$content = $movie->getPostPrefix() . eol . eol;
		$content .= $movie->description;
		$content .= eol;
		$content .= eol;
		$content .= "Mehr Infos und Reservierungen unter [gegenlicht.net](" . get_permalink( $movie->wp_post_id ) . ")";

		$message = Discord::message( $webhook_url );
		$message->setUsername( $movie->proposedBy );
		$message->setContent( $content );
		$message->setImage( get_the_post_thumbnail_url( $movie->wp_post_id, size: 'original' ) );
		$message->setAvatarUrl( map_get_person_avatar_url( $movie ) );
		$message->send();
	}


}