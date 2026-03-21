<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( empty( $slite_track ) ) return;

$track = $slite_track;
$tags  = $track->tags ?? [];

add_filter( 'pre_get_document_title', fn() => esc_html( $track->title ) . ' — ' . get_bloginfo('name') );

get_header();
?>
<div class="sproz-view-wrap">

    <?php echo do_shortcode( '[sproz_player track="' . $track->id . '"]' ); ?>

    <?php if ( ! empty( $tags ) ) : ?>
    <div class="sproz-view-section" style="margin-top:1.5rem">
        <h2 style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#666680;margin-bottom:10px"><?php esc_html_e( 'Tags', 'sproz-music-player' ); ?></h2>
        <div class="sproz-tag-cloud">
            <?php foreach ( $tags as $tag ) : ?>
                <a href="<?php echo esc_url( home_url( '/music-library/?view=tag&slug=' . sanitize_title( $tag ) ) ); ?>" class="sproz-tag-link"><?php echo esc_html( $tag ); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( $track->description ) : ?>
    <div class="sproz-view-section" style="margin-top:1.5rem">
        <h2 style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#666680;margin-bottom:10px"><?php esc_html_e( 'About', 'sproz-music-player' ); ?></h2>
        <div class="sproz-track-description" style="color:#8888aa;font-size:.88rem;line-height:1.7"><?php echo wp_kses_post( $track->description ); ?></div>
    </div>
    <?php endif; ?>

</div>
<?php get_footer(); ?>
