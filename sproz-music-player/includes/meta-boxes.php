<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Register meta boxes ───────────────────────────────────────────────────────
add_action( 'add_meta_boxes', function () {

    add_meta_box(
        'slite_track_audio',
        __( '🎵 Audio Source', 'sproz-music-player' ),
        'slite_render_audio_meta_box',
        'slite_track',
        'normal',
        'high'
    );

    add_meta_box(
        'slite_track_info',
        __( '📋 Track Info', 'sproz-music-player' ),
        'slite_render_track_info_meta_box',
        'slite_track',
        'normal',
        'default'
    );

    add_meta_box(
        'sproz_album_tracks',
        __( '🎵 Playlist Tracks', 'sproz-music-player' ),
        'slite_render_album_tracks_meta_box',
        'slite_album',
        'normal',
        'high'
    );

    add_meta_box(
        'slite_album_info',
        __( '📋 Album Info', 'sproz-music-player' ),
        'slite_render_album_info_meta_box',
        'slite_album',
        'normal',
        'default'
    );
} );

// ── Track: Audio Source meta box ──────────────────────────────────────────────
function slite_render_audio_meta_box( $post ) {
    wp_nonce_field( 'slite_save_meta', 'sproz_nonce' );
    $audio_url  = get_post_meta( $post->ID, '_slite_audio_url',  true );
    $audio_type = get_post_meta( $post->ID, '_slite_audio_type', true ) ?: 'external';
    $audio_file = get_post_meta( $post->ID, '_slite_audio_file', true );
    ?>
    <div class="slite-meta-box">
        <div class="slite-field">
            <label><?php esc_html_e( 'Audio Source Type', 'sproz-music-player' ); ?></label>
            <select name="slite_audio_type" id="slite_audio_type">
                <option value="external" <?php selected( $audio_type, 'external' ); ?>><?php esc_html_e( 'External URL (S3, R2, CDN, SoundCloud…)', 'sproz-music-player' ); ?></option>
                <option value="local"    <?php selected( $audio_type, 'local'    ); ?>><?php esc_html_e( 'Upload / Local File', 'sproz-music-player' ); ?></option>
            </select>
        </div>

        <div class="slite-field" id="slite_external_wrap" style="<?php echo $audio_type !== 'local' ? '' : 'display:none'; ?>">
            <label for="slite_audio_url"><?php esc_html_e( 'External Audio URL', 'sproz-music-player' ); ?></label>
            <input type="url" name="slite_audio_url" id="slite_audio_url"
                   value="<?php echo esc_url( $audio_url ); ?>"
                   placeholder="https://your-bucket.r2.dev/track.mp3" />
            <p class="slite-hint"><?php esc_html_e( 'Paste any direct MP3/M4A/OGG URL — S3, Cloudflare R2, Backblaze B2, SoundCloud, etc.', 'sproz-music-player' ); ?></p>
        </div>

        <div class="slite-field" id="slite_local_wrap" style="<?php echo $audio_type === 'local' ? '' : 'display:none'; ?>">
            <label for="slite_audio_file"><?php esc_html_e( 'Audio File', 'sproz-music-player' ); ?></label>
            <div class="slite-upload-row">
                <input type="url" name="slite_audio_file" id="slite_audio_file"
                       value="<?php echo esc_url( $audio_file ); ?>" readonly />
                <button type="button" class="button slite-upload-btn" data-target="slite_audio_file">
                    <?php esc_html_e( 'Choose File', 'sproz-music-player' ); ?>
                </button>
            </div>
        </div>
    </div>
    <?php
}

// ── Track: Info meta box ──────────────────────────────────────────────────────
function slite_render_track_info_meta_box( $post ) {
    $artist   = get_post_meta( $post->ID, '_slite_artist',   true );
    $duration = get_post_meta( $post->ID, '_slite_duration', true );
    $album_id = get_post_meta( $post->ID, '_slite_album_id', true );
    $track_no = get_post_meta( $post->ID, '_slite_track_no', true );

    // Get all albums for dropdown
    $albums = get_posts( [ 'post_type' => 'slite_album', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
    ?>
    <div class="slite-meta-box slite-grid-2">
        <div class="slite-field">
            <label for="slite_artist"><?php esc_html_e( 'Artist Name', 'sproz-music-player' ); ?></label>
            <input type="text" name="slite_artist" id="slite_artist"
                   value="<?php echo esc_attr( $artist ); ?>" placeholder="Artist name" />
        </div>
        <div class="slite-field">
            <label for="slite_duration"><?php esc_html_e( 'Duration (mm:ss)', 'sproz-music-player' ); ?></label>
            <input type="text" name="slite_duration" id="slite_duration"
                   value="<?php echo esc_attr( $duration ); ?>" placeholder="3:45" />
        </div>
        <div class="slite-field">
            <label for="slite_album_id"><?php esc_html_e( 'Album / Playlist', 'sproz-music-player' ); ?></label>
            <select name="slite_album_id" id="slite_album_id">
                <option value=""><?php esc_html_e( '— None —', 'sproz-music-player' ); ?></option>
                <?php foreach ( $albums as $album ) : ?>
                    <option value="<?php echo $album->ID; ?>" <?php selected( $album_id, $album->ID ); ?>>
                        <?php echo esc_html( $album->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="slite-field">
            <label for="slite_track_no"><?php esc_html_e( 'Track Number', 'sproz-music-player' ); ?></label>
            <input type="number" name="slite_track_no" id="slite_track_no"
                   value="<?php echo esc_attr( $track_no ); ?>" min="1" placeholder="1" />
        </div>
    </div>
    <?php
}

// ── Album: Tracks meta box ────────────────────────────────────────────────────
function slite_render_album_tracks_meta_box( $post ) {
    wp_nonce_field( 'slite_save_meta', 'sproz_nonce' );
    $track_ids = get_post_meta( $post->ID, '_slite_track_ids', true ) ?: [];

    // All tracks
    $all_tracks = get_posts( [
        'post_type'   => 'slite_track',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ] );
    ?>
    <div class="slite-meta-box">
        <p class="slite-hint"><?php esc_html_e( 'Select and order the tracks for this album/playlist.', 'sproz-music-player' ); ?></p>
        <div class="slite-track-picker">
            <div class="slite-track-list" id="slite_available_tracks">
                <h4><?php esc_html_e( 'Available Tracks', 'sproz-music-player' ); ?></h4>
                <?php foreach ( $all_tracks as $track ) :
                    if ( in_array( $track->ID, $track_ids ) ) continue;
                    $artist = get_post_meta( $track->ID, '_slite_artist', true );
                ?>
                    <div class="slite-track-item" data-id="<?php echo $track->ID; ?>">
                        <span class="slite-track-title"><?php echo esc_html( $track->post_title ); ?></span>
                        <?php if ( $artist ) : ?><span class="slite-track-artist"><?php echo esc_html( $artist ); ?></span><?php endif; ?>
                        <button type="button" class="slite-add-track dashicons dashicons-plus-alt" title="Add"></button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="slite-track-list slite-playlist-tracks" id="slite_selected_tracks">
                <h4><?php esc_html_e( 'Playlist Order', 'sproz-music-player' ); ?> <small><?php esc_html_e( '(drag to reorder)', 'sproz-music-player' ); ?></small></h4>
                <?php foreach ( $track_ids as $tid ) :
                    $track  = get_post( $tid );
                    if ( ! $track ) continue;
                    $artist = get_post_meta( $tid, '_slite_artist', true );
                ?>
                    <div class="slite-track-item slite-selected" data-id="<?php echo $tid; ?>">
                        <span class="dashicons dashicons-menu slite-drag-handle"></span>
                        <span class="slite-track-title"><?php echo esc_html( $track->post_title ); ?></span>
                        <?php if ( $artist ) : ?><span class="slite-track-artist"><?php echo esc_html( $artist ); ?></span><?php endif; ?>
                        <button type="button" class="slite-remove-track dashicons dashicons-minus" title="Remove"></button>
                        <input type="hidden" name="slite_track_ids[]" value="<?php echo $tid; ?>" />
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

// ── Album: Info meta box ──────────────────────────────────────────────────────
function slite_render_album_info_meta_box( $post ) {
    $artist      = get_post_meta( $post->ID, '_slite_artist',      true );
    $year        = get_post_meta( $post->ID, '_slite_year',        true );
    $label       = get_post_meta( $post->ID, '_slite_label',       true );
    $player_skin = get_post_meta( $post->ID, '_sproz_player_skin', true ) ?: 'dark';
    ?>
    <div class="slite-meta-box slite-grid-2">
        <div class="slite-field">
            <label for="slite_artist"><?php esc_html_e( 'Artist / Band', 'sproz-music-player' ); ?></label>
            <input type="text" name="slite_artist" id="slite_artist"
                   value="<?php echo esc_attr( $artist ); ?>" placeholder="Artist name" />
        </div>
        <div class="slite-field">
            <label for="slite_year"><?php esc_html_e( 'Release Year', 'sproz-music-player' ); ?></label>
            <input type="number" name="slite_year" id="slite_year"
                   value="<?php echo esc_attr( $year ); ?>" placeholder="2024" min="1900" max="2099" />
        </div>
        <div class="slite-field">
            <label for="slite_label"><?php esc_html_e( 'Record Label', 'sproz-music-player' ); ?></label>
            <input type="text" name="slite_label" id="slite_label"
                   value="<?php echo esc_attr( $label ); ?>" placeholder="Label name" />
        </div>
        <div class="slite-field">
            <label for="sproz_player_skin"><?php esc_html_e( 'Player Skin', 'sproz-music-player' ); ?></label>
            <select name="sproz_player_skin" id="sproz_player_skin">
                <option value="dark"  <?php selected( $player_skin, 'dark'  ); ?>><?php esc_html_e( 'Dark', 'sproz-music-player' ); ?></option>
                <option value="light" <?php selected( $player_skin, 'light' ); ?>><?php esc_html_e( 'Light', 'sproz-music-player' ); ?></option>
            </select>
        </div>
    </div>
    <?php
}

// ── Save meta ─────────────────────────────────────────────────────────────────
add_action( 'save_post', function ( $post_id ) {
    if ( ! isset( $_POST['sproz_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['sproz_nonce'], 'slite_save_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $post_type = get_post_type( $post_id );

    if ( $post_type === 'slite_track' ) {
        $audio_type = sanitize_text_field( $_POST['slite_audio_type'] ?? 'external' );
        update_post_meta( $post_id, '_slite_audio_type', $audio_type );

        if ( $audio_type === 'local' ) {
            update_post_meta( $post_id, '_slite_audio_url', esc_url_raw( $_POST['slite_audio_file'] ?? '' ) );
        } else {
            update_post_meta( $post_id, '_slite_audio_url', esc_url_raw( $_POST['slite_audio_url'] ?? '' ) );
        }

        update_post_meta( $post_id, '_slite_artist',   sanitize_text_field( $_POST['slite_artist']   ?? '' ) );
        update_post_meta( $post_id, '_slite_duration', sanitize_text_field( $_POST['slite_duration'] ?? '' ) );
        update_post_meta( $post_id, '_slite_album_id', intval( $_POST['slite_album_id'] ?? 0 ) );
        update_post_meta( $post_id, '_slite_track_no', intval( $_POST['slite_track_no'] ?? 0 ) );
    }

    if ( $post_type === 'slite_album' ) {
        $track_ids = array_map( 'intval', $_POST['slite_track_ids'] ?? [] );
        update_post_meta( $post_id, '_slite_track_ids',     $track_ids );
        update_post_meta( $post_id, '_slite_artist',        sanitize_text_field( $_POST['slite_artist']      ?? '' ) );
        update_post_meta( $post_id, '_slite_year',          sanitize_text_field( $_POST['slite_year']        ?? '' ) );
        update_post_meta( $post_id, '_slite_label',         sanitize_text_field( $_POST['slite_label']       ?? '' ) );
        update_post_meta( $post_id, '_sproz_player_skin',   sanitize_text_field( $_POST['sproz_player_skin'] ?? 'dark' ) );
    }
} );
