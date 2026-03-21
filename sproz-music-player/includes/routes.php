<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Sproz_Routes {

    public static function init() {
        add_action( 'init',              [ __CLASS__, 'add_rewrite_rules' ] );
        add_filter( 'query_vars',        [ __CLASS__, 'add_query_vars'    ] );
        add_action( 'template_redirect', [ __CLASS__, 'handle_routes'     ] );
    }

    public static function add_rewrite_rules() {
        add_rewrite_rule( '^slite-track/([0-9]+)/?$', 'index.php?slite_track_id=$matches[1]', 'top' );
        add_rewrite_rule( '^slite-album/([0-9]+)/?$', 'index.php?slite_album_id=$matches[1]', 'top' );
        add_rewrite_rule( '^music-library/?$',         'index.php?slite_library=1',            'top' );
    }

    public static function add_query_vars( $vars ) {
        $vars[] = 'slite_track_id';
        $vars[] = 'slite_album_id';
        $vars[] = 'slite_library';
        return $vars;
    }

    public static function handle_routes() {
        $track_id = get_query_var( 'slite_track_id' );
        $album_id = get_query_var( 'slite_album_id' );
        $library  = get_query_var( 'slite_library' );

        if ( ! $track_id && ! $album_id && ! $library ) return;

        // Tell WordPress this is a valid singular page so get_header() works
        global $wp_query;
        $wp_query->is_singular = true;
        $wp_query->is_404      = false;
        status_header( 200 );

        // ── Single Track ──────────────────────────────────────────────────────
        if ( $track_id ) {
            $slite_track = Sproz_DB::get_track( (int) $track_id );
            if ( ! $slite_track || $slite_track->status !== 'publish' ) {
                $wp_query->set_404(); status_header( 404 );
                include get_query_template( '404' );
                exit;
            }
            include SPROZ_PATH . 'templates/view-track.php';
            exit;
        }

        // ── Album ─────────────────────────────────────────────────────────────
        if ( $album_id ) {
            $slite_album = Sproz_DB::get_album( (int) $album_id );
            if ( ! $slite_album || $slite_album->status !== 'publish' ) {
                $wp_query->set_404(); status_header( 404 );
                include get_query_template( '404' );
                exit;
            }
            $track_ids    = Sproz_DB::get_album_track_ids( (int) $album_id );
            // If pivot table empty, fall back to direct album_id query
            if ( empty( $track_ids ) ) {
                $track_ids = array_column( Sproz_DB::get_tracks( [ 'album_id' => (int) $album_id, 'limit' => 500 ] ), 'id' );
            }
            $sproz_tracks = array_values( array_filter(
                array_map( fn($id) => Sproz_DB::get_track( (int) $id ), $track_ids )
            ) );
            include SPROZ_PATH . 'templates/view-album.php';
            exit;
        }

        // ── Music Library ─────────────────────────────────────────────────────
        if ( $library ) {
            $slite_view   = sanitize_key( $_GET['view'] ?? 'albums' );
            $slite_slug   = sanitize_text_field( $_GET['slug'] ?? '' );
            $sproz_albums = $sproz_tracks = $sproz_genres = $slite_term = [];

            if ( $slite_view === 'albums' ) {
                $sproz_albums = Sproz_DB::get_albums();
            } elseif ( $slite_view === 'tracks' ) {
                $sproz_tracks = Sproz_DB::get_tracks( [ 'limit' => 200, 'orderby' => 'created_at', 'order' => 'DESC' ] );
            } elseif ( $slite_view === 'genres' ) {
                $sproz_genres = Sproz_DB::get_genres();
            } elseif ( $slite_view === 'genre' && $slite_slug ) {
                $slite_term   = Sproz_DB::get_genre_by_slug( $slite_slug );
                $sproz_tracks = Sproz_DB::get_tracks( [ 'genre_slug' => $slite_slug, 'limit' => 200 ] );
                $slite_view   = 'tracks';
            } elseif ( $slite_view === 'category' && $slite_slug ) {
                $sproz_tracks = Sproz_DB::get_tracks( [ 'cat_slug' => $slite_slug, 'limit' => 200 ] );
                $slite_view   = 'tracks';
            } elseif ( $slite_view === 'tag' && $slite_slug ) {
                $sproz_tracks = Sproz_DB::get_tracks( [ 'tag_slug' => $slite_slug, 'limit' => 200 ] );
                $slite_view   = 'tracks';
            }

            include SPROZ_PATH . 'templates/view-library.php';
            exit;
        }
    }
}

Sproz_Routes::init();
