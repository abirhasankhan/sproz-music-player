=== Sproz Music Player ===
Contributors: yourname
Tags: music player, audio player, playlist, mp3, waveform, S3, Cloudflare R2
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

A lightweight WordPress music player with playlist, waveform visualizer, album art, genres, categories, tags and full external audio URL support (S3, Cloudflare R2, Backblaze B2, etc.).

== Features ==

* Custom post types: Tracks and Albums/Playlists
* Taxonomies: Genres, Music Categories, Music Tags
* Waveform visualizer powered by WaveSurfer.js
* Album art display
* External audio URL support — paste any S3, R2, B2, or CDN link
* Local file upload via WordPress Media Library
* Dark and Light player skins
* Play, Pause, Previous, Next, Shuffle, Repeat controls
* Volume slider
* Shortcode: [sproz_player]
* WordPress Widget support

== Shortcode Usage ==

Display an album/playlist:
  [sproz_player album="42"]

Display a single track:
  [sproz_player track="15"]

Filter by genre:
  [sproz_player genre="jazz"]

Filter by music category:
  [sproz_player category="hip-hop"]

Filter by tag:
  [sproz_player tag="chill"]

Override skin:
  [sproz_player album="42" skin="light"]

Limit number of tracks (default 50):
  [sproz_player genre="pop" limit="20"]

== Installation ==

1. Upload the `sproz-music-player` folder to `/wp-content/plugins/`
2. Activate the plugin from the WordPress admin > Plugins
3. Go to Music Player > Tracks to add your first tracks
4. Go to Music Player > Albums to create playlists
5. Use the [sproz_player] shortcode in any page or post

== External Audio URLs (S3, Cloudflare R2, etc.) ==

When adding/editing a Track:
1. Set "Audio Source Type" to "External URL"
2. Paste your direct MP3/M4A URL from S3, Cloudflare R2, Backblaze B2, or any CDN

Make sure the audio file is publicly accessible (CORS configured if needed).

== Changelog ==

= 1.0.0 =
* Initial release
