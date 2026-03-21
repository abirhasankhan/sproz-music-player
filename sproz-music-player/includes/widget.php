<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'widgets_init', function () {
    register_widget( 'Sproz_Player_Widget' );
} );

class Sproz_Player_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct( 'sproz_player_widget', __( '🎵 Sproz Music Player Player', 'sproz-music-player' ),
            [ 'description' => __( 'Display a music player in any sidebar.', 'sproz-music-player' ) ] );
    }

    public function widget( $args, $instance ) {
        $album_id = ! empty( $instance['album_id'] ) ? intval( $instance['album_id'] ) : 0;
        $skin     = ! empty( $instance['skin'] )     ? $instance['skin']               : 'dark';
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
        echo $album_id
            ? do_shortcode( "[sproz_player album=\"{$album_id}\" skin=\"{$skin}\"]" )
            : '<p>' . esc_html__( 'Select an album in widget settings.', 'sproz-music-player' ) . '</p>';
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title    = $instance['title']    ?? '';
        $album_id = $instance['album_id'] ?? '';
        $skin     = $instance['skin']     ?? 'dark';
        $albums   = Sproz_DB::get_albums();
        ?>
        <p><label><?php esc_html_e('Title:','sproz-music-player'); ?><input class="widefat" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
        <p><label><?php esc_html_e('Album:','sproz-music-player'); ?>
            <select class="widefat" name="<?php echo $this->get_field_name('album_id'); ?>">
                <option value=""><?php esc_html_e('— Select —','sproz-music-player'); ?></option>
                <?php foreach ($albums as $a) : ?>
                    <option value="<?php echo $a->id; ?>" <?php selected($album_id,$a->id); ?>><?php echo esc_html($a->title); ?></option>
                <?php endforeach; ?>
            </select></label></p>
        <p><label><?php esc_html_e('Skin:','sproz-music-player'); ?>
            <select class="widefat" name="<?php echo $this->get_field_name('skin'); ?>">
                <option value="dark"  <?php selected($skin,'dark');  ?>><?php esc_html_e('Dark','sproz-music-player'); ?></option>
                <option value="light" <?php selected($skin,'light'); ?>><?php esc_html_e('Light','sproz-music-player'); ?></option>
            </select></label></p>
        <?php
    }

    public function update( $new, $old ) {
        return [ 'title' => sanitize_text_field($new['title']), 'album_id' => intval($new['album_id']), 'skin' => sanitize_text_field($new['skin']) ];
    }
}
