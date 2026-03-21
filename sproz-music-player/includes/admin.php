<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers all admin menu pages for Sproz Music Player using custom tables.
 */
class Sproz_Admin {

    public static function init() {
        add_action( 'admin_menu',            [ __CLASS__, 'register_menus' ] );
        add_action( 'admin_post_slite_save_track',    [ __CLASS__, 'handle_save_track' ] );
        add_action( 'admin_post_slite_delete_track',  [ __CLASS__, 'handle_delete_track' ] );
        add_action( 'admin_post_slite_save_album',    [ __CLASS__, 'handle_save_album' ] );
        add_action( 'admin_post_slite_delete_album',  [ __CLASS__, 'handle_delete_album' ] );
        add_action( 'admin_post_slite_save_genre',    [ __CLASS__, 'handle_save_taxonomy' ] );
        add_action( 'admin_post_slite_save_category', [ __CLASS__, 'handle_save_taxonomy' ] );
        add_action( 'admin_post_slite_save_tag',      [ __CLASS__, 'handle_save_taxonomy' ] );
        add_action( 'admin_post_slite_delete_term',   [ __CLASS__, 'handle_delete_term' ] );
    }

    /* ── Menus ───────────────────────────────────────────────────────────────── */
    public static function register_menus() {
        add_menu_page(
            __( 'Music Player', 'sproz-music-player' ),
            __( 'Music Player', 'sproz-music-player' ),
            'manage_options',
            'slite-tracks',
            [ __CLASS__, 'page_tracks' ],
            'dashicons-format-audio',
            30
        );
        add_submenu_page( 'slite-tracks', __( 'Tracks',           'sproz-music-player' ), __( 'Tracks',           'sproz-music-player' ), 'manage_options', 'slite-tracks',     [ __CLASS__, 'page_tracks'     ] );
        add_submenu_page( 'slite-tracks', __( 'Albums',           'sproz-music-player' ), __( 'Albums',           'sproz-music-player' ), 'manage_options', 'slite-albums',     [ __CLASS__, 'page_albums'     ] );
        add_submenu_page( 'slite-tracks', __( 'Genres',           'sproz-music-player' ), __( 'Genres',           'sproz-music-player' ), 'manage_options', 'slite-genres',     [ __CLASS__, 'page_genres'     ] );
        add_submenu_page( 'slite-tracks', __( 'Categories',       'sproz-music-player' ), __( 'Categories',       'sproz-music-player' ), 'manage_options', 'slite-categories', [ __CLASS__, 'page_categories' ] );
        add_submenu_page( 'slite-tracks', __( 'Tags',             'sproz-music-player' ), __( 'Tags',             'sproz-music-player' ), 'manage_options', 'slite-tags',       [ __CLASS__, 'page_tags'       ] );
    }

    /* ══════════════════════════════════════════════════════════════════════════
       TRACKS PAGE
    ══════════════════════════════════════════════════════════════════════════ */
    public static function page_tracks() {
        $action = $_GET['action'] ?? 'list';
        $id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

        if ( $action === 'edit' || $action === 'new' ) {
            $track = $id ? Sproz_DB::get_track( $id ) : null;
            self::render_track_form( $track );
        } else {
            self::render_track_list();
        }
    }

    private static function render_track_list() {
        $tracks = Sproz_DB::get_tracks( [ 'limit' => 200, 'orderby' => 'created_at', 'order' => 'DESC' ] );
        $msg    = self::get_flash();
        ?>
        <div class="wrap slite-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Tracks', 'sproz-music-player' ); ?></h1>
            <a href="<?php echo admin_url( 'admin.php?page=slite-tracks&action=new' ); ?>" class="page-title-action"><?php esc_html_e( '+ Add New', 'sproz-music-player' ); ?></a>
            <a href="<?php echo wp_nonce_url( admin_url('admin.php?slite_repair=1'), 'slite_repair' ); ?>" class="page-title-action" style="background:#f0b429;color:#1a1a1a" onclick="return confirm('This will sync all tracks into their album playlists. Continue?')"><?php esc_html_e( '🔧 Fix Album Tracks', 'sproz-music-player' ); ?></a>
            <?php if ( $msg ) echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>'; ?>
            <?php if ( isset($_GET['repaired']) ) echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Album tracks synced successfully!','sproz-music-player') . '</p></div>'; ?>
            <table class="wp-list-table widefat fixed striped" id="slite-track-table">
                <thead><tr>
                    <th><?php esc_html_e( 'Title', 'sproz-music-player' ); ?></th>
                    <th><?php esc_html_e( 'Artist', 'sproz-music-player' ); ?></th>
                    <th><?php esc_html_e( 'Genres', 'sproz-music-player' ); ?></th>
                    <th><?php esc_html_e( 'Duration', 'sproz-music-player' ); ?></th>
                    <th><?php esc_html_e( 'Plays', 'sproz-music-player' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'sproz-music-player' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'sproz-music-player' ); ?></th>
                </tr></thead>
                <tbody>
                <?php if ( empty( $tracks ) ) : ?>
                    <tr><td colspan="7"><?php esc_html_e( 'No tracks yet. Add your first track!', 'sproz-music-player' ); ?></td></tr>
                <?php else : foreach ( $tracks as $t ) : ?>
                    <!-- Track row -->
                    <tr class="slite-track-row" id="slite-row-<?php echo $t->id; ?>">
                        <td><strong><?php echo esc_html( $t->title ); ?></strong></td>
                        <td><?php echo esc_html( $t->artist ); ?></td>
                        <td><?php echo esc_html( implode( ', ', $t->genres ) ); ?></td>
                        <td><?php echo esc_html( $t->duration ); ?></td>
                        <td><?php echo (int) $t->play_count; ?></td>
                        <td><span class="slite-status slite-status-<?php echo esc_attr( $t->status ); ?>"><?php echo esc_html( $t->status ); ?></span></td>
                        <td>
                            <a href="<?php echo admin_url( 'admin.php?page=slite-tracks&action=edit&id=' . $t->id ); ?>"><?php esc_html_e( 'Edit', 'sproz-music-player' ); ?></a> |
                            <a href="#" class="slite-quick-edit-btn" data-id="<?php echo $t->id; ?>"
                               data-title="<?php echo esc_attr($t->title); ?>"
                               data-artist="<?php echo esc_attr($t->artist); ?>"
                               data-duration="<?php echo esc_attr($t->duration); ?>"
                               data-status="<?php echo esc_attr($t->status); ?>"
                               data-audio-url="<?php echo esc_attr($t->audio_url); ?>"
                               data-art-url="<?php echo esc_attr($t->art_url); ?>"><?php esc_html_e( 'Quick Edit', 'sproz-music-player' ); ?></a> |
                            <a href="<?php echo esc_url( home_url( '/slite-track/' . $t->id ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'sproz-music-player' ); ?></a> |
                            <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=slite_delete_track&id=' . $t->id ), 'slite_delete_track_' . $t->id ); ?>"
                               onclick="return confirm('<?php esc_attr_e( 'Delete this track?', 'sproz-music-player' ); ?>')"
                               style="color:#a00"><?php esc_html_e( 'Delete', 'sproz-music-player' ); ?></a>
                        </td>
                    </tr>
                    <!-- Quick Edit inline row (hidden by default) -->
                    <tr class="slite-quick-edit-row" id="slite-qe-<?php echo $t->id; ?>" style="display:none">
                        <td colspan="7">
                            <div class="slite-quick-edit-panel">
                                <h4><?php esc_html_e('Quick Edit', 'sproz-music-player'); ?> — <em class="slite-qe-title-display"><?php echo esc_html($t->title); ?></em></h4>
                                <form class="slite-qe-form" data-id="<?php echo $t->id; ?>">
                                    <?php wp_nonce_field( 'slite_quick_edit', 'slite_qe_nonce' ); ?>
                                    <div class="slite-qe-grid">
                                        <div class="slite-field">
                                            <label><?php esc_html_e('Title','sproz-music-player'); ?></label>
                                            <input type="text" name="title" value="<?php echo esc_attr($t->title); ?>" required />
                                        </div>
                                        <div class="slite-field">
                                            <label><?php esc_html_e('Artist','sproz-music-player'); ?></label>
                                            <input type="text" name="artist" value="<?php echo esc_attr($t->artist); ?>" />
                                        </div>
                                        <div class="slite-field">
                                            <label><?php esc_html_e('Duration','sproz-music-player'); ?></label>
                                            <input type="text" name="duration" value="<?php echo esc_attr($t->duration); ?>" placeholder="3:45" />
                                        </div>
                                        <div class="slite-field">
                                            <label><?php esc_html_e('Status','sproz-music-player'); ?></label>
                                            <select name="status">
                                                <option value="publish" <?php selected($t->status,'publish'); ?>><?php esc_html_e('Published','sproz-music-player'); ?></option>
                                                <option value="draft"   <?php selected($t->status,'draft');   ?>><?php esc_html_e('Draft','sproz-music-player'); ?></option>
                                            </select>
                                        </div>
                                        <div class="slite-field slite-qe-full">
                                            <label><?php esc_html_e('Audio URL','sproz-music-player'); ?></label>
                                            <input type="url" name="audio_url" value="<?php echo esc_url($t->audio_url); ?>" placeholder="https://…/track.mp3" />
                                        </div>
                                        <div class="slite-field slite-qe-full">
                                            <label><?php esc_html_e('Art URL','sproz-music-player'); ?></label>
                                            <div class="slite-upload-row">
                                                <input type="url" name="art_url" id="slite-qe-art-<?php echo $t->id; ?>" value="<?php echo esc_url($t->art_url); ?>" placeholder="https://…/cover.jpg" />
                                                <button type="button" class="button slite-upload-btn" data-target="slite-qe-art-<?php echo $t->id; ?>"><?php esc_html_e('Choose','sproz-music-player'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="slite-qe-actions">
                                        <button type="submit" class="button button-primary"><?php esc_html_e('Update','sproz-music-player'); ?></button>
                                        <button type="button" class="button slite-qe-cancel"><?php esc_html_e('Cancel','sproz-music-player'); ?></button>
                                        <span class="slite-qe-spinner spinner"></span>
                                        <span class="slite-qe-msg"></span>
                                    </div>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function render_track_form( ?object $track ) {
        $is_new  = ! $track;
        $genres  = Sproz_DB::get_genres();
        $cats    = Sproz_DB::get_categories();
        $tags    = Sproz_DB::get_tags();
        $albums  = Sproz_DB::get_albums();

        $sel_genres = $track ? array_column( Sproz_DB::get_track_genres(     $track->id ), 'id' ) : [];
        $sel_cats   = $track ? array_column( Sproz_DB::get_track_categories( $track->id ), 'id' ) : [];
        $sel_tags   = $track ? array_column( Sproz_DB::get_track_tags(       $track->id ), 'id' ) : [];
        ?>
        <div class="wrap slite-wrap">
            <h1><?php echo $is_new ? esc_html__( 'Add New Track', 'sproz-music-player' ) : esc_html__( 'Edit Track', 'sproz-music-player' ); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=slite-tracks'); ?>" class="button">&larr; <?php esc_html_e('Back to Tracks','sproz-music-player'); ?></a>
            <br><br>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field( 'slite_save_track', 'sproz_nonce' ); ?>
                <input type="hidden" name="action" value="slite_save_track" />
                <?php if ( $track ) : ?><input type="hidden" name="id" value="<?php echo $track->id; ?>" /><?php endif; ?>

                <div class="slite-form-grid">
                    <!-- Left column -->
                    <div class="slite-form-main">
                        <div class="postbox">
                            <h2 class="hndle"><?php esc_html_e('Track Details','sproz-music-player'); ?></h2>
                            <div class="inside slite-fields">
                                <?php self::field_text( 'title',  __('Title','sproz-music-player'),  $track->title  ?? '', true ); ?>
                                <?php self::field_text( 'artist', __('Artist','sproz-music-player'), $track->artist ?? '' ); ?>
                                <div class="slite-row-2">
                                    <?php self::field_text( 'duration',     __('Duration (mm:ss)','sproz-music-player'), $track->duration     ?? '', false, '3:45' ); ?>
                                    <?php self::field_number( 'track_number', __('Track Number','sproz-music-player'),  $track->track_number ?? 0 ); ?>
                                </div>
                                <div class="slite-field">
                                    <label><?php esc_html_e('Album','sproz-music-player'); ?></label>
                                    <select name="album_id">
                                        <option value="0"><?php esc_html_e('— None —','sproz-music-player'); ?></option>
                                        <?php foreach ( $albums as $a ) : ?>
                                            <option value="<?php echo $a->id; ?>" <?php selected( $track->album_id ?? 0, $a->id ); ?>><?php echo esc_html($a->title); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="postbox">
                            <h2 class="hndle"><?php esc_html_e('🎵 Audio Source','sproz-music-player'); ?></h2>
                            <div class="inside slite-fields">
                                <div class="slite-field">
                                    <label><?php esc_html_e('Source Type','sproz-music-player'); ?></label>
                                    <select name="audio_type" id="slite_audio_type">
                                        <option value="external" <?php selected($track->audio_type ?? 'external','external'); ?>><?php esc_html_e('External URL (S3, R2, CDN…)','sproz-music-player'); ?></option>
                                        <option value="local"    <?php selected($track->audio_type ?? '','local'); ?>><?php esc_html_e('Upload / Local File','sproz-music-player'); ?></option>
                                    </select>
                                </div>
                                <div class="slite-field" id="slite_external_wrap">
                                    <label><?php esc_html_e('External Audio URL','sproz-music-player'); ?></label>
                                    <input type="url" name="audio_url" value="<?php echo esc_url($track->audio_url ?? ''); ?>" placeholder="https://your-bucket.r2.dev/song.mp3" />
                                </div>
                                <div class="slite-field" id="slite_local_wrap" style="display:none">
                                    <label><?php esc_html_e('Upload Audio File','sproz-music-player'); ?></label>
                                    <div class="slite-upload-row">
                                        <input type="url" name="audio_url_local" id="slite_audio_file" value="<?php echo esc_url($track->audio_url ?? ''); ?>" readonly />
                                        <button type="button" class="button slite-upload-btn" data-target="slite_audio_file"><?php esc_html_e('Choose File','sproz-music-player'); ?></button>
                                    </div>
                                </div>
                                <div class="slite-field">
                                    <label><?php esc_html_e('Album Art URL','sproz-music-player'); ?></label>
                                    <div class="slite-upload-row">
                                        <input type="url" name="art_url" id="slite_art_url" value="<?php echo esc_url($track->art_url ?? ''); ?>" placeholder="https://…/cover.jpg" />
                                        <button type="button" class="button slite-upload-btn" data-target="slite_art_url"><?php esc_html_e('Choose Image','sproz-music-player'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right column -->
                    <div class="slite-form-side">
                        <div class="postbox">
                            <h2 class="hndle"><?php esc_html_e('Publish','sproz-music-player'); ?></h2>
                            <div class="inside slite-fields">
                                <div class="slite-field">
                                    <label><?php esc_html_e('Status','sproz-music-player'); ?></label>
                                    <select name="status">
                                        <option value="publish" <?php selected($track->status ?? 'publish','publish'); ?>><?php esc_html_e('Published','sproz-music-player'); ?></option>
                                        <option value="draft"   <?php selected($track->status ?? '','draft'); ?>><?php esc_html_e('Draft','sproz-music-player'); ?></option>
                                    </select>
                                </div>
                                <?php submit_button( $is_new ? __('Add Track','sproz-music-player') : __('Update Track','sproz-music-player') ); ?>
                            </div>
                        </div>

                        <?php self::taxonomy_box( 'genre_ids',    __('Genres','sproz-music-player'),     $genres, $sel_genres ); ?>
                        <?php self::taxonomy_box( 'category_ids', __('Categories','sproz-music-player'), $cats,   $sel_cats   ); ?>
                        <?php self::taxonomy_box( 'tag_ids',      __('Tags','sproz-music-player'),       $tags,   $sel_tags   ); ?>
                    </div>
                </div><!-- .slite-form-grid -->
            </form>
        </div>
        <?php
    }

    /* ── Track save/delete handlers ──────────────────────────────────────────── */
    public static function handle_save_track() {
        check_admin_referer( 'slite_save_track', 'sproz_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

        $id        = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
        $audio_url = $_POST['audio_type'] === 'local'
                     ? esc_url_raw( $_POST['audio_url_local'] ?? '' )
                     : esc_url_raw( $_POST['audio_url'] ?? '' );

        $data = [
            'title'        => $_POST['title']        ?? '',
            'artist'       => $_POST['artist']       ?? '',
            'audio_url'    => $audio_url,
            'audio_type'   => $_POST['audio_type']   ?? 'external',
            'duration'     => $_POST['duration']     ?? '',
            'track_number' => $_POST['track_number'] ?? 0,
            'album_id'     => $_POST['album_id']     ?? 0,
            'art_url'      => $_POST['art_url']      ?? '',
            'description'  => $_POST['description']  ?? '',
            'status'       => $_POST['status']       ?? 'publish',
        ];

        $old_album_id = $id ? ( Sproz_DB::get_track( $id )->album_id ?? 0 ) : 0;

        if ( $id ) { Sproz_DB::update_track( $id, $data ); }
        else        { $id = Sproz_DB::insert_track( $data ); }

        Sproz_DB::set_track_genres(     $id, array_map('intval', $_POST['genre_ids']    ?? [] ) );
        Sproz_DB::set_track_categories( $id, array_map('intval', $_POST['category_ids'] ?? [] ) );
        Sproz_DB::set_track_tags(       $id, array_map('intval', $_POST['tag_ids']      ?? [] ) );

        // ── Auto-sync album_tracks pivot when album_id changes ────────────────
        $new_album_id = (int) ( $_POST['album_id'] ?? 0 );

        // Remove from old album if album changed
        if ( $old_album_id && $old_album_id !== $new_album_id ) {
            $old_ids = array_values( array_filter( Sproz_DB::get_album_track_ids( $old_album_id ), fn($x) => (int)$x !== $id ) );
            Sproz_DB::set_album_tracks( $old_album_id, $old_ids );
        }

        // Add to new album if set and not already in it
        if ( $new_album_id ) {
            $current_ids = array_map( 'intval', Sproz_DB::get_album_track_ids( $new_album_id ) );
            if ( ! in_array( $id, $current_ids ) ) {
                $current_ids[] = $id;
                Sproz_DB::set_album_tracks( $new_album_id, $current_ids );
            }
        }

        self::set_flash( __( 'Track saved.', 'sproz-music-player' ) );
        wp_redirect( admin_url( 'admin.php?page=slite-tracks' ) );
        exit;
    }

    public static function handle_delete_track() {
        $id = (int) ( $_GET['id'] ?? 0 );
        check_admin_referer( 'slite_delete_track_' . $id );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        Sproz_DB::delete_track( $id );
        self::set_flash( __( 'Track deleted.', 'sproz-music-player' ) );
        wp_redirect( admin_url( 'admin.php?page=slite-tracks' ) );
        exit;
    }

    /* ══════════════════════════════════════════════════════════════════════════
       ALBUMS PAGE
    ══════════════════════════════════════════════════════════════════════════ */
    public static function page_albums() {
        $action = $_GET['action'] ?? 'list';
        $id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
        if ( $action === 'edit' || $action === 'new' ) {
            $album = $id ? Sproz_DB::get_album( $id ) : null;
            self::render_album_form( $album );
        } else {
            self::render_album_list();
        }
    }

    private static function render_album_list() {
        $albums = Sproz_DB::get_albums();
        $msg    = self::get_flash();
        ?>
        <div class="wrap slite-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Albums / Playlists', 'sproz-music-player' ); ?></h1>
            <a href="<?php echo admin_url( 'admin.php?page=slite-albums&action=new' ); ?>" class="page-title-action">+ <?php esc_html_e( 'Add New', 'sproz-music-player' ); ?></a>
            <?php if ( $msg ) echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>'; ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr>
                    <th><?php esc_html_e('Title','sproz-music-player'); ?></th>
                    <th><?php esc_html_e('Artist','sproz-music-player'); ?></th>
                    <th><?php esc_html_e('Year','sproz-music-player'); ?></th>
                    <th><?php esc_html_e('Tracks','sproz-music-player'); ?></th>
                    <th><?php esc_html_e('Shortcode','sproz-music-player'); ?></th>
                    <th><?php esc_html_e('Actions','sproz-music-player'); ?></th>
                </tr></thead>
                <tbody>
                <?php if ( empty($albums) ) : ?>
                    <tr><td colspan="6"><?php esc_html_e('No albums yet.','sproz-music-player'); ?></td></tr>
                <?php else : foreach ( $albums as $a ) :
                    $count = count( Sproz_DB::get_album_track_ids( $a->id ) );
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($a->title); ?></strong></td>
                        <td><?php echo esc_html($a->artist); ?></td>
                        <td><?php echo esc_html($a->release_year ?: '—'); ?></td>
                        <td><?php echo $count; ?></td>
                        <td><code>[sproz_player album="<?php echo $a->id; ?>"]</code></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=slite-albums&action=edit&id='.$a->id); ?>"><?php esc_html_e('Edit','sproz-music-player'); ?></a> |
                            <a href="<?php echo esc_url( home_url( '/slite-album/' . $a->id ) ); ?>" target="_blank"><?php esc_html_e('View','sproz-music-player'); ?></a> |
                            <a href="<?php echo wp_nonce_url( admin_url('admin-post.php?action=slite_delete_album&id='.$a->id), 'slite_delete_album_'.$a->id ); ?>"
                               onclick="return confirm('Delete?')" style="color:#a00"><?php esc_html_e('Delete','sproz-music-player'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function render_album_form( ?object $album ) {
        $is_new     = ! $album;
        $all_tracks = Sproz_DB::get_tracks( [ 'limit' => 500 ] );
        $sel_ids    = $album ? Sproz_DB::get_album_track_ids( $album->id ) : [];
        $sel_ids    = array_map( 'intval', $sel_ids );
        ?>
        <div class="wrap slite-wrap">
            <h1><?php echo $is_new ? esc_html__('Add New Album','sproz-music-player') : esc_html__('Edit Album','sproz-music-player'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=slite-albums'); ?>" class="button">&larr; <?php esc_html_e('Back','sproz-music-player'); ?></a><br><br>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('slite_save_album','sproz_nonce'); ?>
                <input type="hidden" name="action" value="slite_save_album" />
                <?php if ( $album ) : ?><input type="hidden" name="id" value="<?php echo $album->id; ?>" /><?php endif; ?>

                <div class="slite-form-grid">
                    <div class="slite-form-main">
                        <div class="postbox">
                            <h2 class="hndle"><?php esc_html_e('Album Info','sproz-music-player'); ?></h2>
                            <div class="inside slite-fields">
                                <?php self::field_text('title',  __('Album Title','sproz-music-player'),  $album->title  ?? '', true ); ?>
                                <?php self::field_text('artist', __('Artist / Band','sproz-music-player'), $album->artist ?? '' ); ?>
                                <div class="slite-row-2">
                                    <?php self::field_text('release_year', __('Year','sproz-music-player'), $album->release_year ?? '', false, '2024'); ?>
                                    <?php self::field_text('record_label', __('Label','sproz-music-player'), $album->record_label ?? '' ); ?>
                                </div>
                                <div class="slite-field">
                                    <label><?php esc_html_e('Album Art URL','sproz-music-player'); ?></label>
                                    <div class="slite-upload-row">
                                        <input type="url" name="art_url" id="slite_album_art" value="<?php echo esc_url($album->art_url ?? ''); ?>" placeholder="https://…/cover.jpg" />
                                        <button type="button" class="button slite-upload-btn" data-target="slite_album_art"><?php esc_html_e('Choose','sproz-music-player'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Track picker -->
                        <div class="postbox">
                            <h2 class="hndle"><?php esc_html_e('🎵 Playlist Tracks','sproz-music-player'); ?></h2>
                            <div class="inside">
                                <div class="slite-track-picker">
                                    <div class="slite-track-list" id="slite_available_tracks">
                                        <h4><?php esc_html_e('Available','sproz-music-player'); ?></h4>
                                        <?php foreach ( $all_tracks as $t ) :
                                            if ( in_array( (int)$t->id, $sel_ids ) ) continue; ?>
                                            <div class="slite-track-item" data-id="<?php echo $t->id; ?>">
                                                <span class="slite-track-title"><?php echo esc_html($t->title); ?></span>
                                                <span class="slite-track-artist"><?php echo esc_html($t->artist); ?></span>
                                                <button type="button" class="slite-add-track dashicons dashicons-plus-alt"></button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="slite-track-list slite-playlist-tracks" id="slite_selected_tracks">
                                        <h4><?php esc_html_e('Playlist Order','sproz-music-player'); ?> <small><?php esc_html_e('drag to reorder','sproz-music-player'); ?></small></h4>
                                        <?php foreach ( $sel_ids as $tid ) :
                                            $t = Sproz_DB::get_track( $tid );
                                            if ( ! $t ) continue; ?>
                                            <div class="slite-track-item slite-selected" data-id="<?php echo $t->id; ?>">
                                                <span class="dashicons dashicons-menu slite-drag-handle"></span>
                                                <span class="slite-track-title"><?php echo esc_html($t->title); ?></span>
                                                <span class="slite-track-artist"><?php echo esc_html($t->artist); ?></span>
                                                <button type="button" class="slite-remove-track dashicons dashicons-minus"></button>
                                                <input type="hidden" name="track_ids[]" value="<?php echo $t->id; ?>" />
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="slite-form-side">
                        <div class="postbox">
                            <h2 class="hndle"><?php esc_html_e('Publish','sproz-music-player'); ?></h2>
                            <div class="inside slite-fields">
                                <div class="slite-field">
                                    <label><?php esc_html_e('Player Skin','sproz-music-player'); ?></label>
                                    <select name="player_skin">
                                        <option value="dark"  <?php selected($album->player_skin ?? 'dark','dark'); ?>><?php esc_html_e('Dark','sproz-music-player'); ?></option>
                                        <option value="light" <?php selected($album->player_skin ?? '','light'); ?>><?php esc_html_e('Light','sproz-music-player'); ?></option>
                                    </select>
                                </div>
                                <div class="slite-field">
                                    <label><?php esc_html_e('Status','sproz-music-player'); ?></label>
                                    <select name="status">
                                        <option value="publish" <?php selected($album->status ?? 'publish','publish'); ?>><?php esc_html_e('Published','sproz-music-player'); ?></option>
                                        <option value="draft"   <?php selected($album->status ?? '','draft'); ?>><?php esc_html_e('Draft','sproz-music-player'); ?></option>
                                    </select>
                                </div>
                                <?php submit_button( $is_new ? __('Create Album','sproz-music-player') : __('Update Album','sproz-music-player') ); ?>
                                <?php if ( $album ) : ?>
                                <p><code>[sproz_player album="<?php echo $album->id; ?>"]</code></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    public static function handle_save_album() {
        check_admin_referer('slite_save_album','sproz_nonce');
        if ( ! current_user_can('manage_options') ) wp_die('Unauthorized');
        $id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $data = [
            'title'        => $_POST['title']        ?? '',
            'artist'       => $_POST['artist']       ?? '',
            'art_url'      => $_POST['art_url']      ?? '',
            'release_year' => $_POST['release_year'] ?? 0,
            'record_label' => $_POST['record_label'] ?? '',
            'player_skin'  => $_POST['player_skin']  ?? 'dark',
            'status'       => $_POST['status']       ?? 'publish',
        ];
        if ( $id ) { Sproz_DB::update_album($id,$data); }
        else        { $id = Sproz_DB::insert_album($data); }
        Sproz_DB::set_album_tracks( $id, array_map('intval', $_POST['track_ids'] ?? []) );
        self::set_flash( __('Album saved.','sproz-music-player') );
        wp_redirect( admin_url('admin.php?page=slite-albums') );
        exit;
    }

    public static function handle_delete_album() {
        $id = (int)($_GET['id'] ?? 0);
        check_admin_referer('slite_delete_album_'.$id);
        Sproz_DB::delete_album($id);
        self::set_flash(__('Album deleted.','sproz-music-player'));
        wp_redirect( admin_url('admin.php?page=slite-albums') );
        exit;
    }

    /* ══════════════════════════════════════════════════════════════════════════
       TAXONOMY PAGES (Genres / Categories / Tags)
    ══════════════════════════════════════════════════════════════════════════ */
    public static function page_genres()     { self::render_taxonomy_page('genre',    __('Genres','sproz-music-player'),     __('Genre','sproz-music-player')    ); }
    public static function page_categories() { self::render_taxonomy_page('category', __('Categories','sproz-music-player'), __('Category','sproz-music-player') ); }
    public static function page_tags()       { self::render_taxonomy_page('tag',      __('Tags','sproz-music-player'),       __('Tag','sproz-music-player')      ); }

    private static function render_taxonomy_page( string $type, string $plural, string $singular ) {
        $items = match($type) {
            'genre'    => Sproz_DB::get_genres(),
            'category' => Sproz_DB::get_categories(),
            'tag'      => Sproz_DB::get_tags(),
            default    => [],
        };
        $page_slug = 'slite-' . $type . 's';
        $msg       = self::get_flash();
        ?>
        <div class="wrap slite-wrap">
            <h1><?php echo esc_html($plural); ?></h1>
            <?php if ($msg) echo '<div class="notice notice-success is-dismissible"><p>'.esc_html($msg).'</p></div>'; ?>
            <div class="slite-tax-layout">
                <!-- Add form -->
                <div class="slite-tax-form postbox">
                    <h2 class="hndle"><?php printf( esc_html__('Add New %s','sproz-music-player'), esc_html($singular) ); ?></h2>
                    <div class="inside">
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                            <?php wp_nonce_field('slite_save_'.$type,'sproz_nonce'); ?>
                            <input type="hidden" name="action"    value="slite_save_<?php echo $type; ?>" />
                            <input type="hidden" name="term_type" value="<?php echo $type; ?>" />
                            <?php self::field_text('name', __('Name','sproz-music-player'), '', true); ?>
                            <?php self::field_text('slug', __('Slug (auto-generated)','sproz-music-player'), '', false); ?>
                            <?php self::field_text('description', __('Description','sproz-music-player'), '', false); ?>
                            <?php submit_button( sprintf( __('Add %s','sproz-music-player'), $singular ) ); ?>
                        </form>
                    </div>
                </div>

                <!-- List -->
                <div>
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr>
                            <th><?php esc_html_e('Name','sproz-music-player'); ?></th>
                            <th><?php esc_html_e('Slug','sproz-music-player'); ?></th>
                            <th><?php esc_html_e('Shortcode','sproz-music-player'); ?></th>
                            <th><?php esc_html_e('Actions','sproz-music-player'); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php if ( empty($items) ) : ?>
                            <tr><td colspan="4"><?php printf( esc_html__('No %s yet.','sproz-music-player'), strtolower($plural) ); ?></td></tr>
                        <?php else : foreach ($items as $item) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($item->name); ?></strong></td>
                                <td><?php echo esc_html($item->slug); ?></td>
                                <td><code>[sproz_player <?php echo $type; ?>="<?php echo esc_attr($item->slug); ?>"]</code></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(
                                        admin_url('admin-post.php?action=slite_delete_term&type='.$type.'&id='.$item->id),
                                        'slite_delete_term_'.$item->id
                                    ); ?>" onclick="return confirm('Delete?')" style="color:#a00"><?php esc_html_e('Delete','sproz-music-player'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    public static function handle_save_taxonomy() {
        $type = sanitize_key( $_POST['term_type'] ?? '' );
        check_admin_referer( 'slite_save_'.$type, 'sproz_nonce' );
        if ( ! current_user_can('manage_options') ) wp_die('Unauthorized');

        $name = sanitize_text_field( $_POST['name'] ?? '' );
        $slug = sanitize_title( $_POST['slug'] ?? $name );
        $desc = sanitize_text_field( $_POST['description'] ?? '' );

        match($type) {
            'genre'    => Sproz_DB::insert_genre($name,$slug,$desc),
            'category' => Sproz_DB::insert_category($name,$slug,$desc),
            'tag'      => Sproz_DB::insert_tag($name,$slug,$desc),
            default    => null,
        };

        $pages = [ 'genre'=>'slite-genres', 'category'=>'slite-categories', 'tag'=>'slite-tags' ];
        self::set_flash( sprintf( __('%s added.','sproz-music-player'), ucfirst($type) ) );
        wp_redirect( admin_url('admin.php?page='.($pages[$type]??'slite-genres')) );
        exit;
    }

    public static function handle_delete_term() {
        $id   = (int)($_GET['id']   ?? 0);
        $type = sanitize_key($_GET['type'] ?? '');
        check_admin_referer('slite_delete_term_'.$id);
        match($type) {
            'genre'    => Sproz_DB::delete_genre($id),
            'category' => Sproz_DB::delete_category($id),
            'tag'      => Sproz_DB::delete_tag($id),
            default    => null,
        };
        $pages = [ 'genre'=>'slite-genres', 'category'=>'slite-categories', 'tag'=>'slite-tags' ];
        self::set_flash( sprintf( __('%s deleted.','sproz-music-player'), ucfirst($type) ) );
        wp_redirect( admin_url('admin.php?page='.($pages[$type]??'slite-genres')) );
        exit;
    }

    /* ══════════════════════════════════════════════════════════════════════════
       REUSABLE FIELD HELPERS
    ══════════════════════════════════════════════════════════════════════════ */
    private static function field_text( string $name, string $label, string $value = '', bool $required = false, string $placeholder = '' ) { ?>
        <div class="slite-field">
            <label for="slite_<?php echo $name; ?>"><?php echo esc_html($label); ?><?php if($required) echo ' <span style="color:red">*</span>'; ?></label>
            <input type="text" id="slite_<?php echo $name; ?>" name="<?php echo $name; ?>"
                   value="<?php echo esc_attr($value); ?>"
                   placeholder="<?php echo esc_attr($placeholder); ?>"
                   <?php if($required) echo 'required'; ?> />
        </div>
    <?php }

    private static function field_number( string $name, string $label, int $value = 0 ) { ?>
        <div class="slite-field">
            <label><?php echo esc_html($label); ?></label>
            <input type="number" name="<?php echo $name; ?>" value="<?php echo $value; ?>" min="0" />
        </div>
    <?php }

    private static function taxonomy_box( string $field, string $label, array $items, array $selected ) { ?>
        <div class="postbox">
            <h2 class="hndle"><?php echo esc_html($label); ?></h2>
            <div class="inside">
                <?php if ( empty($items) ) : ?>
                    <p style="color:#888;font-size:.82rem"><?php printf( esc_html__('No %s yet — add them first.','sproz-music-player'), strtolower($label) ); ?></p>
                <?php else : foreach ($items as $item) : ?>
                    <label style="display:block;margin-bottom:4px">
                        <input type="checkbox" name="<?php echo $field; ?>[]"
                               value="<?php echo $item->id; ?>"
                               <?php checked( in_array((int)$item->id,$selected) ); ?> />
                        <?php echo esc_html($item->name); ?>
                    </label>
                <?php endforeach; endif; ?>
            </div>
        </div>
    <?php }

    /* ── Flash messages ──────────────────────────────────────────────────────── */
    private static function set_flash( string $msg ): void {
        set_transient( 'slite_flash_' . get_current_user_id(), $msg, 30 );
    }
    private static function get_flash(): string {
        $key = 'slite_flash_' . get_current_user_id();
        $msg = get_transient( $key );
        if ( $msg ) delete_transient( $key );
        return $msg ?: '';
    }
}

Sproz_Admin::init();

// ── Quick Edit AJAX handler ───────────────────────────────────────────────────
add_action( 'wp_ajax_sproz_quick_edit_track', function () {
    check_ajax_referer( 'slite_quick_edit', 'slite_qe_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    $id = (int) ( $_POST['id'] ?? 0 );
    if ( ! $id ) wp_send_json_error( 'Invalid ID' );

    $existing = Sproz_DB::get_track( $id );
    $data = [
        'title'        => sanitize_text_field( $_POST['title']    ?? '' ),
        'artist'       => sanitize_text_field( $_POST['artist']   ?? '' ),
        'duration'     => sanitize_text_field( $_POST['duration'] ?? '' ),
        'status'       => in_array( $_POST['status'] ?? '', ['publish','draft'] ) ? $_POST['status'] : 'publish',
        'audio_url'    => esc_url_raw( $_POST['audio_url'] ?? '' ),
        'art_url'      => esc_url_raw( $_POST['art_url']   ?? '' ),
        'audio_type'   => 'external',
        'album_id'     => $existing ? (int)$existing->album_id : 0,
        'track_number' => $existing ? (int)$existing->track_number : 0,
        'description'  => $existing ? $existing->description : '',
    ];

    Sproz_DB::update_track( $id, $data );

    wp_send_json_success( [
        'title'    => $data['title'],
        'artist'   => $data['artist'],
        'duration' => $data['duration'],
        'status'   => $data['status'],
    ] );
} );

// ── One-time repair: sync pivot table from track.album_id column ──────────────
add_action( 'admin_init', function () {
    if ( ! isset( $_GET['slite_repair'] ) ) return;
    if ( ! current_user_can( 'manage_options' ) ) return;
    check_admin_referer( 'slite_repair' );

    global $wpdb;
    $p = $wpdb->prefix;

    // Get all tracks that have album_id set
    $tracks = $wpdb->get_results( "SELECT id, album_id, track_number FROM {$p}sproz_tracks WHERE album_id > 0 AND status = 'publish' ORDER BY album_id, track_number ASC" );

    // Group by album
    $by_album = [];
    foreach ( $tracks as $t ) {
        $by_album[ (int) $t->album_id ][] = (int) $t->id;
    }

    // For each album, merge with any existing pivot entries
    foreach ( $by_album as $album_id => $track_ids ) {
        $existing = array_map( 'intval', Sproz_DB::get_album_track_ids( $album_id ) );
        $merged   = array_values( array_unique( array_merge( $existing, $track_ids ) ) );
        Sproz_DB::set_album_tracks( $album_id, $merged );
    }

    wp_redirect( admin_url( 'admin.php?page=slite-tracks&repaired=1' ) );
    exit;
} );
