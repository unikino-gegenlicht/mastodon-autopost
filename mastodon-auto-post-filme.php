<?php
/**
 * Mastodon Auto Post Filme
 *
 * @package       MASTOFILM
 * @author        Benjamin Witte
 * @license       gplv2
 * @version       0.0.1
 *
 * @wordpress-plugin
 * Plugin Name:   Mastodon Auto Post Filme
 * Plugin URI:    https://gegenlicht.net
 * Description:   Plugin um bald laufende Filme im Gegenlicht einige Zeit vorher automatisch anzukündigen
 * Version:       0.0.1
 * Author:        Benjamin Witte
 * Author URI:    https://your-author-domain.com
 * Text Domain:   mastodon-auto-post-filme
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with Mastodon Auto Post Filme. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path(__FILE__) . 'includes/mapf-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/mapf-core.php';
