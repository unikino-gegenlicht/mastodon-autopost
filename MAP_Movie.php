<?php

include __DIR__ . '/discord/get-person-avatar.php';

const eol   = "\r\n";
const space = " ";

$utc     = new DateTimeZone( 'UTC' );
$germany = new DateTimeZone( 'Europe/Berlin' );

$threeDaysFromNow = DateTimeImmutable::createFromFormat( "Y-m-d", date( "Y-m-d", strtotime( '+3 days' ) ), $utc );
$threeDaysFromNow = $threeDaysFromNow->setTimezone( $germany );

$twoDaysFromNow = DateTimeImmutable::createFromFormat( "Y-m-d", date( "Y-m-d", strtotime( '+2 day' ) ), $utc );
$twoDaysFromNow = $twoDaysFromNow->setTimezone( $germany );

$oneDayFromNow = DateTimeImmutable::createFromFormat( "Y-m-d", date( "Y-m-d", strtotime( '+1 day' ) ), $utc );
$oneDayFromNow = $oneDayFromNow->setTimezone( $germany );

$today = DateTimeImmutable::createFromFormat( "Y-m-d", date( "Y-m-d", strtotime( 'today' ) ), $utc );
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
	public function getPostPrefix( int $dayDifference ): string {
		global $threeDaysFromNow, $twoDaysFromNow, $oneDayFromNow, $today;
		$starting_time = $this->start->format( "H:i" );
		$weekday       = $this->start->format( "l" );
		switch ( $dayDifference ) {
			case 0:
				$status_prefix = "Heute ist es wieder soweit, es gibt wieder was zu sehen im GEGENLICHT.";
				if ( $this->licensed ) {
					$status_prefix .= space . "Wir zeigen euch $this->name";
				}
		}


		return $status_prefix;

	}


}