<?php
/*
 * Die Seite, die sich im Admin Control Panel befindet
 *
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

//ab dem closing tag kommt er richtige Content
?>

<div class="wrap">
	<h1>Yay!</h1>
	<p>Erstmal hier bitte alles ignorieren. Hier wird an einem Mastodon Auto Poster gebastelt für bald im Gegenlicht laufende Filme</p>
<!--	<form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
		<?php wp_nonce_field(); ?>
		<input type="hidden" name="action" value="mapf_send_post_test">
		<input type="submit" value="Test (bitte nur drücken wenn man weiß was passiert 🙈😅">
	</form>-->
	<form method="post">
		<input type="submit" name="test" id="test" value="Test (bitte nur drücken wenn man weiß was passiert 🙈😅)" /><br/>
	</form>

<?php

function mapf_send_Post_Test()
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
						$titel = get_post_meta(get_the_ID(),'hauptfilm_title',true);
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
				$statusText = 'In drei Tagen haben wir im Unikino um ' . $uhrzeit . ' Uhr den Film ' . strtoupper($titel) . ' für euch im Programm
' . get_permalink() . '

#Kino #Uni #Oldenburg #' . $genre . ' #Gegenlicht';
			}
			elseif (date('Y-m-d') == date('Y-m-d', strtotime('-1 day', strtotime($datum))))
			{
				//hier werden Toots generiert die ein Tag vorher gepostet werden sollen
				$statusText = 'Morgen ist wieder Kinotag und wir zeigen im #GEGENLICHT um ' . $uhrzeit . ' Uhr den Film ' . strtoupper($titel) . '
' . get_permalink() . '

#Kino #Uni #Oldenburg #' . $genre;
			}
			elseif (date('Y-m-d') == date('Y-m-d', strtotime($datum)))
			{
				//hier werden Toots generiert die am gleichen Tag kurz vor der Vorstellung gepostet werden sollen
				$statusText = 'Kommt vorbei, wir zeigen nachher im Unikino um ' . $uhrzeit . ' Uhr den Film ' . strtoupper($titel) . '
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
    $base_url = 'https://instanz.name'; // URL der Mastodon Instanz (bitte kein '/' am Ende.)
    $visibility = 'direct'; // "Direct" means sending welcome message as a private message. The four tiers of visibility for toots are Public , Unlisted, Private, and Direct (default)
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
	var_dump($status_data);
    $mastodon->postStatus($status_data);
	
	echo 'ok';
}

if(array_key_exists('test',$_POST)){
   mapf_send_Post_Test();
}

?>
</div>
