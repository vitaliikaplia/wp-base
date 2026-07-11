/**
 * Strip pattern metadata from inserted blocks (WP 7.0+)
 *
 * WordPress 7.0 tags every block inserted from a pattern with
 * `metadata.patternName` (plus the pattern `categories` / `name`). That makes
 * the List View and the inspector label it a "pattern" and — before our
 * `disableContentOnlyForUnsyncedPatterns` setting — gated editing behind a
 * detach step. We only use patterns as a shortcut to drop in ready-made blocks,
 * never as synced/overridable patterns, so remove that metadata the moment it
 * appears: the inserted blocks then behave as ordinary blocks. A
 * `wp_insert_post_data` filter does the same server-side as a backstop.
 *
 * Self-contained: it waits for the block-editor store rather than declaring
 * script dependencies, so it stays inert on non-editor admin pages.
 */
(function () {

    function setup() {
        if (!window.wp || !wp.data || !wp.data.select || !wp.data.dispatch) {
            return false;
        }

        var sel = wp.data.select('core/block-editor');
        var dis = wp.data.dispatch('core/block-editor');
        if (!sel || !dis || typeof sel.getBlocks !== 'function') {
            return false;
        }

        var busy = false;

        function collect(blocks, out) {
            for (var i = 0; i < blocks.length; i++) {
                var b = blocks[i];
                if (b.attributes && b.attributes.metadata && b.attributes.metadata.patternName) {
                    out.push(b);
                }
                if (b.innerBlocks && b.innerBlocks.length) {
                    collect(b.innerBlocks, out);
                }
            }
            return out;
        }

        var listener = function () {
            if (busy) { return; }
            var hits = collect(sel.getBlocks(), []);
            if (!hits.length) { return; }

            busy = true;
            try {
                hits.forEach(function (b) {
                    // keep any unrelated metadata (e.g. block bindings); drop only
                    // the pattern-injected keys so the block reads as a plain block
                    var md = Object.assign({}, b.attributes.metadata);
                    delete md.patternName;
                    delete md.categories;
                    delete md.name;
                    dis.updateBlockAttributes(b.clientId, {
                        metadata: Object.keys(md).length ? md : undefined
                    });
                });
            } catch (e) {}
            busy = false;
        };

        // scope the subscription to the block-editor store (WP 6.6+) to avoid
        // firing on every unrelated store change
        try {
            wp.data.subscribe(listener, 'core/block-editor');
        } catch (e) {
            wp.data.subscribe(listener);
        }
        return true;
    }

    if (setup()) { return; }

    // editor stores load asynchronously — retry briefly until they exist
    var tries = 0;
    var timer = setInterval(function () {
        if (setup() || ++tries > 50) {
            clearInterval(timer);
        }
    }, 200);

}());
