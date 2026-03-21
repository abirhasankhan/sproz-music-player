<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'sproz_player', function ( $atts ) {
    $atts = shortcode_atts( [
        'album'    => '',
        'track'    => '',
        'genre'    => '',
        'category' => '',
        'tag'      => '',
        'skin'     => '',
        'limit'    => 50,
    ], $atts, 'sproz_player' );

    $tracks = []; $title = ''; $artist = ''; $art = '';
    $skin   = sanitize_text_field( $atts['skin'] );

    if ( $atts['track'] ) {
        $row = Sproz_DB::get_track( (int) $atts['track'] );
        if ( ! $row ) return '';
        $tracks = [ sproz_build_track_data( $row ) ];
        $title  = $row->title; $art = $row->art_url;
        if ( ! $skin ) $skin = 'dark';

    } elseif ( $atts['album'] ) {
        $album = Sproz_DB::get_album( (int) $atts['album'] );
        if ( ! $album ) return '';

        // Use get_album_track_ids (pivot table) for reliable ordering,
        // falling back to direct album_id query if pivot is empty.
        $track_ids = Sproz_DB::get_album_track_ids( (int) $atts['album'] );
        if ( ! empty( $track_ids ) ) {
            foreach ( $track_ids as $tid ) {
                $row = Sproz_DB::get_track( (int) $tid );
                if ( $row && $row->status === 'publish' ) $tracks[] = sproz_build_track_data( $row );
            }
        } else {
            // Fallback: query tracks directly by album_id column
            $rows = Sproz_DB::get_tracks( [ 'album_id' => (int) $atts['album'], 'limit' => 500 ] );
            foreach ( $rows as $row ) $tracks[] = sproz_build_track_data( $row );
        }

        $title = $album->title; $artist = $album->artist; $art = $album->art_url;
        if ( ! $skin ) $skin = $album->player_skin ?: 'dark';

    } else {
        $q = [ 'limit' => (int) $atts['limit'] ];
        if ( $atts['genre']    ) { $q['genre_slug'] = sanitize_text_field($atts['genre']);    $term = Sproz_DB::get_genre_by_slug($atts['genre']); $title = $term ? $term->name : ''; }
        elseif ( $atts['category'] ) { $q['cat_slug']   = sanitize_text_field($atts['category']); }
        elseif ( $atts['tag']  )    { $q['tag_slug']    = sanitize_text_field($atts['tag']);   }
        $rows = Sproz_DB::get_tracks( $q );
        foreach ( $rows as $row ) $tracks[] = sproz_build_track_data( $row );
        if ( ! $skin ) $skin = 'dark';
    }

    if ( empty( $tracks ) ) return '<p class="sproz-empty">' . esc_html__( 'No tracks found.', 'sproz-music-player' ) . '</p>';

    $unique_id = 'slite-' . uniqid();
    ob_start();
    include SPROZ_PATH . 'templates/player.php';
    return ob_get_clean();
} );

function sproz_build_track_data( object $row ): array {
    return [
        'id'         => (int) $row->id,
        'title'      => $row->title,
        'artist'     => $row->artist,
        'url'        => $row->audio_url,
        'art'        => $row->art_url ?: '',
        'duration'   => $row->duration,
        'genres'     => $row->genres     ?? [],
        'categories' => $row->categories ?? [],
        'tags'       => $row->tags       ?? [],
    ];
}
