<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Sproz Music Player — Playlist template
 * Inspired by modern streaming UI: hero header + full tracklist table
 */

$first       = $tracks[0] ?? null;
$cover_img   = $art    ?: ( $first ? $first['art']   : '' );
$cover_title = $title  ?: ( $first ? $first['title'] : '' );
$cover_sub   = $artist ?: ( $first ? $first['artist']: '' );
$track_count = count( $tracks );

// Total duration
$total_secs = 0;
foreach ( $tracks as $t ) {
    if ( ! empty( $t['duration'] ) ) {
        $parts = explode( ':', trim($t['duration']) );
        if ( count($parts) === 2 ) $total_secs += (int)$parts[0]*60 + (int)$parts[1];
    }
}
$total_dur = '';
if ( $total_secs > 0 ) {
    $h = floor( $total_secs / 3600 );
    $m = floor( ($total_secs % 3600) / 60 );
    if ( $h > 0 ) $total_dur = $h . ' hr ' . $m . ' min';
    else $total_dur = $m . ' min';
}
?>

<div id="<?php echo esc_attr($unique_id); ?>"
     class="sproz-player sproz-skin-<?php echo esc_attr($skin); ?>"
     data-tracks="<?php echo esc_attr( json_encode( $tracks ) ); ?>">

    <!-- ── Hero Section ──────────────────────────────────────────────── -->
    <div class="sproz-hero">
        <div class="sproz-hero-cover">
            <?php if ( $cover_img ) : ?>
                <img src="<?php echo esc_url($cover_img); ?>" alt="<?php echo esc_attr($cover_title); ?>" />
            <?php else : ?>
                <div class="sproz-hero-cover-placeholder">
                    <svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                </div>
            <?php endif; ?>
        </div>

        <div class="sproz-hero-info">
            <div class="sproz-hero-label"><?php echo $track_count > 1 ? esc_html__('PLAYLIST','sproz-music-player') : esc_html__('SINGLE','sproz-music-player'); ?></div>
            <h2 class="sproz-hero-title"><?php echo esc_html( $cover_title ); ?></h2>
            <?php if ( $cover_sub ) : ?>
            <div class="sproz-hero-artist"><?php echo esc_html( $cover_sub ); ?></div>
            <?php endif; ?>
            <div class="sproz-hero-meta">
                <?php if ( $track_count > 0 ) : ?>
                <span><?php echo $track_count; ?> <?php echo $track_count === 1 ? esc_html__('Song','sproz-music-player') : esc_html__('Songs','sproz-music-player'); ?></span>
                <?php endif; ?>
                <?php if ( $total_dur ) : ?>
                <span class="sproz-meta-dot">·</span>
                <span><?php echo esc_html($total_dur); ?></span>
                <?php endif; ?>
            </div>
            <div class="sproz-hero-actions">
                <button class="sproz-btn-play-all sproz-btn spz-btn-play" title="<?php esc_attr_e('Play','sproz-music-player'); ?>">
                    <svg class="sproz-icon-play" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    <svg class="sproz-icon-pause" viewBox="0 0 24 24" style="display:none"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                    <span class="sproz-play-label"><?php esc_html_e('Play Now','sproz-music-player'); ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Track Table ───────────────────────────────────────────────── -->
    <div class="sproz-table-wrap">
        <!-- Header -->
        <div class="sproz-table-head">
            <span class="sproz-col-num">#</span>
            <span class="sproz-col-track"><?php esc_html_e('TRACK','sproz-music-player'); ?></span>
            <span class="sproz-col-album"><?php esc_html_e('ALBUM','sproz-music-player'); ?></span>
            <span class="sproz-col-dur">
                <svg viewBox="0 0 24 24" width="14" height="14"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
        </div>

        <!-- Rows -->
        <div class="sproz-table-body">
            <?php foreach ( $tracks as $i => $track ) : ?>
            <div class="sproz-row sproz-playlist-item sproz-playlist-item sproz-v2-row"
                 data-index="<?php echo $i; ?>"
                 data-trackid="<?php echo (int)$track['id']; ?>">

                <!-- Col: Number / state indicator -->
                <div class="sproz-col-num sproz-row-state">
                    <span class="sproz-track-num"><?php echo $i + 1; ?></span>
                    <svg class="sproz-row-play-icon" viewBox="0 0 24 24" style="display:none"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    <span class="sproz-v2-playing-bars sproz-bars" style="display:none">
                        <span class="sproz-v2-bar sproz-bar" style="display:none"></span>
                        <span class="sproz-v2-bar sproz-bar" style="display:none"></span>
                        <span class="sproz-v2-bar sproz-bar" style="display:none"></span>
                    </span>
                </div>

                <!-- Col: Track info -->
                <div class="sproz-col-track sproz-row-info">
                    <?php if ( $track['art'] ) : ?>
                    <img class="sproz-row-art" src="<?php echo esc_url($track['art']); ?>" alt="" />
                    <?php else : ?>
                    <div class="sproz-row-art sproz-row-art-empty">
                        <svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                    </div>
                    <?php endif; ?>
                    <div class="sproz-row-text">
                        <span class="sproz-row-title"><?php echo esc_html($track['title']); ?></span>
                        <?php if ( $track['artist'] ) : ?>
                        <span class="sproz-row-artist"><?php echo esc_html($track['artist']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Col: Album name (genres as fallback) -->
                <div class="sproz-col-album sproz-row-album">
                    <?php
                    $album_label = '';
                    if ( ! empty($track['genres']) ) $album_label = implode(', ', $track['genres']);
                    echo esc_html($album_label);
                    ?>
                </div>

                <!-- Col: Duration -->
                <div class="sproz-col-dur sproz-row-dur">
                    <?php echo esc_html($track['duration']); ?>
                </div>

            </div>


            <?php endforeach; ?>
        </div>
    </div>

</div>
