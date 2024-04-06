<?php

require_once plugin_dir_path( __FILE__ ) . '../consts.php';

function map_render_discord_section() {
	?>
    <p>Stelle hier alle Optionen für Discord ein</p>
	<?php
}

function map_render_mastodon_section() {
	?>
    <p>Stelle hier alle Optionen für Mastodon ein</p>
	<?php
}

function map_render_url_input( $args ) {
	?>
    <input type="url" name="<?= $args['id'] ?>" id="<?= $args['id'] ?>"
           value="<?= get_option( $args['id'], 'unset' ) ?>"/>
    <br/>
    <label for="<?= $args['id'] ?>"><?= $args['description'] ?></label><br/>
	<?php
}

function map_render_text_input( $args ) {
	?>
    <input type="text" name="<?= $args['id'] ?>" id="<?= $args['id'] ?>"
           value="<?= get_option( $args['id'], 'unset' ) ?>"/>
    <br/>
    <label for="<?= $args['id'] ?>"><?= $args['description'] ?></label><br/>
	<?php
}


function map_render_password_input( $args ) {
	?>
    <input type="password" name="<?= $args['id'] ?>" id="<?= $args['id'] ?>"
           value="<?= get_option( $args['id'], 'unset' ) ?>"/>
    <br/>
    <label for="<?= $args['id'] ?>"><?= $args['description'] ?></label><br/>

	<?php
}

function map_settings_html(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		add_settings_error( 'movie-autopost-errors', 'movie-autopost-settings-stored', 'Sie haben nicht die erforderlichen Rechte, um diese Seite aufzurufen', 'error' );
		settings_errors( 'movie-autopost-errors' );

		return;
	}

	if ( isset( $_GET['settings-updated'] ) ) {
		add_settings_error( 'movie-autopost-errors', 'movie-autopost-settings-stored', 'Einstellungen erfolgreich gespeichert', 'success' );
	}
	settings_errors( 'movie-autopost-errors' );

	?>
    <div class="wrap">
        <h1><?= esc_html( get_admin_page_title() ) ?> </h1>
        <form action="options.php" method="post">
			<?php
			settings_fields( OptionGroup );
			do_settings_sections( OptionGroup );
			submit_button( 'Speichern' );
			?>
        </form>
    </div>
	<?php
}
