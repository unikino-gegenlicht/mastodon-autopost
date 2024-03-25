<?php
/*
 * Allgemeine Funktionen
 *
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


/*
 * Neues Menü zum Admin Control Panel hinzufügen
 *
*/
// Hook the 'admin_menu' action hook, run the function named 'mapf_Test_Admin_Link()'
add_action( 'admin_menu', 'mapf_Test_Admin_Link' );
// Add a new top level menu link to the ACP
function mapf_Test_Admin_Link()
{
      add_menu_page(
        'Mastodon Auto Post Filme Test', // Title of the page
        'Mastodon Test', // Text to show on the menu link
        'manage_options', // Capability requirement to see the link
        'mastodon-auto-post-filme/includes/mapf-acp.php' // The 'slug' - file to display when clicking the link
    );
}


/*
 * Cron, damit die Toots am Tag gesendet werden
 *
*/


function mapf_deactivate() {
	wp_clear_scheduled_hook( 'mapf_cron' );
}

add_action('init', function() {
	add_action( 'mapf_cron', 'mapf_send_Post' );
	register_deactivation_hook( __FILE__, 'mapf_deactivate' );

	if (! wp_next_scheduled ( 'mapf_cron' )) {
		wp_schedule_event( '1683627462', 'daily', 'mapf_cron' );
	}
});


/*
 * Hier passiert später die Magie ;)
 *
*/
function mapf_send_Post()
{
	//part um Text feszulegen
	
					// Einschränkung auf neuesten Film.
					$args = array(  'post_type'     => 'film',
									'meta_query'    => array(
										array(
											'key'       => 'hauptfilm_date',
											'value'     => date("Y-m-d", strtotime( date("Y-m-d") )),
											'compare'   =>'>=',
											'type'      =>'date',
										)),
									'post_status'       => 'publish',
									'orderby'   		=> 'meta_value date',
									'meta_key'			=> 'hauptfilm_date',
									'order'             => 'ASC',
									'posts_per_page'    => '1'
								);

					// Variable to call WP_Query.
					$filmabfrage = new WP_Query( $args );

					if ( $filmabfrage->have_posts() & get_theme_mod('sepa_checkbox')== false ) :

					

				//Beginn WHile Schleife
					while ( $filmabfrage->have_posts() ) : $filmabfrage->the_post();
						
						$datum = get_post_meta(get_the_ID(),'hauptfilm_date',true);
						$uhrzeit = get_post_meta(get_the_ID(),'hauptfilm_time',true);
						if (get_post_meta(get_the_ID(),'hauptfilm_license_ok',true) == true && !empty(get_post_meta(get_the_ID(),'hauptfilm_title',true))) {
							$titel = 'den Film ' . strtoupper(get_post_meta(get_the_ID(),'hauptfilm_title',true));
						}
						else {
							$titel = get_the_title();
						};
						$genre = get_post_meta(get_the_ID(),'hauptfilm_shown_genre',true);


						if ( has_post_thumbnail(get_the_ID()) ) {
						$img 	= wp_get_attachment_image_src( get_post_thumbnail_id(get_the_ID()), 'large' );
						} 
						
			//ab hier spielen wir mit den Daten
			$statusText = '';
			echo date('Y-m-d', strtotime($datum));
			if (date('Y-m-d') == date('Y-m-d', strtotime('-3 day', strtotime($datum))))
			{
				//hier werden Toots generiert die drei Tage vorher gepostet werden sollen
				$statusText = 'In drei Tagen haben wir im Unikino um ' . $uhrzeit . ' Uhr ' . $titel . ' für euch im Programm
' . get_permalink() . '

#Kino #Uni #Oldenburg #' . $genre . ' #Gegenlicht';
			}
			elseif (date('Y-m-d') == date('Y-m-d', strtotime('-1 day', strtotime($datum))))
			{
				//hier werden Toots generiert die ein Tag vorher gepostet werden sollen
				$statusText = 'Morgen ist wieder Kinotag und wir zeigen im #GEGENLICHT um ' . $uhrzeit . ' Uhr ' . $titel . '
' . get_permalink() . '

#Kino #Uni #Oldenburg #' . $genre;
			}
			elseif (date('Y-m-d') == date('Y-m-d', strtotime($datum)))
			{
				//hier werden Toots generiert die am gleichen Tag kurz vor der Vorstellung gepostet werden sollen
				$statusText = 'Kommt vorbei, wir zeigen nachher im Unikino um ' . $uhrzeit . ' Uhr ' . $titel . '
' . get_permalink() . '

#Kino #Uni #Oldenburg #' . $genre . ' #Gegenlicht';	
			}
			
			
			//$statusText = 'Am ' . date_i18n("d. F", strtotime($datum)) . ' ist es wieder soweit und wir zeigen um ' . $uhrzeit . ' Uhr den Film ' . $titel . ' #Kino #Uni #Oldenburg #' . $genre . '#Gegenlicht';

					// End the Loop
					endwhile;
			else:
				exit;
			endif;

				wp_reset_postdata();
	
	
	//end part um Text festzulegen
	
	
	
	
	
	
	$token = 'XXX'; // Token der MastodonInstanz mit dem Account
    $base_url = 'https://instance.name'; // URL der Mastodon Instanz (bitte kein '/' am Ende.)
    $visibility = 'public'; // "Direct" means sending welcome message as a private message. The four tiers of visibility for toots are Public , Unlisted, Private, and Direct (default)
    $language = 'de'; // Sprache, für Englisch en

    $mastodon = new MastodonAPI($token, $base_url);

    //$curl_file = curl_file_create('Link zum Bild', 'image/jpg', 'ggl_theater_screen.jpg');
	//var_dump($curl_file);
    //$body = [
    //    'file' => $curl_file,
    //];
	//var_dump($body);
    //$response = $mastodon->uploadMedia($body);
	//var_dump($response);
    //$file_id = $response->id;
	//var_dump($file_id);

    $status_data = [
        'status'      => $statusText,
        'visibility'  => $visibility,
        'language'    => $language,
    //    'media_ids[]' => $file_id,
    ];
    $mastodon->postStatus($status_data);
}

add_action( 'admin_post_my_media_update', 'mapf_send_Post' );
