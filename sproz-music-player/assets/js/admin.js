/* Sproz Music Player – Admin JS */
jQuery(function ($) {
    'use strict';

    // ── Audio source type toggle ───────────────────────────────────────────────
    $('#slite_audio_type').on('change', function () {
        if ($(this).val() === 'local') {
            $('#slite_external_wrap').hide();
            $('#slite_local_wrap').show();
        } else {
            $('#slite_local_wrap').hide();
            $('#slite_external_wrap').show();
        }
    });

    // ── Media library uploader ─────────────────────────────────────────────────
    $(document).on('click', '.slite-upload-btn', function (e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var frame = wp.media({
            title:    'Select File',
            button:   { text: 'Use this file' },
            multiple: false,
        });
        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#' + targetId).val(attachment.url);
        });
        frame.open();
    });

    // ── Playlist track picker ──────────────────────────────────────────────────
    $(document).on('click', '.slite-add-track', function () {
        var item   = $(this).closest('.slite-track-item');
        var id     = item.data('id');
        var title  = item.find('.slite-track-title').text();
        var artist = item.find('.slite-track-artist').text();
        var html = '<div class="slite-track-item slite-selected" data-id="' + id + '">'
            + '<span class="dashicons dashicons-menu slite-drag-handle"></span>'
            + '<span class="slite-track-title">' + title + '</span>'
            + (artist ? '<span class="slite-track-artist">' + artist + '</span>' : '')
            + '<button type="button" class="slite-remove-track dashicons dashicons-minus" title="Remove"></button>'
            + '<input type="hidden" name="track_ids[]" value="' + id + '" /></div>';
        $('#slite_selected_tracks').append(html);
        item.remove();
    });

    $(document).on('click', '.slite-remove-track', function () {
        var item   = $(this).closest('.slite-track-item');
        var id     = item.data('id');
        var title  = item.find('.slite-track-title').text();
        var artist = item.find('.slite-track-artist').text();
        var html = '<div class="slite-track-item" data-id="' + id + '">'
            + '<span class="slite-track-title">' + title + '</span>'
            + (artist ? '<span class="slite-track-artist">' + artist + '</span>' : '')
            + '<button type="button" class="slite-add-track dashicons dashicons-plus-alt" title="Add"></button></div>';
        $('#slite_available_tracks').append(html);
        item.remove();
    });

    // ── Sortable drag-to-reorder ───────────────────────────────────────────────
    if ($.fn.sortable) {
        $('#slite_selected_tracks').sortable({
            handle: '.slite-drag-handle',
            placeholder: 'sortable-ghost',
            axis: 'y',
        });
    }

    // ════════════════════════════════════════════════════════════════════════════
    // QUICK EDIT
    // ════════════════════════════════════════════════════════════════════════════

    // Open Quick Edit row
    $(document).on('click', '.slite-quick-edit-btn', function (e) {
        e.preventDefault();
        var id = $(this).data('id');

        // Close any other open quick-edit rows first
        $('.slite-quick-edit-row').slideUp(150);

        var $qeRow = $('#slite-qe-' + id);
        $qeRow.slideDown(200);

        // Scroll row into view
        $('html, body').animate({ scrollTop: $qeRow.offset().top - 80 }, 200);
    });

    // Cancel Quick Edit
    $(document).on('click', '.slite-qe-cancel', function () {
        $(this).closest('.slite-quick-edit-row').slideUp(150);
    });

    // Submit Quick Edit via AJAX
    $(document).on('submit', '.slite-qe-form', function (e) {
        e.preventDefault();
        var $form    = $(this);
        var id       = $form.data('id');
        var $spinner = $form.find('.slite-qe-spinner');
        var $msg     = $form.find('.slite-qe-msg');
        var $row     = $('#slite-row-' + id);

        $spinner.addClass('is-active');
        $msg.text('');

        var formData = $form.serialize() + '&action=sproz_quick_edit_track&id=' + id;

        $.post(ajaxurl, formData, function (res) {
            $spinner.removeClass('is-active');

            if (res.success) {
                var d = res.data;

                // Update the visible row cells inline
                $row.find('td:eq(0) strong').text(d.title);
                $row.find('td:eq(1)').text(d.artist);
                $row.find('td:eq(3)').text(d.duration);
                $row.find('td:eq(5) .slite-status')
                    .text(d.status)
                    .removeClass('slite-status-publish slite-status-draft')
                    .addClass('slite-status-' + d.status);

                // Update the quick-edit button data attributes
                $row.find('.slite-quick-edit-btn')
                    .data('title', d.title)
                    .data('artist', d.artist)
                    .data('duration', d.duration)
                    .data('status', d.status);

                // Flash green success
                $msg.css('color', '#46b450').text('✓ Saved!');
                setTimeout(function () {
                    $('#slite-qe-' + id).slideUp(200);
                }, 800);

                // Briefly highlight the updated row
                $row.css('background', '#edfaee');
                setTimeout(function () { $row.css('background', ''); }, 1200);

            } else {
                $msg.css('color', '#dc3232').text('Error: ' + (res.data || 'Could not save'));
            }
        }).fail(function () {
            $spinner.removeClass('is-active');
            $msg.css('color', '#dc3232').text('Network error. Please try again.');
        });
    });

});
