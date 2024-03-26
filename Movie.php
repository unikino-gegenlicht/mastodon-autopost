<?php

include __DIR__.'/vendor/autoload.php';
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

	
}