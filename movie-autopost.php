<?php
/**
 * Mastodon Auto Post Filme
 *
 * @package       Movie Autopost
 * @author        Benjamin Witte, Jan Eike Suchard
 * @license       EUPL1.2-or-later
 * @version       1.0.1
 *
 * @wordpress-plugin
 * Plugin Name:   Movie Autopost
 * Plugin URI:    https://gegenlicht.net
 * Description:   Plugin um bald laufende Filme im Gegenlicht einige Zeit vorher automatisch anzukündigen
 * Version:       1.0.1
 * Author:        Benjamin Witte, Jan Eike Suchard
 * Author URI:    https://your-author-domain.com
 * Text Domain:   movie-autopost
 * Domain Path:   /languages
 * License:       EUPL1.2-or-later
 * License URI:   https://joinup.ec.europa.eu/sites/default/files/custom-page/attachment/2020-03/EUPL-1.2%20EN.txt
 *
 * You should have received a copy of the GNU General Public License
 * along with Mastodon Auto Post Filme. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

require_once 'views/settings.php';
require_once 'views/index.php';
require_once 'test/test.php';
require_once 'common/get-movies.php';

const OptionGroup                     = "movie-autopost";
const OptionsDiscordWebhookUrl        = OptionGroup . '_discord-webhook-url';
const OptionsDiscordFallbackAvatarUrl = OptionGroup . '_discord-fallback-avatar-url';
const OptionsMastodonInstance         = OptionGroup . '_mastodon-instance';
const OptionsMastodonToken            = OptionGroup . '_mastodon-api-key';
const OptionsTestPostID            = OptionGroup . '_test-post-id';

const CronName = "movie-autopost_cron";

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a menu item for Autopost Tests to the admin menu.
 *
 * @return void
 */
function map_configure_menus() {
	add_menu_page( 'Social Media Autopost', 'Social Media Autopost', 'manage_options', OptionGroup, 'map_overview_html' );
	add_submenu_page( OptionGroup, 'Einstellungen', 'Einstellungen', 'manage_options', OptionGroup . '-settings', 'map_settings_html' );
}

/**
 * Initializes the settings for the plugin.
 *
 * Registers the options for Discord webhook URL, Mastodon instance, and Mastodon token.
 *
 * @return void
 */
function map_initialize_settings() {
	add_option( OptionsDiscordWebhookUrl );
	add_option( OptionsDiscordFallbackAvatarUrl );
	add_option( OptionsMastodonInstance );
	add_option( OptionsMastodonToken );
	add_option( OptionsTestPostID );
	register_setting( OptionGroup, OptionsDiscordWebhookUrl, array(
		"type"        => "string",
		"description" => "Die Webhook URL über die die Discord-Nachrichten verschickt werden"
	) );
	register_setting( OptionGroup, OptionsDiscordFallbackAvatarUrl, array(
		"type"        => "string",
		"description" => "Eine URL zu einem Bild, das als Avatar verwendet wird, wenn kein Avatar für das Teammitglied bekannt ist"
	) );
	register_setting( OptionGroup, OptionsMastodonInstance, array(
		"type"        => "string",
		"description" => "Die URL zu der Mastodon Instanz, auf der der Post abgesetzt wird"
	) );
	register_setting( OptionGroup, OptionsMastodonToken, array(
		"type"        => "string",
		"description" => "Der API-Token um auf Mastodon zugreifen zu können"
	) );
	register_setting( OptionGroup, OptionsTestPostID, array(
		"type"        => "string",
		"description" => "Die ID des Posts, der zum testen des Plugins verwendet werden soll"
	) );
	add_settings_section( OptionGroup . '-discord', 'Discord', 'map_render_discord_section', OptionGroup );
	add_settings_field(
		OptionsDiscordWebhookUrl,
		'Discord Webhook URL',
		'map_render_url_input',
		OptionGroup,
		OptionGroup . '-discord',
		array(
			"id"          => OptionsDiscordWebhookUrl,
			"description" => "URL an die der Film geschickt wird"
		)
	);
	add_settings_field(
		OptionsDiscordFallbackAvatarUrl,
		'Discord Avatar Fallback URL',
		'map_render_url_input',
		OptionGroup,
		OptionGroup . '-discord',
		array(
			"id"          => OptionsDiscordFallbackAvatarUrl,
			"description" => "Der Avatar, der verwendet werden soll, wenn kein anderer verfügbar ist"
		)
	);
	add_settings_section( OptionGroup . '-mastodon', 'Mastodon', 'map_render_mastodon_section', OptionGroup );
	add_settings_field(
		OptionsMastodonInstance,
		'Mastodon Instance URL',
		'map_render_url_input',
		OptionGroup,
		OptionGroup . '-mastodon',
		array(
			"id"          => OptionsMastodonInstance,
			"description" => "Die Mastodon-Instanz die zum posten verwendet wird"
		)
	);
	add_settings_field(
		OptionsMastodonToken,
		'Access Token',
		'map_render_password_input',
		OptionGroup,
		OptionGroup . '-mastodon',
		array(
			"id"          => OptionsMastodonToken,
			"description" => "Der API-Key für die Mastodon Instanz"
		)
	);
	add_settings_section('default', 'Sonstiges', '', OptionGroup);
	add_settings_field(
		OptionsTestPostID,
		'Access Token',
		'map_render_text_input',
		OptionGroup,
		'default',
		array(
			"id"          => OptionsTestPostID,
			"description" => "Die Post-ID, die zum testen des Plugins verwendet wird"
		)
	);


}

function map_register_cron() {
	add_action( CronName, 'map_cron' );
	add_option( OptionGroup . '_last-cron' );
	if ( ! wp_next_scheduled( CronName ) ) {
		wp_schedule_event( 1711474200, 'daily', CronName );
	}
}

function map_cron() {
	$movies = map_get_movies();
	foreach ( $movies as $movie ) {
		try {
			$movie->postToDiscord();
		} catch ( Exception $e ) {
			wp_mail(get_option( 'admin_email' ), '[Autopost] Unable to Post to Discord', $e->getMessage());
		}
		try {
			$movie->postToMastodon();
		} catch ( ErrorException $e ) {
			wp_mail(get_option( 'admin_email' ), '[Autopost] Unable to Post to Mastodon', $e->getMessage());

		}
	}
	update_option( OptionGroup . '_last-cron', date( 'd.m.Y H:i:s' ) );
}

function map_run_filter_query_test() {
	$movies = map_get_movies();
	echo json_encode( $movies );
	wp_die();
}

#[NoReturn] function map_run_cron(): void {
	map_cron();
	wp_die();
}

function map_cleanup() {
	wp_clear_scheduled_hook( CronName );
}

register_deactivation_hook( __FILE__, 'map_cleanup' );

add_action( 'admin_init', callback: 'map_initialize_settings' );
add_action( 'admin_menu', callback: 'map_configure_menus' );
add_action('init', 'map_register_cron');
add_action( 'wp_ajax_movie_autopost_test_discord', 'map_run_discord_test' );
add_action( 'wp_ajax_movie_autopost_test_mastodon', 'map_run_mastodon_test' );
add_action( 'wp_ajax_movie_autopost_test_query', 'map_run_filter_query_test' );
add_action( 'wp_ajax_movie_autopost_execute_cron_manually', 'map_run_cron' );
