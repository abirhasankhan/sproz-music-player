<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="sproz-sticky-bar" class="sproz-sticky-bar sproz-sticky-hidden" role="region" aria-label="Music Player">

    <!-- ── Left: Art + Track info ──────────────────────────────────── -->
    <div class="sproz-sticky-left">
        <div class="sproz-sticky-art" id="sproz-sticky-art">
            <span class="sproz-sticky-art-placeholder">♪</span>
            <img id="sproz-sticky-art-img" src="" alt="" />
        </div>
        <div class="sproz-sticky-info">
            <div class="sproz-sticky-title" id="sproz-sticky-title">—</div>
            <div class="sproz-sticky-artist" id="sproz-sticky-artist"></div>
        </div>
    </div>

    <!-- ── Center: Controls + Progress ────────────────────────────── -->
    <div class="sproz-sticky-center">

        <div class="sproz-sticky-controls">
            <button class="sproz-sb-btn sproz-sb-shuffle" title="Shuffle">
                <svg viewBox="0 0 24 24"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/></svg>
            </button>
            <button class="sproz-sb-btn sproz-sb-prev" title="Previous">
                <svg viewBox="0 0 24 24"><polygon points="19 20 9 12 19 4 19 20"/><line x1="5" y1="19" x2="5" y2="5"/></svg>
            </button>
            <button class="sproz-sb-btn sproz-sb-play" title="Play / Pause">
                <svg class="sproz-sb-icon-play"  viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                <svg class="sproz-sb-icon-pause" viewBox="0 0 24 24" style="display:none"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
            </button>
            <button class="sproz-sb-btn sproz-sb-next" title="Next">
                <svg viewBox="0 0 24 24"><polygon points="5 4 15 12 5 20 5 4"/><line x1="19" y1="5" x2="19" y2="19"/></svg>
            </button>
            <button class="sproz-sb-btn sproz-sb-repeat" title="Repeat">
                <svg viewBox="0 0 24 24"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            </button>
        </div>

        <div class="sproz-sticky-progress-row">
            <span class="sproz-sb-cur">0:00</span>
            <div class="sproz-sticky-progress-wrap-inner">
                <div class="sproz-sticky-progress-bg-inner">
                    <div class="sproz-sticky-progress-fill-inner" id="sproz-sb-fill"></div>
                </div>
                <input type="range" class="sproz-sticky-seek-inner" id="sproz-sb-seek" min="0" max="1000" value="0" step="1" />
            </div>
            <span class="sproz-sb-dur">0:00</span>
        </div>

    </div>

    <!-- ── Right: Volume + Queue + Close ───────────────────────────── -->
    <div class="sproz-sticky-right">
        <svg class="sproz-sb-vol-icon" viewBox="0 0 24 24">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
        </svg>
        <input type="range" class="sproz-sb-volume" id="sproz-sb-volume" min="0" max="1" step="0.01" value="1" />

        <button class="sproz-sb-btn" id="sproz-sb-queue-toggle" title="Queue">
            <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
        <button class="sproz-sb-btn" id="sproz-sb-close" title="Close">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <!-- ── Queue drawer ─────────────────────────────────────────────── -->
    <div id="sproz-sb-queue">
        <div class="sproz-sb-queue-header">
            <span>Queue</span>
            <button class="sproz-sb-btn" id="sproz-sb-queue-close" style="width:24px;height:24px">✕</button>
        </div>
        <div class="sproz-sb-queue-list" id="sproz-sb-queue-list"></div>
    </div>

</div>
