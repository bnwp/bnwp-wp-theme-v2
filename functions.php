<?php
/**
 * BNWP WikiConnect v2 — theme functions.
 *
 * No framework, no build step, no CDN. Plain PHP + CSS custom properties +
 * vanilla JS. The content model (post types, taxonomy, meta keys) is kept
 * byte-compatible with v1 so no migration is needed.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BNWP_VERSION', '2.0.0');
define('BNWP_LANGS', 'bn,en');


/* -------------------------------------------------------------------------
 * 1. Theme setup
 * ---------------------------------------------------------------------- */

function bnwp_setup() {
    load_theme_textdomain('bnwp', get_template_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ));
    add_theme_support('custom-logo', array(
        'height' => 120, 'width' => 360, 'flex-height' => true, 'flex-width' => true,
    ));

    add_image_size('bnwp-card', 720, 420, true);
    add_image_size('bnwp-avatar', 320, 320, true);

    register_nav_menus(array(
        'primary'    => __('Primary Menu', 'bnwp'),
        'primary_en' => __('Primary Menu (English override — optional)', 'bnwp'),
        'footer'     => __('Footer Menu', 'bnwp'),
    ));
}
add_action('after_setup_theme', 'bnwp_setup');

function bnwp_content_width() {
    $GLOBALS['content_width'] = 760;
}
add_action('after_setup_theme', 'bnwp_content_width', 0);


/* -------------------------------------------------------------------------
 * 2. Bilingual layer  (?lang=en + _bnwp_language meta, carried over from v1)
 * ---------------------------------------------------------------------- */

function bnwp_langs() {
    return explode(',', BNWP_LANGS);
}

/**
 * The language being viewed.
 *
 * One record now carries both languages, so this depends only on the URL —
 * it no longer has to infer a language from the post being shown.
 */
function bnwp_current_language() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    // set by bnwp_parse_language_prefix() from the /en/ path segment
    if (!empty($GLOBALS['bnwp_path_lang'])) {
        return $cached = $GLOBALS['bnwp_path_lang'];
    }

    // legacy ?lang=en — still honoured so old links render, then redirected
    if (isset($_GET['lang'])) {
        $requested = sanitize_key(wp_unslash($_GET['lang']));
        if (in_array($requested, bnwp_langs(), true)) {
            return $cached = $requested;
        }
    }

    return $cached = 'bn';
}

function bnwp_is_en() {
    return bnwp_current_language() === 'en';
}

/** Pick one of two strings for the current language. */
function bnwp_text($bengali, $english) {
    return bnwp_is_en() ? $english : $bengali;
}

function bnwp_site_name() {
    return bnwp_is_en() ? 'Bangla WikiConnect' : get_bloginfo('name');
}

function bnwp_locale() {
    return bnwp_is_en() ? 'en_US' : 'bn_BD';
}

function bnwp_html_lang() {
    return bnwp_is_en() ? 'en' : 'bn';
}

/** Force the <html lang> attribute to the view language, not the WP site locale. */
function bnwp_language_attributes($output) {
    return 'lang="' . esc_attr(bnwp_html_lang()) . '"';
}
add_filter('language_attributes', 'bnwp_language_attributes');

function bnwp_document_title_parts($title) {
    $archive = bnwp_archive_title_text();
    if ($archive !== '') {
        $title['title'] = $archive;
        unset($title['tagline']);
    }
    if (bnwp_is_en() && isset($title['site']) && $title['site'] === get_bloginfo('name')) {
        $title['site'] = bnwp_site_name();
    }
    return $title;
}
add_filter('document_title_parts', 'bnwp_document_title_parts');

/** Append/strip ?lang=en on a URL. */
/** The subdirectory WordPress is installed in: "/intrepid", or "" at the root. */
function bnwp_home_path() {
    static $path = null;
    if ($path === null) {
        $parts = wp_parse_url(home_url('/'));
        $path  = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
    }
    return $path;
}

/**
 * Put a URL into a language.
 *
 * English lives under /en/ rather than ?lang=en. Google's documentation lists
 * URL parameters as "not recommended" for multilingual sites, and a path
 * segment is also what readers expect to be able to share. Any existing /en/
 * prefix and any legacy ?lang= are stripped first, so this is safe to apply
 * more than once to the same URL.
 */
function bnwp_lang_arg($url, $lang = null) {
    $lang  = $lang ? $lang : bnwp_current_language();
    $parts = wp_parse_url($url);

    if (!is_array($parts)) {
        return $url;
    }

    $origin = isset($parts['host'])
        ? (isset($parts['scheme']) ? $parts['scheme'] : 'https') . '://' . $parts['host']
          . (isset($parts['port']) ? ':' . $parts['port'] : '')
        : '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

    $query = '';
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $q);
        unset($q['lang']);                       // drop the legacy parameter
        $query = $q ? '?' . http_build_query($q) : '';
    }

    $base = bnwp_home_path();
    $rel  = isset($parts['path']) ? $parts['path'] : '/';
    if ($base !== '' && strpos($rel, $base) === 0) {
        $rel = substr($rel, strlen($base));
    }
    $rel = '/' . ltrim($rel, '/');

    if ($rel === '/en' || $rel === '/en/') {
        $rel = '/';
    } elseif (strpos($rel, '/en/') === 0) {
        $rel = substr($rel, 3);
    }

    if ($lang === 'en') {
        $rel = '/en' . ($rel === '/' ? '/' : $rel);
    }

    return $origin . $base . $rel . $query . $fragment;
}

/**
 * Read the language out of the path before WordPress parses the request, then
 * hand WordPress the URL without it. Nothing else in the theme has to know
 * that /en/ exists — routing, canonical redirects and templates all see the
 * ordinary address.
 */
function bnwp_parse_language_prefix($do_parse, $wp = null, $extra = null) {
    $uri  = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
    $path = strtok($uri, '?');
    $qs   = strpos($uri, '?') !== false ? substr($uri, strpos($uri, '?')) : '';

    $base = bnwp_home_path();
    $rel  = $path;
    if ($base !== '' && strpos($rel, $base) === 0) {
        $rel = substr($rel, strlen($base));
    }
    $rel = '/' . ltrim($rel, '/');

    if ($rel === '/en' || $rel === '/en/') {
        $GLOBALS['bnwp_path_lang'] = 'en';
        $_SERVER['REQUEST_URI'] = ($base === '' ? '/' : $base . '/') . $qs;
    } elseif (strpos($rel, '/en/') === 0) {
        $GLOBALS['bnwp_path_lang'] = 'en';
        $_SERVER['REQUEST_URI'] = $base . substr($rel, 3) . $qs;
    }

    return $do_parse;
}
add_filter('do_parse_request', 'bnwp_parse_language_prefix', 1, 3);

/** Every generated link inherits the current language. */
function bnwp_localise_link($url) {
    if (is_admin() || !bnwp_is_en()) {
        return $url;
    }
    return bnwp_lang_arg($url, 'en');
}
foreach (array(
    'post_link', 'page_link', 'post_type_link', 'attachment_link',
    'term_link', 'post_type_archive_link', 'get_pagenum_link',
    'year_link', 'month_link', 'day_link',
) as $bnwp_link_filter) {
    add_filter($bnwp_link_filter, 'bnwp_localise_link', 20);
}
unset($bnwp_link_filter);

/** Send the old ?lang=en addresses to their /en/ equivalent, once. */
function bnwp_redirect_legacy_lang() {
    if (is_admin() || wp_doing_ajax() || !isset($_GET['lang'])) {
        return;
    }

    $lang = sanitize_key(wp_unslash($_GET['lang']));
    if (!in_array($lang, bnwp_langs(), true)) {
        return;
    }

    // REQUEST_URI already carries any subdirectory, so take only scheme and
    // host from home_url() rather than concatenating two paths.
    $home   = wp_parse_url(home_url('/'));
    $origin = (isset($home['scheme']) ? $home['scheme'] : 'https') . '://'
            . (isset($home['host']) ? $home['host'] : '')
            . (isset($home['port']) ? ':' . $home['port'] : '');

    $uri     = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
    $current = $origin . $uri;
    $target  = bnwp_lang_arg($current, $lang);

    if ($target && $target !== $current) {
        wp_safe_redirect($target, 301);
        exit;
    }
}
add_action('template_redirect', 'bnwp_redirect_legacy_lang', 1);

/** A page by slug, in the requested language. One page serves both. */
function bnwp_page_url($base_slug, $lang = null) {
    $lang = $lang ? $lang : bnwp_current_language();
    $page = get_page_by_path($base_slug, OBJECT, 'page');
    $url  = $page ? get_permalink($page) : home_url('/' . trim($base_slug, '/') . '/');

    return bnwp_lang_arg($url, $lang);
}

/** The equivalent of the current view in the other language. */
/**
 * The same view in the other language.
 *
 * One record serves both languages, so this is now just the current URL with
 * the language flag flipped. It can no longer fail to find a twin, which is
 * what used to drop readers back onto the homepage.
 */
function bnwp_translation_url($target) {
    $target = $target === 'en' ? 'en' : 'bn';

    $request = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
    $path    = strtok($request, '?');

    // REQUEST_URI already contains any subdirectory the site lives in, so take
    // only the scheme and host from home_url() — passing the path through it
    // would double the prefix (/intrepid/intrepid/...).
    $home    = wp_parse_url(home_url('/'));
    $origin  = (isset($home['scheme']) ? $home['scheme'] : 'https') . '://'
             . (isset($home['host']) ? $home['host'] : '')
             . (isset($home['port']) ? ':' . $home['port'] : '');
    $current = $origin . $path;

    $query = array();
    if (!empty($_SERVER['QUERY_STRING'])) {
        parse_str(wp_unslash($_SERVER['QUERY_STRING']), $query);
    }
    unset($query['lang']);

    if ($query) {
        $current = add_query_arg(array_map('sanitize_text_field', $query), $current);
    }

    return bnwp_lang_arg($current, $target);
}

/** The meta clause that limits a listing to the current language. */
/* -------------------------------------------------------------------------
 * 2b. Paired English fields
 *
 * A post carries both languages: the native title, body and excerpt hold the
 * Bengali, and matching _bnwp_*_en fields hold the English. Reading happens
 * through three filters, so templates keep calling the_title(), the_content()
 * and the ordinary meta helpers.
 *
 * An empty English field falls back to the Bengali, so a half-translated
 * record is never a blank page.
 * ---------------------------------------------------------------------- */

/** Meta suffixed "_en" when viewing English, otherwise the plain key. */
function bnwp_get_meta_i18n($key, $post_id = null, $default = '') {
    $post_id = $post_id ? $post_id : get_the_ID();

    if (bnwp_is_en()) {
        $english = get_post_meta($post_id, $key . '_en', true);
        if ($english !== '') {
            return $english;
        }
    }
    return bnwp_get_meta($key, $post_id, $default);
}

/** Does this record have any English at all? Used by the admin column. */
function bnwp_has_english($post_id) {
    foreach (array('_bnwp_title_en', '_bnwp_body_en') as $key) {
        if (trim((string) get_post_meta($post_id, $key, true)) !== '') {
            return true;
        }
    }
    return false;
}

function bnwp_filter_title($title, $post_id = null) {
    if (!bnwp_is_en() || is_admin() || !$post_id) {
        return $title;
    }
    $english = get_post_meta($post_id, '_bnwp_title_en', true);
    return $english !== '' ? $english : $title;
}
add_filter('the_title', 'bnwp_filter_title', 10, 2);

function bnwp_filter_single_title($title) {
    if (!bnwp_is_en() || is_admin() || !is_singular()) {
        return $title;
    }
    $english = get_post_meta(get_queried_object_id(), '_bnwp_title_en', true);
    return $english !== '' ? $english : $title;
}
add_filter('single_post_title', 'bnwp_filter_single_title');

function bnwp_filter_content($content) {
    if (!bnwp_is_en() || is_admin()) {
        return $content;
    }

    $post_id = get_the_ID();
    if (!$post_id) {
        return $content;
    }

    $english = get_post_meta($post_id, '_bnwp_body_en', true);
    if (trim((string) $english) === '') {
        return $content;
    }

    // Run the usual formatting by hand rather than re-entering the_content.
    return wpautop(do_shortcode(wp_kses_post($english)));
}
add_filter('the_content', 'bnwp_filter_content', 9);

function bnwp_filter_excerpt($excerpt, $post = null) {
    if (!bnwp_is_en() || is_admin()) {
        return $excerpt;
    }

    $post_id = $post instanceof WP_Post ? $post->ID : get_the_ID();
    $english = get_post_meta($post_id, '_bnwp_excerpt_en', true);
    if ($english !== '') {
        return $english;
    }

    // No English excerpt, but an English body: summarise that instead of
    // handing back a Bengali excerpt on an English page.
    $body = get_post_meta($post_id, '_bnwp_body_en', true);
    if (trim((string) $body) !== '') {
        return wp_trim_words(wp_strip_all_tags($body), 40, '…');
    }

    return $excerpt;
}
add_filter('get_the_excerpt', 'bnwp_filter_excerpt', 10, 2);

/**
 * Menu links, fixed in one place.
 *
 * The site's menu was built with root-relative custom links ("/about/",
 * "/posts"). Those break on any install that is not at the domain root — a
 * subdirectory staging clone sends every click back to production — and they
 * bypass the language layer. Rewriting them through home_url() here fixes
 * every menu item at once, without editing them one by one in the admin.
 */
function bnwp_nav_link_attr($atts, $item, $args, $depth) {
    if (empty($atts['href'])) {
        return $atts;
    }

    $href = $atts['href'];

    // "/about/" -> home_url('/about/'). Leaves "//host", "http(s)://" and "#"
    // alone. A bare "/" is the Home item and has to be handled on its own, or
    // the length guard skips it and it never picks up the /en/ prefix.
    if ($href === '/') {
        $href = home_url('/');
    } elseif (strlen($href) > 1 && $href[0] === '/' && $href[1] !== '/') {
        $href = home_url($href);
    }

    if (bnwp_is_en() && strpos($href, home_url()) === 0) {
        $href = bnwp_lang_arg($href, 'en');
    }

    $atts['href'] = $href;

    $atts['class'] = trim((isset($atts['class']) ? $atts['class'] : '') . ' nav__link');

    return $atts;
}
add_filter('nav_menu_link_attributes', 'bnwp_nav_link_attr', 10, 4);


/* -------------------------------------------------------------------------
 * 3. Images
 *
 * Team photos and project logos are stored as external Wikimedia Commons
 * URLs. v1 emitted them at original resolution — two of them were 4.3 MB
 * files rendered into 138 px avatars. bnwp_img_url() rewrites any Commons
 * URL to its thumbnail form, which is what actually fixes the page weight.
 * ---------------------------------------------------------------------- */

function bnwp_avatar_placeholder() {
    return get_template_directory_uri() . '/assets/uploads/avatar-placeholder.svg';
}

/**
 * Commons serves thumbnails only at a fixed set of widths; anything else is
 * a 400, which renders as a broken image. Snap up to the next allowed size.
 * Verified against upload.wikimedia.org — the set is global, not per file.
 */
function bnwp_commons_width($width) {
    $allowed = array(120, 250, 500, 960, 1280, 1920);
    $width   = max(1, (int) $width);

    foreach ($allowed as $size) {
        if ($size >= $width) {
            return $size;
        }
    }
    return end($allowed);
}

/**
 * Rewrite a Wikimedia Commons file URL to a width-constrained thumbnail.
 * Returns other URLs untouched.
 */
function bnwp_commons_thumb($url, $width = 480) {
    if (strpos($url, 'upload.wikimedia.org') === false) {
        return $url;
    }

    $width = bnwp_commons_width($width);

    // Already a thumbnail: swap the width prefix on the last segment.
    if (strpos($url, '/thumb/') !== false) {
        return preg_replace('#/\d+px-([^/]+)$#', '/' . $width . 'px-$1', $url);
    }

    // Original: /wikipedia/commons/a/ab/Name.jpg
    //        -> /wikipedia/commons/thumb/a/ab/Name.jpg/<w>px-Name.jpg
    if (!preg_match('#^(https?://upload\.wikimedia\.org/wikipedia/[^/]+)/([0-9a-f])/([0-9a-f]{2})/(.+)$#i', $url, $m)) {
        return $url;
    }

    $file = $m[4];
    $name = $file;

    // SVG and multi-page formats render to PNG/JPG thumbnails.
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'svg') {
        $name .= '.png';
    } elseif (in_array($ext, array('pdf', 'djvu', 'tif', 'tiff'), true)) {
        $name = 'page1-' . $width . 'px-' . $file . '.jpg';
        return $m[1] . '/thumb/' . $m[2] . '/' . $m[3] . '/' . $file . '/' . $name;
    }

    return $m[1] . '/thumb/' . $m[2] . '/' . $m[3] . '/' . $file . '/' . $width . 'px-' . $name;
}

/** Validate a stored image URL and size it down where possible. */
/**
 * Accept any way of naming a Commons file and return its upload URL.
 *
 * Handles a Commons file page (commons.wikimedia.org/wiki/File:X), a bare
 * "File:X" title, and the underlying upload.wikimedia.org URL. MediaWiki
 * stores a file at /{md5[0]}/{md5[0..1]}/{name}, so the path is computable
 * from the name alone — no API call needed.
 */
function bnwp_commons_url($input) {
    $input = trim((string) $input);

    if ($input === '' || strpos($input, 'upload.wikimedia.org') !== false) {
        return $input;
    }

    if (preg_match('#commons\.wikimedia\.org/.*?(?:File|Image)(?::|%3A)(.+)$#iu', $input, $m)) {
        $title = $m[1];                       // a Commons file page URL
    } elseif (preg_match('#^(?:File|Image):(.+)$#iu', $input, $m)) {
        $title = $m[1];                       // a bare "File:Name.jpg" title
    } else {
        return $input;                        // an ordinary URL or path
    }

    $title = explode('#', explode('?', $title)[0])[0];
    $title = str_replace(' ', '_', trim(rawurldecode($title)));
    if ($title === '') {
        return $input;
    }

    $hash = md5($title);
    return 'https://upload.wikimedia.org/wikipedia/commons/'
        . $hash[0] . '/' . substr($hash, 0, 2) . '/' . $title;
}

/**
 * Clean a media reference, which may be a URL *or* a Commons file title.
 *
 * esc_url_raw() discards "File:Name.jpg" outright, because "file" is not an
 * allowed protocol — so running it over these fields silently emptied every
 * Commons title typed into the editor, while the identical value written over
 * the REST API survived. bnwp_commons_url() accepts both forms; so must the
 * saving. Keep this in step with the patterns it matches.
 */
function bnwp_sanitize_media_ref($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^(?:File|Image):#iu', $value)) {
        return sanitize_text_field($value);
    }
    return esc_url_raw($value);
}

/** Meta keys holding an image: a Media Library URL or a Commons file title. */
function bnwp_media_meta_keys() {
    return array('_bnwp_logo', '_bnwp_img');
}

/** Percent-encode a URL path so non-Latin filenames survive intact. */
function bnwp_encode_url($url) {
    $parts = wp_parse_url($url);
    if (empty($parts['path'])) {
        return $url;
    }

    $path = implode('/', array_map(function ($segment) {
        return rawurlencode(rawurldecode($segment));
    }, explode('/', $parts['path'])));

    $out = (isset($parts['scheme']) ? $parts['scheme'] . '://' : '')
        . (isset($parts['host']) ? $parts['host'] : '') . $path;

    return empty($parts['query']) ? $out : $out . '?' . $parts['query'];
}

/** Attachment ID for a URL on this site, or 0. Cached — it is a DB lookup. */
function bnwp_attachment_id($url) {
    static $cache = array();

    if ($url === '' || isset($cache[$url])) {
        return isset($cache[$url]) ? $cache[$url] : 0;
    }
    if (strpos($url, home_url()) !== 0) {
        return $cache[$url] = 0;
    }
    return $cache[$url] = (int) attachment_url_to_postid($url);
}

/**
 * Validate a stored image reference and size it down where possible.
 */
function bnwp_img_url($url, $width = 480, $fallback = '') {
    $url = bnwp_commons_url(trim((string) $url));

    if ($url === '' || $url === '#') {
        return $fallback !== '' ? $fallback : bnwp_avatar_placeholder();
    }
    if (!preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
        return $fallback !== '' ? $fallback : bnwp_avatar_placeholder();
    }

    return bnwp_encode_url(bnwp_commons_thumb($url, $width));
}

/**
 * Echo a responsive <img> for an external/meta image.
 * Always emits width/height to reserve layout space (no CLS).
 */
function bnwp_image($url, $args = array()) {
    $a = wp_parse_args($args, array(
        'w'        => 480,
        'h'        => 0,
        'alt'      => '',
        'class'    => '',
        'loading'  => 'lazy',
        'sizes'    => '',
        'fallback' => '',
        'fit'      => 'cover',
    ));

    $resolved = bnwp_commons_url(trim((string) $url));

    /*
     * Media-library images: hand off to WordPress so it serves one of the
     * sizes it already generated, with a srcset. Emitting the original meant
     * the browser was downscaling a 960px logo into a 48px tile — a 20x
     * reduction, which is what made them look soft.
     */
    $attachment = bnwp_attachment_id($resolved);
    if ($attachment) {
        $attrs = array(
            'alt'      => $a['alt'],
            'decoding' => 'async',
            'style'    => 'object-fit:' . $a['fit'],
        );
        if ($a['class'] !== '') {
            $attrs['class'] = $a['class'];
        }
        if ($a['loading'] === 'eager') {
            $attrs['fetchpriority'] = 'high';
        } else {
            $attrs['loading'] = $a['loading'];
        }

        /*
         * A height means a box to fill — an avatar — and WordPress should
         * hand back one of its cropped square sizes. With no height the image
         * is a logo, and asking for array($w, $w) made WordPress serve the
         * hard-cropped 150x150 thumbnail: a wide logo came back with its ends
         * cut off. Named uncropped sizes keep the shape.
         */
        if ($a['h']) {
            $size = array((int) $a['w'], (int) $a['h']);
        } else {
            $size = (int) $a['w'] <= 300 ? 'medium' : 'large';
        }
        echo wp_get_attachment_image($attachment, $size, false, $attrs);
        return;
    }

    $src = bnwp_img_url($url, $a['w'], $a['fallback']);

    // 1x / 2x for Commons-hosted files, both snapped to a servable width.
    $srcset = '';
    if (strpos($src, 'upload.wikimedia.org') !== false) {
        $x2 = bnwp_encode_url(bnwp_commons_thumb(bnwp_commons_url($url), bnwp_commons_width($a['w']) * 2));
        if ($x2 !== $src) {
            $srcset = esc_url($src) . ' 1x, ' . esc_url($x2) . ' 2x';
        }
    }

    // External files can vanish — one of the team photos was deleted from
    // Commons — so fall back rather than leaving a broken image icon.
    $placeholder = $a['fallback'] !== '' ? $a['fallback'] : bnwp_avatar_placeholder();
    $onerror = '';
    if ($src !== $placeholder && strpos($src, home_url()) !== 0) {
        $onerror = sprintf(
            ' onerror="this.onerror=null;this.removeAttribute(\'srcset\');this.src=\'%s\'"',
            esc_url($placeholder)
        );
    }

    printf(
        '<img src="%1$s"%2$s width="%3$d"%4$s alt="%5$s" loading="%6$s" decoding="async"%7$s style="object-fit:%8$s"%9$s>',
        esc_url($src),
        $srcset ? ' srcset="' . $srcset . '"' : '',
        (int) $a['w'],
        $a['h'] ? ' height="' . (int) $a['h'] . '"' : '',
        esc_attr($a['alt']),
        esc_attr($a['loading']),
        $a['class'] ? ' class="' . esc_attr($a['class']) . '"' : '',
        esc_attr($a['fit']),
        $onerror
    );
}


/* -------------------------------------------------------------------------
 * 3b. The all-members listing lives at /teams/
 *
 * It used to answer at /persona/ while every team page answered at /teams/…,
 * which read as two unrelated things. Profiles keep /persona/<name>/: those
 * URLs are indexed, and a person is not a team.
 * ---------------------------------------------------------------------- */

function bnwp_teams_archive_rule() {
    add_rewrite_rule('^teams/?$', 'index.php?post_type=persona', 'top');
}
add_action('init', 'bnwp_teams_archive_rule');

/** Everything that links to the listing asks WordPress for it, so answer here. */
function bnwp_persona_archive_link($link, $post_type) {
    return $post_type === 'persona' ? home_url('/teams/') : $link;
}
add_filter('post_type_archive_link', 'bnwp_persona_archive_link', 10, 2);

/** The old address, kept working for anything already pointing at it. */
function bnwp_redirect_persona_archive() {
    if (is_feed() || !is_post_type_archive('persona')) {
        return;
    }
    $path = isset($_SERVER['REQUEST_URI'])
        ? (string) wp_parse_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])), PHP_URL_PATH)
        : '';
    if (strpos($path, '/persona') === false) {
        return;
    }
    wp_safe_redirect(bnwp_lang_arg(home_url('/teams/')), 301);
    exit;
}
add_action('template_redirect', 'bnwp_redirect_persona_archive', 2);

/**
 * Rewrite rules are cached in the database and only rebuilt on activation,
 * which uploading a new version of an already-active theme is not. Bump the
 * number below whenever a rule changes and the next page load fixes itself.
 */
function bnwp_maybe_flush_rewrites() {
    if (get_option('bnwp_rewrite_version') !== '2') {
        flush_rewrite_rules();
        update_option('bnwp_rewrite_version', '2');
    }
}
add_action('init', 'bnwp_maybe_flush_rewrites', 99);

/**
 * What a listing page is called, in the reader's language.
 *
 * Yoast names a taxonomy archive "<term> Archives" from the raw term, so the
 * English team pages carried Bengali titles, and every one of them carried a
 * word no reader needs.
 */
function bnwp_archive_title_text() {
    if (is_tax('team')) {
        $term = get_queried_object();
        return $term instanceof WP_Term ? bnwp_term_name($term) : '';
    }
    if (is_post_type_archive('persona')) {
        return bnwp_home_text('members_title');
    }
    if (is_post_type_archive('project')) {
        return bnwp_text('প্রকল্পসমূহ', 'Projects');
    }
    return '';
}


/* -------------------------------------------------------------------------
 * 4. Content types  (identical keys to v1 — do not rename)
 * ---------------------------------------------------------------------- */

/**
 * Every meta key the theme owns. `_bnwp_status` is new in v2; everything
 * else is inherited from v1 and must keep its exact name.
 */
function bnwp_meta_keys() {
    return array_merge(array(
        '_bnwp_language', '_bnwp_source_file', '_bnwp_logo', '_bnwp_wiki',
        '_bnwp_lead', '_bnwp_status', '_bnwp_name', '_bnwp_role', '_bnwp_username',
        '_bnwp_wiki_label', '_bnwp_wiki_label_en',
        '_bnwp_proj_lang', '_bnwp_proj_lang_en', '_bnwp_dates', '_bnwp_sortkey',
        '_bnwp_location', '_bnwp_email', '_bnwp_img', '_bnwp_bio', '_bnwp_user',
        '_bnwp_organisers', '_bnwp_jury', '_bnwp_link', '_bnwp_links',
        // the English half of each record
        '_bnwp_title_en', '_bnwp_body_en', '_bnwp_excerpt_en',
        '_bnwp_lead_en', '_bnwp_role_en', '_bnwp_bio_en', '_bnwp_location_en',
    ), bnwp_team_meta_keys());
}

/**
 * The role and order fields every team carries, one set each.
 *
 * They are generated from the teams themselves rather than listed by hand, so
 * a team added on the Teams screen gets its fields — in the admin box, over
 * the REST API and through the save handler — without a code change.
 */
function bnwp_team_meta_keys() {
    static $keys = null;
    if ($keys !== null) {
        return $keys;
    }
    if (!taxonomy_exists('team')) {
        return array();   // too early to ask; do not cache the empty answer
    }
    $keys = array();
    foreach (bnwp_base_teams() as $term) {
        $keys[] = '_bnwp_role_' . $term->slug;
        $keys[] = '_bnwp_role_' . $term->slug . '_en';
        $keys[] = '_bnwp_order_' . $term->slug;
    }
    return $keys;
}

/** Meta holding rich text, which must keep its markup through sanitising. */
function bnwp_richtext_meta_keys() {
    return array('_bnwp_body_en');
}

/** Meta holding several lines, which sanitize_text_field would collapse. */
function bnwp_multiline_meta_keys() {
    return array(
        '_bnwp_links', '_bnwp_organisers', '_bnwp_jury', '_bnwp_excerpt_en',
    );
}

/**
 * How a given meta key is cleaned. Shared by the REST registration and the
 * admin save handler so the two cannot drift apart - they did once, and every
 * English body on the site was flattened before anyone noticed.
 */
function bnwp_meta_sanitizer($key) {
    if (in_array($key, bnwp_richtext_meta_keys(), true)) {
        return 'wp_kses_post';
    }
    if (in_array($key, bnwp_multiline_meta_keys(), true)) {
        return 'sanitize_textarea_field';
    }
    if (in_array($key, bnwp_media_meta_keys(), true)) {
        return 'bnwp_sanitize_media_ref';
    }
    return 'sanitize_text_field';
}

/**
 * Teams in the order the site should present them.
 *
 * Alphabetical order is an accident of naming — it put Technical team before
 * Core team, and in Bengali it sorts differently again. Each team carries an
 * Order number instead, lowest first, with 0 meaning unranked and sorting
 * last, the same rule the Order box on a person follows.
 */
function bnwp_team_default_order() {
    // How the group presents itself, used for any team not given its own
    // number on the Teams screen. Unknown teams fall to the end.
    return array('cot' => 10, 'jury' => 20, 'technical' => 30, 'former' => 90);
}

function bnwp_team_order($term) {
    $term = is_object($term) ? $term : get_term((int) $term, 'team');
    if (!$term || is_wp_error($term)) {
        return PHP_INT_MAX;
    }
    $n = (int) get_term_meta($term->term_id, '_bnwp_order', true);
    if ($n > 0) {
        return $n;
    }
    $defaults = bnwp_team_default_order();
    if (isset($defaults[$term->slug])) {
        return $defaults[$term->slug];
    }
    // A former team follows the team it belongs to, but after every active
    // one, so the filter row reads current teams first and the past ones last.
    if (bnwp_team_is_former($term)) {
        $b = bnwp_team_base($term);
        return 1000 + (isset($defaults[$b]) ? $defaults[$b] : 900);
    }
    return PHP_INT_MAX;
}

/** Sort a list of team terms in place and hand it back. */
function bnwp_sort_teams($terms) {
    if (!is_array($terms) || is_wp_error($terms)) {
        return $terms;
    }
    usort($terms, function ($a, $b) {
        $oa = bnwp_team_order($a);
        $ob = bnwp_team_order($b);
        if ($oa !== $ob) {
            return $oa <=> $ob;
        }
        return strcoll(bnwp_term_name($a), bnwp_term_name($b));
    });
    return $terms;
}

/** Every team, ordered. The one place templates should ask. */
function bnwp_teams($hide_empty = true) {
    $terms = get_terms(array('taxonomy' => 'team', 'hide_empty' => (bool) $hide_empty));
    return is_wp_error($terms) ? array() : bnwp_sort_teams($terms);
}

/**
 * Leaving a team is recorded per team, as former-cot, former-jury and so on.
 *
 * A single flat "former" team threw away the only interesting part — which
 * team somebody left — and forced a person who had stepped down from one team
 * while staying on another to be either wholly past or wholly present. The
 * slug below is kept only as the address of the Former members tab, which
 * gathers the per-team ones under their own headings; nobody is filed under it.
 */
function bnwp_former_team() {
    return 'former';
}

/** The active team a former one belongs to: former-cot -> cot. */
function bnwp_team_base($team) {
    $slug = is_object($team) ? $team->slug : (string) $team;
    return strpos($slug, 'former-') === 0 ? substr($slug, 7) : $slug;
}

/** The former counterpart of a team: cot -> former-cot. */
function bnwp_former_slug($team) {
    return 'former-' . bnwp_team_base($team);
}

/** Is this team a past one — either a former-x team or the old flat one? */
function bnwp_team_is_former($team) {
    $slug = is_object($team) ? $team->slug : (string) $team;
    return $slug === bnwp_former_team() || strpos($slug, 'former-') === 0;
}

/** The teams people are actually filed under, ordered, past ones left out. */
function bnwp_base_teams() {
    $out = array();
    foreach (bnwp_teams(false) as $term) {
        if (!bnwp_team_is_former($term)) {
            $out[] = $term;
        }
    }
    return $out;
}

/** A team term by slug, or null. */
function bnwp_team_term($slug) {
    $term = get_term_by('slug', $slug, 'team');
    return $term instanceof WP_Term ? $term : null;
}

/** Somebody's teams, ordered. */
function bnwp_person_teams($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $terms   = get_the_terms($post_id, 'team');
    return is_array($terms) ? bnwp_sort_teams($terms) : array();
}

/**
 * Is this person wholly in the past — is every team they are in a former one?
 *
 * Somebody who has stepped down from one team but still sits on another is
 * current, and appears in the main listing under the team they are still on.
 */
function bnwp_is_former($post_id = null) {
    $teams = bnwp_person_teams($post_id ? $post_id : get_the_ID());
    if (!$teams) {
        return false;
    }
    foreach ($teams as $term) {
        if (!bnwp_team_is_former($term)) {
            return false;
        }
    }
    return true;
}

/**
 * What somebody does, in the context they are being shown in.
 *
 * One standing role could not describe the same person in two places — the
 * technical lead who is also an ordinary reviewer read as "Technical Lead" on
 * the reviewers page. Each team therefore carries its own role, with the
 * all-members role as the fallback, so a field left blank never leaves a card
 * bare. A former team reuses the role of the team it belongs to and prints it
 * as "Former …", which is the only place that word is added.
 */
function bnwp_person_role($post_id = null, $team = '') {
    $post_id = $post_id ? $post_id : get_the_ID();
    $slug    = is_object($team) ? $team->slug : (string) $team;

    $role = $slug === '' ? '' : bnwp_get_meta_i18n('_bnwp_role_' . bnwp_team_base($slug), $post_id);
    if ($role === '') {
        $role = bnwp_get_meta_i18n('_bnwp_role', $post_id);
    }
    if ($role !== '' && $slug !== '' && bnwp_team_is_former($slug)) {
        $role = bnwp_text('প্রাক্তন ', 'Former ') . $role;
    }
    return $role;
}

/**
 * Where somebody sits in a listing. Zero means unranked and sorts last, so
 * numbering one person is enough to pin them to the top and the rest can be
 * left alone. The all-members order is the Order box; each team may override
 * it with its own number.
 */
function bnwp_person_order($post_id = null, $team = '') {
    $post_id = $post_id ? $post_id : get_the_ID();
    $slug    = is_object($team) ? $team->slug : (string) $team;

    $n = $slug === '' ? 0 : (int) get_post_meta($post_id, '_bnwp_order_' . bnwp_team_base($slug), true);
    if ($n <= 0) {
        $n = (int) get_post_field('menu_order', $post_id);
    }
    return $n > 0 ? $n : PHP_INT_MAX;
}

/**
 * Everybody in a team, in that team's order; pass '' for everybody on the
 * site.
 *
 * Sorting happens here rather than in SQL because the number that decides it
 * can live in either the Order box or a per-team field, and there are a few
 * dozen people in total — not a number worth a join over.
 */
function bnwp_people($team = '') {
    $args = array(
        'post_type'      => 'persona',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );
    if ($team !== '') {
        $args['tax_query'] = array(array(
            'taxonomy' => 'team', 'field' => 'slug', 'terms' => $team,
        ));
    }
    $people = get_posts($args);
    usort($people, function ($a, $b) use ($team) {
        $oa = bnwp_person_order($a->ID, $team);
        $ob = bnwp_person_order($b->ID, $team);
        if ($oa !== $ob) {
            return $oa < $ob ? -1 : 1;
        }
        return strcoll(get_the_title($a->ID), get_the_title($b->ID));
    });
    return $people;
}

/** Everybody still on a team somewhere. */
function bnwp_active_people() {
    return array_values(array_filter(bnwp_people(), function ($p) {
        return !bnwp_is_former($p->ID);
    }));
}

/** Everybody whose every team is a past one. */
function bnwp_former_people() {
    return array_values(array_filter(bnwp_people(), function ($p) {
        return bnwp_is_former($p->ID);
    }));
}

/**
 * One person tile. Shared by the team listings and the front page so the two
 * cannot drift apart, and so the role always follows the team being shown.
 */
function bnwp_person_card($post, $team = '') {
    $id       = $post->ID;
    $username = (string) get_post_meta($id, '_bnwp_username', true);
    $role     = bnwp_person_role($id, $team);
    $external = bnwp_person_is_external($id);

    printf(
        '<a class="person reveal" href="%s"%s>',
        esc_url(bnwp_person_url($id)),
        $external ? ' rel="noopener"' : ''
    );
    bnwp_image(get_post_meta($id, '_bnwp_img', true), array(
        'w' => 176, 'h' => 176, 'class' => 'person__avatar', 'alt' => get_the_title($id),
    ));
    printf('<span class="person__name">%s</span>', esc_html(get_the_title($id)));
    if ($username !== '') {
        printf('<span class="person__handle">@%s</span>', esc_html($username));
    }
    if ($role !== '') {
        printf('<span class="person__role">%s</span>', esc_html($role));
    }
    if ($external) {
        bnwp_external_mark();
    }
    echo '</a>';
}

/** A grid of person tiles, or nothing at all when the team is empty. */
function bnwp_people_grid($people, $team = '') {
    if (!$people) {
        return;
    }
    echo '<div class="grid grid--4" data-stagger>';
    foreach ($people as $person) {
        bnwp_person_card($person, $team);
    }
    echo '</div>';
}

/** Find a team member by wiki username, falling back to the post slug. */
function bnwp_persona_by_key($key) {
    $key = trim((string) $key);
    if ($key === '') {
        return null;
    }
    // A profile page resolves every credit on the site to find its own, which
    // is two queries a name; the answer cannot change mid-request.
    static $cache = array();
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $found = get_posts(array(
        'post_type'      => 'persona',
        'posts_per_page' => 1,
        'post_status'    => array('publish', 'draft'),
        'meta_query'     => array(array('key' => '_bnwp_username', 'value' => $key, 'compare' => '=')),
    ));
    if ($found) {
        $cache[$key] = $found[0];
        return $cache[$key];
    }
    $found = get_posts(array(
        'post_type'      => 'persona',
        'posts_per_page' => 1,
        'post_status'    => array('publish', 'draft'),
        'name'           => sanitize_title($key),
    ));
    $cache[$key] = $found ? $found[0] : null;
    return $cache[$key];
}

/**
 * The people credited on a project: one per line, in the order written.
 *
 * A plain line is a wiki username, matched to a team member so their photo,
 * role and page come from the one record. A line containing a bar is somebody
 * with no record here — a guest juror invited to this contest — written out
 * in full:
 *
 *     Bengali name | English name | Bengali description | English description | Link | Photo
 *
 * Both kinds sit in the same field and render in the order given, so a panel
 * can mix the two freely. Commas still work on a plain line, which is how the
 * field used to be stored.
 */
function bnwp_people_entries($raw) {
    $entries = array();

    foreach (preg_split('/\r\n|\r|\n/', (string) $raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        if (strpos($line, '|') !== false) {
            $p = array_map('trim', explode('|', $line));

            // Which shape this line is, decided by whether the first field
            // names somebody on the site. A team member may be given a
            // description for this project alone, because what somebody does
            // on a contest is rarely what their team role says.
            $person = bnwp_persona_by_key($p[0]);
            if ($person) {
                $q = array_pad($p, 3, '');
                $entries[] = array(
                    'type' => 'persona',
                    'post' => $person,
                    'note' => bnwp_is_en() && $q[2] !== '' ? $q[2] : ($q[1] !== '' ? $q[1] : $q[2]),
                );
                continue;
            }

            $q    = array_pad($p, 6, '');
            $name = bnwp_is_en() && $q[1] !== '' ? $q[1] : ($q[0] !== '' ? $q[0] : $q[1]);
            $note = bnwp_is_en() && $q[3] !== '' ? $q[3] : ($q[2] !== '' ? $q[2] : $q[3]);
            if ($name === '') {
                continue;
            }
            $entries[] = array(
                'type' => 'guest',
                'name' => $name,
                'note' => $note,
                'url'  => filter_var($q[4], FILTER_VALIDATE_URL) ? $q[4] : '',
                'img'  => $q[5],
            );
            continue;
        }

        foreach (array_filter(array_map('trim', explode(',', $line))) as $key) {
            $person = bnwp_persona_by_key($key);
            $entries[] = $person
                ? array('type' => 'persona', 'post' => $person, 'note' => '')
                // An unmatched username is shown rather than dropped, so a
                // typo is visible on the page instead of silently missing.
                : array('type' => 'guest', 'name' => $key, 'note' => '', 'url' => '', 'img' => '');
        }
    }
    return $entries;
}

/**
 * The projects somebody is credited on, split by what they did there.
 *
 * Credits are written on the project, not on the person, so this is the only
 * way round: read every project's two credit fields and keep the ones naming
 * this person. There are a few dozen projects, and the name lookups behind
 * bnwp_people_entries() are remembered for the length of the request.
 */
function bnwp_person_projects($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $out     = array('organisers' => array(), 'jury' => array());

    $projects = get_posts(bnwp_project_order_args(array(
        'post_type'      => 'project',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
    )));
    foreach ($projects as $project) {
        foreach (array_keys($out) as $field) {
            foreach (bnwp_people_entries(get_post_meta($project->ID, '_bnwp_' . $field, true)) as $entry) {
                if ($entry['type'] === 'persona' && (int) $entry['post']->ID === (int) $post_id) {
                    $out[$field][] = $project;
                    break;
                }
            }
        }
    }
    return $out;
}

/** A compact list of projects, shaped like the people rows beside it. */
function bnwp_project_rows($projects) {
    if (!$projects) {
        return;
    }
    echo '<div class="peoplelist">';
    foreach ($projects as $project) {
        printf('<a class="peoplelist__row" href="%s">', esc_url(bnwp_lang_arg(get_permalink($project))));
        echo '<span class="peoplelist__logo logo-tile">';
        bnwp_image(get_post_meta($project->ID, '_bnwp_logo', true), array(
            'w' => 128, 'alt' => '', 'fit' => 'contain',
        ));
        echo '</span><span class="peoplelist__text">';
        printf('<span class="peoplelist__name">%s</span>', esc_html(get_the_title($project->ID)));
        $when = bnwp_project_dates($project->ID);
        if ($when !== '') {
            printf('<span class="peoplelist__role">%s</span>', esc_html($when));
        }
        echo '</span></a>';
    }
    echo '</div>';
}

/**
 * The wording on the front page.
 *
 * Every line is "Bengali | English", the same shape as the Impact, Partners
 * and Social rows, so one Customiser section holds both languages and nobody
 * has to touch a template to reword the home page. Leave a row empty and the
 * default below is used; leave the English half empty and it falls back to
 * the Bengali, exactly as the rest of the site does.
 */
function bnwp_home_defaults() {
    return array(
        'impact_title'   => array('এখন পর্যন্ত আমাদের অবদান', 'Our impact so far'),
        'eyebrow'        => array('মুক্ত জ্ঞান আন্দোলন · বাংলাদেশ', 'Free knowledge movement · Bangladesh'),
        'heading'        => array('বাংলা উইকিসংযোগ একটি সহযোগিতামূলক উদ্যোগ', 'Bangla WikiConnect is a collaborative initiative'),
        'lead'           => array(
            'বাংলা ভাষায় উইকিপিডিয়ার বিষয়বস্তু বৃদ্ধি এবং সম্প্রসারণের উপর আমরা দৃষ্টি নিবদ্ধ করি। বিভিন্ন আকর্ষণীয় প্রতিযোগিতা, সম্পাদনা-অ-থন এবং প্রশিক্ষণ কর্মসূচির মাধ্যমে উইকিপিডিয়া ও এর সহযোগী প্রকল্প — উইকিউক্তি, উইকিভ্রমণ, উইকিবই ও উইকিঅভিধানে উচ্চমানের, অন্তর্ভুক্তিমূলক বিষয়বস্তু তৈরি করাই আমাদের লক্ষ্য।',
            'We focus on growing and expanding Wikipedia content in Bangla. Through contests, edit-a-thons and training programmes, we aim to build high-quality, inclusive content across Wikipedia and its sister projects — Wikiquote, Wikivoyage, Wikibooks and Wiktionary.'
        ),
        'cta_primary'    => array('আরও জানুন', 'Learn more'),
        'cta_secondary'  => array('মেটা’উইকিতে পড়ুন', 'Read on Meta-Wiki'),
        'projects_title' => array('আমাদের প্রকল্পসমূহ', 'Our projects'),
        'projects_sub'   => array('চলমান ও সদ্য সমাপ্ত প্রতিযোগিতা এবং কর্মসূচি', 'Ongoing and recently completed contests and programmes'),
        'projects_all'   => array('সব প্রকল্প দেখুন', 'All projects'),
        'newsroom_title' => array('বার্তাকক্ষ', 'Newsroom'),
        'newsroom_all'   => array('সব পোস্ট', 'All posts'),
        'team_title'     => array('মূল দল', 'Core team'),
        'team_sub'       => array('যাঁরা এই উদ্যোগ এগিয়ে নিচ্ছেন', 'The people driving this initiative'),
        'team_all'       => array('সব সদস্য', 'All members'),
        'partners_title' => array('আমাদের অংশীদার', 'Our partners'),

        // The members listing at /teams/. It is an archive rather than a
        // Page, so it has no editor of its own — this is where its wording
        // lives, alongside the home page's.
        'members_title'  => array('সদস্যবৃন্দ', 'Members'),
        'members_sub'    => array('বাংলা উইকিসংযোগের স্বেচ্ছাসেবী দল।', 'The volunteer team behind Bangla WikiConnect.'),
        'former_title'   => array('প্রাক্তন সদস্যবৃন্দ', 'Former members'),
        'former_sub'     => array('যাঁরা অতীতে এই উদ্যোগে অবদান রেখেছেন।', 'People who contributed to this initiative in the past.'),
        'teamnav_label'  => array('দল বাছাই', 'Filter by team'),
        'teamnav_all'    => array('সবাই', 'Everyone'),
    );
}

function bnwp_home_text($key) {
    $defaults = bnwp_home_defaults();
    if (!isset($defaults[$key])) {
        return '';
    }
    list($bn, $en) = $defaults[$key];

    $raw = trim((string) get_theme_mod('bnwp_home_' . $key, ''));
    if ($raw !== '') {
        $parts = array_map('trim', explode('|', $raw, 2));
        if ($parts[0] !== '') {
            $bn = $parts[0];
            $en = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : $parts[0];
        }
    }
    return bnwp_text($bn, $en);
}

/**
 * A person's social and profile links: one "Label | URL" per line.
 *
 * External jurors usually have no page on this site, and often no wiki account
 * either, so this is where their public presence goes.
 */
function bnwp_person_links($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $raw     = (string) get_post_meta($post_id, '_bnwp_links', true);
    $out     = array();

    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line, 2));
        $url   = count($parts) === 2 ? $parts[1] : $parts[0];
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            continue;
        }
        $label = count($parts) === 2 ? $parts[0] : '';
        if ($label === '') {
            $host  = (string) wp_parse_url($url, PHP_URL_HOST);
            $label = $host !== '' ? preg_replace('#^www\.#', '', $host) : $url;
        }
        $out[] = array('label' => $label, 'url' => $url);
    }
    return $out;
}

/**
 * Where a person's card should point.
 *
 * External jurors and guest reviewers often have no page on this site — only
 * a profile elsewhere. Setting _bnwp_link on their record sends every card
 * and list straight there instead of to an empty local page.
 */
function bnwp_person_url($post_id = null) {
    $post_id  = $post_id ? $post_id : get_the_ID();
    $external = trim((string) get_post_meta($post_id, '_bnwp_link', true));

    if ($external !== '' && preg_match('#^https?://#i', $external)) {
        return $external;
    }
    return bnwp_lang_arg(get_permalink($post_id));
}

/** True when this person links off-site. */
function bnwp_person_is_external($post_id = null) {
    $post_id  = $post_id ? $post_id : get_the_ID();
    $external = trim((string) get_post_meta($post_id, '_bnwp_link', true));
    return $external !== '' && preg_match('#^https?://#i', $external);
}

/** Small outward arrow, shown on cards that leave the site. */
function bnwp_external_mark() {
    echo '<svg class="ext-mark" viewBox="0 0 24 24" aria-hidden="true" focusable="false" '
        . 'fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7M9 7h8v8"/></svg>';
}

/** Meta keys stored as a comma-separated list rather than a single value. */
function bnwp_list_meta_keys() {
    return array();
}

/** One row: a team member, drawn from their own record. */
function bnwp_person_row_post($post, $note = '') {
    $id       = $post->ID;
    $username = (string) get_post_meta($id, '_bnwp_username', true);
    $external = bnwp_person_is_external($id);

    printf(
        '<a class="peoplelist__row" href="%s"%s>',
        esc_url(bnwp_person_url($id)),
        $external ? ' rel="noopener"' : ''
    );
    bnwp_image(get_post_meta($id, '_bnwp_img', true), array(
        'w' => 120, 'h' => 120, 'alt' => '', 'class' => 'peoplelist__avatar', 'fit' => 'cover',
    ));
    echo '<span class="peoplelist__text">';
    printf('<span class="peoplelist__name">%s</span>', esc_html(get_the_title($id)));
    // What somebody does on a project is not their standing team role, so the
    // role is deliberately not used here — only what this project says, and
    // their username when it says nothing.
    if ($note !== '') {
        printf('<span class="peoplelist__note">%s</span>', esc_html($note));
    } elseif ($username !== '') {
        printf('<span class="peoplelist__role">@%s</span>', esc_html($username));
    }
    echo '</span>';
    if ($external) {
        bnwp_external_mark();
    }
    echo '</a>';
}

/** One row: a guest with no record on this site. */
function bnwp_person_row_guest($person) {
    $tag = $person['url'] !== '' ? 'a' : 'div';
    printf(
        '<%s class="peoplelist__row%s"%s>',
        $tag,
        $person['url'] !== '' ? '' : ' peoplelist__row--static',
        $person['url'] !== '' ? ' href="' . esc_url($person['url']) . '" rel="noopener"' : ''
    );
    bnwp_image($person['img'], array(
        'w' => 120, 'h' => 120, 'alt' => '', 'class' => 'peoplelist__avatar', 'fit' => 'cover',
    ));
    echo '<span class="peoplelist__text">';
    printf('<span class="peoplelist__name">%s</span>', esc_html($person['name']));
    if ($person['note'] !== '') {
        printf('<span class="peoplelist__note">%s</span>', esc_html($person['note']));
    }
    echo '</span>';
    if ($person['url'] !== '') {
        bnwp_external_mark();
    }
    printf('</%s>', $tag);
}

/**
 * A compact list of people for the organiser and jury panels, team members
 * and guests together, in the order they were written.
 */
function bnwp_person_rows($entries) {
    if (!$entries) {
        return;
    }
    echo '<div class="peoplelist">';
    foreach ($entries as $entry) {
        if ($entry['type'] === 'persona') {
            bnwp_person_row_post($entry['post'], isset($entry['note']) ? $entry['note'] : '');
        } else {
            bnwp_person_row_guest($entry);
        }
    }
    echo '</div>';
}

/**
 * When a contest ran: "YYYY-MM-DD - YYYY-MM-DD", written out in words.
 *
 * Stored as plain ISO so it sorts and nobody has to type a month name twice;
 * shown as "৭ মে – ৭ জুন ২০২৫" or "7 May – 7 June 2025". A year is printed
 * once when both ends share it, and the month once when both share that, so
 * a one-month contest reads "1–30 April 2026" rather than repeating itself.
 * A single date, with no second half, is fine.
 */
function bnwp_parse_dates($raw) {
    // Pull the dates out rather than splitting on the separator: the ISO
    // dates contain hyphens themselves, so splitting on "-" tore them apart.
    // This also means any separator works — hyphen, en dash, "to", থেকে.
    preg_match_all('/(\d{4})-(\d{2})-(\d{2})/', (string) $raw, $found, PREG_SET_ORDER);

    $out = array();
    foreach (array_slice($found, 0, 2) as $m) {
        $y = (int) $m[1]; $mo = (int) $m[2]; $d = (int) $m[3];
        if ($mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31) {
            $out[] = array('y' => $y, 'm' => $mo, 'd' => $d);
        }
    }
    return $out;
}

function bnwp_format_dates($raw) {
    $parts = bnwp_parse_dates($raw);
    if (!$parts) {
        return '';
    }
    $en     = bnwp_is_en();
    $months = $en
        ? array(1 => 'January', 'February', 'March', 'April', 'May', 'June',
                     'July', 'August', 'September', 'October', 'November', 'December')
        : bnwp_bn_months();
    $num = function ($n) use ($en) {
        return $en ? (string) $n : bnwp_bn_numerals((string) $n);
    };
    $one = function ($p, $withMonth = true, $withYear = true) use ($months, $num) {
        $s = $num($p['d']);
        if ($withMonth) { $s .= ' ' . $months[$p['m']]; }
        if ($withYear)  { $s .= ' ' . $num($p['y']); }
        return $s;
    };

    if (count($parts) === 1) {
        return $one($parts[0]);
    }
    list($a, $b) = $parts;
    $sameYear  = $a['y'] === $b['y'];
    $sameMonth = $sameYear && $a['m'] === $b['m'];
    $dash = ' – ';

    if ($sameMonth) {
        return $num($a['d']) . '–' . $one($b);
    }
    return $one($a, true, !$sameYear) . $dash . $one($b);
}

/** The dates of a project, written out for the current language. */
function bnwp_project_dates($post_id = null) {
    return bnwp_format_dates(bnwp_get_meta('_bnwp_dates', $post_id));
}

/**
 * The other editions of the same contest.
 *
 * Slugs here end in a year — wiki-quote-2025, wiki-quote-2026 — so dropping it
 * gives the family. Linking the editions to each other is worth more than the
 * random picks that used to fill this block: a fixed, meaningful link is one a
 * search engine can follow and a reader can use, and random ones gave crawlers
 * a different page every visit.
 */
function bnwp_project_family($slug) {
    return preg_replace('/-(?:19|20)\d{2}$/', '', (string) $slug);
}

function bnwp_related_projects($limit = 3) {
    $current = get_post();
    if (!$current) {
        return null;
    }
    $family = bnwp_project_family($current->post_name);

    $siblings = array();
    foreach (get_posts(array(
        'post_type'      => 'project',
        'posts_per_page' => -1,
        'post__not_in'   => array($current->ID),
        'fields'         => 'ids',
    )) as $id) {
        if (bnwp_project_family(get_post_field('post_name', $id)) === $family) {
            $siblings[] = $id;
        }
    }

    // Other editions first, then whatever the listing would show next, so the
    // block is always full without ever being random.
    $ids = $siblings;
    if (count($ids) < $limit) {
        $fill = get_posts(bnwp_project_order_args(array(
            'post_type'      => 'project',
            'posts_per_page' => $limit - count($ids),
            'post__not_in'   => array_merge(array($current->ID), $ids),
            'fields'         => 'ids',
        )));
        $ids = array_merge($ids, $fill);
    }
    $ids = array_slice($ids, 0, $limit);
    if (!$ids) {
        return null;
    }

    $q = new WP_Query(array(
        'post_type'      => 'project',
        'posts_per_page' => $limit,
        'post__in'       => $ids,
        'orderby'        => 'post__in',
        'no_found_rows'  => true,
    ));
    return $q->have_posts() ? $q : null;
}

/**
 * Whether a contest is upcoming, running or over — worked out from its dates.
 *
 * This used to be a dropdown somebody had to remember to change, which meant
 * a finished contest went on calling itself চলমান until it was noticed. The
 * dates already say when it ran, so they decide. A project with no dates has
 * no status and shows no chip.
 */
function bnwp_project_status($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $parts   = bnwp_parse_dates(get_post_meta($post_id, '_bnwp_dates', true));
    if (!$parts) {
        return '';
    }
    $ymd   = function ($p) { return sprintf('%04d%02d%02d', $p['y'], $p['m'], $p['d']); };
    $today = current_time('Ymd');
    $start = $ymd($parts[0]);
    $end   = $ymd(isset($parts[1]) ? $parts[1] : $parts[0]);

    if ($today < $start) { return 'upcoming'; }
    if ($today > $end)   { return 'completed'; }
    return 'ongoing';
}

/**
 * A single number a listing can sort on: ongoing first, then upcoming, then
 * finished, and within each the most recent start first. Kept in meta so the
 * database does the ordering instead of PHP paging through everything.
 *
 * Because the status now moves with the calendar, so does this key — see
 * bnwp_refresh_sortkeys(), which rebuilds them daily.
 */
function bnwp_project_sortkey($post_id) {
    $parts = bnwp_parse_dates(get_post_meta($post_id, '_bnwp_dates', true));
    $start = $parts ? (int) sprintf('%04d%02d%02d', $parts[0]['y'], $parts[0]['m'], $parts[0]['d']) : 0;
    $rank  = array('ongoing' => 3, 'upcoming' => 2, 'completed' => 1);
    $status = bnwp_project_status($post_id);
    $group  = isset($rank[$status]) ? $rank[$status] : 0;

    /*
     * Everything is read back in one descending pass, so each group has to
     * encode the direction it wants inside the key.
     *
     *   ongoing   — the one that started earliest has been running longest
     *   upcoming  — the one starting soonest comes first
     *   finished  — the most recent first
     *
     * The first two want ascending starts out of a descending sort, so their
     * dates are subtracted from 99999999 and come back reversed.
     */
    $ascending = ($group === 3 || $group === 2);
    return $group . sprintf('%08d', $ascending ? 99999999 - $start : $start);
}

/**
 * Rebuild every project's sort key.
 *
 * A contest that ended overnight has to fall out of the ongoing group without
 * anyone editing it, so this runs daily as well as on save.
 */
function bnwp_refresh_sortkeys() {
    foreach (get_posts(array(
        'post_type'      => 'project',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    )) as $id) {
        update_post_meta($id, '_bnwp_sortkey', bnwp_project_sortkey($id));
    }
}
add_action('bnwp_refresh_sortkeys', 'bnwp_refresh_sortkeys');

function bnwp_schedule_sortkeys() {
    if (!wp_next_scheduled('bnwp_refresh_sortkeys')) {
        wp_schedule_event(time() + 300, 'daily', 'bnwp_refresh_sortkeys');
    }
}
add_action('init', 'bnwp_schedule_sortkeys');
add_action('after_switch_theme', 'bnwp_refresh_sortkeys');

function bnwp_save_project_sortkey($post_id) {
    if (get_post_type($post_id) !== 'project' || wp_is_post_revision($post_id)) {
        return;
    }
    update_post_meta($post_id, '_bnwp_sortkey', bnwp_project_sortkey($post_id));
}
add_action('save_post', 'bnwp_save_project_sortkey', 20);

/**
 * ...and again once the dates themselves have landed.
 *
 * The REST API saves the post first and its meta second, so save_post runs
 * while _bnwp_dates still holds the previous value — every key built that way
 * came out with a start date of 00000000. Watching the meta write closes it.
 * No recursion: the key written here is _bnwp_sortkey, which this ignores.
 */
function bnwp_sortkey_after_meta($meta_id, $post_id, $meta_key) {
    if ($meta_key === '_bnwp_dates') {
        bnwp_save_project_sortkey($post_id);
    }
}
add_action('added_post_meta', 'bnwp_sortkey_after_meta', 10, 3);
add_action('updated_post_meta', 'bnwp_sortkey_after_meta', 10, 3);

/**
 * Order project listings by that key.
 *
 * The meta_query names both an EXISTS and a NOT EXISTS clause so the join is
 * a LEFT one: a project that has never been saved since this key arrived
 * sorts last rather than vanishing from the archive entirely.
 */
function bnwp_project_order_args($args = array()) {
    $args['meta_query'] = array(
        'relation'  => 'OR',
        'has_key'   => array('key' => '_bnwp_sortkey', 'compare' => 'EXISTS'),
        'no_key'    => array('key' => '_bnwp_sortkey', 'compare' => 'NOT EXISTS'),
    );
    $args['orderby'] = array('has_key' => 'DESC', 'date' => 'DESC');
    return $args;
}

function bnwp_order_projects($query) {
    if (is_admin() || !$query->is_main_query() || !$query->is_post_type_archive('project')) {
        return;
    }
    foreach (bnwp_project_order_args() as $key => $value) {
        $query->set($key, $value);
    }
}
add_action('pre_get_posts', 'bnwp_order_projects');

/**
 * The language a contest is run in.
 *
 * This is a fact about the project, not about the reader: a Bangla Wikiquote
 * contest is in Bangla whichever version of the site you are looking at. It
 * used to echo the view language, so every project called itself English on
 * the English site.
 */
function bnwp_project_language($post_id = null) {
    $value = trim((string) bnwp_get_meta_i18n('_bnwp_proj_lang', $post_id));
    return $value !== '' ? $value : bnwp_text('বাংলা', 'Bangla');
}

function bnwp_project_statuses() {
    return array(
        // "আসন্ন" rather than "শীঘ্রই" — it is the word the organisation already
        // uses for upcoming work on its own newsroom page.
        'ongoing'   => bnwp_text('চলমান', 'Ongoing'),
        'upcoming'  => bnwp_text('আসন্ন', 'Upcoming'),
        'completed' => bnwp_text('সমাপ্ত', 'Ended'),
    );
}

function bnwp_project_status_label($key) {
    $all = bnwp_project_statuses();
    return isset($all[$key]) ? $all[$key] : '';
}

function bnwp_status_chip_class($key) {
    $map = array('ongoing' => 'chip--live', 'upcoming' => 'chip--soon', 'completed' => 'chip--past');
    return 'chip--status ' . (isset($map[$key]) ? $map[$key] : 'chip--past');
}

/** The whole chip, or nothing when a project has no status set. */
function bnwp_status_chip($status) {
    $label = bnwp_project_status_label($status);
    if ($label === '') {
        return;
    }
    printf(
        '<span class="chip %s">%s</span>',
        esc_attr(bnwp_status_chip_class($status)),
        esc_html($label)
    );
}

/**
 * Taxonomy terms carry a single name, so the team filter stayed Bengali in
 * the English view. An optional "English name" on each term fixes that
 * without duplicating the terms themselves.
 */
function bnwp_term_name($term) {
    if (!$term instanceof WP_Term) {
        return '';
    }
    if (!bnwp_is_en()) {
        return $term->name;
    }

    $en = get_term_meta($term->term_id, '_bnwp_name_en', true);
    if ($en !== '') {
        return $en;
    }

    // Sensible defaults for the three teams the site ships with, so English
    // works without anyone having to fill the field in first.
    $known = array(
        'cot'       => 'Core Team',
        'technical' => 'Technical Team',
        'jury'      => 'Jury Coordination',
    );
    return isset($known[$term->slug]) ? $known[$term->slug] : $term->name;
}

/**
 * A team's blurb in the reader's language.
 *
 * The description box on a term holds one string, so the Former members page
 * was in English whichever language the site was being read in. The Bengali
 * goes in the box; the English goes beside it, the same paired shape every
 * record on this site uses.
 */
function bnwp_term_description($term) {
    if (!$term instanceof WP_Term) {
        return '';
    }
    if (bnwp_is_en()) {
        $en = trim((string) get_term_meta($term->term_id, '_bnwp_desc_en', true));
        if ($en !== '') {
            return $en;
        }
    }
    return (string) $term->description;
}

function bnwp_register_content_types() {
    register_post_type('project', array(
        'labels' => array(
            'name'          => __('Projects', 'bnwp'),
            'singular_name' => __('Project', 'bnwp'),
            'add_new_item'  => __('Add New Project', 'bnwp'),
            'edit_item'     => __('Edit Project', 'bnwp'),
        ),
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => array('slug' => 'projects', 'with_front' => false),
        'menu_icon'    => 'dashicons-portfolio',
        'supports'     => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions'),
        'show_in_rest' => true,
    ));

    register_post_type('persona', array(
        'labels' => array(
            'name'          => __('Team Members', 'bnwp'),
            'singular_name' => __('Team Member', 'bnwp'),
            'add_new_item'  => __('Add New Team Member', 'bnwp'),
            'edit_item'     => __('Edit Team Member', 'bnwp'),
        ),
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => array('slug' => 'persona', 'with_front' => false),
        'menu_icon'    => 'dashicons-groups',
        // page-attributes gives each person an Order box, which is what the
        // listings sort on — see bnwp_order_people().
        'supports'     => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions', 'page-attributes'),
        'show_in_rest' => true,
    ));

    register_taxonomy('team', array('persona'), array(
        'labels' => array(
            'name'          => __('Teams', 'bnwp'),
            'singular_name' => __('Team', 'bnwp'),
        ),
        'public'       => true,
        'hierarchical' => false,
        'rewrite'      => array('slug' => 'teams', 'with_front' => false),
        'show_in_rest' => true,
    ));

    register_term_meta('team', '_bnwp_order', array(
        'type'              => 'integer',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => 'absint',
        'auth_callback'     => function () { return current_user_can('manage_categories'); },
    ));

    register_term_meta('team', '_bnwp_desc_en', array(
        'type'              => 'string',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => 'sanitize_textarea_field',
        'auth_callback'     => function () { return current_user_can('manage_categories'); },
    ));

    register_term_meta('team', '_bnwp_name_en', array(
        'type'              => 'string',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => function () { return current_user_can('manage_categories'); },
    ));

    foreach (bnwp_meta_keys() as $key) {
        register_post_meta('', $key, array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => bnwp_meta_sanitizer($key),
            'auth_callback'     => function () { return current_user_can('edit_posts'); },
        ));
    }
}
add_action('init', 'bnwp_register_content_types');

function bnwp_flush_rewrites() {
    bnwp_register_content_types();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'bnwp_flush_rewrites');

function bnwp_get_meta($key, $post_id = null, $default = '') {
    $post_id = $post_id ? $post_id : get_the_ID();
    $value = get_post_meta($post_id, $key, true);
    return $value !== '' ? $value : $default;
}


/* -------------------------------------------------------------------------
 * 5. Dates, numerals, reading time
 * ---------------------------------------------------------------------- */

function bnwp_bn_numerals($value) {
    return strtr((string) $value, array(
        '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
        '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
    ));
}

function bnwp_bn_months() {
    return array(
        1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
        5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
    );
}

function bnwp_post_date($post_id = null) {
    $ts = get_post_timestamp($post_id ? $post_id : get_the_ID());
    if (!$ts) {
        return '';
    }

    if (bnwp_is_en()) {
        return wp_date('j F Y', $ts);
    }

    $months = bnwp_bn_months();
    return sprintf(
        '%s %s %s',
        bnwp_bn_numerals(wp_date('j', $ts)),
        $months[(int) wp_date('n', $ts)],
        bnwp_bn_numerals(wp_date('Y', $ts))
    );
}

function bnwp_iso_date($post_id = null) {
    $ts = get_post_timestamp($post_id ? $post_id : get_the_ID());
    return $ts ? gmdate('c', $ts) : '';
}

function bnwp_reading_time($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
    $words   = $content !== '' ? count(preg_split('/\s+/u', trim($content))) : 0;
    $minutes = max(1, (int) ceil($words / 200));

    return bnwp_is_en()
        ? sprintf('%d min read', $minutes)
        : sprintf('%s মিনিট পড়া', bnwp_bn_numerals($minutes));
}

/** Localised number for display (Bengali digits in bn). */
function bnwp_num($value) {
    return bnwp_is_en() ? $value : bnwp_bn_numerals($value);
}


/* -------------------------------------------------------------------------
 * 6. Assets
 * ---------------------------------------------------------------------- */

function bnwp_enqueue() {
    $dir = get_template_directory();
    $uri = get_template_directory_uri();

    $css = $dir . '/assets/css/app.css';
    $js  = $dir . '/assets/js/app.js';

    // Bengali-first pairing: Tiro Bangla for display, Anek Bangla for text.
    wp_enqueue_style(
        'bnwp-fonts',
        'https://fonts.googleapis.com/css2?family=Tiro+Bangla:ital@0;1&family=Anek+Bangla:wght@400..700&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'bnwp-app',
        $uri . '/assets/css/app.css',
        array('bnwp-fonts'),
        file_exists($css) ? filemtime($css) : BNWP_VERSION
    );

    wp_enqueue_script(
        'bnwp-app',
        $uri . '/assets/js/app.js',
        array(),
        file_exists($js) ? filemtime($js) : BNWP_VERSION,
        array('strategy' => 'defer', 'in_footer' => true)
    );

    wp_localize_script('bnwp-app', 'BNWP', array(
        'lang'   => bnwp_html_lang(),
        'digits' => bnwp_is_en() ? '0123456789' : '০১২৩৪৫৬৭৮৯',
    ));

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'bnwp_enqueue');

/** Inline the colour-mode bootstrap so there is no flash of the wrong theme. */
function bnwp_colormode_bootstrap() {
    ?>
    <script>
    (function () {
        var d = document.documentElement;
        d.classList.remove('no-js');
        try {
            var s = localStorage.getItem('bnwp-theme');
            d.setAttribute('data-theme', s || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
        } catch (e) {
            d.setAttribute('data-theme', 'light');
        }
    })();
    </script>
    <?php
}
add_action('wp_head', 'bnwp_colormode_bootstrap', 1);

/** Preconnect only to the font host we actually use. */
function bnwp_resource_hints($hints, $relation) {
    if ($relation === 'preconnect') {
        $hints[] = array('href' => 'https://fonts.gstatic.com', 'crossorigin');
    }
    return $hints;
}
add_filter('wp_resource_hints', 'bnwp_resource_hints', 10, 2);

/** Trim WordPress head noise we do not need. */
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');


/* -------------------------------------------------------------------------
 * 7. SEO
 *
 * Yoast is installed on this site. When it is active we stay out of its way
 * and only add what it does not do: hreflang for the /en/ twin, and correct
 * the tags it builds from the raw post object (see bnwp_yoast_url below).
 * ---------------------------------------------------------------------- */

function bnwp_yoast_active() {
    return defined('WPSEO_VERSION');
}

function bnwp_meta_description() {
    if (is_singular()) {
        $lead = bnwp_get_meta('_bnwp_lead');
        if ($lead !== '') {
            return wp_trim_words($lead, 32, '…');
        }
        $excerpt = get_the_excerpt();
        if ($excerpt !== '') {
            return wp_trim_words($excerpt, 32, '…');
        }
        return wp_trim_words(wp_strip_all_tags(get_post_field('post_content', get_the_ID())), 32, '…');
    }

    if (is_post_type_archive('project')) {
        return bnwp_text(
            'বাংলা উইকিসংযোগ আয়োজিত চলমান ও সমাপ্ত সব প্রকল্প ও প্রতিযোগিতা।',
            'All ongoing and completed projects and contests organised by Bangla WikiConnect.'
        );
    }

    if (is_post_type_archive('persona') || is_tax('team')) {
        return bnwp_text(
            'বাংলা উইকিসংযোগের স্বেচ্ছাসেবী দলের সদস্যবৃন্দ।',
            'The volunteer team behind Bangla WikiConnect.'
        );
    }

    return get_bloginfo('description');
}

/** The first image in a body of text, which is the one a share card wants. */
function bnwp_first_content_image($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $body    = bnwp_is_en() ? (string) get_post_meta($post_id, '_bnwp_body_en', true) : '';
    if (trim($body) === '') {
        $body = (string) get_post_field('post_content', $post_id);
    }
    if (preg_match('#<img[^>]+src=["\']([^"\']+)["\']#i', $body, $m)) {
        return $m[1];
    }
    return '';
}

function bnwp_og_image() {
    if (is_singular()) {
        if (has_post_thumbnail()) {
            return get_the_post_thumbnail_url(get_the_ID(), 'full');
        }
        // Cover images live in the body now, so the share card takes the
        // first picture the article actually shows.
        $inline = bnwp_first_content_image();
        if ($inline !== '') {
            return $inline;
        }
        $fallback = bnwp_get_meta('_bnwp_logo');
        if ($fallback === '') {
            $fallback = bnwp_get_meta('_bnwp_img');
        }
        if ($fallback !== '') {
            return bnwp_img_url($fallback, 1200);
        }
    }
    return get_template_directory_uri() . '/assets/uploads/Bangla_WikiConnect_LOGO.png';
}

function bnwp_seo_head() {
    $canonical = bnwp_translation_url(bnwp_current_language());

    // hreflang — always ours, Yoast does not know about the /en/ twin.
    printf(
        '<link rel="alternate" hreflang="bn" href="%s">' . "\n",
        esc_url(bnwp_translation_url('bn'))
    );
    printf(
        '<link rel="alternate" hreflang="en" href="%s">' . "\n",
        esc_url(bnwp_translation_url('en'))
    );
    printf(
        '<link rel="alternate" hreflang="x-default" href="%s">' . "\n",
        esc_url(bnwp_translation_url('bn'))
    );

    if (bnwp_yoast_active()) {
        return; // Yoast emits description, canonical, OG and Twitter itself.
    }

    printf('<meta name="description" content="%s">' . "\n", esc_attr(bnwp_meta_description()));
    printf('<link rel="canonical" href="%s">' . "\n", esc_url($canonical));

    printf('<meta property="og:type" content="%s">' . "\n", is_singular('post') ? 'article' : 'website');
    printf('<meta property="og:site_name" content="%s">' . "\n", esc_attr(bnwp_site_name()));
    printf('<meta property="og:locale" content="%s">' . "\n", esc_attr(bnwp_locale()));
    printf('<meta property="og:title" content="%s">' . "\n", esc_attr(wp_get_document_title()));
    printf('<meta property="og:description" content="%s">' . "\n", esc_attr(bnwp_meta_description()));
    printf('<meta property="og:url" content="%s">' . "\n", esc_url($canonical));
    printf('<meta property="og:image" content="%s">' . "\n", esc_url(bnwp_og_image()));

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    printf('<meta name="twitter:title" content="%s">' . "\n", esc_attr(wp_get_document_title()));
    printf('<meta name="twitter:description" content="%s">' . "\n", esc_attr(bnwp_meta_description()));
    printf('<meta name="twitter:image" content="%s">' . "\n", esc_url(bnwp_og_image()));
}
add_action('wp_head', 'bnwp_seo_head', 5);

/**
 * Yoast wins over the theme's own tags, but it has no per-page descriptions
 * configured — so every page was being served the site tagline. Feed it the
 * theme's description and image when it has nothing better of its own.
 */
function bnwp_yoast_metadesc($desc) {
    $desc = trim((string) $desc);
    if ($desc !== '' && $desc !== get_bloginfo('description')) {
        return $desc; // an author wrote one; leave it alone
    }
    $ours = bnwp_meta_description();
    return $ours !== '' ? $ours : $desc;
}
add_filter('wpseo_metadesc', 'bnwp_yoast_metadesc');
add_filter('wpseo_opengraph_desc', 'bnwp_yoast_metadesc');
add_filter('wpseo_twitter_description', 'bnwp_yoast_metadesc');

function bnwp_yoast_og_image($image) {
    if (is_singular(array('project', 'persona')) && !has_post_thumbnail()) {
        return bnwp_og_image();
    }
    return $image;
}
add_filter('wpseo_opengraph_image', 'bnwp_yoast_og_image');

/**
 * Yoast builds its tags from the raw post object and the WP site name, so it
 * knows nothing about /en/: every English page was canonicalising to its
 * Bengali twin — which tells search engines not to index it — and carrying a
 * Bengali <title>. Point Yoast at the current view instead. Bengali pages are
 * left alone; Yoast is already right about those.
 */
function bnwp_yoast_url($url) {
    return bnwp_is_en() ? bnwp_translation_url('en') : $url;
}
add_filter('wpseo_canonical', 'bnwp_yoast_url');
add_filter('wpseo_opengraph_url', 'bnwp_yoast_url');

function bnwp_yoast_title($title) {
    if (!bnwp_is_en() || $title === '') {
        return $title;
    }
    if (is_singular()) {
        $bn = get_post_field('post_title', get_the_ID());
        $en = get_post_meta(get_the_ID(), '_bnwp_title_en', true);
        if ($bn !== '' && $en !== '') {
            $title = str_replace($bn, $en, $title);
        }
    }
    return str_replace(get_bloginfo('name'), bnwp_site_name(), $title);
}
add_filter('wpseo_title', 'bnwp_yoast_title');
add_filter('wpseo_opengraph_title', 'bnwp_yoast_title');
add_filter('wpseo_twitter_title', 'bnwp_yoast_title');

/**
 * Listing pages get the same name in the tab that they carry as a heading.
 * Runs after bnwp_yoast_title(), so the site name has already been localised
 * and only the separator has to be kept.
 */
function bnwp_yoast_archive_title($title) {
    $text = bnwp_archive_title_text();
    if ($text === '' || $title === '') {
        return $title;
    }
    $sep = preg_match('/\s([-\x{2013}\x{2014}|\x{00b7}\x{00bb}:])\s/u', $title, $m) ? $m[1] : '-';
    return $text . ' ' . $sep . ' ' . bnwp_site_name();
}
add_filter('wpseo_title', 'bnwp_yoast_archive_title', 20);
add_filter('wpseo_opengraph_title', 'bnwp_yoast_archive_title', 20);
add_filter('wpseo_twitter_title', 'bnwp_yoast_archive_title', 20);

/** og:locale follows the view, not the WP site locale (which is en_US here). */
add_filter('wpseo_og_locale', 'bnwp_locale');

/**
 * Yoast's sitemap lists only the Bengali URLs, so the English pages were
 * reachable through hreflang alone. Register a second sitemap holding the
 * /en/ twin of every public URL and add it to Yoast's index.
 *
 * get_permalink() and friends are filtered to add /en/ only while the request
 * itself is English, which a sitemap request is not — so each URL is passed
 * through bnwp_lang_arg() explicitly.
 */
function bnwp_en_sitemap_entries() {
    $urls = array(home_url('/') => '');

    foreach (array('project', 'persona') as $type) {
        $link = get_post_type_archive_link($type);
        if ($link) {
            $urls[$link] = '';
        }
    }

    $listable = bnwp_teams();
    // The Former members tab has no posts filed under it — it gathers the
    // per-team ones — so hide_empty drops it, though it is a real page linked
    // from every team listing.
    $past = bnwp_team_term(bnwp_former_team());
    if ($past && bnwp_former_people()) {
        $listable[] = $past;
    }
    foreach ($listable as $term) {
        $link = get_term_link($term);
        if (!is_wp_error($link)) {
            $urls[$link] = '';
        }
    }

    $q = new WP_Query(array(
        'post_type'      => array('post', 'page', 'project', 'persona'),
        'post_status'    => 'publish',
        'posts_per_page' => 1000,
        'no_found_rows'  => true,
        'orderby'        => 'modified',
        'order'          => 'DESC',
    ));
    foreach ($q->posts as $post) {
        $urls[get_permalink($post)] = mysql2date(DATE_W3C, $post->post_modified_gmt, false);
    }

    $out = array();
    foreach ($urls as $url => $modified) {
        $out[bnwp_lang_arg($url, 'en')] = $modified;
    }
    return $out;
}

/**
 * Yoast fires this as an action and reads the result back off its own object —
 * a returned string is discarded, and the wrapper is ours to supply. Yoast
 * only adds the XML declaration and stylesheet around it.
 */
function bnwp_en_sitemap_body() {
    if (!isset($GLOBALS['wpseo_sitemaps'])) {
        return;
    }
    $xml = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "
";
    foreach (bnwp_en_sitemap_entries() as $url => $modified) {
        $xml .= "	<url>
		<loc>" . esc_url($url) . "</loc>
";
        if ($modified !== '') {
            $xml .= "		<lastmod>" . esc_html($modified) . "</lastmod>
";
        }
        $xml .= "	</url>
";
    }
    $xml .= '</urlset>';

    $GLOBALS['wpseo_sitemaps']->set_sitemap($xml);
    $GLOBALS['wpseo_sitemaps']->set_bad_sitemap(false);
}

/** Yoast caches sitemaps per type; ours has to be dropped when content moves. */
function bnwp_invalidate_en_sitemap() {
    if (class_exists('WPSEO_Sitemaps_Cache')) {
        WPSEO_Sitemaps_Cache::invalidate('en');
    }
}
add_action('save_post', 'bnwp_invalidate_en_sitemap');
add_action('deleted_post', 'bnwp_invalidate_en_sitemap');

function bnwp_register_en_sitemap() {
    if (isset($GLOBALS['wpseo_sitemaps']) && is_object($GLOBALS['wpseo_sitemaps'])) {
        $GLOBALS['wpseo_sitemaps']->register_sitemap('en', 'bnwp_en_sitemap_body');
    }
}
add_action('init', 'bnwp_register_en_sitemap', 99);

function bnwp_en_sitemap_index($links) {
    $modified = get_lastpostmodified('gmt');
    $modified = $modified ? mysql2date(DATE_W3C, $modified, false) : gmdate(DATE_W3C);
    return $links . "	<sitemap>
		<loc>" . esc_url(home_url('/en-sitemap.xml'))
        . "</loc>
		<lastmod>" . esc_html($modified) . "</lastmod>
	</sitemap>
";
}
add_filter('wpseo_sitemap_index', 'bnwp_en_sitemap_index');

/** The trail shown on single views, as data for search engines. */
function bnwp_breadcrumb_trail() {
    $trail = array(array('name' => bnwp_text('প্রচ্ছদ', 'Home'), 'url' => home_url('/')));

    if (is_singular('project')) {
        $link = get_post_type_archive_link('project');
        $trail[] = array('name' => bnwp_text('প্রকল্পসমূহ', 'Projects'), 'url' => $link ? $link : home_url('/projects/'));
        $trail[] = array('name' => wp_strip_all_tags(get_the_title()), 'url' => get_permalink());
    } elseif (is_singular('persona')) {
        $link = get_post_type_archive_link('persona');
        $trail[] = array('name' => bnwp_text('সদস্যবৃন্দ', 'Members'), 'url' => $link ? $link : home_url('/persona/'));
        $trail[] = array('name' => wp_strip_all_tags(get_the_title()), 'url' => get_permalink());
    } elseif (is_singular('post')) {
        $trail[] = array('name' => bnwp_text('পোস্টসমূহ', 'Posts'), 'url' => bnwp_page_url('posts'));
        $trail[] = array('name' => wp_strip_all_tags(get_the_title()), 'url' => get_permalink());
    } elseif (is_page() && !is_front_page()) {
        $trail[] = array('name' => wp_strip_all_tags(get_the_title()), 'url' => get_permalink());
    } else {
        return array();
    }

    return $trail;
}

/** Organization + Article structured data. */
function bnwp_schema() {
    $graph = array();

    // sameAs tells search engines which accounts are genuinely ours, which is
    // the main entity-resolution signal an organisation can give.
    $same_as = array('https://meta.wikimedia.org/wiki/Bangla_WikiConnect');
    foreach (bnwp_socials() as $channel) {
        if (!empty($channel['url'])) {
            $same_as[] = $channel['url'];
        }
    }

    $graph[] = array(
        '@type'  => 'Organization',
        '@id'    => home_url('/#organization'),
        'name'   => bnwp_site_name(),
        'url'    => home_url('/'),
        'logo'   => get_template_directory_uri() . '/assets/uploads/Bangla_WikiConnect_LOGO.png',
        'email'  => 'connect@bnwp.org',
        'sameAs' => array_values(array_unique($same_as)),
    );

    if (is_singular('post')) {
        $author = bnwp_persona_by_key(bnwp_get_meta('_bnwp_user'));
        $post_schema = array(
            '@type'            => 'BlogPosting',
            '@id'              => get_permalink() . '#article',
            'headline'         => wp_strip_all_tags(get_the_title()),
            'datePublished'    => bnwp_iso_date(),
            'dateModified'     => get_the_modified_date('c'),
            'inLanguage'       => bnwp_html_lang(),
            'description'      => bnwp_meta_description(),
            'image'            => bnwp_og_image(),
            'wordCount'        => str_word_count(wp_strip_all_tags(get_the_content())),
            'mainEntityOfPage' => array('@type' => 'WebPage', '@id' => get_permalink()),
            'publisher'        => array('@id' => home_url('/#organization')),
            'isPartOf'         => array('@id' => home_url('/#organization')),
        );
        $post_schema['author'] = $author
            ? array('@type' => 'Person', 'name' => get_the_title($author->ID), 'url' => get_permalink($author->ID))
            : array('@id' => home_url('/#organization'));
        $graph[] = $post_schema;
    }

    if (is_singular('project')) {
        /*
         * A contest is an Event, not a generic CreativeWork: it has a start,
         * an end and a way to attend, and describing it properly is what lets
         * a search engine show the dates beside the result.
         */
        $parts  = bnwp_parse_dates(bnwp_get_meta('_bnwp_dates'));
        $iso    = function ($p) { return sprintf('%04d-%02d-%02d', $p['y'], $p['m'], $p['d']); };
        $state  = bnwp_project_status();
        $wiki   = bnwp_get_meta('_bnwp_wiki');

        $event = array(
            '@type'                => 'Event',
            '@id'                  => get_permalink() . '#event',
            'name'                 => wp_strip_all_tags(get_the_title()),
            'description'          => bnwp_meta_description(),
            'inLanguage'           => bnwp_html_lang(),
            'image'                => bnwp_og_image(),
            'url'                  => get_permalink(),
            'eventAttendanceMode'  => 'https://schema.org/OnlineEventAttendanceMode',
            'eventStatus'          => 'https://schema.org/EventScheduled',
            'organizer'            => array('@id' => home_url('/#organization')),
            'isAccessibleForFree'  => true,
        );
        if ($parts) {
            $event['startDate'] = $iso($parts[0]);
            $event['endDate']   = $iso(isset($parts[1]) ? $parts[1] : $parts[0]);
        }
        if ($wiki !== '') {
            $event['location'] = array('@type' => 'VirtualLocation', 'url' => $wiki);
            // The contest page on the wiki is the authoritative record of it.
            $event['sameAs'] = $wiki;
        }
        // Entering costs nothing, and saying so explicitly is what lets a
        // result carry a "free" label rather than leaving price unknown.
        $event['offers'] = array(
            '@type'         => 'Offer',
            'price'         => '0',
            'priceCurrency' => 'BDT',
            'availability'  => 'https://schema.org/InStock',
            'url'           => $wiki !== '' ? $wiki : get_permalink(),
            'validFrom'     => $parts ? $iso($parts[0]) : null,
        );
        $event['offers'] = array_filter($event['offers'], function ($v) { return $v !== null; });

        // Everyone credited on the project, which ties the people pages to the
        // contests they ran and back again.
        $people = array();
        foreach (array_merge(bnwp_people_entries(bnwp_get_meta('_bnwp_organisers')),
                             bnwp_people_entries(bnwp_get_meta('_bnwp_jury'))) as $entry) {
            if ($entry['type'] === 'persona') {
                $people[] = array(
                    '@type' => 'Person',
                    'name'  => wp_strip_all_tags(get_the_title($entry['post']->ID)),
                    'url'   => get_permalink($entry['post']->ID),
                );
            }
        }
        if ($people) {
            $event['contributor'] = $people;
        }
        $graph[] = $event;
    }

    if (is_singular('persona')) {
        // Their own accounts elsewhere, which is how a search engine works out
        // that this page and those profiles are the same person.
        $same = array();
        $username = bnwp_get_meta('_bnwp_username');
        if ($username !== '') {
            $same[] = 'https://meta.wikimedia.org/wiki/User:' . rawurlencode($username);
        }
        foreach (bnwp_person_links() as $link) {
            $same[] = $link['url'];
        }
        $external = bnwp_get_meta('_bnwp_link');
        if ($external !== '') {
            $same[] = $external;
        }

        $person = array(
            '@type'         => 'Person',
            '@id'           => get_permalink() . '#person',
            'name'          => wp_strip_all_tags(get_the_title()),
            'url'           => get_permalink(),
            'image'         => bnwp_og_image(),
            'memberOf'      => array('@id' => home_url('/#organization')),
            'worksFor'      => array('@id' => home_url('/#organization')),
        );
        // These follow the reader's language; the old version always emitted
        // the Bengali, so the English pages described people in Bengali.
        foreach (array('alternateName' => $username,
                       'description'   => bnwp_get_meta_i18n('_bnwp_bio'),
                       'jobTitle'      => bnwp_get_meta_i18n('_bnwp_role'),
                       'homeLocation'  => bnwp_get_meta_i18n('_bnwp_location')) as $key => $value) {
            if (trim((string) $value) !== '') {
                $person[$key] = $value;
            }
        }
        if ($same) {
            $person['sameAs'] = array_values(array_unique($same));
        }
        $graph[] = $person;
    }

    $trail = bnwp_breadcrumb_trail();
    if (count($trail) > 1) {
        $items = array();
        foreach ($trail as $i => $crumb) {
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['name'],
                'item'     => $crumb['url'],
            );
        }
        $graph[] = array('@type' => 'BreadcrumbList', 'itemListElement' => $items);
    }

    printf(
        '<script type="application/ld+json">%s</script>' . "\n",
        wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $graph), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}
add_action('wp_head', 'bnwp_schema', 6);


/* -------------------------------------------------------------------------
 * 8. Navigation
 * ---------------------------------------------------------------------- */

/** `nav__item` belongs on the <li>; `nav__link` goes on the <a> (above). */
function bnwp_nav_item_class($classes, $item, $args, $depth) {
    $classes[] = 'nav__item';
    return $classes;
}
add_filter('nav_menu_css_class', 'bnwp_nav_item_class', 10, 4);

function bnwp_nav_submenu_class($classes, $args, $depth) {
    $classes[] = 'nav__sub';
    return $classes;
}
add_filter('nav_menu_submenu_css_class', 'bnwp_nav_submenu_class', 10, 3);

/** Menu used when no WP menu is assigned — all URLs resolved, never hardcoded. */
function bnwp_primary_menu_fallback() {
    $lang    = bnwp_current_language();
    $persona = get_post_type_archive_link('persona');
    $project = get_post_type_archive_link('project');

    $items = array(
        array(bnwp_text('পরিচিতি', 'About'),     bnwp_page_url('about', $lang)),
        array(bnwp_text('প্রকল্পসমূহ', 'Projects'), bnwp_lang_arg($project ? $project : home_url('/projects/'), $lang)),
        array(bnwp_text('সদস্য', 'Members'),      bnwp_lang_arg($persona ? $persona : home_url('/persona/'), $lang)),
        array(bnwp_text('বার্তাকক্ষ', 'Newsroom'),  bnwp_page_url('newsroom', $lang)),
        array(bnwp_text('পোস্টসমূহ', 'Posts'),     bnwp_page_url('posts', $lang)),
        array(bnwp_text('যোগাযোগ', 'Contact'),    bnwp_page_url('contact', $lang)),
    );

    echo '<ul class="nav__list">';
    foreach ($items as $item) {
        printf(
            '<li class="nav__item"><a class="nav__link" href="%s">%s</a></li>',
            esc_url($item[1]),
            esc_html($item[0])
        );
    }
    echo '</ul>';
}

/**
 * An English label on each menu item, so one menu serves both languages.
 *
 * Two menu locations meant two menus to keep in step, and they drifted — the
 * Bengali menu lost Projects and Contact while the English side, having no
 * menu assigned at all, quietly showed the theme's built-in list instead. One
 * menu with paired labels is the same bargain the rest of the site makes:
 * leave the English box empty and the Bengali label is used.
 */
function bnwp_menu_item_field($item_id, $item, $depth, $args) {
    $value = get_post_meta($item_id, '_bnwp_title_en', true);
    ?>
    <p class="field-bnwp-title-en description description-wide">
        <label for="bnwp-title-en-<?php echo (int) $item_id; ?>">
            <?php esc_html_e('English label', 'bnwp'); ?><br>
            <input type="text" class="widefat" id="bnwp-title-en-<?php echo (int) $item_id; ?>"
                   name="bnwp_menu_title_en[<?php echo (int) $item_id; ?>]"
                   value="<?php echo esc_attr($value); ?>">
            <span class="description"><?php esc_html_e('Shown when the site is read in English. Leave empty to use the label above.', 'bnwp'); ?></span>
        </label>
    </p>
    <?php
}
add_action('wp_nav_menu_item_custom_fields', 'bnwp_menu_item_field', 10, 4);

function bnwp_menu_item_save($menu_id, $item_id) {
    if (!current_user_can('edit_theme_options')) {
        return;
    }
    if (!isset($_POST['bnwp_menu_title_en']) || !is_array($_POST['bnwp_menu_title_en'])) {
        return;
    }
    $all = wp_unslash($_POST['bnwp_menu_title_en']);
    if (!isset($all[$item_id])) {
        return;
    }
    $value = sanitize_text_field($all[$item_id]);
    if ($value === '') {
        delete_post_meta($item_id, '_bnwp_title_en');
    } else {
        update_post_meta($item_id, '_bnwp_title_en', $value);
    }
}
add_action('wp_update_nav_menu_item', 'bnwp_menu_item_save', 10, 2);

/** Swap in the English label on the front end. */
function bnwp_menu_item_title($item) {
    if (is_admin() || empty($item->ID) || !bnwp_is_en()) {
        return $item;
    }
    $english = get_post_meta($item->ID, '_bnwp_title_en', true);
    if (is_string($english) && $english !== '') {
        $item->title = $english;
    }
    return $item;
}
add_filter('wp_setup_nav_menu_item', 'bnwp_menu_item_title');

/**
 * One menu drives both languages. A menu assigned to the English location is
 * still honoured, for anyone who genuinely wants a different English
 * structure; otherwise both read the same menu and the labels do the work.
 * With no menu assigned at all, the theme's own list is used.
 */
function bnwp_primary_nav() {
    $location = (bnwp_is_en() && has_nav_menu('primary_en')) ? 'primary_en' : 'primary';

    if (has_nav_menu($location)) {
        wp_nav_menu(array(
            'theme_location' => $location,
            'container'      => false,
            'menu_class'     => 'nav__list',
            'fallback_cb'    => 'bnwp_primary_menu_fallback',
            'depth'          => 2,
        ));
        return;
    }

    bnwp_primary_menu_fallback();
}

function bnwp_language_switcher() {
    $target = bnwp_is_en() ? 'bn' : 'en';
    $label  = $target === 'en' ? 'English' : 'বাংলা';
    ?>
    <a class="lang-switch"
       href="<?php echo esc_url(bnwp_translation_url($target)); ?>"
       hreflang="<?php echo esc_attr($target); ?>"
       lang="<?php echo esc_attr($target); ?>"
       aria-label="<?php echo esc_attr('Switch to ' . $label); ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 0c2.5 2.4 3.8 5.5 3.8 9s-1.3 6.6-3.8 9c-2.5-2.4-3.8-5.5-3.8-9S9.5 5.4 12 3ZM3.5 9h17M3.5 15h17"/>
        </svg>
        <span><?php echo esc_html($label); ?></span>
    </a>
    <?php
}

function bnwp_pagination() {
    the_posts_pagination(array(
        'mid_size'           => 1,
        'prev_text'          => bnwp_text('পূর্ববর্তী', 'Previous'),
        'next_text'          => bnwp_text('পরবর্তী', 'Next'),
        'screen_reader_text' => bnwp_text('পৃষ্ঠা তালিকা', 'Posts navigation'),
    ));
}

/* -------------------------------------------------------------------------
 * 9. Impact numbers — editable in Appearance → Customise → Impact numbers
 * ---------------------------------------------------------------------- */

function bnwp_stats_default() {
    return implode("\n", array(
        '1600000 | শব্দ যোগ হয়েছে | words added',
        '2000 | নিবন্ধ তৈরি | articles created',
        '100 | চিত্র আপলোড | images uploaded',
        '20 | স্বেচ্ছাসেবী আয়োজক | volunteer organisers',
        '2 | কর্মশালা | workshops',
        '2 | টিউটোরিয়াল | tutorials',
    ));
}

/**
 * Pick the unit a number should be shown in.
 *
 * English uses the short scale (K / M / B); Bengali uses the South Asian
 * scale (হাজার / লক্ষ / কোটি), which groups differently — a lakh is 10^5, not
 * 10^6 — so the two cannot share one divisor. Below 10,000 both show the
 * number in full. Returns [mantissa, unit].
 */
function bnwp_number_scale($n) {
    $n = (float) $n;

    if (bnwp_is_en()) {
        if ($n >= 1e9) { return array($n / 1e9, 'B'); }
        if ($n >= 1e6) { return array($n / 1e6, 'M'); }
        if ($n >= 1e4) { return array($n / 1e3, 'K'); }
        return array($n, '');
    }

    if ($n >= 1e7) { return array($n / 1e7, ' কোটি'); }
    if ($n >= 1e5) { return array($n / 1e5, ' লক্ষ'); }
    if ($n >= 1e4) { return array($n / 1e3, ' হাজার'); }
    return array($n, '');
}

/** A whole number rendered for the current language, scaled where it helps. */
function bnwp_format_number($n) {
    list($value, $unit) = bnwp_number_scale($n);

    if ($unit === '') {
        return bnwp_num(number_format_i18n((float) $n));
    }

    $rounded = round($value, 1);
    $decimals = (abs($rounded - round($rounded)) < 0.05) ? 0 : 1;

    return bnwp_num(number_format_i18n($rounded, $decimals)) . $unit;
}

/**
 * Parse the Customiser textarea into stat rows.
 *
 * Three fields:  value | Bengali label | English label
 * Four fields:   Bengali value | English value | Bengali label | English label
 *
 * The four-field form exists for word-numbers such as "১৬ লক্ষ+", which have
 * no meaning in the English view. Plain numerals are localised automatically,
 * so they only need the three-field form.
 */
function bnwp_stats() {
    $raw = get_theme_mod('bnwp_stats', bnwp_stats_default());
    $rows = array();

    foreach (preg_split('/\r\n|\r|\n/', (string) $raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode('|', $line));
        $pick  = function ($bn, $en) {
            return bnwp_is_en() && $en !== '' ? $en : $bn;
        };

        $value = isset($parts[0]) ? $parts[0] : '';
        $label = $pick(
            isset($parts[1]) ? $parts[1] : '',
            isset($parts[2]) ? $parts[2] : ''
        );

        if ($value === '') {
            continue;
        }

        // A plain number is scaled for the language and always gets a "+".
        // Anything else is shown exactly as typed.
        $count = null;
        $suffix = '';
        if (preg_match('/^(\d[\d,\s]*)\+?$/u', $value, $m)) {
            $count  = (int) preg_replace('/\D/', '', $m[1]);
            $suffix = '+';
            $value  = bnwp_format_number($count) . $suffix;
        }

        $rows[] = array('value' => $value, 'count' => $count, 'suffix' => $suffix, 'label' => $label);
    }

    return $rows;
}

/* -------------------------------------------------------------------------
 * Partners and social channels
 *
 * These used to be written into the templates. They are content, not design,
 * so they live in the Customiser now — with the previous values as the
 * defaults, so an untouched site looks exactly as it did.
 * ---------------------------------------------------------------------- */

function bnwp_partners_default() {
    return implode("\n", array(
        'উইকিমিডিয়া ফাউন্ডেশন | Wikimedia Foundation | https://wikimediafoundation.org/ | Wikimedia_Foundation_logo_-_vertical.png',
        'উইকিমিডিয়া বাংলাদেশ | Wikimedia Bangladesh | https://wikimedia.org.bd/ | Wikimedia_Bangladesh_logo.png',
        'উইকিনন্দিনী | WikiNandini | https://meta.wikimedia.org/wiki/WikiNandini | WikiNandini_text_logo_2024.png',
        'উইকি লাভস উইমেন | Wiki Loves Women | https://meta.wikimedia.org/wiki/Wiki_Loves_Women | Wiki_Loves_Women_South_Asia.png',
    ));
}

/** One partner per line: Bengali name | English name | URL | image */
function bnwp_partners() {
    $raw  = get_theme_mod('bnwp_partners', bnwp_partners_default());
    $rows = array();

    foreach (preg_split('/\r\n|\r|\n/', (string) $raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $p = array_map('trim', explode('|', $line));
        $name = bnwp_is_en() && !empty($p[1]) ? $p[1] : (isset($p[0]) ? $p[0] : '');
        if ($name === '') {
            continue;
        }

        // A bare filename means a logo shipped with the theme.
        $image = isset($p[3]) ? $p[3] : '';
        if ($image !== '' && strpos($image, '/') === false && strpos($image, ':') === false) {
            $image = get_template_directory_uri() . '/assets/uploads/' . $image;
        }

        $rows[] = array(
            'name'  => $name,
            'url'   => isset($p[2]) ? $p[2] : '',
            'image' => $image,
        );
    }
    return $rows;
}

function bnwp_socials_default() {
    return implode("\n", array(
        'Facebook | https://facebook.com/banglawikiconnect | facebook',
        'YouTube | https://youtube.com/@banglawikiconnect | youtube',
        'LinkedIn | https://www.linkedin.com/company/wikiconnect | linkedin',
        'Telegram | https://t.me/bnwikiconnect | telegram',
        'GitHub | https://github.com/bnwp | github',
    ));
}

/** The inline icon set. Anything unknown falls back to a generic link glyph. */
function bnwp_social_icon($key) {
    $icons = array(
        'facebook' => 'M15 8h-2.5c-.5 0-1 .4-1 1v2H15l-.4 3h-3v8H8.4v-8H6v-3h2.4V9.2C8.4 6.9 9.9 5 12.6 5H15Z',
        'youtube'  => 'M21.6 7.2c-.2-.9-.9-1.6-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4c-.9.2-1.6.9-1.8 1.8C2 8.8 2 12 2 12s0 3.2.4 4.8c.2.9.9 1.6 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4c.9-.2 1.6-.9 1.8-1.8.4-1.6.4-4.8.4-4.8s0-3.2-.4-4.8ZM10 15V9l5 3-5 3Z',
        'linkedin' => 'M6.9 8.5H4V20h2.9V8.5ZM5.4 4a1.7 1.7 0 1 0 0 3.4 1.7 1.7 0 0 0 0-3.4ZM20 13.4c0-3-1.6-4.4-3.8-4.4-1.7 0-2.5.9-3 1.6V8.5H10.4V20h2.9v-6.2c0-1.3.6-2.2 1.8-2.2s1.9.8 1.9 2.2V20H20Z',
        'telegram' => 'M21.7 4.4 2.9 11.6c-.9.3-.9 1.6 0 1.9l4.6 1.5 1.8 5.4c.2.7 1.1.9 1.6.3l2.5-2.7 4.7 3.4c.6.5 1.5.1 1.7-.6l3-14.8c.2-.9-.7-1.6-1.1-1.6ZM9.6 14.5l8.2-5.3-6.9 6.5-.4 3.4-.9-4.6Z',
        'github'   => 'M12 2a10 10 0 0 0-3.2 19.5c.5.1.7-.2.7-.5v-1.8c-2.8.6-3.4-1.3-3.4-1.3-.4-1.2-1.1-1.5-1.1-1.5-.9-.6.1-.6.1-.6 1 .1 1.5 1 1.5 1 .9 1.5 2.3 1.1 2.9.8.1-.6.3-1.1.6-1.3-2.2-.3-4.6-1.1-4.6-5 0-1.1.4-2 1-2.7-.1-.3-.4-1.3.1-2.7 0 0 .8-.3 2.7 1a9.4 9.4 0 0 1 5 0c1.9-1.3 2.7-1 2.7-1 .5 1.4.2 2.4.1 2.7.6.7 1 1.6 1 2.7 0 3.9-2.4 4.7-4.6 5 .3.3.7 1 .7 2v2.9c0 .3.2.6.7.5A10 10 0 0 0 12 2Z',
        'instagram'=> 'M12 7.4A4.6 4.6 0 1 0 12 16.6 4.6 4.6 0 0 0 12 7.4Zm0 7.6a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm5.9-7.8a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0ZM21 8.1c-.1-1.5-.4-2.8-1.5-3.8C18.4 3.2 17.1 3 15.6 2.9 14.1 2.8 9.9 2.8 8.4 2.9 6.9 3 5.6 3.2 4.5 4.3 3.4 5.3 3.1 6.6 3 8.1c-.1 1.5-.1 5.8 0 7.3.1 1.5.4 2.8 1.5 3.8 1.1 1.1 2.4 1.3 3.9 1.4 1.5.1 5.7.1 7.2 0 1.5-.1 2.8-.3 3.9-1.4 1.1-1 1.4-2.3 1.5-3.8.1-1.5.1-5.8 0-7.3Zm-1.9 8.9c-.3.8-1 1.4-1.8 1.7-1.2.5-4.2.4-5.6.4s-4.4.1-5.6-.4c-.8-.3-1.5-.9-1.8-1.7-.5-1.2-.4-4.2-.4-5.6s-.1-4.4.4-5.6c.3-.8 1-1.4 1.8-1.7C7.3 3.6 10.3 3.7 11.7 3.7s4.4-.1 5.6.4c.8.3 1.5.9 1.8 1.7.5 1.2.4 4.2.4 5.6s.1 4.4-.4 5.6Z',
        'mastodon' => 'M12 2c-4 0-7 1-7 1S3 4.4 3 8.6c0 4.9-.3 9.3 4.4 10.6 1.8.5 3.3.6 4.5.5 2.2-.1 3.4-.8 3.4-.8l-.1-1.6s-1.6.5-3.3.4c-1.7-.1-3.5-.2-3.8-2.3 0-.2 0-.4 0-.6 3.7.9 6.8.4 7.7.3 2.4-.3 4.5-1.8 4.8-3.2.4-2.2.4-5.3.4-5.3C21 4.4 19 3 19 3s-3-1-7-1Zm4.4 10.2h-1.9V8.6c0-1-.4-1.5-1.3-1.5-1 0-1.4.6-1.4 1.8v2.5h-1.8V8.9c0-1.2-.5-1.8-1.4-1.8-.9 0-1.3.5-1.3 1.5v3.6H5.4V8.5c0-1 .3-1.8.8-2.4.5-.6 1.2-.9 2.1-.9 1 0 1.8.4 2.3 1.2l.5.8.5-.8c.5-.8 1.3-1.2 2.3-1.2.9 0 1.6.3 2.1.9.5.6.8 1.4.8 2.4v3.7Z',
        'twitter'  => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231 5.45-6.231Zm-1.161 17.52h1.833L7.084 4.126H5.117l11.966 15.644Z',
    );
    // What a channel is called and what its glyph is keyed under are not the
    // same thing: the site is listed as "twitter" and the logo is X's.
    $aliases = array(
        'x'       => 'twitter',
        'x/twitter' => 'twitter',
        'twitter/x' => 'twitter',
        'fb'      => 'facebook',
        'yt'      => 'youtube',
        'ig'      => 'instagram',
        'in'      => 'linkedin',
    );
    $key = strtolower(trim($key));
    if (isset($aliases[$key])) {
        $key = $aliases[$key];
    }
    // the fallback is a filled glyph too, since .channel svg uses fill
    return isset($icons[$key])
        ? $icons[$key]
        : 'M14 3h7v7h-2V6.4l-8.3 8.3-1.4-1.4L17.6 5H14V3ZM5 5h5v2H7v10h10v-3h2v5H5V5Z';
}

/** One channel per line: Label | URL | icon */
function bnwp_socials() {
    $raw  = get_theme_mod('bnwp_socials', bnwp_socials_default());
    $rows = array();

    foreach (preg_split('/\r\n|\r|\n/', (string) $raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $p = array_map('trim', explode('|', $line));
        if (empty($p[0]) || empty($p[1])) {
            continue;
        }
        $rows[] = array(
            'label' => $p[0],
            'url'   => $p[1],
            'icon'  => isset($p[2]) ? $p[2] : $p[0],
        );
    }
    return $rows;
}

function bnwp_customize($wp_customize) {
    $wp_customize->add_section('bnwp_impact', array(
        'title'       => __('Impact numbers', 'bnwp'),
        'priority'    => 30,
        'description' => __('One row per line:<br><strong>number | Bengali label | English label</strong><br><br>Enter the plain number — 1600000, not "16 lakh". It is scaled and localised automatically: 1.6M in English, ১৬ লক্ষ in Bengali, with a + appended and a count-up as it scrolls into view. Numbers below 10,000 are shown in full. Anything that is not a number is printed exactly as typed.', 'bnwp'),
    ));

    $wp_customize->add_setting('bnwp_stats', array(
        'default'           => bnwp_stats_default(),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('bnwp_stats', array(
        'label'    => __('Rows', 'bnwp'),
        'section'  => 'bnwp_impact',
        'type'     => 'textarea',
        'input_attrs' => array('rows' => 8, 'style' => 'font-family:ui-monospace,monospace;'),
    ));

    $wp_customize->add_section('bnwp_home_section', array(
        'title'       => __('Home page text', 'bnwp'),
        'priority'    => 24,
        'description' => __('Each box is <strong>Bengali | English</strong>. Leave a box empty to keep the wording the theme ships with; leave the English half empty and English readers see the Bengali.', 'bnwp'),
    ));

    $bnwp_home_labels = array(
        'eyebrow'        => __('Hero: small line above the heading', 'bnwp'),
        'heading'        => __('Hero: heading', 'bnwp'),
        'lead'           => __('Hero: paragraph', 'bnwp'),
        'cta_primary'    => __('Hero: first button', 'bnwp'),
        'cta_secondary'  => __('Hero: second button', 'bnwp'),
        'impact_title'   => __('Impact panel: heading', 'bnwp'),
        'projects_title' => __('Projects: heading', 'bnwp'),
        'projects_sub'   => __('Projects: sub-heading', 'bnwp'),
        'projects_all'   => __('Projects: link to all', 'bnwp'),
        'newsroom_title' => __('Newsroom: heading', 'bnwp'),
        'newsroom_all'   => __('Newsroom: link to all', 'bnwp'),
        'team_title'     => __('Team: heading', 'bnwp'),
        'team_sub'       => __('Team: sub-heading', 'bnwp'),
        'team_all'       => __('Team: link to all', 'bnwp'),
        'partners_title' => __('Partners: heading', 'bnwp'),
    );
    foreach ($bnwp_home_labels as $bnwp_key => $bnwp_label) {
        $bnwp_defaults = bnwp_home_defaults();
        $wp_customize->add_setting('bnwp_home_' . $bnwp_key, array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_textarea_field',
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control('bnwp_home_' . $bnwp_key, array(
            'label'       => $bnwp_label,
            'section'     => 'bnwp_home_section',
            'type'        => in_array($bnwp_key, array('lead', 'heading'), true) ? 'textarea' : 'text',
            'input_attrs' => array(
                'placeholder' => $bnwp_defaults[$bnwp_key][0] . ' | ' . $bnwp_defaults[$bnwp_key][1],
            ),
        ));
    }
    unset($bnwp_key, $bnwp_label, $bnwp_defaults);

    $wp_customize->add_section('bnwp_listing_section', array(
        'title'       => __('Members page text', 'bnwp'),
        'priority'    => 25,
        'description' => __('The members listing and the team pages are archives, not Pages, so they do not appear under Pages and have no editor of their own. The headings below are the whole of their wording. A single team\'s name and blurb belong on <strong>Team Members &rarr; Teams</strong> instead, where each team carries a Bengali and an English version of both.<br><br>Each box is <strong>Bengali | English</strong>.', 'bnwp'),
    ));

    $bnwp_listing_labels = array(
        'members_title' => __('Members: heading', 'bnwp'),
        'members_sub'   => __('Members: paragraph', 'bnwp'),
        'former_title'  => __('Former members: heading', 'bnwp'),
        'former_sub'    => __('Former members: paragraph', 'bnwp'),
        'teamnav_label' => __('Team filter: label', 'bnwp'),
        'teamnav_all'   => __('Team filter: the "everyone" chip', 'bnwp'),
    );
    foreach ($bnwp_listing_labels as $bnwp_key => $bnwp_label) {
        $bnwp_defaults = bnwp_home_defaults();
        $wp_customize->add_setting('bnwp_home_' . $bnwp_key, array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_textarea_field',
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control('bnwp_home_' . $bnwp_key, array(
            'label'       => $bnwp_label,
            'section'     => 'bnwp_listing_section',
            'type'        => in_array($bnwp_key, array('members_sub', 'former_sub'), true) ? 'textarea' : 'text',
            'input_attrs' => array(
                'placeholder' => $bnwp_defaults[$bnwp_key][0] . ' | ' . $bnwp_defaults[$bnwp_key][1],
            ),
        ));
    }
    unset($bnwp_key, $bnwp_label, $bnwp_defaults);

    $wp_customize->add_section('bnwp_partners_section', array(
        'title'       => __('Partners', 'bnwp'),
        'priority'    => 31,
        'description' => __('One partner per line:<br><strong>Bengali name | English name | URL | image</strong><br><br>The image can be a file shipped with the theme (just the filename), a full URL, or a Wikimedia Commons title such as File:Name.svg.', 'bnwp'),
    ));
    $wp_customize->add_setting('bnwp_partners', array(
        'default'           => bnwp_partners_default(),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('bnwp_partners', array(
        'label'       => __('Rows', 'bnwp'),
        'section'     => 'bnwp_partners_section',
        'type'        => 'textarea',
        'input_attrs' => array('rows' => 6, 'style' => 'font-family:ui-monospace,monospace;'),
    ));

    $wp_customize->add_section('bnwp_socials_section', array(
        'title'       => __('Social channels', 'bnwp'),
        'priority'    => 32,
        'description' => __('One channel per line:<br><strong>Label | URL | icon</strong><br><br>Known icons: facebook, youtube, linkedin, telegram, github, instagram, mastodon. Anything else gets a generic link icon. Shown on the Contact page.', 'bnwp'),
    ));
    $wp_customize->add_setting('bnwp_socials', array(
        'default'           => bnwp_socials_default(),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('bnwp_socials', array(
        'label'       => __('Rows', 'bnwp'),
        'section'     => 'bnwp_socials_section',
        'type'        => 'textarea',
        'input_attrs' => array('rows' => 6, 'style' => 'font-family:ui-monospace,monospace;'),
    ));
}
add_action('customize_register', 'bnwp_customize');


/* -------------------------------------------------------------------------
 * 10. Admin — custom fields for projects, members and posts
 * ---------------------------------------------------------------------- */

function bnwp_add_meta_boxes() {
    add_meta_box('bnwp_project', __('Project Details', 'bnwp'), 'bnwp_project_box', 'project', 'normal', 'high');
    add_meta_box('bnwp_persona', __('Team Member Details', 'bnwp'), 'bnwp_persona_box', 'persona', 'normal', 'high');
    add_meta_box('bnwp_post', __('BNWP Post Details', 'bnwp'), 'bnwp_post_box', 'post', 'side', 'default');

    foreach (array('post', 'page', 'project', 'persona') as $type) {
        add_meta_box(
            'bnwp_english',
            __('English version', 'bnwp'),
            'bnwp_english_box',
            $type,
            'normal',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'bnwp_add_meta_boxes');

/**
 * Projects and people are edited through these fields far more than through
 * the body, and the block editor hides registered meta boxes in a collapsed
 * drawer below the content where nobody finds them. The classic editor puts
 * them back in the page, which for a record made mostly of fields is simply
 * the right screen. Posts and pages keep the block editor.
 */
function bnwp_classic_editor_post_types($use_block_editor, $post_type) {
    return in_array($post_type, array('project', 'persona'), true) ? false : $use_block_editor;
}
add_filter('use_block_editor_for_post_type', 'bnwp_classic_editor_post_types', 10, 2);

/**
 * The English half of the record.
 *
 * The Bengali side stays exactly where WordPress puts it — the normal title
 * box and editor. This panel holds the English equivalents. Anything left
 * blank falls back to the Bengali, so a partial translation is safe.
 */
function bnwp_english_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');

    ?>
    <p class="description" style="margin:0 0 14px;">
        <?php esc_html_e('Leave a field blank to fall back to the Bengali. Readers see this when the site is viewed in English.', 'bnwp'); ?>
    </p>

    <p class="bnwp-field">
        <label for="_bnwp_title_en"><strong><?php esc_html_e('English title', 'bnwp'); ?></strong></label>
        <input class="widefat" type="text" id="_bnwp_title_en" name="_bnwp_title_en"
               value="<?php echo esc_attr(bnwp_get_meta('_bnwp_title_en', $post->ID)); ?>"
               style="font-size:1.4em;padding:6px 8px;">
    </p>

    <p class="bnwp-field" style="margin-bottom:4px;">
        <label for="_bnwp_body_en"><strong><?php esc_html_e('English body', 'bnwp'); ?></strong></label>
    </p>
    <?php
    wp_editor(
        bnwp_get_meta('_bnwp_body_en', $post->ID),
        '_bnwp_body_en',
        array(
            'textarea_name' => '_bnwp_body_en',
            'textarea_rows' => 14,
            'media_buttons' => true,
            'teeny'         => false,
            'quicktags'     => true,
        )
    );
    ?>

    <p class="bnwp-field" style="margin-top:14px;">
        <label for="_bnwp_excerpt_en"><strong><?php esc_html_e('English excerpt', 'bnwp'); ?></strong></label>
        <textarea class="widefat" rows="2" id="_bnwp_excerpt_en" name="_bnwp_excerpt_en"><?php
            echo esc_textarea(bnwp_get_meta('_bnwp_excerpt_en', $post->ID));
        ?></textarea>
    </p>

    <?php if ($post->post_type === 'project') : ?>
        <?php bnwp_field_text(__('English lead / summary', 'bnwp'), '_bnwp_lead_en', bnwp_get_meta('_bnwp_lead_en', $post->ID)); ?>
    <?php endif; ?>

    <?php if ($post->post_type === 'persona') : ?>
        <?php
        bnwp_field_text(__('English role', 'bnwp'), '_bnwp_role_en', bnwp_get_meta('_bnwp_role_en', $post->ID));
        bnwp_field_text(__('English location', 'bnwp'), '_bnwp_location_en', bnwp_get_meta('_bnwp_location_en', $post->ID));
        bnwp_field_text(__('English short bio', 'bnwp'), '_bnwp_bio_en', bnwp_get_meta('_bnwp_bio_en', $post->ID));
        ?>
    <?php endif; ?>
    <?php
}

/**
 * Order and English name on the Teams screens.
 *
 * Terms have no Attributes panel of their own, so the two fields are added to
 * the add and edit forms by hand.
 */
function bnwp_team_add_fields() {
    ?>
    <div class="form-field">
        <label for="bnwp_term_order"><?php esc_html_e('Order', 'bnwp'); ?></label>
        <input type="number" min="0" step="1" name="bnwp_term_order" id="bnwp_term_order" value="">
        <p><?php esc_html_e('Lowest first. Leave at 0 and this team sorts after every numbered one.', 'bnwp'); ?></p>
    </div>
    <div class="form-field">
        <label for="bnwp_term_name_en"><?php esc_html_e('English name', 'bnwp'); ?></label>
        <input type="text" name="bnwp_term_name_en" id="bnwp_term_name_en" value="">
        <p><?php esc_html_e('Shown to English readers. Leave blank to use the Bengali name.', 'bnwp'); ?></p>
    </div>
    <div class="form-field">
        <label for="bnwp_term_desc_en"><?php esc_html_e('Description (English)', 'bnwp'); ?></label>
        <textarea name="bnwp_term_desc_en" id="bnwp_term_desc_en" rows="3"></textarea>
        <p><?php esc_html_e('The Bengali goes in the Description box; this is what English readers see.', 'bnwp'); ?></p>
    </div>
    <?php
}
add_action('team_add_form_fields', 'bnwp_team_add_fields');

function bnwp_team_edit_fields($term) {
    $order = (int) get_term_meta($term->term_id, '_bnwp_order', true);
    $name  = (string) get_term_meta($term->term_id, '_bnwp_name_en', true);
    $desc  = (string) get_term_meta($term->term_id, '_bnwp_desc_en', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="bnwp_term_order"><?php esc_html_e('Order', 'bnwp'); ?></label></th>
        <td>
            <input type="number" min="0" step="1" name="bnwp_term_order" id="bnwp_term_order"
                   value="<?php echo esc_attr($order); ?>">
            <p class="description"><?php esc_html_e('Lowest first. Leave at 0 and this team sorts after every numbered one. Used by the filter row on the team page and the badges on a profile.', 'bnwp'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="bnwp_term_name_en"><?php esc_html_e('English name', 'bnwp'); ?></label></th>
        <td>
            <input type="text" name="bnwp_term_name_en" id="bnwp_term_name_en"
                   value="<?php echo esc_attr($name); ?>" class="regular-text">
            <p class="description"><?php esc_html_e('Shown to English readers. Leave blank to use the Bengali name.', 'bnwp'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="bnwp_term_desc_en"><?php esc_html_e('Description (English)', 'bnwp'); ?></label></th>
        <td>
            <textarea name="bnwp_term_desc_en" id="bnwp_term_desc_en" rows="4" class="large-text"><?php echo esc_textarea($desc); ?></textarea>
            <p class="description"><?php esc_html_e('The paragraph under the heading on this team\'s page, for English readers. Put the Bengali in the Description box above; leave this blank and English readers see the Bengali.', 'bnwp'); ?></p>
        </td>
    </tr>
    <?php
}
add_action('team_edit_form_fields', 'bnwp_team_edit_fields');

function bnwp_team_save_fields($term_id) {
    if (!current_user_can('manage_categories')) {
        return;
    }
    if (isset($_POST['bnwp_term_order'])) {
        update_term_meta($term_id, '_bnwp_order', absint(wp_unslash($_POST['bnwp_term_order'])));
    }
    if (isset($_POST['bnwp_term_name_en'])) {
        update_term_meta($term_id, '_bnwp_name_en', sanitize_text_field(wp_unslash($_POST['bnwp_term_name_en'])));
    }
    if (isset($_POST['bnwp_term_desc_en'])) {
        update_term_meta($term_id, '_bnwp_desc_en', sanitize_textarea_field(wp_unslash($_POST['bnwp_term_desc_en'])));
    }
}
add_action('created_team', 'bnwp_team_save_fields');
add_action('edited_team', 'bnwp_team_save_fields');

/** An "Order" column on the Teams list, so the running order is visible. */
function bnwp_team_columns($columns) {
    $columns['bnwp_order'] = __('Order', 'bnwp');
    return $columns;
}
add_filter('manage_edit-team_columns', 'bnwp_team_columns');

function bnwp_team_column_value($content, $column, $term_id) {
    if ($column !== 'bnwp_order') {
        return $content;
    }
    $n = (int) get_term_meta($term_id, '_bnwp_order', true);
    return $n > 0 ? (string) $n : '—';
}
add_filter('manage_team_custom_column', 'bnwp_team_column_value', 10, 3);

/** An "EN" column so it is obvious at a glance what still needs translating. */
function bnwp_english_column($columns) {
    $out = array();
    foreach ($columns as $key => $label) {
        $out[$key] = $label;
        if ($key === 'title') {
            $out['bnwp_en'] = __('EN', 'bnwp');
        }
    }
    return $out;
}

function bnwp_english_column_value($column, $post_id) {
    if ($column !== 'bnwp_en') {
        return;
    }
    echo bnwp_has_english($post_id)
        ? '<span title="' . esc_attr__('Has an English version', 'bnwp') . '" style="color:#2E7D5B;font-size:16px;">&#10003;</span>'
        : '<span title="' . esc_attr__('Bengali only', 'bnwp') . '" style="color:#b0aca4;">&mdash;</span>';
}

// Pages use their own hook names; everything else follows the post pattern.
add_filter('manage_pages_columns', 'bnwp_english_column');
add_action('manage_pages_custom_column', 'bnwp_english_column_value', 10, 2);

foreach (array('post', 'project', 'persona') as $bnwp_type) {
    add_filter("manage_{$bnwp_type}_posts_columns", 'bnwp_english_column');
    add_action("manage_{$bnwp_type}_posts_custom_column", 'bnwp_english_column_value', 10, 2);
}
unset($bnwp_type);

function bnwp_field_text($label, $name, $value, $type = 'text') {
    printf(
        '<p class="bnwp-field"><label for="%1$s">%2$s</label>'
        . '<input class="widefat" type="%4$s" id="%1$s" name="%1$s" value="%3$s"></p>',
        esc_attr($name),
        esc_html($label),
        esc_attr($value),
        esc_attr($type)
    );
}

function bnwp_field_textarea($label, $name, $value, $help = '', $rows = 4) {
    printf(
        '<p class="bnwp-field"><label for="%1$s">%2$s</label>'
        . '<textarea class="widefat" id="%1$s" name="%1$s" rows="%4$d">%3$s</textarea>',
        esc_attr($name),
        esc_html($label),
        esc_textarea($value),
        (int) $rows
    );
    if ($help !== '') {
        printf('<span class="description">%s</span>', esc_html($help));
    }
    echo '</p>';
}

function bnwp_field_select($label, $name, $value, $options) {
    printf('<p class="bnwp-field"><label for="%s">%s</label>', esc_attr($name), esc_html($label));
    printf('<select class="widefat" id="%1$s" name="%1$s">', esc_attr($name));
    printf('<option value="">%s</option>', esc_html__('— none —', 'bnwp'));
    foreach ($options as $key => $text) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($key),
            selected($value, $key, false),
            esc_html($text)
        );
    }
    echo '</select></p>';
}

function bnwp_field_media($label, $name, $value) {
    $has = $value !== '';
    ?>
    <div class="bnwp-field bnwp-media-field">
        <label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label>
        <div class="bnwp-media-controls">
            <input class="widefat" type="text" inputmode="url" data-bnwp-media-url
                   id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>"
                   value="<?php echo esc_attr($value); ?>">
            <button type="button" class="button bnwp-select-media" data-target="<?php echo esc_attr($name); ?>">
                <?php esc_html_e('Choose image', 'bnwp'); ?>
            </button>
            <button type="button" class="button-link-delete bnwp-remove-media<?php echo $has ? '' : ' is-hidden'; ?>"
                    data-target="<?php echo esc_attr($name); ?>">
                <?php esc_html_e('Clear', 'bnwp'); ?>
            </button>
        </div>
        <p class="description"><?php esc_html_e('Media Library image, a Wikimedia Commons file page URL, or a bare File:Name.jpg title. Commons files are resized to a servable thumbnail automatically.', 'bnwp'); ?></p>
        <div class="bnwp-media-preview<?php echo $has ? '' : ' is-hidden'; ?>">
            <img src="<?php echo esc_url($value); ?>" alt="">
        </div>
    </div>
    <?php
}

function bnwp_project_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');
    bnwp_field_media(__('Logo', 'bnwp'), '_bnwp_logo', bnwp_get_meta('_bnwp_logo', $post->ID));
    bnwp_field_text(__('Wiki URL', 'bnwp'), '_bnwp_wiki', bnwp_get_meta('_bnwp_wiki', $post->ID), 'url');
    bnwp_field_text(
        __('Wiki link text', 'bnwp'),
        '_bnwp_wiki_label',
        bnwp_get_meta('_bnwp_wiki_label', $post->ID)
    );
    bnwp_field_text(
        __('Wiki link text (English)', 'bnwp'),
        '_bnwp_wiki_label_en',
        bnwp_get_meta('_bnwp_wiki_label_en', $post->ID)
    );
    echo '<p class="description" style="margin-top:-8px;">'
        . esc_html__('What the Wiki row in the At a glance panel reads. Leave empty and the domain is shown.', 'bnwp')
        . '</p>';
    bnwp_field_text(__('Lead / summary', 'bnwp'), '_bnwp_lead', bnwp_get_meta('_bnwp_lead', $post->ID));
    bnwp_field_text(
        __('Timeline', 'bnwp'),
        '_bnwp_dates',
        bnwp_get_meta('_bnwp_dates', $post->ID)
    );
    echo '<p class="description" style="margin-top:-8px;">'
        . esc_html__('When the contest ran, as 2025-05-07 - 2025-06-07. Written out in words in both languages, and used to order the project listings. One date on its own is fine.', 'bnwp')
        . '</p>';
    $bnwp_now = bnwp_project_status($post->ID);
    echo '<p class="description" style="margin-top:-4px;"><strong>'
        . esc_html__('Status:', 'bnwp') . '</strong> '
        . esc_html($bnwp_now ? bnwp_project_status_label($bnwp_now) : __('set a timeline and this follows automatically', 'bnwp'))
        . ' — ' . esc_html__('worked out from the dates above, so there is nothing to keep up to date by hand.', 'bnwp')
        . '</p>';
    bnwp_field_text(
        __('Contest language', 'bnwp'),
        '_bnwp_proj_lang',
        bnwp_get_meta('_bnwp_proj_lang', $post->ID)
    );
    bnwp_field_text(
        __('Contest language (English)', 'bnwp'),
        '_bnwp_proj_lang_en',
        bnwp_get_meta('_bnwp_proj_lang_en', $post->ID)
    );
    echo '<p class="description" style="margin-top:-8px;">'
        . esc_html__('The language the contest itself is run in — not the language somebody is reading the site in. Leave both empty for Bangla.', 'bnwp')
        . '</p>';

    bnwp_field_textarea(
        __('Organisers', 'bnwp'),
        '_bnwp_organisers',
        bnwp_get_meta('_bnwp_organisers', $post->ID),
        '',
        4
    );
    bnwp_field_textarea(
        __('Jury / reviewers', 'bnwp'),
        '_bnwp_jury',
        bnwp_get_meta('_bnwp_jury', $post->ID),
        '',
        5
    );
    echo '<div class="description" style="margin-top:-8px;">'
        . '<p style="margin:.2em 0;"><strong>' . esc_html__('One person per line, shown in the order you write them.', 'bnwp') . '</strong></p>'
        . '<p style="margin:.5em 0 .2em;">' . esc_html__('Just a wiki username credits that team member, using their photo and page:', 'bnwp') . '</p>'
        . '<p style="margin:.2em 0;"><code>Yahya</code></p>'
        . '<p style="margin:.5em 0 .2em;">' . esc_html__('Add a description to say what they did on this project. Their standing team role is never used here, because it is rarely what they did on a contest:', 'bnwp') . '</p>'
        . '<p style="margin:.2em 0;"><code>' . esc_html__('Wiki username | Bengali description | English description', 'bnwp') . '</code></p>'
        . '<p style="margin:.5em 0 .2em;">' . esc_html__('Somebody with no record on this site — a guest juror invited to this contest only — takes the long form, six parts:', 'bnwp') . '</p>'
        . '<p style="margin:.2em 0;"><code>' . esc_html__('Bengali name | English name | Bengali description | English description | Link | Photo', 'bnwp') . '</code></p>'
        . '<p style="margin:.6em 0 .2em;"><strong>' . esc_html__('Example', 'bnwp') . '</strong></p>'
        . '<p style="margin:.2em 0;"><code>Yahya</code><br><code>RiazACU | প্রধান আয়োজক | Lead organiser</code><br><code>ড. রেহানা সুলতানা | Dr Rehana Sultana | বিশেষ বিচারক | Guest judge | https://example.edu/rsultana | File:Rehana_Sultana.jpg</code></p>'
        . '<p style="margin:.5em 0 .2em;">' . esc_html__('The two are told apart automatically: if the first field names somebody on the site it is a team member, otherwise it is a guest. Keep the bars for anything you skip. Leave Jury empty and everyone in the Reviewers team is shown instead.', 'bnwp') . '</p>'
        . '</div>';
}

function bnwp_persona_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');
    bnwp_field_text(__('Display name', 'bnwp'), '_bnwp_name', bnwp_get_meta('_bnwp_name', $post->ID));
    bnwp_field_text(__('Role (all members)', 'bnwp'), '_bnwp_role', bnwp_get_meta('_bnwp_role', $post->ID));
    echo '<p class="description" style="margin-top:-8px;">'
        . esc_html__('Shown on the members listing and on this profile. Each team below can override it; leave a team blank and this is used there too.', 'bnwp')
        . ' ' . esc_html__('The Order box under Attributes decides where this person sits in the members listing — lowest first, 0 meaning unranked.', 'bnwp')
        . '</p>';
    bnwp_field_text(__('Wiki username', 'bnwp'), '_bnwp_username', bnwp_get_meta('_bnwp_username', $post->ID));
    bnwp_field_text(__('Location', 'bnwp'), '_bnwp_location', bnwp_get_meta('_bnwp_location', $post->ID));
    bnwp_field_text(__('Email', 'bnwp'), '_bnwp_email', bnwp_get_meta('_bnwp_email', $post->ID), 'email');
    bnwp_field_media(__('Profile image', 'bnwp'), '_bnwp_img', bnwp_get_meta('_bnwp_img', $post->ID));
    bnwp_field_text(__('Short bio', 'bnwp'), '_bnwp_bio', bnwp_get_meta('_bnwp_bio', $post->ID));
    bnwp_field_text(__('External profile URL', 'bnwp'), '_bnwp_link', bnwp_get_meta('_bnwp_link', $post->ID), 'url');
    echo '<p class="description" style="margin-top:-8px;">'
        . esc_html__('For guest or external jurors who have no page here: their cards link straight to this address instead of to a local profile.', 'bnwp')
        . '</p>';
    bnwp_field_textarea(
        __('Social links', 'bnwp'),
        '_bnwp_links',
        bnwp_get_meta('_bnwp_links', $post->ID),
        __('One per line, as "Label | URL" — for example: Website | https://example.org. The label may be left off and the domain is used instead. Shown on this person\'s page.', 'bnwp'),
        5
    );

    bnwp_persona_team_fields($post);
}

/**
 * Role and order, once per team.
 *
 * What somebody does is rarely the same on two teams, and where they belong in
 * one listing says nothing about the other, so each team keeps its own pair.
 * Both fall back to the all-members values above when left blank, which is why
 * filling none of this in changes nothing.
 *
 * Former teams have no fields of their own: they reuse the ones belonging to
 * the team they are the past of, and the site prints them as "Former …".
 */
function bnwp_persona_team_fields($post) {
    $teams = bnwp_base_teams();
    if (!$teams) {
        return;
    }
    echo '<hr style="margin:1.5em 0 1em;">';
    echo '<p class="description" style="margin:0 0 1em;"><strong>'
        . esc_html__('Per team', 'bnwp') . '</strong> — '
        . esc_html__('used on that team\'s page, and on the front page for the core team. Leave a box empty and the all-members value above is used.', 'bnwp')
        . '</p>';

    foreach ($teams as $term) {
        $on = has_term($term->term_id, 'team', $post->ID)
            || has_term(bnwp_former_slug($term), 'team', $post->ID);
        printf(
            '<p style="margin:1.2em 0 .4em;font-weight:600;">%s%s</p>',
            esc_html($term->name),
            $on ? '' : ' <span style="font-weight:400;opacity:.6;">' . esc_html__('(not on this team)', 'bnwp') . '</span>'
        );
        bnwp_field_text(
            __('Role', 'bnwp'),
            '_bnwp_role_' . $term->slug,
            bnwp_get_meta('_bnwp_role_' . $term->slug, $post->ID)
        );
        bnwp_field_text(
            __('Role (English)', 'bnwp'),
            '_bnwp_role_' . $term->slug . '_en',
            bnwp_get_meta('_bnwp_role_' . $term->slug . '_en', $post->ID)
        );
        bnwp_field_text(
            __('Order', 'bnwp'),
            '_bnwp_order_' . $term->slug,
            bnwp_get_meta('_bnwp_order_' . $term->slug, $post->ID),
            'number'
        );
    }
}

function bnwp_post_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');
    bnwp_field_text(__('Author wiki username', 'bnwp'), '_bnwp_user', bnwp_get_meta('_bnwp_user', $post->ID));
}

function bnwp_save_meta($post_id) {
    if (!isset($_POST['bnwp_meta_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bnwp_meta_nonce'])), 'bnwp_save_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $media_keys = bnwp_media_meta_keys();

    foreach (bnwp_meta_keys() as $key) {
        if (!isset($_POST[$key])) {
            continue;
        }
        $raw = wp_unslash($_POST[$key]);

        if (in_array($key, bnwp_list_meta_keys(), true)) {
            $items = is_array($raw) ? $raw : explode(',', (string) $raw);
            $items = array_filter(array_map('sanitize_text_field', array_map('trim', $items)));
            update_post_meta($post_id, $key, implode(', ', array_unique($items)));
            continue;
        }

        if (in_array($key, bnwp_richtext_meta_keys(), true)
            || in_array($key, bnwp_multiline_meta_keys(), true)) {
            $clean = bnwp_meta_sanitizer($key);
            update_post_meta($post_id, $key, $clean($raw));
            continue;
        }

        if ($key === '_bnwp_status') {
            $value = array_key_exists($raw, bnwp_project_statuses()) ? $raw : '';
        } elseif ($key === '_bnwp_language') {
            $value = in_array($raw, bnwp_langs(), true) ? $raw : 'bn';
        } elseif ($key === '_bnwp_email') {
            $value = sanitize_email($raw);
        } elseif (in_array($key, $media_keys, true)) {
            $value = bnwp_sanitize_media_ref($raw);
        } elseif ($key === '_bnwp_wiki') {
            $value = esc_url_raw($raw);
        } else {
            $value = sanitize_text_field($raw);
        }

        update_post_meta($post_id, $key, $value);
    }
}
add_action('save_post', 'bnwp_save_meta');

/** Media Library picker, loaded only on the two edit screens that use it. */
function bnwp_admin_assets($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, array('project', 'persona'), true)) {
        return;
    }

    $uri = get_template_directory_uri();
    wp_enqueue_media();
    wp_enqueue_style('bnwp-admin', $uri . '/assets/css/admin.css', array(), BNWP_VERSION);
    wp_enqueue_script('bnwp-admin', $uri . '/assets/js/admin-media.js', array('jquery'), BNWP_VERSION, true);
    wp_localize_script('bnwp-admin', 'bnwpMedia', array(
        'frameTitle' => __('Choose an image', 'bnwp'),
        'buttonText' => __('Use this image', 'bnwp'),
    ));
}
add_action('admin_enqueue_scripts', 'bnwp_admin_assets');


/**
 * The real WikiConnect mark. Used for the header brand, the footer brand and
 * the rotating hero motif — one image everywhere, no synthetic stand-in.
 */
function bnwp_logo_img($size = 40, $class = '', $loading = 'lazy') {
    // Source mark is 307x297.
    $w = (int) round($size * (307 / 297));
    printf(
        '<img class="brand__logo %s" src="%s" width="%d" height="%d" alt="" loading="%s" decoding="async"%s>',
        esc_attr($class),
        esc_url(get_template_directory_uri() . '/assets/uploads/Bangla_WikiConnect_LOGO.png'),
        $w,
        (int) $size,
        esc_attr($loading),
        $loading === 'eager' ? ' fetchpriority="high"' : ''
    );
}
