<?php

function map_overview_html() {
	?>
    <div class="wrap">
        <h1><?= esc_html( get_admin_page_title() ) ?></h1>
        <br/>
        <br/>
        <table>
            <tr>
                <th>Letzte Cron-Ausführung</th>
                <td><?= get_option(OptionGroup.'_last-cron', 'unbekannt')?></td>
            </tr>
        </table>

        <h2>Tests</h2>
        <p>
            In diesem Abschnitt können die beiden Portale getestet werden. Hierfür wird ein standardisierter Post auf
            den jeweiligen Portalen veröffentlicht.
        </p>
        <script>
            function map_test_discord() {
                jQuery.post(ajaxurl, {action: "movie_autopost_test_discord"}, function (responseContent) {
                    let response = JSON.parse(responseContent)
                    alert(response.message);
                })
            }
            function map_test_mastodon() {
                jQuery.post(ajaxurl, {action: "movie_autopost_test_mastodon"}, function (responseContent) {
                    let response = JSON.parse(responseContent)
                    alert(response.message);
                })
            }
            function map_test_movie_filter() {
                jQuery.post(ajaxurl, {action: "movie_autopost_test_query"}, function (responseContent) {
                    let response = JSON.parse(responseContent)
                    alert(response);
                })
            }
            function map_test_execute_cron() {
                jQuery.post(ajaxurl, {action: "movie_autopost_execute_cron_manually"}, function (responseContent) {
                    let response = JSON.parse(responseContent)
                    alert(response);
                })
            }
        </script>
        <button class="button" onclick="map_test_discord()">
            Test Discord Autopost
        </button>
        <button class="button" onclick="map_test_mastodon()">
            Test Mastodon Autopost
        </button>
        <button class="button" onclick="map_test_movie_filter()">
            Test Movie Filter Query
        </button>
        <button class="button" onclick="map_test_execute_cron()">
            Run Cron Task Manually
        </button>
    </div>
	<?php
}