<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( empty( $slite_album ) ) return;

$album  = $slite_album;
$tracks = $sproz_tracks ?? [];

add_filter( 'pre_get_document_title', fn() => esc_html( $album->title ) . ' — ' . get_bloginfo('name') );

get_header();
?>
<div class="sproz-view-wrap">

    <?php echo do_shortcode( '[sproz_player album="' . $album->id . '" skin="' . esc_attr( $album->player_skin ) . '"]' ); ?>

    <?php if ( $album->description ) : ?>
    <div class="sproz-view-section">
        <h2><?php esc_html_e( 'About', 'sproz-music-player' ); ?></h2>
        <div class="sproz-track-description"><?php echo wp_kses_post( $album->description ); ?></div>
    </div>
    <?php endif; ?>

</div>
<?php get_footer(); ?>
