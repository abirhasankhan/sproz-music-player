<?php
/**
 * Plugin Name: Sproz Music Player
 * Plugin URI:  https://abirkhan.is-a.dev//sproz-music-player
 * Description: Lightweight music player with custom DB tables, playlist, waveform, album art, genres, categories, tags, external audio (S3, R2) and public view pages.
 * Version:     2.3.0
 * Author:      Abir Khan
 * License:     GPL2
 * Text Domain: sproz-music-player
 */

if (!defined('ABSPATH'))
    exit;

define('SPROZ_VERSION', '2.3.0');
define('SPROZ_PATH', plugin_dir_path(__FILE__));
define('SPROZ_URL', plugin_dir_url(__FILE__));

// ── Core includes ─────────────────────────────────────────────────────────────
require_once SPROZ_PATH . 'includes/database.php';
require_once SPROZ_PATH . 'includes/db.php';
require_once SPROZ_PATH . 'includes/shortcode.php';
require_once SPROZ_PATH . 'includes/widget.php';
require_once SPROZ_PATH . 'includes/rest-api.php';
require_once SPROZ_PATH . 'includes/admin.php';
require_once SPROZ_PATH . 'includes/routes.php';    // public view pages

// ── Enqueue front-end assets ──────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function () {

    wp_enqueue_style('sproz-player', SPROZ_URL . 'assets/css/player.css', [], SPROZ_VERSION);
    wp_enqueue_style('sproz-sticky-bar', SPROZ_URL . 'assets/css/sticky-bar.css', ['sproz-player'], SPROZ_VERSION);
    wp_enqueue_style('sproz-views', SPROZ_URL . 'assets/css/views.css', ['sproz-player'], SPROZ_VERSION);

    wp_enqueue_script('wavesurfer', 'https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/7.8.7/wavesurfer.min.js', [], '7.8.7', true);
    wp_enqueue_script('sproz-sticky-bar', SPROZ_URL . 'assets/js/sticky-bar.js', ['wavesurfer'], SPROZ_VERSION, true);
    wp_enqueue_script('sproz-player', SPROZ_URL . 'assets/js/player.js', ['sproz-sticky-bar'], SPROZ_VERSION, true);

    wp_localize_script('sproz-sticky-bar', 'sprozAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sproz_nonce'),
        'restUrl' => rest_url('sproz-music-player/v1/'),
        'siteUrl' => home_url(),
    ]);
});

// ── Inject sticky bar into footer ─────────────────────────────────────────────
add_action('wp_footer', function () {
    echo '<div id="sproz-global-wave" style="position:fixed;bottom:-200px;left:0;width:200px;height:20px;overflow:hidden;opacity:0;pointer-events:none;visibility:hidden;" aria-hidden="true"></div>';
    include SPROZ_PATH . 'templates/sticky-bar.php';
});

// AJAX navigation handled purely in JS (sticky-bar.js)


// ── Enqueue admin assets ──────────────────────────────────────────────────────
add_action('admin_enqueue_scripts', function ($hook) {
    $slite_pages = [
        'music-player_page_slite-tracks',
        'music-player_page_slite-albums',
        'music-player_page_slite-genres',
        'music-player_page_slite-categories',
        'music-player_page_slite-tags',
        'toplevel_page_slite-tracks'
    ];
    if (!in_array($hook, $slite_pages))
        return;
    wp_enqueue_style('sproz-admin', SPROZ_URL . 'assets/css/admin.css', [], SPROZ_VERSION);
    wp_enqueue_script(
        'sproz-admin',
        SPROZ_URL . 'assets/js/admin.js',
        ['jquery', 'media-upload', 'jquery-ui-sortable'],
        SPROZ_VERSION,
        true
    );
});

// ── Activation ────────────────────────────────────────────────────────────────
register_activation_hook(__FILE__, 'slite_activate');
function slite_activate()
{
    require_once SPROZ_PATH . 'includes/database.php';
    require_once SPROZ_PATH . 'includes/routes.php';
    sproz_create_tables();
    Sproz_Routes::add_rewrite_rules();
    flush_rewrite_rules();
    do_action('slite_activated');
}

// ── Deactivation ──────────────────────────────────────────────────────────────
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

// ── Uninstall ─────────────────────────────────────────────────────────────────
register_uninstall_hook(__FILE__, 'sproz_uninstall');
function sproz_uninstall()
{
    if (get_option('slite_delete_on_uninstall')) {
        require_once plugin_dir_path(__FILE__) . 'includes/database.php';
        sproz_drop_tables();
    }
}

// ── DB upgrade check ──────────────────────────────────────────────────────────
add_action('admin_init', function () {
    if (get_option('sproz_db_version') !== SPROZ_DB_VERSION) {
        sproz_create_tables();
    }
});
