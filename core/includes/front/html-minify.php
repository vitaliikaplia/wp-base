<?php

if(!defined('ABSPATH')){exit;}

$minify_mode = get_option('minify_mode', 'disabled');

if(
    $minify_mode && $minify_mode !== 'disabled'
    && !is_admin()
    && !wp_is_json_request()
    && !wp_doing_ajax()
    && !(defined('DOING_CRON') && DOING_CRON)
    && !(defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)
){

    function html_minify_callback($html){
        if(empty($html)){
            return $html;
        }

        // Skip non-HTML responses (file downloads, streams, etc.)
        foreach(headers_list() as $header){
            if(stripos($header, 'content-type:') === 0 && stripos($header, 'text/html') === false){
                return $html;
            }
            // Skip file downloads
            if(stripos($header, 'content-disposition:') === 0){
                return $html;
            }
        }

        // Skip if content doesn't look like HTML
        $trimmed = ltrim($html);
        if(empty($trimmed) || $trimmed[0] !== '<'){
            return $html;
        }

        $mode = get_option('minify_mode', 'disabled');

        if($mode === 'basic'){
            return html_minify_basic($html);
        } elseif($mode === 'full'){
            return html_minify_full($html);
        }

        return $html;
    }

    function html_minify_basic($html){
        // Remove leading whitespace (indentation) from each line
        $html = preg_replace('/^[ \t]+/m', '', $html);
        // Remove empty lines
        $html = preg_replace('/\n\s*\n/', "\n", $html);
        return $html;
    }

    function html_minify_full($html){
        $compressor = new FLHM_HTML_Compression($html);
        return $compressor->__toString();
    }

    class FLHM_HTML_Compression
    {
        protected $flhm_compress_css = true;
        protected $flhm_compress_js = false;
        protected $flhm_info_comment;

        protected $flhm_remove_comments = true;
        protected $html;

        public function __construct($html)
        {
            $this->flhm_info_comment = (bool) get_option('minify_show_comment');
            if(!empty($html)){
                $this->flhm_parseHTML($html);
            }
        }

        public function __toString()
        {
            return $this->html;
        }

        protected function flhm_bottomComment($raw, $compressed)
        {
            $raw = strlen($raw);
            $compressed = strlen($compressed);
            $savings = ($raw - $compressed) / $raw * 100;
            $savings = round($savings, 2);
            return '<!--HTML compressed, size saved '.$savings.'%. From '.$raw.' bytes, now '.$compressed.' bytes-->';
        }

        protected function flhm_minifyHTML($html)
        {
            $pattern = '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si';
            preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
            $overriding = false;
            $raw_tag = false;
            $html = '';

            foreach($matches as $token){
                $tag = (isset($token['tag'])) ? strtolower($token['tag']) : null;
                $content = $token[0];

                if(is_null($tag)){
                    if(!empty($token['script'])){
                        $strip = $this->flhm_compress_js;
                    } elseif(!empty($token['style'])){
                        $strip = $this->flhm_compress_css;
                    } elseif($content == '<!--wp-html-compression no compression-->'){
                        $overriding = !$overriding;
                        continue;
                    } elseif($this->flhm_remove_comments){
                        if(!$overriding && $raw_tag != 'textarea'){
                            $content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);
                        }
                    }
                } else {
                    if($tag == 'pre' || $tag == 'textarea'){
                        $raw_tag = $tag;
                    } elseif($tag == '/pre' || $tag == '/textarea'){
                        $raw_tag = false;
                    } else {
                        if($raw_tag || $overriding){
                            $strip = false;
                        } else {
                            $strip = true;
                            $content = preg_replace('/(\s+)(\w++(?<!\baction|\balt|\bcontent|\bsrc)="")/', '$1', $content);
                            $content = str_replace(' />', '/>', $content);
                        }
                    }
                }

                if($strip){
                    $content = $this->flhm_removeWhiteSpace($content);
                }

                $html .= $content;
            }

            return $html;
        }

        public function flhm_parseHTML($html)
        {
            $this->html = $this->flhm_minifyHTML($html);
            $this->html = $this->flhm_removeBlockSpaces($this->html);
            if($this->flhm_info_comment){
                $this->html .= "\n" . $this->flhm_bottomComment($html, $this->html);
            }
        }

        protected function flhm_removeBlockSpaces($html)
        {
            $block = 'html|head|body|div|section|article|aside|nav|header|footer|main|figure|figcaption|'
                . 'form|fieldset|table|thead|tbody|tfoot|tr|th|td|col|colgroup|caption|'
                . 'ul|ol|li|dl|dt|dd|p|h[1-6]|blockquote|pre|hr|br|'
                . 'link|meta|script|style|noscript|template|'
                . 'details|summary|address|picture|source|'
                . 'svg|path|circle|rect|line|polyline|polygon|g|defs|use|symbol|clipPath|'
                . 'video|audio|canvas|iframe|map|area|'
                . '!doctype|!DOCTYPE';

            // Remove whitespace between > and block-level tags (opening and closing)
            $html = preg_replace('/>\s+<(\/?(?:' . $block . ')[\s>\/])/i', '><$1', $html);
            // Remove whitespace between block closing tag and <
            $html = preg_replace('/(<\/(?:' . $block . ')>)\s+</i', '$1<', $html);

            return $html;
        }

        protected function flhm_removeWhiteSpace($str)
        {
            $str = str_replace("\t", ' ', $str);
            $str = str_replace("\n", ' ', $str);
            $str = str_replace("\r", ' ', $str);
            while(stristr($str, '  ')){
                $str = str_replace('  ', ' ', $str);
            }
            return $str;
        }
    }

    add_action('init', function(){
        ob_start('html_minify_callback');
    });

}
