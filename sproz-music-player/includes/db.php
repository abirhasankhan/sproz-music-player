<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sproz_DB — static helper for all custom table queries.
 * Every public method sanitises its own inputs and uses $wpdb->prepare().
 */
class Sproz_DB {

    /* ══════════════════════════════════════════════════════════════════════════
       TRACKS
    ══════════════════════════════════════════════════════════════════════════ */

    public static function insert_track( array $data ): int {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'sproz_tracks',
            self::sanitize_track( $data ),
            self::track_formats()
        );
        return (int) $wpdb->insert_id;
    }

    public static function update_track( int $id, array $data ): bool {
        global $wpdb;
        return (bool) $wpdb->update(
            $wpdb->prefix . 'sproz_tracks',
            self::sanitize_track( $data ),
            [ 'id' => $id ],
            self::track_formats(),
            [ '%d' ]
        );
    }

    public static function delete_track( int $id ): void {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}sproz_tracks",           [ 'id'       => $id ], [ '%d' ] );
        $wpdb->delete( "{$p}sproz_album_tracks",     [ 'track_id' => $id ], [ '%d' ] );
        $wpdb->delete( "{$p}sproz_track_genres",     [ 'track_id' => $id ], [ '%d' ] );
        $wpdb->delete( "{$p}sproz_track_categories", [ 'track_id' => $id ], [ '%d' ] );
        $wpdb->delete( "{$p}sproz_track_tags",       [ 'track_id' => $id ], [ '%d' ] );
    }

    public static function get_track( int $id ): ?object {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sproz_tracks WHERE id = %d LIMIT 1", $id )
        );
        return $row ? self::hydrate_track( $row ) : null;
    }

    /**
     * Get tracks with optional filters.
     *
     * @param array $args {
     *   album_id   int
     *   genre_slug string
     *   cat_slug   string
     *   tag_slug   string
     *   search     string
     *   status     string   default 'publish'
     *   orderby    string   title|artist|track_number|created_at|play_count
     *   order      string   ASC|DESC
     *   limit      int
     *   offset     int
     * }
     */
    public static function get_tracks( array $args = [] ): array {
        global $wpdb;
        $p = $wpdb->prefix;

        $defaults = [
            'album_id'   => 0,
            'genre_slug' => '',
            'cat_slug'   => '',
            'tag_slug'   => '',
            'search'     => '',
            'status'     => 'publish',
            'orderby'    => 'track_number',
            'order'      => 'ASC',
            'limit'      => 100,
            'offset'     => 0,
        ];
        $args = wp_parse_args( $args, $defaults );

        $joins  = '';
        $wheres = [ "t.status = %s" ];
        $params = [ $args['status'] ];

        // ── Album filter (ordered by sort_order) ──────────────────────────────
        if ( $args['album_id'] ) {
            $joins   .= " INNER JOIN {$p}sproz_album_tracks albt ON albt.track_id = t.id AND albt.album_id = %d";
            $params[] = (int) $args['album_id'];
            $args['orderby'] = 'albt.sort_order';
            $args['order']   = 'ASC';
        }

        // ── Genre filter ──────────────────────────────────────────────────────
        if ( $args['genre_slug'] ) {
            $joins   .= " INNER JOIN {$p}sproz_track_genres tg ON tg.track_id = t.id"
                      . " INNER JOIN {$p}sproz_genres g ON g.id = tg.genre_id AND g.slug = %s";
            $params[] = $args['genre_slug'];
        }

        // ── Category filter ───────────────────────────────────────────────────
        if ( $args['cat_slug'] ) {
            $joins   .= " INNER JOIN {$p}sproz_track_categories tc ON tc.track_id = t.id"
                      . " INNER JOIN {$p}sproz_categories c ON c.id = tc.category_id AND c.slug = %s";
            $params[] = $args['cat_slug'];
        }

        // ── Tag filter ────────────────────────────────────────────────────────
        if ( $args['tag_slug'] ) {
            $joins   .= " INNER JOIN {$p}sproz_track_tags tt ON tt.track_id = t.id"
                      . " INNER JOIN {$p}sproz_tags tg2 ON tg2.id = tt.tag_id AND tg2.slug = %s";
            $params[] = $args['tag_slug'];
        }

        // ── Full-text search ──────────────────────────────────────────────────
        if ( $args['search'] ) {
            $wheres[] = "MATCH(t.title, t.artist) AGAINST (%s IN BOOLEAN MODE)";
            $params[] = $args['search'] . '*';
        }

        $allowed_orderby = [ 'title', 'artist', 'track_number', 'created_at', 'play_count', 'at.sort_order' ];
        $orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'track_number';
        $order   = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
        $limit   = max( 1, min( 500, (int) $args['limit'] ) );
        $offset  = max( 0, (int) $args['offset'] );

        $where_sql = 'WHERE ' . implode( ' AND ', $wheres );

        // Build final params for prepare (limit + offset appended last)
        $params[] = $limit;
        $params[] = $offset;

        $sql = "SELECT DISTINCT t.* FROM {$p}sproz_tracks t
                {$joins}
                {$where_sql}
                ORDER BY {$orderby} {$order}
                LIMIT %d OFFSET %d";

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
        return array_map( [ __CLASS__, 'hydrate_track' ], $rows ?: [] );
    }

    public static function count_tracks( array $args = [] ): int {
        global $wpdb;
        $args['limit']  = 1;
        $args['offset'] = 0;
        $p = $wpdb->prefix;
        $status = $args['status'] ?? 'publish';
        return (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$p}sproz_tracks WHERE status = %s", $status )
        );
    }

    public static function increment_play_count( int $id ): void {
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}sproz_tracks SET play_count = play_count + 1 WHERE id = %d",
            $id
        ) );
    }

    /* ── Track taxonomy setters ──────────────────────────────────────────────── */

    public static function set_track_genres( int $track_id, array $genre_ids ): void {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}sproz_track_genres", [ 'track_id' => $track_id ], [ '%d' ] );
        foreach ( array_unique( array_map( 'intval', $genre_ids ) ) as $gid ) {
            $wpdb->replace( "{$p}sproz_track_genres", [ 'track_id' => $track_id, 'genre_id' => $gid ], [ '%d', '%d' ] );
        }
    }

    public static function set_track_categories( int $track_id, array $cat_ids ): void {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}sproz_track_categories", [ 'track_id' => $track_id ], [ '%d' ] );
        foreach ( array_unique( array_map( 'intval', $cat_ids ) ) as $cid ) {
            $wpdb->replace( "{$p}sproz_track_categories", [ 'track_id' => $track_id, 'category_id' => $cid ], [ '%d', '%d' ] );
        }
    }

    public static function set_track_tags( int $track_id, array $tag_ids ): void {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}sproz_track_tags", [ 'track_id' => $track_id ], [ '%d' ] );
        foreach ( array_unique( array_map( 'intval', $tag_ids ) ) as $tid ) {
            $wpdb->replace( "{$p}sproz_track_tags", [ 'track_id' => $track_id, 'tag_id' => $tid ], [ '%d', '%d' ] );
        }
    }

    /* ── Track taxonomy getters ──────────────────────────────────────────────── */

    public static function get_track_genres( int $track_id ): array {
        global $wpdb;
        $p = $wpdb->prefix;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT g.* FROM {$p}sproz_genres g
             INNER JOIN {$p}sproz_track_genres tg ON tg.genre_id = g.id
             WHERE tg.track_id = %d ORDER BY g.name", $track_id
        ) ) ?: [];
    }

    public static function get_track_categories( int $track_id ): array {
        global $wpdb;
        $p = $wpdb->prefix;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT c.* FROM {$p}sproz_categories c
             INNER JOIN {$p}sproz_track_categories tc ON tc.category_id = c.id
             WHERE tc.track_id = %d ORDER BY c.name", $track_id
        ) ) ?: [];
    }

    public static function get_track_tags( int $track_id ): array {
        global $wpdb;
        $p = $wpdb->prefix;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT t.* FROM {$p}sproz_tags t
             INNER JOIN {$p}sproz_track_tags tt ON tt.tag_id = t.id
             WHERE tt.track_id = %d ORDER BY t.name", $track_id
        ) ) ?: [];
    }

    /* ══════════════════════════════════════════════════════════════════════════
       ALBUMS
    ══════════════════════════════════════════════════════════════════════════ */

    public static function insert_album( array $data ): int {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'sproz_albums',
            self::sanitize_album( $data ),
            self::album_formats()
        );
        return (int) $wpdb->insert_id;
    }

    public static function update_album( int $id, array $data ): bool {
        global $wpdb;
        return (bool) $wpdb->update(
            $wpdb->prefix . 'sproz_albums',
            self::sanitize_album( $data ),
            [ 'id' => $id ],
            self::album_formats(),
            [ '%d' ]
        );
    }

    public static function delete_album( int $id ): void {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}sproz_albums",       [ 'id'       => $id ], [ '%d' ] );
        $wpdb->delete( "{$p}sproz_album_tracks", [ 'album_id' => $id ], [ '%d' ] );
    }

    public static function get_album( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sproz_albums WHERE id = %d LIMIT 1", $id )
        ) ?: null;
    }

    public static function get_albums( string $status = 'publish', int $limit = 200, int $offset = 0 ): array {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sproz_albums WHERE status = %s ORDER BY title ASC LIMIT %d OFFSET %d",
            $status, $limit, $offset
        ) ) ?: [];
    }

    /* ── Album track order ───────────────────────────────────────────────────── */

    public static function set_album_tracks( int $album_id, array $track_ids ): void {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}sproz_album_tracks", [ 'album_id' => $album_id ], [ '%d' ] );
        foreach ( $track_ids as $order => $track_id ) {
            $wpdb->insert( "{$p}sproz_album_tracks", [
                'album_id'   => $album_id,
                'track_id'   => (int) $track_id,
                'sort_order' => (int) $order,
            ], [ '%d', '%d', '%d' ] );
        }
    }

    public static function get_album_track_ids( int $album_id ): array {
        global $wpdb;
        return $wpdb->get_col( $wpdb->prepare(
            "SELECT track_id FROM {$wpdb->prefix}sproz_album_tracks
             WHERE album_id = %d ORDER BY sort_order ASC",
            $album_id
        ) ) ?: [];
    }

    /* ══════════════════════════════════════════════════════════════════════════
       GENRES
    ══════════════════════════════════════════════════════════════════════════ */

    public static function get_genres(): array {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sproz_genres ORDER BY name ASC" ) ?: [];
    }

    public static function get_genre_by_slug( string $slug ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sproz_genres WHERE slug = %s LIMIT 1", $slug
        ) ) ?: null;
    }

    public static function insert_genre( string $name, string $slug = '', string $desc = '', int $parent = 0 ): int {
        global $wpdb;
        if ( ! $slug ) $slug = sanitize_title( $name );
        $wpdb->insert( $wpdb->prefix . 'sproz_genres',
            [ 'name' => $name, 'slug' => $slug, 'description' => $desc, 'parent_id' => $parent ],
            [ '%s', '%s', '%s', '%d' ]
        );
        return (int) $wpdb->insert_id;
    }

    public static function update_genre( int $id, string $name, string $slug = '', string $desc = '', int $parent = 0 ): void {
        global $wpdb;
        if ( ! $slug ) $slug = sanitize_title( $name );
        $wpdb->update( $wpdb->prefix . 'sproz_genres',
            [ 'name' => $name, 'slug' => $slug, 'description' => $desc, 'parent_id' => $parent ],
            [ 'id' => $id ], [ '%s', '%s', '%s', '%d' ], [ '%d' ]
        );
    }

    public static function delete_genre( int $id ): void {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}sproz_genres",       [ 'id'       => $id ], [ '%d' ] );
        $wpdb->delete( "{$p}sproz_track_genres", [ 'genre_id' => $id ], [ '%d' ] );
    }

    /* ══════════════════════════════════════════════════════════════════════════
       CATEGORIES
    ══════════════════════════════════════════════════════════════════════════ */

    public static function get_categories(): array {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sproz_categories ORDER BY name ASC" ) ?: [];
    }

    public static function insert_category( string $name, string $slug = '', string $desc = '', int $parent = 0 ): int {
        global $wpdb;
        if ( ! $slug ) $slug = sanitize_title( $name );
        $wpdb->insert( $wpdb->prefix . 'sproz_categories',
            [ 'name' => $name, 'slug' => $slug, 'description' => $desc, 'parent_id' => $parent ],
            [ '%s', '%s', '%s', '%d' ]
        );
        return (int) $wpdb->insert_id;
    }

    public static function update_category( int $id, string $name, string $slug = '', string $desc = '', int $parent = 0 ): void {
        global $wpdb;
        if ( ! $slug ) $slug = sanitize_title( $name );
        $wpdb->update( $wpdb->prefix . 'sproz_categories',
            [ 'name' => $name, 'slug' => $slug, 'description' => $desc, 'parent_id' => $parent ],
            [ 'id' => $id ], [ '%s', '%s', '%s', '%d' ], [ '%d' ]
        );
    }

    public static function delete_category( int $id ): void {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}sproz_categories",       [ 'id'          => $id ], [ '%d' ] );
        $wpdb->delete( "{$p}sproz_track_categories", [ 'category_id' => $id ], [ '%d' ] );
    }

    /* ══════════════════════════════════════════════════════════════════════════
       TAGS
    ══════════════════════════════════════════════════════════════════════════ */

    public static function get_tags(): array {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sproz_tags ORDER BY name ASC" ) ?: [];
    }

    public static function insert_tag( string $name, string $slug = '', string $desc = '' ): int {
        global $wpdb;
        if ( ! $slug ) $slug = sanitize_title( $name );
        $wpdb->insert( $wpdb->prefix . 'sproz_tags',
            [ 'name' => $name, 'slug' => $slug, 'description' => $desc ],
            [ '%s', '%s', '%s' ]
        );
        return (int) $wpdb->insert_id;
    }

    public static function update_tag( int $id, string $name, string $slug = '', string $desc = '' ): void {
        global $wpdb;
        if ( ! $slug ) $slug = sanitize_title( $name );
        $wpdb->update( $wpdb->prefix . 'sproz_tags',
            [ 'name' => $name, 'slug' => $slug, 'description' => $desc ],
            [ 'id' => $id ], [ '%s', '%s', '%s' ], [ '%d' ]
        );
    }

    public static function delete_tag( int $id ): void {
        global $wpdb;
        $p = $wpdb->prefix;
        $wpdb->delete( "{$p}sproz_tags",       [ 'id'     => $id ], [ '%d' ] );
        $wpdb->delete( "{$p}sproz_track_tags", [ 'tag_id' => $id ], [ '%d' ] );
    }

    /* ══════════════════════════════════════════════════════════════════════════
       INTERNAL HELPERS
    ══════════════════════════════════════════════════════════════════════════ */

    private static function sanitize_track( array $d ): array {
        return [
            'title'        => sanitize_text_field( $d['title']        ?? '' ),
            'artist'       => sanitize_text_field( $d['artist']       ?? '' ),
            'audio_url'    => esc_url_raw(         $d['audio_url']    ?? '' ),
            'audio_type'   => sanitize_text_field( $d['audio_type']   ?? 'external' ),
            'duration'     => sanitize_text_field( $d['duration']     ?? '' ),
            'track_number' => (int)(                $d['track_number'] ?? 0 ),
            'album_id'     => (int)(                $d['album_id']    ?? 0 ),
            'art_url'      => esc_url_raw(         $d['art_url']     ?? '' ),
            'description'  => wp_kses_post(        $d['description'] ?? '' ),
            'status'       => in_array( $d['status'] ?? '', [ 'publish', 'draft', 'trash' ] )
                              ? $d['status'] : 'publish',
        ];
    }

    private static function track_formats(): array {
        return [ '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' ];
    }

    private static function sanitize_album( array $d ): array {
        return [
            'title'        => sanitize_text_field( $d['title']        ?? '' ),
            'artist'       => sanitize_text_field( $d['artist']       ?? '' ),
            'description'  => wp_kses_post(        $d['description']  ?? '' ),
            'art_url'      => esc_url_raw(         $d['art_url']      ?? '' ),
            'release_year' => (int)(                $d['release_year'] ?? 0 ),
            'record_label' => sanitize_text_field( $d['record_label'] ?? '' ),
            'player_skin'  => in_array( $d['player_skin'] ?? '', [ 'dark', 'light' ] )
                              ? $d['player_skin'] : 'dark',
            'status'       => in_array( $d['status'] ?? '', [ 'publish', 'draft', 'trash' ] )
                              ? $d['status'] : 'publish',
        ];
    }

    private static function album_formats(): array {
        return [ '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ];
    }

    /** Attach taxonomy names to a track row */
    private static function hydrate_track( object $row ): object {
        $row->genres     = array_column( self::get_track_genres(     (int) $row->id ), 'name' );
        $row->categories = array_column( self::get_track_categories( (int) $row->id ), 'name' );
        $row->tags       = array_column( self::get_track_tags(       (int) $row->id ), 'name' );
        return $row;
    }
}
