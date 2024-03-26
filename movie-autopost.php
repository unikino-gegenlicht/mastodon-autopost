<?php
/**
 * Mastodon Auto Post Filme
 *
 * @package       Movie Autopost
 * @author        Benjamin Witte, Jan Eike Suchard
 * @license       EUPL1.2-or-later
 * @version       0.1.0
 *
 * @wordpress-plugin
 * Plugin Name:   Movie Autopost
 * Plugin URI:    https://gegenlicht.net
 * Description:   Plugin um bald laufende Filme im Gegenlicht einige Zeit vorher automatisch anzukündigen
 * Version:       0.1.0
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

const OptionsPrefix               = "movie-autopost";
const OptionsSeparator            = '_';
const OptionsDiscordWebhookUrlKey = OptionsPrefix . OptionsSeparator . 'discord-webhook-url';
const OptionsDiscordFallbackAvatarUrl = OptionsPrefix.OptionsSeparator.'discord-fallback-avatar-url';
const OptionsMastodonInstanceKey  = OptionsPrefix . OptionsSeparator . 'mastodon-instance';
const OptionsMastodonTokenKey     = OptionsPrefix . OptionsSeparator . 'mastodon-api-key';

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
function configure_menus() {
	add_menu_page( 'Social Media Autopost', 'Social Media Autopost', 'manage_options', 'social_media_autopost' );
	add_submenu_page( 'social_media_autopost', 'Configuration', 'Configuration', 'manage_options', 'social_media_autopost-config' );
	add_submenu_page( 'social_media_autopost', 'Tests', 'Tests', 'manage_options', 'social_media_autopost-tests' );
}

/**
 * Initializes the settings for the plugin.
 *
 * Registers the options for Discord webhook URL, Mastodon instance, and Mastodon token.
 *
 * @return void
 */
function initialize_settings() {
	register_setting( "options", OptionsDiscordWebhookUrlKey );
	register_setting( "options", OptionsDiscordFallbackAvatarUrl );
	register_setting( "options", OptionsMastodonInstanceKey );
	register_setting( "options", OptionsMastodonTokenKey );
}

function register_cron() {
	add_action( CronName, 'cron' );
	register_deactivation_hook( __FILE__, 'deregister_cron' );
	if ( ! wp_next_scheduled( CronName ) ) {
		wp_schedule_event( 1711474200, 'daily', CronName );
	}
}

function deregister_cron() {
	wp_clear_scheduled_hook( CronName );
}

add_action( 'admin_menu', callback: 'configure_menus' );
add_action( 'init', callback: 'initialize_settings' );
add_action( 'init', callback: 'register_cron' );


