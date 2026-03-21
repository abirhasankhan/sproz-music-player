# 🎵 Sproz Music Player

A lightweight, self-hosted WordPress music player plugin with persistent playback, Spotify-inspired UI, and full admin CRUD — built as a modern alternative to heavy music plugins.

![Version](https://img.shields.io/badge/version-2.3.0-brightgreen)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![License](https://img.shields.io/badge/license-GPL2-orange)

---

## ✨ Features

- **Persistent sticky player bar** — music never stops when navigating between pages
- **AJAX navigation** — page content swaps without reloading; audio continues seamlessly
- **Spotify-style UI** — hero section with cover art, tracklist table, animated playing bars
- **External audio support** — host files on S3, Cloudflare R2, Backblaze B2, or any CDN
- **Full admin CRUD** — manage tracks, albums, genres, categories, and tags from WP admin
- **Quick Edit** — inline track editing directly from the tracks list
- **Shortcode player** — embed anywhere with `[sproz_player]`
- **Widget support** — drop the player into any widget area
- **Public view pages** — auto-generated pages for tracks, albums, and the music library
- **REST API** — programmatic access to tracks and albums
- **Dark & light skin** — built-in theme support

---

## 📸 Screenshots

| Player Widget | Sticky Bar |
|---|---|
| Spotify-style hero + tracklist | Persistent bottom bar with controls |

---

## 🚀 Installation

1. Download the latest `sproz-music-player.zip` from [Releases](../../releases)
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Activate**
4. Go to **Settings → Permalinks** and click **Save Changes** (flushes rewrite rules for public pages)
5. Go to **Music Player → Tracks** and add your first track

---

## 🗃️ Database Tables

The plugin creates 9 custom tables on activation:

| Table | Description |
|---|---|
| `{prefix}sproz_tracks` | Songs — title, artist, audio URL, duration, art, play count |
| `{prefix}sproz_albums` | Albums / playlists — metadata, skin, release year |
| `{prefix}sproz_album_tracks` | Pivot: album ↔ track with sort order |
| `{prefix}sproz_genres` | Genres taxonomy (hierarchical) |
| `{prefix}sproz_categories` | Music categories taxonomy (hierarchical) |
| `{prefix}sproz_tags` | Music tags (flat) |
| `{prefix}sproz_track_genres` | Pivot: track ↔ genre |
| `{prefix}sproz_track_categories` | Pivot: track ↔ category |
| `{prefix}sproz_track_tags` | Pivot: track ↔ tag |

---

## 🎛️ Shortcode

Embed the player anywhere using `[sproz_player]`:

```
[sproz_player album="42"]               — full album / playlist
[sproz_player track="15"]              — single track
[sproz_player genre="jazz"]            — all tracks in a genre
[sproz_player category="hip-hop"]      — all tracks in a category
[sproz_player tag="chill"]             — all tracks with a tag
[sproz_player album="42" skin="light"] — light skin
[sproz_player genre="pop" limit="20"]  — limit track count
```

### Shortcode Parameters

| Parameter | Type | Default | Description |
|---|---|---|---|
| `album` | int | — | Album ID to display |
| `track` | int | — | Single track ID |
| `genre` | string | — | Genre slug |
| `category` | string | — | Category slug |
| `tag` | string | — | Tag slug |
| `skin` | string | `dark` | `dark` or `light` |
| `limit` | int | `50` | Max number of tracks |

---

## 🌐 Public URLs

After flushing permalinks, these URLs are available automatically:

| URL | Description |
|---|---|
| `/sproz-track/{id}` | Single track page |
| `/sproz-album/{id}` | Album page with full tracklist |
| `/music-library/` | Browse all albums |
| `/music-library/?view=tracks` | All tracks |
| `/music-library/?view=genres` | Genre grid |
| `/music-library/?view=genre&slug=jazz` | Tracks filtered by genre |

---

## 🔌 REST API

| Endpoint | Method | Description |
|---|---|---|
| `/wp-json/sproz-music-player/v1/tracks` | `GET` | List tracks (filter by `album`, `genre`, `category`, `tag`, `limit`) |
| `/wp-json/sproz-music-player/v1/albums` | `GET` | List all albums |
| `/wp-json/sproz-music-player/v1/play/{id}` | `POST` | Increment play count for a track |

---

## 🏗️ File Structure

```
sproz-music-player/
├── sproz-music-player.php       # Main plugin file
├── readme.txt
├── README.md
├── includes/
│   ├── database.php             # Table schema & activation
│   ├── db.php                   # Sproz_DB — all queries
│   ├── admin.php                # Admin CRUD pages, Quick Edit
│   ├── shortcode.php            # [sproz_player] shortcode
│   ├── widget.php               # Sidebar widget
│   ├── rest-api.php             # REST endpoints
│   └── routes.php               # Public URL handling
├── templates/
│   ├── player.php               # Spotify-style player template
│   ├── sticky-bar.php           # Persistent bottom bar HTML
│   ├── view-track.php           # Public single track page
│   ├── view-album.php           # Public album page
│   └── view-library.php         # Music library browse page
└── assets/
    ├── css/
    │   ├── player.css           # Player styles
    │   ├── sticky-bar.css       # Bottom bar styles
    │   ├── views.css            # Public page styles
    │   └── admin.css            # Admin panel styles
    └── js/
        ├── sticky-bar.js        # Core engine — AJAX nav, WaveSurfer, persistent playback
        ├── player.js            # Inline player sync
        └── admin.js             # Quick Edit, media upload, sortable
```

---

## ⚙️ How Continuous Playback Works

The plugin uses a **shell isolation + AJAX navigation** architecture:

1. On boot, the sticky bar and WaveSurfer audio engine are moved into `#sproz-shell` — a `position:fixed` div appended directly to `<body>` that is **never touched by navigation**
2. All page content is wrapped in `#sproz-content` — the single swappable zone
3. Every internal link click is intercepted; instead of a real navigation, the target URL is fetched via `fetch()`
4. Only `#sproz-content` innerHTML is replaced — the shell and audio engine are untouched
5. Shell elements are stripped from fetched HTML **before** DOM parsing to prevent duplicate audio initialization
6. `sessionStorage` acts as a fallback for real reloads (browser back, direct URL entry)

This means music plays **continuously with zero gap** across page navigations.

---

## 🎵 External Audio Support

Set `audio_type = external` and paste any direct MP3/M4A URL. Tested providers:

- **Amazon S3** — public bucket URLs
- **Cloudflare R2** — S3-compatible, free egress
- **Backblaze B2** — low-cost object storage
- **SoundCloud** — direct stream URLs
- **Libsyn / Podbean / Buzzsprout** — podcast hosts

---

## 🛠️ Admin Features

- **Tracks list** with Edit, Quick Edit, View, and Delete actions
- **Quick Edit** — inline AJAX panel: title, artist, duration, status, audio URL, art URL
- **Fix Album Tracks** (🔧) — repair tool to sync tracks with their albums
- **Albums** — manage playlists with cover art, release year, record label, skin selection
- **Genres / Categories / Tags** — full taxonomy management with hierarchy support

---

## 🧱 Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.0+, WordPress Plugin API, custom `$wpdb` queries |
| Audio engine | [WaveSurfer.js 7.8.7](https://wavesurfer.xyz/) |
| Frontend | Vanilla JS (ES5 compatible), CSS custom properties |
| Fonts | [DM Sans](https://fonts.google.com/specimen/DM+Sans) + [Syne](https://fonts.google.com/specimen/Syne) |
| Storage | 9 custom MySQL tables via `dbDelta()` |

---

## 🔧 Requirements

- WordPress 5.8+
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+ (for `FULLTEXT` index on tracks)
- Pretty Permalinks enabled (Settings → Permalinks → any option except Plain)

---

## 🐛 Troubleshooting

**Public pages return 404**
→ Go to Settings → Permalinks → Save Changes to flush rewrite rules.

**Tracks not showing in album**
→ Go to Music Player → Tracks → click the 🔧 Fix Album Tracks button.

**Music stops on page navigation**
→ Make sure your theme's main content uses a standard selector (`.site-main`, `#content`, `main`, etc.). The AJAX nav engine looks for these to perform the content swap.

**WaveSurfer error in console**
→ Ensure the audio file URL is publicly accessible (no auth required) and is a direct MP3/M4A link.

---

## ⬆️ Upgrading from Old Version

If you were using the old `Sonaar Lite` plugin:

1. **Deactivate** the old plugin — do not delete yet
2. **Export** your tracks data if needed
3. **Install** Sproz Music Player
4. **Activate** — new `sproz_*` tables will be created automatically
5. Re-enter your tracks (old `slite_*` tables are separate and unaffected)
6. Delete the old plugin once confirmed working

---

## 📄 License

GPL2 — see [LICENSE](LICENSE) for details.

---

## 👤 Author

Built by **CodeSamurai** — full-stack developer specializing in Nuxt.js, WordPress, and Supabase.

---

## 🤝 Contributing

Pull requests welcome. For major changes, please open an issue first to discuss what you'd like to change.

1. Fork the repo
2. Create your feature branch (`git checkout -b feature/cool-thing`)
3. Commit your changes (`git commit -m 'Add cool thing'`)
4. Push to the branch (`git push origin feature/cool-thing`)
5. Open a Pull Request
