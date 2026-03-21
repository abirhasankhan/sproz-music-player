<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function slite_register_post_types() {

    // ── Track ─────────────────────────────────────────────────────────────────
    register_post_type( 'slite_track', [
        'labels' => [
            'name'               => __( 'Tracks',           'sproz-music-player' ),
            'singular_name'      => __( 'Track',            'sproz-music-player' ),
            'add_new_item'       => __( 'Add New Track',    'sproz-music-player' ),
            'edit_item'          => __( 'Edit Track',       'sproz-music-player' ),
            'new_item'           => __( 'New Track',        'sproz-music-player' ),
            'view_item'          => __( 'View Track',       'sproz-music-player' ),
            'search_items'       => __( 'Search Tracks',    'sproz-music-player' ),
            'not_found'          => __( 'No tracks found',  'sproz-music-player' ),
            'menu_name'          => __( 'Music Player',     'sproz-music-player' ),
        ],
        'public'            => true,
        'has_archive'       => true,
        'supports'          => [ 'title', 'thumbnail', 'excerpt', 'author' ],
        'menu_icon'         => 'dashicons-format-audio',
        'show_in_rest'      => true,
        'rewrite'           => [ 'slug' => 'tracks' ],
    ] );

    // ── Album / Playlist ──────────────────────────────────────────────────────
    register_post_type( 'slite_album', [
        'labels' => [
            'name'               => __( 'Albums / Playlists', 'sproz-music-player' ),
            'singular_name'      => __( 'Album',              'sproz-music-player' ),
            'add_new_item'       => __( 'Add New Album',      'sproz-music-player' ),
            'edit_item'          => __( 'Edit Album',         'sproz-music-player' ),
            'menu_name'          => __( 'Albums',             'sproz-music-player' ),
        ],
        'public'            => true,
        'has_archive'       => true,
        'supports'          => [ 'title', 'thumbnail', 'excerpt' ],
        'menu_icon'         => 'dashicons-album',
        'show_in_rest'      => true,
        'show_in_menu'      => 'edit.php?post_type=slite_track',
        'rewrite'           => [ 'slug' => 'albums' ],
    ] );
}
add_action( 'init', 'slite_register_post_types' );
