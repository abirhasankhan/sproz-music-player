<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Custom Tables:
 *
 *  {prefix}sproz_tracks          — individual songs
 *  {prefix}sproz_albums          — albums / playlists
 *  {prefix}sproz_album_tracks    — pivot: album ↔ track (ordered)
 *  {prefix}sproz_genres          — genres taxonomy
 *  {prefix}sproz_categories      — music categories taxonomy
 *  {prefix}sproz_tags            — music tags taxonomy
 *  {prefix}sproz_track_genres    — pivot: track ↔ genre
 *  {prefix}sproz_track_categories— pivot: track ↔ category
 *  {prefix}sproz_track_tags      — pivot: track ↔ tag
 */

define( 'SPROZ_DB_VERSION', '1.0.0' );

function sproz_create_tables() {
    global $wpdb;
    $c  = $wpdb->get_charset_collate();
    $p  = $wpdb->prefix;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // ── Tracks ────────────────────────────────────────────────────────────────
    dbDelta( "CREATE TABLE {$p}sproz_tracks (
        id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        title         VARCHAR(255)    NOT NULL DEFAULT '',
        artist        VARCHAR(255)    NOT NULL DEFAULT '',
        audio_url     TEXT            NOT NULL,
        audio_type    VARCHAR(20)     NOT NULL DEFAULT 'external',
        duration      VARCHAR(20)     NOT NULL DEFAULT '',
        track_number  SMALLINT        NOT NULL DEFAULT 0,
        album_id      BIGINT UNSIGNED NOT NULL DEFAULT 0,
        art_url       TEXT            NOT NULL DEFAULT '',
        description   TEXT            NOT NULL DEFAULT '',
        play_count    BIGINT UNSIGNED NOT NULL DEFAULT 0,
        status        VARCHAR(20)     NOT NULL DEFAULT 'publish',
        created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY album_id (album_id),
        KEY status (status),
        KEY artist (artist(100)),
        FULLTEXT KEY search_idx (title, artist)
    ) $c;" );

    // ── Albums ────────────────────────────────────────────────────────────────
    dbDelta( "CREATE TABLE {$p}sproz_albums (
        id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        title        VARCHAR(255)    NOT NULL DEFAULT '',
        artist       VARCHAR(255)    NOT NULL DEFAULT '',
        description  TEXT            NOT NULL DEFAULT '',
        art_url      TEXT            NOT NULL DEFAULT '',
        release_year SMALLINT        NOT NULL DEFAULT 0,
        record_label VARCHAR(255)    NOT NULL DEFAULT '',
        player_skin  VARCHAR(20)     NOT NULL DEFAULT 'dark',
        status       VARCHAR(20)     NOT NULL DEFAULT 'publish',
        created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY status (status)
    ) $c;" );

    // ── Album ↔ Track pivot (ordered) ─────────────────────────────────────────
    dbDelta( "CREATE TABLE {$p}sproz_album_tracks (
        id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        album_id   BIGINT UNSIGNED NOT NULL,
        track_id   BIGINT UNSIGNED NOT NULL,
        sort_order SMALLINT        NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY album_track (album_id, track_id),
        KEY album_id (album_id),
        KEY track_id (track_id)
    ) $c;" );

    // ── Genres ────────────────────────────────────────────────────────────────
    dbDelta( "CREATE TABLE {$p}sproz_genres (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name        VARCHAR(200)    NOT NULL DEFAULT '',
        slug        VARCHAR(200)    NOT NULL DEFAULT '',
        description TEXT            NOT NULL DEFAULT '',
        parent_id   BIGINT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) $c;" );

    // ── Music Categories ──────────────────────────────────────────────────────
    dbDelta( "CREATE TABLE {$p}sproz_categories (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name        VARCHAR(200)    NOT NULL DEFAULT '',
        slug        VARCHAR(200)    NOT NULL DEFAULT '',
        description TEXT            NOT NULL DEFAULT '',
        parent_id   BIGINT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) $c;" );

    // ── Music Tags ────────────────────────────────────────────────────────────
    dbDelta( "CREATE TABLE {$p}sproz_tags (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name        VARCHAR(200)    NOT NULL DEFAULT '',
        slug        VARCHAR(200)    NOT NULL DEFAULT '',
        description TEXT            NOT NULL DEFAULT '',
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) $c;" );

    // ── Track ↔ Genre pivot ───────────────────────────────────────────────────
    dbDelta( "CREATE TABLE {$p}sproz_track_genres (
        track_id BIGINT UNSIGNED NOT NULL,
        genre_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (track_id, genre_id),
        KEY genre_id (genre_id)
    ) $c;" );

    // ── Track ↔ Category pivot ────────────────────────────────────────────────
    dbDelta( "CREATE TABLE {$p}sproz_track_categories (
        track_id    BIGINT UNSIGNED NOT NULL,
        category_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (track_id, category_id),
        KEY category_id (category_id)
    ) $c;" );

    // ── Track ↔ Tag pivot ─────────────────────────────────────────────────────
    dbDelta( "CREATE TABLE {$p}sproz_track_tags (
        track_id BIGINT UNSIGNED NOT NULL,
        tag_id   BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (track_id, tag_id),
        KEY tag_id (tag_id)
    ) $c;" );

    update_option( 'sproz_db_version', SPROZ_DB_VERSION );
}

function sproz_drop_tables() {
    global $wpdb;
    $p = $wpdb->prefix;
    $tables = [
        'sproz_track_tags', 'sproz_track_categories', 'sproz_track_genres',
        'sproz_tags', 'sproz_categories', 'sproz_genres',
        'sproz_album_tracks', 'sproz_albums', 'sproz_tracks',
    ];
    foreach ( $tables as $t ) {
        $wpdb->query( "DROP TABLE IF EXISTS {$p}{$t}" );
    }
    delete_option( 'sproz_db_version' );
}
