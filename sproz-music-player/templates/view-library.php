<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// Variables set by routes.php: $slite_view, $sproz_albums, $sproz_tracks, $sproz_genres, $slite_term

$view    = $slite_view   ?? 'albums';
$albums  = $sproz_albums ?? [];
$tracks  = $sproz_tracks ?? [];
$genres  = $sproz_genres ?? [];
$term    = $slite_term   ?? null;

add_filter( 'pre_get_document_title', fn() => __('Music Library','sproz-music-player') . ' — ' . get_bloginfo('name') );

get_header();
?>
<div class="sproz-view-wrap slite-library">

    <div class="slite-library-header">
        <h1 class="slite-library-title"><?php echo $term ? esc_html($term->name) : esc_html__('Music Library','sproz-music-player'); ?></h1>
        <nav class="slite-library-tabs">
            <a href="<?php echo esc_url(home_url('/music-library/')); ?>" class="slite-tab <?php echo ($view==='albums'&&!$term)?'active':''; ?>"><?php esc_html_e('Albums','sproz-music-player'); ?></a>
            <a href="<?php echo esc_url(home_url('/music-library/?view=tracks')); ?>" class="slite-tab <?php echo $view==='tracks'?'active':''; ?>"><?php esc_html_e('Tracks','sproz-music-player'); ?></a>
            <a href="<?php echo esc_url(home_url('/music-library/?view=genres')); ?>" class="slite-tab <?php echo $view==='genres'?'active':''; ?>"><?php esc_html_e('Genres','sproz-music-player'); ?></a>
        </nav>
    </div>

    <?php if ( $view === 'albums' ) : ?>
    <div class="slite-album-grid">
        <?php if ( empty($albums) ) : ?><p class="sproz-empty"><?php esc_html_e('No albums yet.','sproz-music-player'); ?></p>
        <?php else : foreach ( $albums as $album ) : $tcount = count(Sproz_DB::get_album_track_ids($album->id)); ?>
            <a href="<?php echo esc_url(home_url('/slite-album/'.$album->id)); ?>" class="slite-album-card">
                <div class="slite-album-card-art">
                    <?php if ($album->art_url) : ?><img src="<?php echo esc_url($album->art_url); ?>" alt="" loading="lazy" /><?php else : ?><div class="slite-album-card-art-placeholder">💿</div><?php endif; ?>
                    <div class="slite-album-card-play"><svg viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg></div>
                </div>
                <div class="slite-album-card-info">
                    <span class="slite-album-card-title"><?php echo esc_html($album->title); ?></span>
                    <span class="slite-album-card-artist"><?php echo esc_html($album->artist); ?></span>
                    <span class="slite-album-card-count"><?php echo $tcount; ?> <?php esc_html_e('tracks','sproz-music-player'); ?></span>
                </div>
            </a>
        <?php endforeach; endif; ?>
    </div>

    <?php elseif ( $view === 'tracks' ) :
        $all_data = array_map('sproz_build_track_data', $tracks); ?>
    <?php if (!empty($tracks)) : ?>
    <div class="slite-library-play-all">
        <button class="sproz-play-hero-btn" data-tracks="<?php echo esc_attr(json_encode($all_data)); ?>" data-index="0">
            <svg viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg> <?php esc_html_e('Play All','sproz-music-player'); ?>
        </button>
        <span class="slite-track-count"><?php echo count($tracks); ?> <?php esc_html_e('tracks','sproz-music-player'); ?></span>
    </div>
    <?php endif; ?>
    <div class="slite-tracklist">
        <?php if (empty($tracks)) : ?><p class="sproz-empty" style="padding:1rem"><?php esc_html_e('No tracks found.','sproz-music-player'); ?></p>
        <?php else : foreach ($tracks as $i => $track) : ?>
            <div class="slite-tracklist-row" data-index="<?php echo $i; ?>" data-tracks="<?php echo esc_attr(json_encode($all_data)); ?>">
                <span class="slite-tl-num"><?php echo $i+1; ?></span>
                <div class="slite-tl-art">
                    <?php if ($track->art_url) : ?><img src="<?php echo esc_url($track->art_url); ?>" alt="" loading="lazy" /><?php else : ?><div class="slite-tl-art-placeholder">♪</div><?php endif; ?>
                    <div class="slite-tl-play-overlay"><svg viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg></div>
                </div>
                <div class="slite-tl-info">
                    <span class="slite-tl-title"><?php echo esc_html($track->title); ?></span>
                    <span class="slite-tl-artist"><?php echo esc_html($track->artist); ?></span>
                    <?php if (!empty($track->genres)) : ?><span class="slite-tl-genre"><?php echo esc_html(implode(', ',$track->genres)); ?></span><?php endif; ?>
                </div>
                <div class="slite-tl-tags"><?php foreach ($track->tags??[] as $tag) : ?><span class="sproz-tag-link"><?php echo esc_html($tag); ?></span><?php endforeach; ?></div>
                <span class="slite-tl-dur"><?php echo esc_html($track->duration); ?></span>
                <a href="<?php echo esc_url(home_url('/slite-track/'.$track->id)); ?>" class="slite-tl-link" onclick="event.stopPropagation()">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 8 12 12 14 14"/></svg>
                </a>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <?php elseif ( $view === 'genres' ) : ?>
    <div class="slite-genre-grid">
        <?php if (empty($genres)) : ?><p class="sproz-empty"><?php esc_html_e('No genres yet.','sproz-music-player'); ?></p>
        <?php else : foreach ($genres as $g) : ?>
            <a href="<?php echo esc_url(home_url('/music-library/?view=genre&slug='.$g->slug)); ?>" class="slite-genre-card">
                <span class="slite-genre-icon">🎵</span>
                <span class="slite-genre-name"><?php echo esc_html($g->name); ?></span>
            </a>
        <?php endforeach; endif; ?>
    </div>
    <?php endif; ?>

</div>
<script>
document.querySelectorAll('.sproz-play-hero-btn').forEach(function(b){ b.addEventListener('click',function(){ if(window.sprozPlay) window.sprozPlay(JSON.parse(this.dataset.tracks),0); }); });
document.querySelectorAll('.slite-tracklist-row').forEach(function(r){ r.addEventListener('click',function(e){ if(e.target.closest('.slite-tl-link')) return; if(window.sprozPlay) window.sprozPlay(JSON.parse(this.dataset.tracks),parseInt(this.dataset.index,10)); }); });
</script>
<?php get_footer(); ?>
