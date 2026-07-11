/**
 * managed SVG icons dashboard
 */
(function ($) {
    function initIconsManager(page) {
        const $page = $(page);

        if (!$page.length || !window.managedIcons) {
            return;
        }

        if ($page.data('icons-ready')) {
            return;
        }

        $page.data('icons-ready', true);

        const $grid = $page.find('.icons-grid');
        const $notices = $page.find('.icons-notices');
        const $uploadButton = $page.find('.upload-icons');
        const $bulkDeleteButton = $page.find('.bulk-delete-icons');
        const $bulkActions = $page.find('.icons-bulk-actions');
        const $deleteSelectedButton = $page.find('.delete-selected-icons');
        const $cancelBulkSelectionButton = $page.find('.cancel-bulk-selection');
        const $fileInput = $page.find('.icons-file-input');
        const $modal = $page.find('.icon-modal');
        const $modalTitle = $modal.find('h2').first();
        const $importModal = $page.find('.icons-import-modal');
        const $importPreview = $importModal.find('.icons-import-preview');
        const $importPreviewIcon = $importModal.find('.icons-import-preview__icon');
        const $importOptions = $importModal.find('.icons-import-options');
        const $fitViewboxOption = $importModal.find('.icon-fit-viewbox');
        const $currentColorOption = $importModal.find('.icon-current-color');
        const $importSummary = $importModal.find('.icons-import-summary');
        const $importInvalid = $importModal.find('.icons-import-invalid');
        const $importMessage = $importModal.find('.icons-import-message');
        const $confirmImportButton = $importModal.find('.confirm-icons-import');
        const $modalPreview = $modal.find('.icon-modal__preview');
        const $modalPreviewIcon = $modal.find('.icon-modal__preview .icons-import-preview__icon');
        const $modalFitViewboxOption = $modal.find('.icon-edit-fit-viewbox');
        const $modalCurrentColorOption = $modal.find('.icon-edit-current-color');
        const $input = $modal.find('#icon-id, .icon-id');
        const $message = $modal.find('.icon-modal__message');
        const $saveButton = $modal.find('.save-icon-id');
        const $downloadButton = $modal.find('.download-icon');
        const $deleteButton = $modal.find('.delete-icon');
        const isPicker = String($page.data('icons-picker') || '') === '1';
        const $pickerChooseButton = $page.find('.choose-selected-icon');
        const $pickerEditButton = $page.find('.edit-selected-icon');
        let activeIconId = '';
        let activeCard = null;
        let activeIconLocked = false;
        let spriteDocuments = {};
        let deleteCountdown = 0;
        let deleteCountdownTimer = null;
        let pendingSvgContent = '';
        let pendingSvgSourceName = '';
        let pendingImportAnalysis = null;
        let pendingImportOptions = {};
        let dragDepth = 0;
        let isBulkSelecting = false;
        let selectedIconIds = [];
        let lastSelectedIconId = '';
        let bulkDeleteCountdown = 0;
        let bulkDeleteCountdownTimer = null;
        let pickerSelectedIconId = isPicker ? String($page.data('selected-icon-id') || '') : '';
        let activeIconViewBox = '';
        let pendingIconViewBox = '';

        function getMessage(key, fallback) {
            const messages = window.managedIcons.messages || {};

            return messages[key] || fallback;
        }

        function isCardLocked($card) {
            return String($card.data('icon-locked') || '') === '1';
        }

        function setMessage(message, type) {
            $message
                .removeClass('is-error is-success')
                .addClass(type ? 'is-' + type : '')
                .text(message || '');
        }

        function showNotice(message, type) {
            const noticeType = type || 'success';
            const $notice = $(
                '<div class="notice notice-' + noticeType + ' is-dismissible">' +
                '<p></p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">' + getMessage('dismissNotice', 'Dismiss this notice.') + '</span>' +
                '</button>' +
                '</div>'
            );

            $notice.find('p').text(message);
            $notices.html($notice);
        }

        function formatMessage(template, values) {
            let message = template;

            values.forEach(function (value, index) {
                message = message.replace('%' + (index + 1) + '$s', value);
                message = message.replace('%s', value);
            });

            return message;
        }

        function spriteUrlFor(iconId) {
            const prefix = window.managedIcons.prefix || '';

            if (prefix && String(iconId || '').indexOf(prefix) === 0) {
                return window.managedIcons.spriteUrl;
            }

            return window.managedIcons.themeSpriteUrl || window.managedIcons.spriteUrl;
        }

        function getIconHref(iconId) {
            return spriteUrlFor(iconId) + '#' + iconId;
        }

        function getIconDisplayId(iconId) {
            const prefix = window.managedIcons.prefix || '';

            if (prefix && String(iconId || '').indexOf(prefix) === 0) {
                return iconId.substring(prefix.length);
            }

            return iconId;
        }

        function normalizeIconId(iconId) {
            const prefix = window.managedIcons.prefix || '';
            let normalized = String(iconId || '')
                .trim()
                .toLowerCase()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9-]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');

            if (!normalized || (prefix && normalized === prefix.replace(/-$/, ''))) {
                return '';
            }

            if (!prefix || normalized.indexOf(prefix) === 0) {
                return normalized;
            }

            return prefix + normalized;
        }

        function iconIdExists(iconId) {
            let exists = false;

            $page.find('.icon-card').each(function () {
                const currentIconId = $(this).data('icon-id');

                if (currentIconId === iconId && currentIconId !== activeIconId) {
                    exists = true;
                    return false;
                }
            });

            return exists;
        }

        function validateIconId(iconId) {
            if (!iconId) {
                return getMessage('emptyIconId', 'Icon name cannot be empty.');
            }

            if (iconIdExists(iconId)) {
                return getMessage('duplicateIconId', 'Icon name already exists.');
            }

            return '';
        }

        function renderIconCard(icon) {
            const $card = $('<button type="button" class="icon-card"></button>');
            const $preview = $('<span class="icon-card__preview"></span>');
            const $svg = $('<svg aria-hidden="true" focusable="false"></svg>');
            const $title = $('<span class="icon-card__title"></span>');
            const $check = $('<span class="icon-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>');

            $card
                .attr('data-icon-id', icon.id)
                .attr('data-icon-display-id', icon.display_id)
                .attr('data-icon-viewbox', icon.viewBox || '')
                .data('icon-id', icon.id)
                .data('icon-display-id', icon.display_id)
                .data('icon-viewbox', icon.viewBox || '');

            $svg.html('<use href="' + getIconHref(icon.id) + '"></use>');
            $title.text(icon.display_id);

            $preview.append($svg);
            $card.append($preview).append($title).append($check);
            setIconCardViewBox($card, icon.viewBox || '');

            return $card;
        }

        function ensureIconsGrid() {
            $page.find('.icons-empty').remove();
            $grid.prop('hidden', false).removeAttr('hidden');
            $bulkDeleteButton.prop('hidden', false).removeAttr('hidden');
        }

        function showEmptyState() {
            if ($page.find('.icon-card').length) {
                return;
            }

            setBulkSelectionMode(false);
            $grid.prop('hidden', true).attr('hidden', 'hidden');
            $bulkDeleteButton.prop('hidden', true).attr('hidden', 'hidden');

            if ($page.find('.icons-empty').length) {
                return;
            }

            const $empty = $('<div class="notice notice-warning inline icons-empty"><p></p></div>');
            $empty.find('p').text(getMessage('empty', 'No icons found in the sprite yet.'));
            $empty.insertAfter($notices);
        }

        function updateBulkSelectionControls() {
            const count = selectedIconIds.length;

            if ($deleteSelectedButton.hasClass('is-confirming-delete')) {
                return;
            }

            $deleteSelectedButton
                .prop('disabled', !count)
                .text(count
                    ? formatMessage(getMessage('deleteSelectedCount', 'Delete selected (%s)'), [count])
                    : getMessage('deleteSelected', 'Delete selected')
                );
        }

        function resetBulkDeleteConfirmation() {
            window.clearInterval(bulkDeleteCountdownTimer);
            bulkDeleteCountdownTimer = null;
            bulkDeleteCountdown = 0;
            $deleteSelectedButton.removeClass('is-confirming-delete');
            updateBulkSelectionControls();
        }

        function startBulkDeleteConfirmation() {
            bulkDeleteCountdown = 3;
            $deleteSelectedButton
                .addClass('is-confirming-delete')
                .prop('disabled', false)
                .text(getMessage('confirmDelete', 'Confirm delete') + ' (' + bulkDeleteCountdown + ')');

            bulkDeleteCountdownTimer = window.setInterval(function () {
                bulkDeleteCountdown -= 1;

                if (bulkDeleteCountdown <= 0) {
                    resetBulkDeleteConfirmation();
                    return;
                }

                $deleteSelectedButton.text(getMessage('confirmDelete', 'Confirm delete') + ' (' + bulkDeleteCountdown + ')');
            }, 1000);
        }

        function setBulkSelectionMode(enabled) {
            isBulkSelecting = enabled;

            if (!enabled) {
                selectedIconIds = [];
                lastSelectedIconId = '';
                resetBulkDeleteConfirmation();
                $page
                    .removeClass('is-bulk-selecting')
                    .find('.icon-card')
                    .removeClass('is-selected')
                    .attr('aria-pressed', 'false');
                if (isPicker && pickerSelectedIconId) {
                    getPickerSelectedCard().addClass('is-selected').attr('aria-pressed', 'true');
                }
                $bulkActions.prop('hidden', true).attr('hidden', 'hidden');
                if ($page.find('.icon-card').length) {
                    $bulkDeleteButton.prop('hidden', false).removeAttr('hidden');
                }
                updateBulkSelectionControls();
                updatePickerControls();
                return;
            }

            $page.addClass('is-bulk-selecting');
            $bulkActions.prop('hidden', false).removeAttr('hidden');
            $bulkDeleteButton.prop('hidden', true).attr('hidden', 'hidden');
            updateBulkSelectionControls();
        }

        function toggleIconSelection($card) {
            const iconId = $card.data('icon-id');

            if (!iconId || isCardLocked($card)) {
                return;
            }

            if ($card.hasClass('is-selected')) {
                selectedIconIds = selectedIconIds.filter(function (selectedIconId) {
                    return selectedIconId !== iconId;
                });
                $card.removeClass('is-selected').attr('aria-pressed', 'false');
            } else {
                selectedIconIds.push(iconId);
                $card.addClass('is-selected').attr('aria-pressed', 'true');
            }

            resetBulkDeleteConfirmation();
            updateBulkSelectionControls();
        }

        function inferIconViewBox(iconId, callback) {
            getSpriteDocument(iconId).done(function (spriteDocument) {
                const symbol = spriteDocument.getElementById(iconId);
                let viewBox = '';

                if (symbol) {
                    viewBox = symbol.getAttribute('viewBox') || getSvgArtworkBounds(buildSvgFromSymbol(symbol, '')) || '';
                }

                callback(viewBox);
            }).fail(function () {
                callback('');
            });
        }

        // The <use> already applies the symbol's own viewBox (offset included); the card
        // svg must frame the SIZE only, or the offset is applied twice and the art clips.
        function sizeOnlyViewBox(viewBox) {
            const parts = String(viewBox || '').trim().split(/[\s,]+/).map(Number);

            if (parts.length === 4 && parts[2] > 0 && parts[3] > 0) {
                return '0 0 ' + parts[2] + ' ' + parts[3];
            }

            return String(viewBox || '').trim();
        }

        function setIconCardViewBox($card, viewBox) {
            const iconViewBox = String(viewBox || '').trim();
            const $svg = $card.find('.icon-card__preview svg');

            $svg.attr('preserveAspectRatio', 'xMidYMid meet');

            if (!iconViewBox) {
                $svg.removeAttr('viewBox');
                return;
            }

            $card
                .attr('data-icon-viewbox', iconViewBox)
                .data('icon-viewbox', iconViewBox);

            $svg.attr('viewBox', sizeOnlyViewBox(iconViewBox));
        }

        function inferMissingIconCardViewBoxes() {
            $page.find('.icon-card').each(function () {
                const $card = $(this);

                if (String($card.data('icon-viewbox') || '').trim()) {
                    setIconCardViewBox($card, $card.data('icon-viewbox'));
                    return;
                }

                inferIconViewBox($card.data('icon-id'), function (viewBox) {
                    if (viewBox && $.contains(document, $card[0])) {
                        setIconCardViewBox($card, viewBox);
                    }
                });
            });
        }

        function selectIconCard($card) {
            const iconId = $card.data('icon-id');

            if (!iconId || isCardLocked($card)) {
                return;
            }

            if (selectedIconIds.indexOf(iconId) === -1) {
                selectedIconIds.push(iconId);
            }

            $card.addClass('is-selected').attr('aria-pressed', 'true');
        }

        function selectIconRange($card) {
            const $cards = $page.find('.icon-card');
            const currentIndex = $cards.index($card);
            let anchorIndex = -1;

            if (lastSelectedIconId) {
                $cards.each(function (index) {
                    if ($(this).data('icon-id') === lastSelectedIconId) {
                        anchorIndex = index;
                        return false;
                    }
                });
            }

            if (anchorIndex < 0 || currentIndex < 0) {
                selectIconCard($card);
                lastSelectedIconId = $card.data('icon-id') || '';
                resetBulkDeleteConfirmation();
                updateBulkSelectionControls();
                return;
            }

            const startIndex = Math.min(anchorIndex, currentIndex);
            const endIndex = Math.max(anchorIndex, currentIndex);

            $cards.slice(startIndex, endIndex + 1).each(function () {
                selectIconCard($(this));
            });

            resetBulkDeleteConfirmation();
            updateBulkSelectionControls();
        }

        function getPickerSelectedCard() {
            if (!pickerSelectedIconId) {
                return $();
            }

            return $page.find('.icon-card').filter(function () {
                return $(this).data('icon-id') === pickerSelectedIconId;
            }).first();
        }

        function updatePickerControls() {
            if (!isPicker) {
                return;
            }

            const $selected = getPickerSelectedCard();
            const hasSelection = Boolean(pickerSelectedIconId && $selected.length);

            $pickerChooseButton.prop('disabled', !hasSelection);
            $pickerEditButton.prop('disabled', !hasSelection || isCardLocked($selected));
        }

        function setPickerSelection($card) {
            if (!isPicker) {
                return;
            }

            const iconId = $card.data('icon-id');

            if (!iconId) {
                return;
            }

            pickerSelectedIconId = iconId;
            $page.attr('data-selected-icon-id', iconId).data('selected-icon-id', iconId);
            $page
                .find('.icon-card')
                .removeClass('is-selected')
                .attr('aria-pressed', 'false');
            $card.addClass('is-selected').attr('aria-pressed', 'true');
            updatePickerControls();
        }

        function clearPickerSelection() {
            if (!isPicker) {
                return;
            }

            pickerSelectedIconId = '';
            $page.attr('data-selected-icon-id', '').data('selected-icon-id', '');
            $page
                .find('.icon-card')
                .removeClass('is-selected')
                .attr('aria-pressed', 'false');
            updatePickerControls();
        }

        function choosePickerIcon() {
            const $card = getPickerSelectedCard();

            if (!$card.length) {
                showNotice(getMessage('pickIconFirst', 'Choose an icon first.'), 'error');
                return;
            }

            $page.trigger('iconPicked', [{
                id: $card.data('icon-id'),
                display_id: $card.data('icon-display-id') || getIconDisplayId($card.data('icon-id')),
                viewBox: $card.data('icon-viewbox') || ''
            }]);
        }

        function editPickerIcon() {
            const $card = getPickerSelectedCard();

            if (!$card.length) {
                showNotice(getMessage('pickIconFirst', 'Choose an icon first.'), 'error');
                return;
            }

            openModal($card);
        }

        function setModalPreviewViewBox(rawViewBox) {
            const viewBox = String(rawViewBox || '').trim().split(/[\s,]+/).map(Number);

            if (viewBox.length === 4 && viewBox[2] > 0 && viewBox[3] > 0) {
                $modalPreview.css('--icon-preview-ratio', viewBox[2] / viewBox[3]);
            } else {
                $modalPreview.css('--icon-preview-ratio', 2);
            }
        }

        function buildSvgFromSymbol(symbol, fallbackViewBox) {
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            const viewBox = symbol.getAttribute('viewBox') || fallbackViewBox || '';
            const presentationAttributes = [
                'fill',
                'stroke',
                'stroke-width',
                'stroke-linecap',
                'stroke-linejoin',
                'stroke-miterlimit',
                'fill-rule',
                'clip-rule',
                'opacity',
                'fill-opacity',
                'stroke-opacity',
                'color',
                'preserveAspectRatio'
            ];

            if (viewBox) {
                svg.setAttribute('viewBox', viewBox);
            }

            presentationAttributes.forEach(function (attribute) {
                if (symbol.hasAttribute(attribute)) {
                    svg.setAttribute(attribute, symbol.getAttribute(attribute));
                }
            });

            Array.prototype.slice.call(symbol.childNodes).forEach(function (child) {
                svg.appendChild(document.importNode(child, true));
            });

            return svg;
        }

        function renderModalIconPreview() {
            const iconId = activeIconId;

            if (!iconId) {
                return;
            }

            // locked (theme default) icons keep the simple <use> preview set in openModal;
            // their symbol lives in the theme sprite and has no editable transforms
            if (activeIconLocked) {
                return;
            }

            getSpriteDocument(iconId).done(function (spriteDocument) {
                const symbol = spriteDocument.getElementById(iconId);
                let previewViewBox = activeIconViewBox;

                if (!symbol || iconId !== activeIconId) {
                    return;
                }

                const previewSvg = buildSvgFromSymbol(symbol, activeIconViewBox);

                previewSvg.setAttribute('preserveAspectRatio', 'xMidYMid meet');

                if ($modalFitViewboxOption.is(':checked')) {
                    const bounds = getSvgArtworkBounds(buildSvgFromSymbol(symbol, activeIconViewBox));

                    if (bounds) {
                        previewViewBox = bounds;
                        previewSvg.setAttribute('viewBox', bounds);
                    }
                }

                if ($modalCurrentColorOption.is(':checked')) {
                    applyCurrentColorPreview(previewSvg);
                }

                pendingIconViewBox = previewViewBox;
                setModalPreviewViewBox(previewViewBox);
                $modalPreviewIcon.empty().append(previewSvg);
            }).fail(function () {
                $modalPreviewIcon.html('<svg aria-hidden="true" focusable="false"><use href="' + getIconHref(iconId) + '"></use></svg>');
            });
        }

        function openModal($card) {
            activeCard = $card;
            activeIconId = $card.data('icon-id');
            activeIconLocked = isCardLocked($card);
            const rawViewBox = String($card.data('icon-viewbox') || '').trim();

            activeIconViewBox = rawViewBox;
            pendingIconViewBox = rawViewBox;
            $input.val($card.data('icon-display-id') || getIconDisplayId(activeIconId));
            $modalFitViewboxOption.prop('checked', false);
            $modalCurrentColorOption.prop('checked', false);

            $modal.toggleClass('is-locked', activeIconLocked);
            $input.prop('disabled', activeIconLocked).prop('readonly', activeIconLocked);
            $modalTitle.text(activeIconLocked ? getMessage('viewTitle', 'Icon') : getMessage('editTitle', 'Edit icon'));

            setModalPreviewViewBox(rawViewBox);
            $modalPreviewIcon.html('<svg aria-hidden="true" focusable="false"><use href="' + getIconHref(activeIconId) + '"></use></svg>');
            renderModalIconPreview();

            if (!rawViewBox) {
                inferIconViewBox(activeIconId, function (inferredViewBox) {
                    const inferredParts = inferredViewBox.split(/[\s,]+/).map(Number);

                    if (activeIconId !== $card.data('icon-id') || inferredParts.length !== 4 || inferredParts[2] <= 0 || inferredParts[3] <= 0) {
                        return;
                    }

                    activeIconViewBox = inferredViewBox;
                    pendingIconViewBox = inferredViewBox;
                    setModalPreviewViewBox(inferredViewBox);
                    renderModalIconPreview();
                });
            }
            setMessage('');

            $modal.addClass('is-open').attr('aria-hidden', 'false');

            if (!activeIconLocked) {
                window.setTimeout(function () {
                    $input.trigger('focus').trigger('select');
                }, 50);
            }
        }

        function closeModal() {
            $modal.removeClass('is-open').attr('aria-hidden', 'true');
            $modalPreviewIcon.empty();
            activeIconId = '';
            activeIconLocked = false;
            activeIconViewBox = '';
            pendingIconViewBox = '';
            activeCard = null;
            resetDeleteConfirmation();
            setMessage('');
        }

        function setImportMessage(message, type) {
            $importMessage
                .removeClass('is-error is-success')
                .addClass(type ? 'is-' + type : '')
                .text(message || '');
        }

        function closeImportModal() {
            $importModal.removeClass('is-open').attr('aria-hidden', 'true');
            pendingSvgContent = '';
            pendingSvgSourceName = '';
            pendingImportAnalysis = null;
            pendingImportOptions = {};
            $importSummary.text('');
            $importInvalid.text('');
            $importPreviewIcon.empty();
            $importPreview.prop('hidden', true).attr('hidden', 'hidden');
            $importOptions.prop('hidden', true).attr('hidden', 'hidden');
            $fitViewboxOption.prop('checked', true);
            $currentColorOption.prop('checked', false);
            setImportMessage('');
            $confirmImportButton.prop('disabled', false);
        }

        function sanitizeSvgForPreview(svg) {
            $(svg)
                .find('script, foreignObject, iframe, object, embed')
                .remove();

            $(svg).find('*').addBack().each(function () {
                Array.prototype.slice.call(this.attributes || []).forEach(function (attribute) {
                    const name = attribute.name.toLowerCase();
                    const value = String(attribute.value || '').trim().toLowerCase();

                    if (name.indexOf('on') === 0 || ((name === 'href' || name === 'xlink:href') && value.indexOf('javascript:') === 0)) {
                        this.removeAttribute(attribute.name);
                    }
                }, this);
            });

            return svg;
        }

        function getPreviewSvg() {
            const document = new window.DOMParser().parseFromString(pendingSvgContent, 'image/svg+xml');
            const svg = document.documentElement;

            if (!svg || svg.nodeName.toLowerCase() !== 'svg') {
                return null;
            }

            const previewSvg = sanitizeSvgForPreview(document.importNode(svg, true));
            previewSvg.removeAttribute('width');
            previewSvg.removeAttribute('height');
            previewSvg.setAttribute('preserveAspectRatio', 'xMidYMid meet');

            return previewSvg;
        }

        function getSvgStrokePadding(svg) {
            let padding = 0;

            $(svg).find('*').each(function () {
                const styles = window.getComputedStyle(this);
                const stroke = styles.stroke || this.getAttribute('stroke') || '';
                const strokeWidth = parseFloat(styles.strokeWidth || this.getAttribute('stroke-width') || '0');

                if (!strokeWidth || stroke === 'none' || stroke === 'transparent' || styles.strokeOpacity === '0') {
                    return;
                }

                padding = Math.max(padding, strokeWidth / 2);
            });

            return padding ? padding + 0.01 : 0;
        }

        function removeInvisibleSvgArtwork(svg) {
            $(svg).find('path, circle, ellipse, rect, polygon, polyline, line, text, tspan').each(function () {
                const styles = window.getComputedStyle(this);
                const fill = styles.fill || this.getAttribute('fill') || '';
                const stroke = styles.stroke || this.getAttribute('stroke') || '';
                const opacity = parseFloat(styles.opacity || this.getAttribute('opacity') || '1');
                const fillOpacity = parseFloat(styles.fillOpacity || this.getAttribute('fill-opacity') || '1');
                const strokeOpacity = parseFloat(styles.strokeOpacity || this.getAttribute('stroke-opacity') || '1');
                const hasFill = fill && fill !== 'none' && fill !== 'transparent' && fillOpacity !== 0;
                const hasStroke = stroke && stroke !== 'none' && stroke !== 'transparent' && strokeOpacity !== 0;

                if (styles.display === 'none' || styles.visibility === 'hidden' || opacity === 0 || (!hasFill && !hasStroke)) {
                    this.remove();
                }
            });
        }

        function getSvgArtworkBounds(svg) {
            const holder = document.createElement('div');
            const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            let bounds = null;

            while (svg.firstChild) {
                group.appendChild(svg.firstChild);
            }

            svg.appendChild(group);
            holder.style.cssText = 'position:absolute;left:-99999px;top:-99999px;width:0;height:0;overflow:hidden;';
            holder.appendChild(svg);
            document.body.appendChild(holder);

            try {
                removeInvisibleSvgArtwork(svg);
                const bbox = group.getBBox();
                const strokePadding = getSvgStrokePadding(svg);

                if (bbox && bbox.width > 0 && bbox.height > 0) {
                    bounds = [
                        bbox.x - strokePadding,
                        bbox.y - strokePadding,
                        bbox.width + (strokePadding * 2),
                        bbox.height + (strokePadding * 2)
                    ].map(function (value) {
                        return parseFloat(Number(value).toFixed(3));
                    }).join(' ');
                }
            } catch (error) {
                bounds = null;
            }

            holder.remove();

            return bounds;
        }

        function applyCurrentColorPreview(svg) {
            $(svg).find('path, circle, ellipse, rect, polygon, polyline, text, tspan').each(function () {
                const $element = $(this);
                const styles = window.getComputedStyle(this);
                const stroke = styles.stroke || $element.attr('stroke');
                const fill = styles.fill || $element.attr('fill');

                if (stroke && stroke !== 'none' && stroke !== 'transparent' && styles.strokeOpacity !== '0') {
                    return;
                }

                if (fill && fill !== 'none' && fill !== 'transparent' && styles.fillOpacity !== '0') {
                    $element.attr('fill', 'currentColor');
                }
            });
        }

        function getViewBoxAspectRatio(rawViewBox) {
            const viewBox = String(rawViewBox || '').trim().split(/[\s,]+/).map(Number);

            if (viewBox.length === 4 && viewBox[2] > 0 && viewBox[3] > 0) {
                return viewBox[2] / viewBox[3];
            }

            return 1;
        }

        function getSvgAspectRatio(svg) {
            return getViewBoxAspectRatio(svg.getAttribute('viewBox'));
        }

        function updateSingleIconImportOptions() {
            const svg = getPreviewSvg();
            const previewSvg = getPreviewSvg();
            let previewRatio = 1;
            let viewBox = '';

            if (!svg || !previewSvg || !pendingImportAnalysis || pendingImportAnalysis.type !== 'icon') {
                pendingImportOptions = {};
                return;
            }

            previewRatio = getSvgAspectRatio(previewSvg);

            if ($fitViewboxOption.is(':checked')) {
                viewBox = getSvgArtworkBounds(svg) || '';

                if (viewBox) {
                    previewRatio = getViewBoxAspectRatio(viewBox);
                    previewSvg.setAttribute('viewBox', viewBox);
                }
            }

            $importPreview.css('--icon-preview-ratio', previewRatio);

            if ($currentColorOption.is(':checked')) {
                applyCurrentColorPreview(previewSvg);
            }

            $importPreviewIcon.empty().append(previewSvg);
            pendingImportOptions = {
                fit_viewbox: $fitViewboxOption.is(':checked'),
                current_color: $currentColorOption.is(':checked'),
                viewBox: viewBox
            };
        }

        function openImportModal(analysis) {
            const stats = analysis.stats || {};
            const icon = analysis.icons && analysis.icons[0] ? analysis.icons[0] : {};
            const summary = analysis.type === 'icon'
                ? formatMessage(
                    stats.invalid
                        ? getMessage('importIconInvalid', 'This SVG icon does not contain visible artwork.')
                        : stats.add
                            ? getMessage('importIconSummary', 'This SVG icon will be imported as "%s".')
                            : getMessage('importIconConflict', 'Icon "%s" already exists and will be skipped.'),
                    [icon.display_id || 'icon']
                )
                : formatMessage(
                    getMessage('importSummary', 'This sprite contains %1$s icons. %2$s will be added and %3$s skipped because of name conflicts.'),
                    [stats.total || 0, stats.add || 0, stats.conflict || 0]
                );

            pendingImportAnalysis = analysis;
            $importSummary.text(summary);
            $importPreviewIcon.empty();
            $importPreview.prop('hidden', true).attr('hidden', 'hidden');
            $importOptions.prop('hidden', true).attr('hidden', 'hidden');

            if (stats.invalid) {
                $importInvalid.text(formatMessage(getMessage('importInvalid', '%s icons have invalid names and will be skipped.'), [stats.invalid]));
            } else {
                $importInvalid.text('');
            }

            $confirmImportButton.prop('disabled', !stats.add);

            if (analysis.type === 'icon') {
                $importPreview.prop('hidden', false).removeAttr('hidden');
                $importOptions.prop('hidden', false).removeAttr('hidden');
                updateSingleIconImportOptions();
            } else {
                pendingImportOptions = {};
            }

            setImportMessage('');
            $importModal.addClass('is-open').attr('aria-hidden', 'false');
        }

        function analyzeSvgImport(svgContent, sourceName) {
            pendingSvgContent = svgContent;
            pendingSvgSourceName = sourceName || '';
            setImportMessage('');

            $.post(window.managedIcons.ajaxUrl, {
                action: 'analyze_managed_icons_import',
                nonce: window.managedIcons.nonce,
                svg_content: svgContent,
                source_name: pendingSvgSourceName
            }).done(function (response) {
                if (!response || !response.success) {
                    showNotice(getMessage('importFailed', 'SVG file could not be imported.'), 'error');
                    return;
                }

                openImportModal(response.data);
            }).fail(function (xhr) {
                const response = xhr.responseJSON;
                const message = response && response.data && response.data.message ? response.data.message : getMessage('importFailed', 'SVG file could not be imported.');
                showNotice(message, 'error');
            });
        }

        function handleSvgFile(file) {
            const isSvg = file && (file.type === 'image/svg+xml' || /\.svg$/i.test(file.name || ''));

            if (!isSvg) {
                showNotice(getMessage('svgOnly', 'Only SVG files are allowed.'), 'error');
                return;
            }

            const reader = new FileReader();

            reader.onload = function () {
                analyzeSvgImport(String(reader.result || ''), file.name || '');
            };

            reader.onerror = function () {
                showNotice(getMessage('readFailed', 'SVG file could not be read.'), 'error');
            };

            reader.readAsText(file);
        }

        function isFileDrag(e) {
            const dataTransfer = e.originalEvent.dataTransfer;
            const types = dataTransfer && dataTransfer.types ? Array.prototype.slice.call(dataTransfer.types) : [];

            return types.indexOf('Files') !== -1;
        }

        function importPendingSprite() {
            if (!pendingSvgContent || !pendingImportAnalysis) {
                return;
            }

            $confirmImportButton.prop('disabled', true);
            setImportMessage('');

            $.post(window.managedIcons.ajaxUrl, {
                action: 'import_managed_icons_sprite',
                nonce: window.managedIcons.nonce,
                svg_content: pendingSvgContent,
                source_name: pendingSvgSourceName,
                import_options: JSON.stringify(pendingImportOptions)
            }).done(function (response) {
                if (!response || !response.success) {
                    setImportMessage(getMessage('importFailed', 'SVG file could not be imported.'), 'error');
                    return;
                }

                if (response.data.spriteUrl) {
                    window.managedIcons.spriteUrl = response.data.spriteUrl;
                    $grid.attr('data-sprite-url', response.data.spriteUrl);
                    spriteDocuments = {};
                }

                ensureIconsGrid();

                (response.data.icons || []).forEach(function (icon) {
                    $grid.append(renderIconCard(icon));
                });

                const message = response.data.message || getMessage('imported', 'SVG sprite imported.');
                closeImportModal();
                showNotice(message, 'success');
            }).fail(function (xhr) {
                const response = xhr.responseJSON;
                const message = response && response.data && response.data.message ? response.data.message : getMessage('importFailed', 'SVG file could not be imported.');
                setImportMessage(message, 'error');
            }).always(function () {
                $confirmImportButton.prop('disabled', false);
            });
        }

        function resetDeleteConfirmation() {
            window.clearInterval(deleteCountdownTimer);
            deleteCountdownTimer = null;
            deleteCountdown = 0;
            $deleteButton
                .removeClass('is-confirming-delete')
                .prop('disabled', false)
                .attr('aria-label', getMessage('delete', 'Delete'))
                .attr('title', getMessage('delete', 'Delete'))
                .html('<span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="screen-reader-text">' + getMessage('delete', 'Delete') + '</span>');
        }

        function startDeleteConfirmation() {
            deleteCountdown = 3;
            const label = getMessage('confirmDelete', 'Confirm delete') + ' (' + deleteCountdown + ')';

            $deleteButton
                .addClass('is-confirming-delete')
                .attr('aria-label', label)
                .attr('title', label)
                .html('<span class="dashicons dashicons-yes" aria-hidden="true"></span><span class="screen-reader-text">' + label + '</span>');

            deleteCountdownTimer = window.setInterval(function () {
                deleteCountdown -= 1;

                if (deleteCountdown <= 0) {
                    resetDeleteConfirmation();
                    return;
                }

                const label = getMessage('confirmDelete', 'Confirm delete') + ' (' + deleteCountdown + ')';
                $deleteButton
                    .attr('aria-label', label)
                    .attr('title', label)
                    .find('.screen-reader-text')
                    .text(label);
            }, 1000);
        }

        function updateCard(iconId, viewBox) {
            const oldIconId = activeIconId;
            const displayIconId = getIconDisplayId(iconId);
            const iconViewBox = typeof viewBox === 'string' ? viewBox : activeCard.data('icon-viewbox') || '';

            activeCard
                .attr('data-icon-id', iconId)
                .data('icon-id', iconId)
                .attr('data-icon-display-id', displayIconId)
                .data('icon-display-id', displayIconId)
                .attr('data-icon-viewbox', iconViewBox)
                .data('icon-viewbox', iconViewBox)
                .find('.icon-card__title')
                .text(displayIconId);

            activeCard.find('use').attr('href', getIconHref(iconId));
            setIconCardViewBox(activeCard, iconViewBox);
            activeIconId = iconId;
            activeIconViewBox = iconViewBox;
            pendingIconViewBox = iconViewBox;

            if (isPicker && pickerSelectedIconId === oldIconId) {
                pickerSelectedIconId = iconId;
                $page.trigger('iconRenamed', [{
                    oldId: oldIconId,
                    id: iconId,
                    display_id: displayIconId,
                    viewBox: iconViewBox
                }]);
            }
        }

        function saveIconId() {
            if (activeIconLocked) {
                return;
            }

            const newIconId = normalizeIconId($input.val());
            const validationError = validateIconId(newIconId);

            if (validationError) {
                setMessage(validationError, 'error');
                return;
            }

            $input.val(getIconDisplayId(newIconId));

            if (newIconId === activeIconId && !$modalFitViewboxOption.is(':checked') && !$modalCurrentColorOption.is(':checked')) {
                closeModal();
                return;
            }

            $saveButton.prop('disabled', true);
            setMessage('');

            $.post(window.managedIcons.ajaxUrl, {
                action: 'rename_managed_icon',
                nonce: window.managedIcons.nonce,
                old_id: activeIconId,
                new_id: newIconId,
                fit_viewbox: $modalFitViewboxOption.is(':checked') ? 1 : 0,
                current_color: $modalCurrentColorOption.is(':checked') ? 1 : 0,
                viewBox: pendingIconViewBox
            }).done(function (response) {
                if (!response || !response.success) {
                    setMessage(getMessage('updateFailed', 'Icon name was not updated.'), 'error');
                    return;
                }

                if (response.data.spriteUrl) {
                    window.managedIcons.spriteUrl = response.data.spriteUrl;
                    $grid.attr('data-sprite-url', response.data.spriteUrl);
                    spriteDocuments = {};
                }

                updateCard(response.data.id, response.data.viewBox || pendingIconViewBox);
                closeModal();
                showNotice(response.data.message || getMessage('updated', 'Icon name updated.'), 'success');
            }).fail(function (xhr) {
                const response = xhr.responseJSON;
                const message = response && response.data && response.data.message ? response.data.message : getMessage('updateFailed', 'Icon name was not updated.');
                setMessage(message, 'error');
            }).always(function () {
                $saveButton.prop('disabled', false);
            });
        }

        function getSpriteDocument(iconId) {
            const url = spriteUrlFor(iconId);

            if (!spriteDocuments[url]) {
                spriteDocuments[url] = $.ajax({
                    url: url,
                    dataType: 'text'
                }).then(function (svg) {
                    return new window.DOMParser().parseFromString(svg, 'image/svg+xml');
                });
            }

            return spriteDocuments[url];
        }

        function downloadIcon() {
            const iconId = activeIconId;

            if (!iconId) {
                return;
            }

            $downloadButton.prop('disabled', true);
            setMessage('');

            getSpriteDocument(iconId).done(function (spriteDocument) {
                const symbol = spriteDocument.getElementById(iconId);

                if (!symbol) {
                    setMessage(getMessage('iconNotFound', 'Icon was not found in the sprite.'), 'error');
                    return;
                }

                const viewBox = symbol.getAttribute('viewBox') || activeCard.data('icon-viewbox') || '';
                const viewBoxAttribute = viewBox ? ' viewBox="' + viewBox + '"' : '';
                const svg = '<svg xmlns="http://www.w3.org/2000/svg"' + viewBoxAttribute + '>' + symbol.innerHTML + '</svg>';
                const blob = new Blob([svg], {type: 'image/svg+xml'});
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');

                link.href = url;
                link.download = getIconDisplayId(iconId) + '.svg';
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            }).fail(function () {
                setMessage(getMessage('downloadFailed', 'SVG icon could not be downloaded.'), 'error');
            }).always(function () {
                $downloadButton.prop('disabled', false);
            });
        }

        function deleteIcon() {
            if (!activeIconId || activeIconLocked) {
                return;
            }

            if (!$deleteButton.hasClass('is-confirming-delete')) {
                startDeleteConfirmation();
                return;
            }

            window.clearInterval(deleteCountdownTimer);
            deleteCountdownTimer = null;
            $deleteButton.prop('disabled', true);
            setMessage('');

            $.post(window.managedIcons.ajaxUrl, {
                action: 'delete_managed_icon',
                nonce: window.managedIcons.nonce,
                icon_id: activeIconId
            }).done(function (response) {
                if (!response || !response.success) {
                    setMessage(getMessage('deleteFailed', 'Icon was not deleted.'), 'error');
                    return;
                }

                if (response.data.spriteUrl) {
                    window.managedIcons.spriteUrl = response.data.spriteUrl;
                    $grid.attr('data-sprite-url', response.data.spriteUrl);
                    spriteDocuments = {};
                }

                const message = response.data && response.data.message ? response.data.message : 'Icon deleted.';
                const deletedIconId = activeIconId;
                activeCard.remove();
                if (isPicker && pickerSelectedIconId === deletedIconId) {
                    clearPickerSelection();
                    $page.trigger('iconDeleted', [{
                        id: deletedIconId
                    }]);
                }
                showEmptyState();
                closeModal();
                showNotice(message, 'success');
            }).fail(function (xhr) {
                const response = xhr.responseJSON;
                const message = response && response.data && response.data.message ? response.data.message : getMessage('deleteFailed', 'Icon was not deleted.');
                setMessage(message, 'error');
            }).always(function () {
                $deleteButton.prop('disabled', false);
            });
        }

        function deleteSelectedIcons() {
            if (!selectedIconIds.length) {
                showNotice(getMessage('noIconsSelected', 'No icons selected.'), 'error');
                return;
            }

            if (!$deleteSelectedButton.hasClass('is-confirming-delete')) {
                startBulkDeleteConfirmation();
                return;
            }

            window.clearInterval(bulkDeleteCountdownTimer);
            bulkDeleteCountdownTimer = null;
            $deleteSelectedButton.prop('disabled', true);

            $.post(window.managedIcons.ajaxUrl, {
                action: 'delete_managed_icons',
                nonce: window.managedIcons.nonce,
                icon_ids: selectedIconIds
            }).done(function (response) {
                if (!response || !response.success) {
                    showNotice(getMessage('bulkDeleteFailed', 'Selected icons were not deleted.'), 'error');
                    return;
                }

                if (response.data.spriteUrl) {
                    window.managedIcons.spriteUrl = response.data.spriteUrl;
                    $grid.attr('data-sprite-url', response.data.spriteUrl);
                    spriteDocuments = {};
                }

                (response.data.deletedIds || []).forEach(function (iconId) {
                    $page
                        .find('.icon-card')
                        .filter(function () {
                            return $(this).data('icon-id') === iconId;
                        })
                        .remove();
                });

                const deletedSelectedIconId = pickerSelectedIconId;

                if (isPicker && (response.data.deletedIds || []).indexOf(deletedSelectedIconId) !== -1) {
                    clearPickerSelection();
                    $page.trigger('iconDeleted', [{
                        id: deletedSelectedIconId
                    }]);
                }

                setBulkSelectionMode(false);
                showEmptyState();
                showNotice(response.data.message || getMessage('bulkDeleted', 'Selected icons deleted.'), 'success');
            }).fail(function (xhr) {
                const response = xhr.responseJSON;
                const message = response && response.data && response.data.message ? response.data.message : getMessage('bulkDeleteFailed', 'Selected icons were not deleted.');
                showNotice(message, 'error');
            }).always(function () {
                updateBulkSelectionControls();
            });
        }

        $input.on('input', function () {
            const normalizedIconId = normalizeIconId($(this).val());
            const validationError = validateIconId(normalizedIconId);

            if (validationError && $(this).val().trim()) {
                setMessage(validationError, 'error');
                return;
            }

            setMessage('');
        });

        $page.on('click', '.icon-card', function (e) {
            if (isBulkSelecting) {
                if (isCardLocked($(this))) {
                    return;
                }

                if (e.shiftKey) {
                    selectIconRange($(this));
                } else {
                    toggleIconSelection($(this));
                    lastSelectedIconId = $(this).data('icon-id') || '';
                }

                return;
            }

            if (isPicker) {
                setPickerSelection($(this));
                return;
            }

            openModal($(this));
        });

        $pickerChooseButton.on('click', choosePickerIcon);

        $pickerEditButton.on('click', editPickerIcon);

        $uploadButton.on('click', function () {
            $fileInput.trigger('click');
        });

        $bulkDeleteButton.on('click', function () {
            setBulkSelectionMode(true);
        });

        $cancelBulkSelectionButton.on('click', function () {
            setBulkSelectionMode(false);
        });

        $deleteSelectedButton.on('click', deleteSelectedIcons);

        $fileInput.on('change', function () {
            if (this.files && this.files[0]) {
                handleSvgFile(this.files[0]);
            }

            this.value = '';
        });

        $page.on('click', '.notice-dismiss', function () {
            $(this).closest('.notice').remove();
        });

        $page.on('click', '[data-icons-modal-close]', closeModal);

        $page.on('click', '[data-icons-import-modal-close]', closeImportModal);

        $confirmImportButton.on('click', importPendingSprite);

        $fitViewboxOption.on('change', updateSingleIconImportOptions);

        $currentColorOption.on('change', updateSingleIconImportOptions);

        $modalFitViewboxOption.on('change', renderModalIconPreview);

        $modalCurrentColorOption.on('change', renderModalIconPreview);

        $saveButton.on('click', saveIconId);

        $downloadButton.on('click', downloadIcon);

        $deleteButton.on('click', deleteIcon);

        $input.on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveIconId();
            }
        });

        $(document).on('keydown.managedIcons', function (e) {
            if (e.key === 'Escape' && $modal.hasClass('is-open')) {
                closeModal();
            }

            if (e.key === 'Escape' && $importModal.hasClass('is-open')) {
                closeImportModal();
            }
        });

        $(document).on('dragenter.managedIcons', function (e) {
            if (!isFileDrag(e)) {
                return;
            }

            dragDepth += 1;
            $page.addClass('is-drag-over');
        });

        $(document).on('dragover.managedIcons', function (e) {
            if (!isFileDrag(e)) {
                return;
            }

            e.preventDefault();
        });

        $(document).on('dragleave.managedIcons', function () {
            dragDepth = Math.max(0, dragDepth - 1);

            if (!dragDepth) {
                $page.removeClass('is-drag-over');
            }
        });

        $(document).on('drop.managedIcons', function (e) {
            const files = e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null;

            if (!files || !files.length) {
                return;
            }

            e.preventDefault();
            dragDepth = 0;
            $page.removeClass('is-drag-over');
            handleSvgFile(files[0]);
        });

        updatePickerControls();
        inferMissingIconCardViewBoxes();

    }

    window.initIconsManager = initIconsManager;

    $(document).ready(function () {
        $('.icons-page').each(function () {
            initIconsManager(this);
        });
    });
})(jQuery);
