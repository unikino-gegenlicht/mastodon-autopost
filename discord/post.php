<?php
include plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';
require_once plugin_dir_path(__FILE__). '../consts.php';

use PhpChannels\DiscordWebhook\Discord;

/**
 * Posts a movie to Discord.
 *
 * @param MAP_Movie[] $movies The movie object to post.
 *
 * @return void
 * @throws Exception
 */
function postToDiscord( array $movies, bool $testing = false ): void {
	$webhook_url = get_option( OptionsDiscordWebhookUrl );
	if ( ! $webhook_url ) {
		return;
	}

	$moviesToday = array();
	$tz          = new DateTimeZone( 'Europe/Berlin' );
	$now         = new DateTimeImmutable( null, timezone: $tz );
	foreach ( $movies as $movie ) {
		if ( $movie->start->diff( $now )->days == 0 && ! $testing ) {
			$moviesToday[] = $movie;
		} else if ( $testing ) {
			$moviesToday[] = $movie;
		}
	}

	foreach ( $moviesToday as $movie ) {
		$content = get_opener_line() . eol . eol;
		$content .= "Heute um" . $movie->start->format( "H:i" ) . " Uhr haben wir für euch $movie->name im Angebot." . eol . eol;
		$content .= $movie->description . eol . eol;
		$content .= "Kommt gerne vorbei und reserviert euch am besten vorher eure :tickets: [hier](" . get_the_permalink( $movie->wp_post_id ) . ")";

		$message = Discord::message( $webhook_url );
		$message->setContent( $content );
		$message->setAvatarUrl( map_get_person_avatar_url( $movie ) );
		$message->setUsername( $movie->proposedBy );
		$message->setImage( get_the_post_thumbnail_url( $movie->wp_post_id, 'original' ) );
		$message->setColor( '16768256' );
		$message->send();
	}

}