<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function slite_register_taxonomies() {

    // ── Genre ─────────────────────────────────────────────────────────────────
    register_taxonomy( 'slite_genre', [ 'slite_track', 'slite_album' ], [
        'labels' => [
            'name'              => __( 'Genres',        'sproz-music-player' ),
            'singular_name'     => __( 'Genre',         'sproz-music-player' ),
            'search_items'      => __( 'Search Genres', 'sproz-music-player' ),
            'all_items'         => __( 'All Genres',    'sproz-music-player' ),
            'edit_item'         => __( 'Edit Genre',    'sproz-music-player' ),
            'add_new_item'      => __( 'Add New Genre', 'sproz-music-player' ),
            'menu_name'         => __( 'Genres',        'sproz-music-player' ),
        ],
        'hierarchical'      => true,   // like categories
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'genre' ],
    ] );

    // ── Music Category ────────────────────────────────────────────────────────
    register_taxonomy( 'slite_category', [ 'slite_track', 'slite_album' ], [
        'labels' => [
            'name'              => __( 'Music Categories',        'sproz-music-player' ),
            'singular_name'     => __( 'Music Category',         'sproz-music-player' ),
            'search_items'      => __( 'Search Categories',      'sproz-music-player' ),
            'all_items'         => __( 'All Categories',         'sproz-music-player' ),
            'edit_item'         => __( 'Edit Category',          'sproz-music-player' ),
            'add_new_item'      => __( 'Add New Category',       'sproz-music-player' ),
            'menu_name'         => __( 'Music Categories',       'sproz-music-player' ),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'music-category' ],
    ] );

    // ── Music Tag ─────────────────────────────────────────────────────────────
    register_taxonomy( 'slite_tag', [ 'slite_track', 'slite_album' ], [
        'labels' => [
            'name'              => __( 'Music Tags',    'sproz-music-player' ),
            'singular_name'     => __( 'Music Tag',    'sproz-music-player' ),
            'search_items'      => __( 'Search Tags',  'sproz-music-player' ),
            'all_items'         => __( 'All Tags',     'sproz-music-player' ),
            'edit_item'         => __( 'Edit Tag',     'sproz-music-player' ),
            'add_new_item'      => __( 'Add New Tag',  'sproz-music-player' ),
            'menu_name'         => __( 'Music Tags',   'sproz-music-player' ),
        ],
        'hierarchical'      => false,  // like post tags
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'music-tag' ],
    ] );
}
add_action( 'init', 'slite_register_taxonomies' );
