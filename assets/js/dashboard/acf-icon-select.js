/**
 * acf managed icon select field
 *
 * Handlers are delegated from `document` rather than bound per-field on init.
 * The field renders in three different contexts — a static options page, a
 * Gutenberg block's sidebar (rendered dynamically when the block is selected),
 * and inside a repeater (sub-fields appended on demand). A per-field init that
 * relied on `acf.addAction('ready_field/append_field/type=icon_select')` only
 * fired reliably for the static case, so the "Pick an icon" button was dead in
 * blocks/repeaters (and broke entirely if `window.acf` was not yet defined at
 * document-ready, skipping the action registration). Delegation removes every
 * timing dependency: a click always resolves its own `.acf-icon-select-field`
 * via `closest()`, no matter when or where the markup appeared.
 *
 * The overlay is PORTALED to <body> while open. In the Gutenberg block
 * inspector the field sits inside a sidebar that establishes its own stacking
 * context / transform, which traps `position: fixed` — the overlay then sizes
 * and stacks relative to that narrow sidebar instead of the viewport (the editor
 * tabs bleed over it, z-index has no effect, and the inset frame collapses so
 * its footer is pulled up). Moving the overlay to <body> on open escapes that
 * ancestor so `fixed`/`inset` cover the viewport and the z-index wins; it is
 * returned to its field on close so it tracks the field's lifecycle.
 */
(function ($) {
    function getIconHref(iconId) {
        if (!window.managedIcons || !iconId) {
            return '';
        }

        const prefix = window.managedIcons.prefix || '';
        const base = (prefix && String(iconId).indexOf(prefix) === 0)
            ? window.managedIcons.spriteUrl
            : (window.managedIcons.themeSpriteUrl || window.managedIcons.spriteUrl);

        return base ? base + '#' + iconId : '';
    }

    // the overlay may be portaled to <body>, so track it on its field and keep a
    // back-reference on the overlay for resolving the field from inside it
    function overlayOf($field) {
        let $overlay = $field.data('acfOverlay');
        if ($overlay && $overlay.length) {
            return $overlay;
        }
        $overlay = $field.find('.acf-icon-select-overlay');
        $field.data('acfOverlay', $overlay);
        $overlay.data('acfField', $field);
        return $overlay;
    }

    // resolve the owning field from any element inside the field OR the overlay
    function fieldOf(el) {
        const $el = $(el);
        const $field = $el.closest('.acf-icon-select-field');
        if ($field.length) {
            return $field;
        }
        // overlay portaled to <body>: follow the stored back-reference
        return $el.closest('.acf-icon-select-overlay').data('acfField') || $();
    }

    function updateField($field, icon) {
        if (!$field || !$field.length) {
            return;
        }

        const iconId = icon && icon.id ? icon.id : '';
        const displayId = icon && icon.display_id ? icon.display_id : '';
        const $input = $field.find('.acf-icon-select-value');
        const $control = $field.find('.acf-icon-select-control');
        const $preview = $field.find('.acf-icon-select-preview');
        const $label = $field.find('.acf-icon-select-label');
        const $picker = overlayOf($field).find('.icons-picker');

        $input.val(iconId).trigger('change');
        $field.attr('data-selected-icon-id', iconId).data('selected-icon-id', iconId);
        $picker.attr('data-selected-icon-id', iconId).data('selected-icon-id', iconId);

        if (!iconId) {
            $control.removeClass('has-icon has-missing-icon');
            $preview.empty();
            $label.text($field.data('pick-label') || 'Pick an icon');
            return;
        }

        $control.removeClass('has-missing-icon').addClass('has-icon');
        $preview.html(
            '<svg focusable="false" aria-hidden="true">' +
            '<use href="' + getIconHref(iconId) + '"></use>' +
            '</svg>'
        );
        $label.text($field.data('pick-another-label') || 'Pick another icon');

        if (displayId) {
            $control.attr('title', displayId);
        }
    }

    function openOverlay($field) {
        const $overlay = overlayOf($field);
        const $picker = $overlay.find('.icons-picker');

        if (window.initIconsManager) {
            window.initIconsManager($picker);
        }

        // portal out of the block inspector's transformed/stacking ancestor so
        // the fixed overlay covers the viewport (fixes both z-index and height)
        if (!$overlay.parent().is('body')) {
            $('body').append($overlay);
        }

        $overlay.addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('acf-icon-select-overlay-open');
    }

    function closeOverlay($field) {
        if (!$field || !$field.length) {
            return;
        }

        const $overlay = overlayOf($field);

        $overlay.removeClass('is-open').attr('aria-hidden', 'true');
        $('body').removeClass('acf-icon-select-overlay-open');

        // return the overlay into its field so it lives/dies with the field
        if (!$overlay.parent().is($field)) {
            $field.append($overlay);
        }
    }

    // one delegated set of handlers for every icon_select field on the page,
    // present now or rendered later (block sidebar, new repeater row, …)
    $(document)
        .on('click', '.acf-icon-select-open', function (event) {
            event.preventDefault();
            openOverlay(fieldOf(this));
        })
        .on('click', '.acf-icon-select-clear', function (event) {
            event.preventDefault();
            updateField(fieldOf(this), null);
        })
        .on('click', '[data-acf-icon-select-close]', function (event) {
            event.preventDefault();
            closeOverlay(fieldOf(this));
        })
        // the picker's custom events bubble up from .icons-picker (icons.js
        // dispatches them with .trigger()); the picker is portaled with the
        // overlay, so match it directly and resolve the field via fieldOf()
        .on('iconPicked', '.icons-picker', function (event, icon) {
            const $field = fieldOf(this);
            updateField($field, icon);
            closeOverlay($field);
        })
        .on('iconRenamed', '.icons-picker', function (event, icon) {
            const $field = fieldOf(this);
            if ($field.length && $field.find('.acf-icon-select-value').val() === icon.oldId) {
                updateField($field, icon);
            }
        })
        .on('iconDeleted', '.icons-picker', function (event, icon) {
            const $field = fieldOf(this);
            if ($field.length && $field.find('.acf-icon-select-value').val() === icon.id) {
                updateField($field, null);
            }
        })
        .on('keydown.acfIconSelect', function (event) {
            if (event.key !== 'Escape') {
                return;
            }
            const $open = $('.acf-icon-select-overlay.is-open');
            if ($open.length) {
                closeOverlay($open.data('acfField') || $open.closest('.acf-icon-select-field'));
            }
        });
})(jQuery);
