/**
 * document ready
 */
(function ($) {
    $(document).ready(function () {

        // mail preview iframe height adjustment
        if ($('.inside .mailPreview').length) {
            $('.inside .mailPreview').on('load', function () {
                try {
                    var iframe = $(this)[0]; // DOM-елемент
                    var contentHeight = iframe.contentWindow.document.body.scrollHeight;
                    $(this).height(contentHeight);
                } catch (e) {
                    console.warn('Не вдалося отримати висоту iframe (можливо, інший домен).');
                }
            });
        }

        // remove yoast settings from profile page
        if ($('body').hasClass('profile-php')) {
            $('#profile-page .yoast.yoast-settings').remove();
        }

        // redirect rules code toggle
        if ($('.redirect-rules-code-option').length) {
            $('.redirect-rules-code-option').on('click', function () {
                $('.redirect-rules-code-option').removeClass('active');
                $(this).addClass('active');
            });
        }

        // redirect rules
        if ($('.redirect-rules-form').length) {
            const homeUrl = $('.redirect-rules-form').data('home-url');

            // update open-link button href on input change
            $('.redirect-rules-form input[type="url"]').on('input change', function () {
                const val = $(this).val().trim();
                const $link = $(this).siblings('.redirect-rules-open-link');
                if (val) {
                    $link.attr('href', val).removeClass('hidden');
                } else {
                    $link.addClass('hidden');
                }
            });

            // auto-fill domain on focus
            if (homeUrl) {
                $('.redirect-rules-form input[type="url"]').on('focus', function () {
                    if (!$(this).val()) {
                        $(this).val(homeUrl).trigger('input');
                    }
                });
            }

            // auto-fill title to prevent wp-post-new-reload loop
            if ($('#title').length && !$('#title').val()) {
                $('#title').val('redirect');
            }
        }

        // pattern grid view toggle
        if ($('body').hasClass('post-type-patterns') && $('body').hasClass('edit-php') && typeof patternGridData !== 'undefined') {
            const $gridBtn = $('<a href="#" class="page-title-action pattern-grid-toggle">' + patternGridData.i18n.gridView + '</a>');
            $('.page-title-action').first().after($gridBtn);

            let $gridContainer = null;
            let gridActive = false;

            // live search next to the toggle — filters visible tiles by title; hidden in list view
            const $gridSearch = $('<input type="search" class="pattern-grid-search" autocomplete="off">')
                .attr('placeholder', patternGridData.i18n.searchPlaceholder)
                .hide();
            $gridBtn.after($gridSearch);

            const filterGrid = function () {
                if (!$gridContainer) { return; }
                const query = ($gridSearch.val() || '').trim().toLowerCase();
                $gridContainer.find('.pattern-grid-item').each(function () {
                    const name = $(this).find('.pattern-grid-item-name').text().toLowerCase();
                    $(this).toggle(query === '' || name.indexOf(query) !== -1);
                });
            };
            $gridSearch.on('input', filterGrid);

            // remember the chosen view (grid vs list) across reloads
            const VIEW_KEY = 'patterns_view';
            const saveView = function (v) { try { localStorage.setItem(VIEW_KEY, v); } catch (e) {} };
            const readView = function () { try { return localStorage.getItem(VIEW_KEY); } catch (e) { return null; } };

            $gridBtn.on('click', function (e) {
                e.preventDefault();

                if (gridActive) {
                    // Drop the grid so previews restart from zero on the next open.
                    if ($gridContainer) {
                        $gridContainer.remove();
                        $gridContainer = null;
                    }
                    $('.wp-list-table, .tablenav, .subsubsub, .search-box').show();
                    $gridBtn.text(patternGridData.i18n.gridView);
                    $gridSearch.val('').hide();
                    gridActive = false;
                    saveView('list');
                    return;
                }

                $('.wp-list-table, .tablenav, .subsubsub, .search-box').hide();
                $gridBtn.text(patternGridData.i18n.backToList);
                gridActive = true;

                $gridContainer = $('<div class="pattern-grid-view"></div>');
                const $columns = $('<div class="pattern-grid-columns"></div>');
                const renderWidth = 1400;
                const renderHeight = 900;

                // Fixed masonry columns: each tile is placed into one column and never
                // moved, so previews that resize on load don't reshuffle the whole grid.
                const COLUMN_COUNT = 3;
                const columnEls = [];
                for (let c = 0; c < COLUMN_COUNT; c++) {
                    const $col = $('<div class="pattern-grid-column"></div>');
                    columnEls.push($col);
                    $columns.append($col);
                }

                $.each(patternGridData.patterns, function (i, pattern) {
                    const patternTitle = $('<span>').text(pattern.title).html();
                    const editTitle = $('<span>').text(patternGridData.i18n.edit).html();
                    const $item = $(
                        '<div class="pattern-grid-item">' +
                        '<div class="pattern-grid-item-title">' +
                        '<span class="pattern-grid-item-name">' + patternTitle + '</span>' +
                        '<a href="' + pattern.edit + '" class="pattern-grid-item-edit dashicons dashicons-edit" title="' + editTitle + '"></a>' +
                        '</div>' +
                        '<div class="pattern-grid-item-preview loading">' +
                        '<iframe src="' + pattern.url + '" loading="lazy" scrolling="no" style="width:' + renderWidth + 'px;height:' + renderHeight + 'px"></iframe>' +
                        '</div>' +
                        '</div>'
                    );

                    $item.find('iframe').on('load', function () {
                        try {
                            const iframe = $(this)[0];
                            const $iframe = $(this);
                            const $preview = $iframe.closest('.pattern-grid-item-preview');
                            const iframeDoc = iframe.contentWindow.document;
                            const containerWidth = $preview.width();
                            const scale = containerWidth / renderWidth;
                            let lastHeight = null;

                            iframeDoc.documentElement.style.overflow = 'hidden';
                            iframeDoc.body.style.margin = '0';
                            iframeDoc.body.style.padding = '0';
                            iframeDoc.body.style.overflow = 'hidden';

                            // Collapse the full-viewport preview frame to the real content
                            // height. The shared preview page styles the body as
                            // min-height:100vh + vertically centred (for the standalone popup);
                            // here we neutralise that inline — scoped to the grid iframe only —
                            // so the body shrinks to its content. Done in JS (not via the
                            // preview page) to leave the popup's centring untouched and to not
                            // depend on a fresh, un-cached style.min.css.
                            iframeDoc.body.style.minHeight = '0';
                            const mainEl = iframeDoc.querySelector('main');
                            if (mainEl) { mainEl.style.flex = '0 0 auto'; }

                            // Real visible content span (top of the first block to bottom of the
                            // last) from element rects, which EXCLUDE the blocks' outer margins.
                            // Used only to detect hairline blocks below.
                            function measuredContentHeight() {
                                const host = iframeDoc.querySelector('main') || iframeDoc.body;
                                const kids = host.children;
                                let top = Infinity, bottom = -Infinity;
                                for (let k = 0; k < kids.length; k++) {
                                    const el = kids[k];
                                    if (el.tagName === 'SCRIPT' || el.tagName === 'STYLE' || el.tagName === 'LINK') continue;
                                    const r = el.getBoundingClientRect();
                                    if (r.width === 0 && r.height === 0) continue;
                                    if (r.top < top) top = r.top;
                                    if (r.bottom > bottom) bottom = r.bottom;
                                }
                                return bottom > top ? Math.ceil(bottom - top) : -1;
                            }

                            function updateHeight() {
                                // Normal blocks: tile height = body.scrollHeight (works for all).
                                // EXCEPTION — hairline blocks (the separator's 1px <hr>): with
                                // almost no content the body fills the iframe viewport and
                                // body.scrollHeight reports the full 900px, stretching the tile.
                                // For such tiny measured content, trust the measurement so the
                                // tile matches the 1px block instead of ballooning to 900.
                                const contentH = measuredContentHeight();
                                const height = (contentH > 0 && contentH <= 8) ? contentH : iframeDoc.body.scrollHeight;

                                // Anti-thrash: skip only REPEAT measurements within 2px. lastHeight
                                // starts null so the FIRST measurement always applies — otherwise a
                                // tiny content height (≤1px, e.g. the separator line) sits <2 from
                                // the initial 0 and would never be applied, leaving the iframe at
                                // its 900px default.
                                if (lastHeight !== null && Math.abs(height - lastHeight) < 2) {
                                    return;
                                }

                                lastHeight = height;
                                $iframe.css({
                                    'height': height + 'px',
                                    'transform': 'scale(' + scale + ')',
                                    'transform-origin': 'top left'
                                });
                                // + 16 = the 8px top / 8px bottom padding emulated on the
                                // preview (border-box), framing the scaled block neatly.
                                $preview.css('height', (Math.ceil(height * scale) + 16) + 'px');
                            }

                            updateHeight();
                            $preview.removeClass('loading');

                            if (window.ResizeObserver) {
                                new ResizeObserver(function () {
                                    updateHeight();
                                }).observe(iframeDoc.body);
                            }
                        } catch (e) {
                            $(this).closest('.pattern-grid-item-preview').removeClass('loading');
                        }
                    });

                    $item.find('.pattern-grid-item-edit').on('click', function (e) {
                        e.stopPropagation();
                    });

                    $item.on('click', function (e) {
                        if (e.metaKey || e.ctrlKey) {
                            window.open(pattern.url, '_blank');
                            return;
                        }

                        const $tempLink = $('<a class="pattern-preview-link" data-url="' + pattern.url + '">');
                        $tempLink.appendTo('body').trigger('click').remove();
                    });

                    columnEls[i % COLUMN_COUNT].append($item);
                });

                $gridContainer.append($columns);
                $('.wrap').append($gridContainer);
                $gridSearch.val('').show();
                saveView('grid');
            });

            // restore the last-used view on load (grid stays grid after a reload)
            if (readView() === 'grid') {
                $gridBtn.trigger('click');
            }
        }

        // pattern preview popup
        $(document).on('click', '.pattern-preview-link', function (e) {
            e.preventDefault();
            const url = $(this).data('url');

            // cmd+click (mac) or ctrl+click (win) opens in new tab
            if (e.metaKey || e.ctrlKey) {
                window.open(url, '_blank');
                return;
            }
            const $bg = $('<div class="pattern-preview-popup-bg"></div>');
            const $popup = $(
                '<div class="pattern-preview-popup">' +
                '<button class="close-pattern-preview" type="button">&times;</button>' +
                '<iframe src="' + url + '"></iframe>' +
                '</div>'
            );
            $('body').append($bg).append($popup);
            $('body').css('overflow', 'hidden');

            $bg.on('click', closePatternPreview);
            $popup.find('.close-pattern-preview').on('click', closePatternPreview);

            $(document).on('keydown.patternPreview', function (e) {
                if (e.key === 'Escape') closePatternPreview();
            });

            function closePatternPreview() {
                $bg.remove();
                $popup.remove();
                $('body').css('overflow', '');
                $(document).off('keydown.patternPreview');
            }
        });

    });
})(jQuery);
