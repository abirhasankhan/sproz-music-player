/**
 * Sproz Music Player — Inline Player (shortcode widget)
 *
 * This file only handles the VISUAL state of inline .sproz-player widgets.
 * All actual audio playback is handled by the global SprozPlayer (sticky-bar.js).
 *
 * sticky-bar.js already calls bindInlinePlayers() on DOMContentLoaded,
 * so this file's job is just to keep inline player UI in sync.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        setInterval(syncInlinePlayers, 500);
    });

    function syncInlinePlayers() {
        const SP = window.SprozPlayer;
        if (!SP || !SP.tracks.length) return;

        const currentUrl = (SP.tracks[SP.index] || {}).url;

        document.querySelectorAll('.sproz-player').forEach(el => {
            const tracks  = JSON.parse(el.dataset.tracks || '[]');
            const playBtn = el.querySelector('.sproz-btn-play');
            const isMine  = tracks.some(t => t.url === currentUrl);

            if (playBtn) {
                const iconPlay  = playBtn.querySelector('.sproz-icon-play');
                const iconPause = playBtn.querySelector('.sproz-icon-pause');
                const showPause = isMine && SP.isPlaying;
                iconPlay  && (iconPlay.style.display  = showPause ? 'none' : '');
                iconPause && (iconPause.style.display = showPause ? ''     : 'none');
            }

            el.querySelectorAll('.sproz-playlist-item').forEach((item, i) => {
                const track = tracks[i];
                item.classList.toggle('is-playing', !!(track && track.url === currentUrl));
            });
        });
    }

})();
