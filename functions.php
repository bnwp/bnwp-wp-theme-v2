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
        'primary'    => __('Primary Menu (Bengali)', 'bnwp'),
        'primary_en' => __('Primary Menu (English)', 'bnwp'),
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
    if (bnwp_is_en() && isset($title['site']) && $title['site'] === get_bloginfo('name')) {
        $title['site'] = bnwp_site_name();
    }
    return $title;
}
add_filter('document_title_parts', 'bnwp_document_title_parts');

/** Append/strip ?lang=en on a URL. */
function bnwp_lang_arg($url, $lang = null) {
    $lang = $lang ? $lang : bnwp_current_language();
    return $lang === 'en' ? add_query_arg('lang', 'en', $url) : remove_query_arg('lang', $url);
}

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

    // "/about/" -> home_url('/about/'). Leaves "//host", "http(s)://" and "#" alone.
    if (strlen($href) > 1 && $href[0] === '/' && $href[1] !== '/') {
        $href = home_url($href);
    }

    if (bnwp_is_en() && strpos($href, home_url()) === 0) {
        $href = add_query_arg('lang', 'en', $href);
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

        $size = array((int) $a['w'], (int) ($a['h'] ? $a['h'] : $a['w']));
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
 * 4. Content types  (identical keys to v1 — do not rename)
 * ---------------------------------------------------------------------- */

/**
 * Every meta key the theme owns. `_bnwp_status` is new in v2; everything
 * else is inherited from v1 and must keep its exact name.
 */
function bnwp_meta_keys() {
    return array(
        '_bnwp_language', '_bnwp_source_file', '_bnwp_logo', '_bnwp_cover', '_bnwp_wiki',
        '_bnwp_lead', '_bnwp_status', '_bnwp_name', '_bnwp_role', '_bnwp_username',
        '_bnwp_location', '_bnwp_email', '_bnwp_img', '_bnwp_bio', '_bnwp_user',
        '_bnwp_organisers', '_bnwp_jury', '_bnwp_link',
        // the English half of each record
        '_bnwp_title_en', '_bnwp_body_en', '_bnwp_excerpt_en',
        '_bnwp_lead_en', '_bnwp_role_en', '_bnwp_bio_en', '_bnwp_location_en',
    );
}

/** Meta holding rich text, which must keep its markup through sanitising. */
function bnwp_richtext_meta_keys() {
    return array('_bnwp_body_en');
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
    return array('_bnwp_organisers', '_bnwp_jury');
}

/**
 * The people named on a project, looked up by wiki username.
 *
 * Usernames are stored rather than post IDs because the Bengali and English
 * records for one person are two different posts — the username is the only
 * identifier shared by both, so one setting serves either language.
 */
function bnwp_personas_by_usernames($csv, $limit = 12) {
    $names = array_values(array_filter(array_map('trim', explode(',', (string) $csv))));
    if (!$names) {
        return null;
    }

    $q = new WP_Query(array(
        'post_type'      => 'persona',
        'posts_per_page' => $limit,
        'no_found_rows'  => true,
        'orderby'        => 'post__in',
        'meta_query'     => array(
            array('key' => '_bnwp_username', 'value' => $names, 'compare' => 'IN'),
        ),
    ));

    return $q->have_posts() ? $q : null;
}

/** A compact list of people, used for the organiser and jury panels. */
function bnwp_person_rows($query) {
    if (!$query) {
        return;
    }
    echo '<div class="peoplelist">';
    while ($query->have_posts()) {
        $query->the_post();
        $username = bnwp_get_meta('_bnwp_username');
        $role     = bnwp_get_meta('_bnwp_role');
        $external = bnwp_person_is_external();

        printf(
            '<a class="peoplelist__row" href="%s"%s>',
            esc_url(bnwp_person_url()),
            $external ? ' rel="noopener"' : ''
        );
        bnwp_image(bnwp_get_meta('_bnwp_img'), array(
            'w' => 120, 'h' => 120, 'alt' => '', 'class' => 'peoplelist__avatar', 'fit' => 'cover',
        ));
        echo '<span class="peoplelist__text">';
        printf('<span class="peoplelist__name">%s</span>', esc_html(get_the_title()));
        if ($role !== '') {
            printf('<span class="peoplelist__role">%s</span>', esc_html($role));
        } elseif ($username !== '') {
            printf('<span class="peoplelist__role">@%s</span>', esc_html($username));
        }
        echo '</span>';
        if ($external) {
            bnwp_external_mark();
        }
        echo '</a>';
    }
    echo '</div>';
    wp_reset_postdata();
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

/** "English name" field on the team taxonomy. */
function bnwp_team_name_en_field($term) {
    $value = is_object($term) ? get_term_meta($term->term_id, '_bnwp_name_en', true) : '';
    if (is_object($term)) {
        echo '<tr class="form-field"><th scope="row"><label for="bnwp_name_en">' . esc_html__('English name', 'bnwp') . '</label></th><td>';
    } else {
        echo '<div class="form-field"><label for="bnwp_name_en">' . esc_html__('English name', 'bnwp') . '</label>';
    }
    printf(
        '<input type="text" name="_bnwp_name_en" id="bnwp_name_en" value="%s"><p class="description">%s</p>',
        esc_attr($value),
        esc_html__('Shown instead of the Bengali name when the site is viewed in English.', 'bnwp')
    );
    echo is_object($term) ? '</td></tr>' : '</div>';
}
add_action('team_add_form_fields', 'bnwp_team_name_en_field');
add_action('team_edit_form_fields', 'bnwp_team_name_en_field');

function bnwp_save_team_name_en($term_id) {
    if (!current_user_can('manage_categories')) {
        return;
    }
    if (isset($_POST['_bnwp_name_en'])) {
        update_term_meta($term_id, '_bnwp_name_en', sanitize_text_field(wp_unslash($_POST['_bnwp_name_en'])));
    }
}
add_action('created_team', 'bnwp_save_team_name_en');
add_action('edited_team', 'bnwp_save_team_name_en');

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
        'supports'     => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions'),
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
            'sanitize_callback' => 'sanitize_text_field',
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
 * and only add what it does not do: hreflang for the ?lang=en twin.
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

function bnwp_og_image() {
    if (is_singular()) {
        $cover = bnwp_get_meta('_bnwp_cover');
        if ($cover === '') {
            $cover = bnwp_get_meta('_bnwp_logo');
        }
        if ($cover === '') {
            $cover = bnwp_get_meta('_bnwp_img');
        }
        if ($cover !== '') {
            return bnwp_img_url($cover, 1200);
        }
        if (has_post_thumbnail()) {
            return get_the_post_thumbnail_url(get_the_ID(), 'full');
        }
    }
    return get_template_directory_uri() . '/assets/uploads/Bangla_WikiConnect_LOGO.png';
}

function bnwp_seo_head() {
    $canonical = bnwp_translation_url(bnwp_current_language());

    // hreflang — always ours, Yoast does not know about the ?lang= twin.
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
        $graph[] = array(
            '@type'            => 'Article',
            'headline'         => wp_strip_all_tags(get_the_title()),
            'datePublished'    => bnwp_iso_date(),
            'dateModified'     => get_the_modified_date('c'),
            'inLanguage'       => bnwp_html_lang(),
            'description'      => bnwp_meta_description(),
            'image'            => bnwp_og_image(),
            'mainEntityOfPage' => get_permalink(),
            'publisher'        => array('@id' => home_url('/#organization')),
        );
    }

    if (is_singular('project')) {
        $graph[] = array(
            '@type'       => 'CreativeWork',
            'name'        => wp_strip_all_tags(get_the_title()),
            'description' => bnwp_meta_description(),
            'inLanguage'  => bnwp_html_lang(),
            'image'       => bnwp_og_image(),
            'url'         => get_permalink(),
            'creator'     => array('@id' => home_url('/#organization')),
        );
    }

    if (is_singular('persona')) {
        $graph[] = array(
            '@type'          => 'Person',
            'name'           => wp_strip_all_tags(get_the_title()),
            'alternateName'  => bnwp_get_meta('_bnwp_username'),
            'description'    => bnwp_get_meta('_bnwp_bio'),
            'jobTitle'       => bnwp_get_meta('_bnwp_role'),
            'image'          => bnwp_og_image(),
            'url'            => get_permalink(),
            'memberOf'       => array('@id' => home_url('/#organization')),
        );
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
 * The assigned WP menu is authored in one language. Using it for both views
 * left the English site with a Bengali menu, so English prefers its own
 * menu location and otherwise falls back to the theme's English labels.
 */
function bnwp_primary_nav() {
    $location = bnwp_is_en() ? 'primary_en' : 'primary';

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
    );
    $key = strtolower(trim($key));
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
 * The English half of the record.
 *
 * The Bengali side stays exactly where WordPress puts it — the normal title
 * box and editor. This panel holds the English equivalents. Anything left
 * blank falls back to the Bengali, so a partial translation is safe.
 */
function bnwp_english_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');

    $has_body = in_array($post->post_type, array('post', 'page', 'project'), true);
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

    <?php if ($has_body) : ?>
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
    <?php endif; ?>

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

/** Every distinct person on the site, keyed by wiki username. */
function bnwp_persona_choices() {
    $people = get_posts(array(
        'post_type'      => 'persona',
        'posts_per_page' => -1,
        'post_status'    => array('publish', 'draft'),
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));

    $choices = array();
    foreach ($people as $person) {
        $username = get_post_meta($person->ID, '_bnwp_username', true);
        if ($username === '' || isset($choices[$username])) {
            continue; // one entry per person, not one per language record
        }
        $choices[$username] = $person->post_title . ' (@' . $username . ')';
    }
    return $choices;
}

function bnwp_field_people($label, $name, $value, $help) {
    $selected = array_filter(array_map('trim', explode(',', (string) $value)));
    $choices  = bnwp_persona_choices();
    ?>
    <div class="bnwp-field">
        <label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label>
        <select class="widefat" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>[]"
                multiple size="<?php echo esc_attr(min(10, max(4, count($choices)))); ?>">
            <?php foreach ($choices as $username => $text) : ?>
                <option value="<?php echo esc_attr($username); ?>"
                    <?php echo in_array($username, $selected, true) ? 'selected' : ''; ?>>
                    <?php echo esc_html($text); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php echo esc_html($help); ?></p>
    </div>
    <?php
}

function bnwp_project_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');
    bnwp_field_media(__('Logo', 'bnwp'), '_bnwp_logo', bnwp_get_meta('_bnwp_logo', $post->ID));
    bnwp_field_media(__('Cover image', 'bnwp'), '_bnwp_cover', bnwp_get_meta('_bnwp_cover', $post->ID));
    bnwp_field_text(__('Wiki URL', 'bnwp'), '_bnwp_wiki', bnwp_get_meta('_bnwp_wiki', $post->ID), 'url');
    bnwp_field_text(__('Lead / summary', 'bnwp'), '_bnwp_lead', bnwp_get_meta('_bnwp_lead', $post->ID));
    bnwp_field_select(__('Status', 'bnwp'), '_bnwp_status', bnwp_get_meta('_bnwp_status', $post->ID), bnwp_project_statuses());

    bnwp_field_people(
        __('Organisers', 'bnwp'),
        '_bnwp_organisers',
        bnwp_get_meta('_bnwp_organisers', $post->ID),
        __('Hold Ctrl (or Cmd) to select several. Chosen by wiki username, so the same selection works for the Bengali and English versions of this project.', 'bnwp')
    );
    bnwp_field_people(
        __('Jury / reviewers', 'bnwp'),
        '_bnwp_jury',
        bnwp_get_meta('_bnwp_jury', $post->ID),
        __('Leave empty to fall back to everyone in the Jury team.', 'bnwp')
    );
}

function bnwp_persona_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');
    bnwp_field_text(__('Display name', 'bnwp'), '_bnwp_name', bnwp_get_meta('_bnwp_name', $post->ID));
    bnwp_field_text(__('Role', 'bnwp'), '_bnwp_role', bnwp_get_meta('_bnwp_role', $post->ID));
    bnwp_field_text(__('Wiki username', 'bnwp'), '_bnwp_username', bnwp_get_meta('_bnwp_username', $post->ID));
    bnwp_field_text(__('Location', 'bnwp'), '_bnwp_location', bnwp_get_meta('_bnwp_location', $post->ID));
    bnwp_field_text(__('Email', 'bnwp'), '_bnwp_email', bnwp_get_meta('_bnwp_email', $post->ID), 'email');
    bnwp_field_media(__('Profile image', 'bnwp'), '_bnwp_img', bnwp_get_meta('_bnwp_img', $post->ID));
    bnwp_field_text(__('Short bio', 'bnwp'), '_bnwp_bio', bnwp_get_meta('_bnwp_bio', $post->ID));
    bnwp_field_text(__('External profile URL', 'bnwp'), '_bnwp_link', bnwp_get_meta('_bnwp_link', $post->ID), 'url');
    echo '<p class="description" style="margin-top:-8px;">'
        . esc_html__('For guest or external jurors who have no page here: their cards link straight to this address instead of to a local profile.', 'bnwp')
        . '</p>';
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

    $url_keys = array('_bnwp_logo', '_bnwp_cover', '_bnwp_wiki', '_bnwp_img');

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

        if (in_array($key, bnwp_richtext_meta_keys(), true)) {
            // the English body keeps its markup, filtered the same way a post is
            update_post_meta($post_id, $key, wp_kses_post($raw));
            continue;
        }

        if ($key === '_bnwp_status') {
            $value = array_key_exists($raw, bnwp_project_statuses()) ? $raw : '';
        } elseif ($key === '_bnwp_language') {
            $value = in_array($raw, bnwp_langs(), true) ? $raw : 'bn';
        } elseif ($key === '_bnwp_excerpt_en') {
            $value = sanitize_textarea_field($raw);
        } elseif ($key === '_bnwp_email') {
            $value = sanitize_email($raw);
        } elseif (in_array($key, $url_keys, true)) {
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
