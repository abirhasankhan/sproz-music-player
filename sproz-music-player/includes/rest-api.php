<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

    register_rest_route( 'sproz-music-player/v1', '/tracks', [
        'methods'             => 'GET',
        'callback'            => 'slite_api_get_tracks',
        'permission_callback' => '__return_true',
        'args' => [
            'album'    => [ 'type' => 'integer', 'default' => 0  ],
            'genre'    => [ 'type' => 'string',  'default' => '' ],
            'category' => [ 'type' => 'string',  'default' => '' ],
            'tag'      => [ 'type' => 'string',  'default' => '' ],
            'limit'    => [ 'type' => 'integer', 'default' => 50 ],
        ],
    ] );

    register_rest_route( 'sproz-music-player/v1', '/albums', [
        'methods'             => 'GET',
        'callback'            => 'slite_api_get_albums',
        'permission_callback' => '__return_true',
    ] );

    // Play count endpoint
    register_rest_route( 'sproz-music-player/v1', '/play/(?P<id>\d+)', [
        'methods'             => 'POST',
        'callback'            => function( $req ) {
            Sproz_DB::increment_play_count( (int) $req['id'] );
            return [ 'ok' => true ];
        },
        'permission_callback' => '__return_true',
    ] );
} );

function slite_api_get_tracks( WP_REST_Request $req ): WP_REST_Response {
    $args = [
        'album_id'   => $req->get_param('album'),
        'genre_slug' => $req->get_param('genre'),
        'cat_slug'   => $req->get_param('category'),
        'tag_slug'   => $req->get_param('tag'),
        'limit'      => $req->get_param('limit'),
    ];
    $rows = Sproz_DB::get_tracks( $args );
    $data = array_map( 'sproz_build_track_data', $rows );
    return rest_ensure_response( $data );
}

function slite_api_get_albums( WP_REST_Request $req ): WP_REST_Response {
    $albums = Sproz_DB::get_albums();
    $data   = [];
    foreach ( $albums as $a ) {
        $data[] = [
            'id'     => $a->id,
            'title'  => $a->title,
            'artist' => $a->artist,
            'art'    => $a->art_url,
            'skin'   => $a->player_skin,
        ];
    }
    return rest_ensure_response( $data );
}
