//@prepros-prepend plugins/select2.min.js

/**
 * document ready
 */
(function ($) {
    $(document).ready(function () {

        /**
         * custom options tabs
         */
        if($('.custom-options-form').length){
            $(window).trigger("custom-options-form");
            $('.custom-options-form').on("change", function(e){
                $(window).trigger("custom-options-form");
            });
            $('.custom-settings-page-tabs a').on("click", function(e){
                e.preventDefault();
                let targetTab = $(this).attr('href').replace('#', '');
                $('.custom-settings-page-tabs a').removeClass('nav-tab-active');
                $('.form-table-options').hide();
                $('#for_tab_' + targetTab).show();
                $(this).addClass('nav-tab-active');
                const url = new URL(window.location);
                url.searchParams.set('current_tab', targetTab);
                window.history.pushState({}, '', url);
                $('input[name="_wp_http_referer"]').val(url.pathname + url.search);
            });
            const urlParams = new URLSearchParams(window.location.search);
            const currentTab = urlParams.get('current_tab');
            if(currentTab){
                $('.custom-settings-page-tabs a[href="#' + currentTab + '"]').trigger('click');
            }
        }

        /**
         * tweaks multi select
         */
        if($('.custom-options-select-multiple').length){
            $('.custom-options-select-multiple').each(function(){
                $(this).select2({
                    closeOnSelect: false,
                    width: '100%'
                });
            });
        }

        /**
         * code fields
         */
        if($('.custom-options-code').length){
            $('.custom-options-code').each(function(){
                let editorSettings = wp.codeEditor.defaultSettings ? _.clone( wp.codeEditor.defaultSettings ) : {};
                wp.codeEditor.initialize( $(this), editorSettings );
            });
        }

        /**
         * range
         */
        if($('.custom-options-range').length){
            $('.custom-options-range').each(function(){
                const thisEl = $(this);
                const thisName = thisEl.attr('name');
                const inputEl = thisEl.parent().find('.val_'+thisName+'_display');
                thisEl.on('input change', function() {
                    inputEl.val(thisEl.val());
                });
                inputEl.on('input', function() {
                    thisEl.val($(this).val());
                });
                inputEl.on('blur', function() {
                    inputEl.val(thisEl.val());
                });
            });
        }

        /**
         * color picker
         */
        if($('.custom-options-color').length){
            $('.custom-options-color').wpColorPicker();
        }

        /**
         * link field
         */
        if($('.custom-link-field').length){
            let currentLinkField = null;
            let isSubmitting = false;

            // Update field UI helper
            function updateLinkFieldUI($field, url, title, target) {
                const $button = $field.find('.custom-link-button');
                const fieldId = $button.attr('id');
                const $linkWrap = $field.find('.link-wrap');

                // Update hidden inputs
                $field.find('#' + fieldId + '_url').val(url);
                $field.find('#' + fieldId + '_title').val(title);
                $field.find('#' + fieldId + '_target').val(target);

                // Update UI
                if (url) {
                    $linkWrap.find('.link-title').text(title || url);
                    $linkWrap.find('.link-url').attr('href', url).text(url);
                    $field.addClass('has-value');
                    $field.toggleClass('has-target', target === '_blank');
                } else {
                    $linkWrap.find('.link-title').text('');
                    $linkWrap.find('.link-url').attr('href', '').text('');
                    $field.removeClass('has-value has-target');
                }
            }

            $('.custom-link-field').each(function(){
                const $field = $(this);
                const $button = $field.find('.custom-link-button');
                const $remove = $field.find('.custom-link-remove');

                // Open wpLink modal
                $button.on('click', function(e) {
                    e.preventDefault();
                    currentLinkField = $field;
                    isSubmitting = false;

                    const fieldId = $button.attr('id');
                    const $inputHtml = $field.find('input[id$="_html"]');
                    const currentUrl = $field.find('#' + fieldId + '_url').val();
                    const currentTitle = $field.find('#' + fieldId + '_title').val();
                    const currentTarget = $field.find('#' + fieldId + '_target').val();

                    wpLink.open($inputHtml.attr('id'));

                    // Pre-fill with current values after modal opens
                    setTimeout(function() {
                        $('#wp-link-url').val(currentUrl || '');
                        $('#wp-link-text').val(currentTitle || '');
                        $('#wp-link-target').prop('checked', currentTarget === '_blank');
                    }, 100);
                });

                // Remove link
                $remove.on('click', function(e) {
                    e.preventDefault();
                    updateLinkFieldUI($field, '', '', '');
                });
            });

            // Capture submit click - get values BEFORE wpLink processes them
            $(document).on('click.customLinkField', '#wp-link-submit', function() {
                if (currentLinkField) {
                    isSubmitting = true;
                    const url = $('#wp-link-url').val() || '';
                    const title = $('#wp-link-text').val() || '';
                    const target = $('#wp-link-target').prop('checked') ? '_blank' : '';

                    updateLinkFieldUI(currentLinkField, url, title, target);
                    currentLinkField = null;
                }
            });

            // Handle wpLink cancel
            $(document).on('click', '#wp-link-cancel, #wp-link-close', function() {
                if (!isSubmitting) {
                    currentLinkField = null;
                }
            });
        }

    });
})(jQuery);

/**
 * custom-options-form
 */
(function ($) {
    $(window).on("custom-options-form", function () {

        if($('.custom-options-form tr[data-conditional-logic="true"]').length){
            $('.custom-options-form tr[data-conditional-logic="true"]').each(function(){
                let thisElement = $(this);
                let action = thisElement.attr('data-conditional-logic-action');
                let rules = JSON.parse(thisElement.attr('data-conditional-logic-rules'));
                const OP = {
                    '==': (a,b)=>a==b, '!=':(a,b)=>a!=b, '>':(a,b)=>(+a)>(+b), '<':(a,b)=>(+a)<(+b),
                    '>=':(a,b)=>(+a)>=(+b), '<=':(a,b)=>(+a)<=(+b),
                    contains:(a,b)=>String(a).includes(String(b)),
                    in:(a,b)=>{const A=Array.isArray(a)?a:[a];const B=Array.isArray(b)?b:String(b).split(',').map(s=>s.trim());return A.some(v=>B.includes(String(v)));},
                    not_in:(a,b)=>{const A=Array.isArray(a)?a:[a];const B=Array.isArray(b)?b:String(b).split(',').map(s=>s.trim());return !A.some(v=>B.includes(String(v)));},
                    empty:(a)=>Array.isArray(a)?a.length===0:(a===undefined||a===null||String(a).trim()===''),
                    not_empty:(a)=>!OP.empty(a)
                };
                const getEl = (n)=>{let $e=$('[name="'+n+'"]'); if(!$e.length)$e=$('#'+n); return $e;};
                const getVal = ($e)=>{
                    if(!$e.length) return '';
                    const t=($e.attr('type')||'').toLowerCase(), tag=($e.prop('tagName')||'').toLowerCase();
                    if(t==='checkbox') return $e.is(':checked')?'1':'0';
                    if(t==='radio'){const n=$e.attr('name'); return $('input[type="radio"][name="'+n+'"]:checked').val()||'';}
                    if(tag==='select') return $e.prop('multiple')?($e.val()||[]).map(String):String($e.val()||'');
                    return String($e.val()||'');
                };
                const evalRules = (r)=>{
                    if(!r) return true;
                    let list=r, rel='AND';
                    if(!Array.isArray(r) && r.rules){rel=String(r.relation||'AND').toUpperCase()==='OR'?'OR':'AND'; list=r.rules;}
                    const res=list.map(rule=>{
                        const fn=OP[(rule.operator||'==').toLowerCase()]||OP['=='];
                        return fn(getVal(getEl(rule.field)), rule.value);
                    });
                    return rel==='OR' ? res.some(Boolean) : res.every(Boolean);
                };
                const passed = evalRules(rules);
                const show = (String(action||'show').toLowerCase()==='show') ? passed : !passed;
                thisElement.toggle(!!show);
            });
        }

    });
})(jQuery);
