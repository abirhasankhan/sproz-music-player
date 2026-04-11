=== Sproz Music Player ===
Contributors: yourname
Tags: music player, audio player, playlist, mp3, waveform, spotify, ajax player, s3, cloudflare r2
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 2.3.0
License: GPL2 or later

A lightweight, Spotify-inspired WordPress music player with persistent playback, AJAX navigation, waveform visualizer, album/playlist system, and full external audio support (S3, Cloudflare R2, Backblaze B2, CDN links).

== Description ==

Sproz Music Player is a modern WordPress audio plugin designed for seamless music playback across pages.

Unlike traditional WordPress music players, Sproz features a persistent audio engine that continues playing music even when navigating between pages using AJAX-based navigation.

It is built for performance, flexibility, and scalability, supporting both local media uploads and external streaming URLs.

== Features ==

* 🎵 Persistent sticky audio player (music continues across page navigation)
* ⚡ AJAX page navigation (no full page reloads)
* 🎧 Spotify-style UI with waveform visualizer
* 📀 Albums / Playlists system
* 🎼 Tracks management with full CRUD
* 🏷️ Taxonomies: Genres, Categories, Tags
* 🌐 External audio URL support (S3, Cloudflare R2, Backblaze B2, CDN)
* 📁 Local file upload via WordPress Media Library
* 🎨 Dark & Light player skins
* ⏯️ Full playback controls (Play, Pause, Next, Previous, Shuffle, Repeat)
* 🔊 Volume control slider
* 🧩 Shortcode support: [sproz_player]
* 🪄 Widget support for sidebar/footer embedding
* 📄 Public pages for tracks, albums, and music library
* 🔌 REST API for programmatic access
* ⚡ Optimized architecture using shell-based persistent audio engine

== Shortcode Usage ==

Display a full album / playlist:
  [sproz_player album="42"]

Display a single track:
  [sproz_player track="15"]

Filter by genre:
  [sproz_player genre="jazz"]

Filter by category:
  [sproz_player category="hip-hop"]

Filter by tag:
  [sproz_player tag="chill"]

Override player skin:
  [sproz_player album="42" skin="light"]

Limit number of tracks:
  [sproz_player genre="pop" limit="20"]

== Installation ==

1. Upload the `sproz-music-player` folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress admin → Plugins
3. Go to Settings → Permalinks and click "Save Changes"
4. Go to Music Player → Tracks and add your first tracks
5. Create Albums / Playlists from Music Player → Albums
6. Use the shortcode `[sproz_player]` in any page, post, or widget area

== External Audio Support ==

Sproz supports external streaming URLs from:

* Amazon S3
* Cloudflare R2
* Backblaze B2
* SoundCloud direct links
* Any CDN with public MP3/M4A access

To use:
1. Set "Audio Source Type" to "External URL"
2. Paste your direct audio file URL

⚠️ Ensure the file is publicly accessible and supports CORS if required.

== REST API ==

* GET /wp-json/sproz-music-player/v1/tracks
* GET /wp-json/sproz-music-player/v1/albums
* POST /wp-json/sproz-music-player/v1/play/{id}

Supports filtering by album, genre, category, tag, and limit.

== Database ==

The plugin uses custom optimized tables:

* Tracks
* Albums
* Album-Track relationships
* Genres
* Categories
* Tags
* Pivot mapping tables for relationships

== Architecture ==

Sproz uses a persistent audio engine architecture:

* Sticky shell (`#sproz-shell`) remains outside page reload system
* Content area (`#sproz-content`) updates via AJAX
* Navigation intercepted using fetch() API
* Audio engine (WaveSurfer.js) remains active globally
* sessionStorage fallback ensures state persistence on refresh

This enables uninterrupted playback across navigation.

== Requirements ==

* WordPress 5.8+
* PHP 8.0+
* MySQL 5.7+ or MariaDB 10.3+
* Pretty Permalinks enabled

== Troubleshooting ==

Music stops on page navigation:
→ Ensure your theme uses standard content wrapper (`#content`, `.site-main`, or `<main>`)

Tracks not visible in album:
→ Go to Music Player → Fix Album Tracks tool

Pages returning 404:
→ Go to Settings → Permalinks → Save Changes

WaveSurfer errors:
→ Ensure audio URL is public and directly accessible (no login required)

== Changelog ==

= 2.3.0 =
* Added persistent playback system (sticky shell architecture)
* Added AJAX navigation engine
* Improved waveform synchronization
* Added REST API enhancements
* Improved album/track relationship handling

= 1.0.0 =
* Initial release

== License ==

GPLv2 or later