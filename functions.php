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

    if (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post) {
            $meta = sanitize_key(get_post_meta($post->ID, '_bnwp_language', true));
            if (in_array($meta, bnwp_langs(), true)) {
                return $cached = $meta;
            }
            if (substr($post->post_name, -3) === '-en') {
                return $cached = 'en';
            }
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

/** Resolve a page by base slug, preferring the "-en" twin in English. */
function bnwp_page_url($base_slug, $lang = null) {
    $lang = $lang ? $lang : bnwp_current_language();
    $slug = $lang === 'en' ? $base_slug . '-en' : $base_slug;
    $page = get_page_by_path($slug, OBJECT, 'page');

    if (!$page && $lang === 'en') {
        $page = get_page_by_path($base_slug, OBJECT, 'page');
    }

    $url = $page ? get_permalink($page) : home_url('/' . trim($base_slug, '/') . '/');
    return bnwp_lang_arg($url, $lang);
}

/** The equivalent of the current view in the other language. */
function bnwp_translation_url($target) {
    $target = $target === 'en' ? 'en' : 'bn';

    if (is_front_page()) {
        return bnwp_lang_arg(home_url('/'), $target);
    }

    if (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post && $post->post_name !== '') {
            $slug = $post->post_name;
            $twin = $target === 'en'
                ? (substr($slug, -3) === '-en' ? $slug : $slug . '-en')
                : preg_replace('/-en$/', '', $slug);

            $match = get_page_by_path($twin, OBJECT, get_post_type($post));
            return bnwp_lang_arg(get_permalink($match ? $match : $post), $target);
        }
    }

    if (is_post_type_archive('project')) {
        return bnwp_lang_arg(get_post_type_archive_link('project'), $target);
    }

    if (is_tax('team')) {
        $link = get_term_link(get_queried_object());
        return bnwp_lang_arg(is_wp_error($link) ? home_url('/persona/') : $link, $target);
    }

    if (is_post_type_archive('persona')) {
        return bnwp_lang_arg(get_post_type_archive_link('persona'), $target);
    }

    if (is_home()) {
        return bnwp_page_url('posts', $target);
    }

    if (is_search()) {
        return add_query_arg('s', get_search_query(), bnwp_lang_arg(home_url('/'), $target));
    }

    return bnwp_lang_arg(home_url('/'), $target);
}

/** The meta clause that limits a listing to the current language. */
function bnwp_lang_meta_query($lang = null) {
    $lang = $lang ? $lang : bnwp_current_language();
    return array(
        'relation' => 'OR',
        array('key' => '_bnwp_language', 'value' => $lang, 'compare' => '='),
        array('key' => '_bnwp_language', 'compare' => 'NOT EXISTS'),
    );
}

/**
 * Is there any content of this post type in the current language?
 *
 * Content is authored as separate Bengali and English records. Until the
 * English twins exist, filtering strictly by language empties the English
 * site — no projects, no team. So the filter only applies where there is
 * something to show; otherwise the reader sees the Bengali records rather
 * than a blank page.
 */
function bnwp_lang_has_content($post_type, $lang = null) {
    static $cache = array();

    $lang = $lang ? $lang : bnwp_current_language();
    $key  = $post_type . '|' . $lang;

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    // Not the main query, so bnwp_filter_main_query() ignores it — no recursion.
    $probe = new WP_Query(array(
        'post_type'              => $post_type,
        'post_status'            => 'publish',
        'posts_per_page'         => 1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'meta_query'             => array(
            array('key' => '_bnwp_language', 'value' => $lang, 'compare' => '='),
        ),
    ));

    return $cache[$key] = !empty($probe->posts);
}

/** Restrict main-query listings to the current language, where that language has content. */
function bnwp_filter_main_query($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->is_post_type_archive('project')) {
        $type = 'project';
    } elseif ($query->is_post_type_archive('persona') || $query->is_tax('team')) {
        $type = 'persona';
    } elseif ($query->is_home()) {
        $type = 'post';
    } elseif ($query->is_search()) {
        $type = null; // search spans everything; always filter
    } else {
        return;
    }

    if ($type !== null && !bnwp_lang_has_content($type)) {
        return;
    }

    $meta_query   = (array) $query->get('meta_query');
    $meta_query[] = bnwp_lang_meta_query();
    $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'bnwp_filter_main_query');

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
 * Rewrite a Wikimedia Commons file URL to a width-constrained thumbnail.
 * Returns other URLs untouched.
 */
function bnwp_commons_thumb($url, $width = 480) {
    $width = max(64, (int) $width);

    if (strpos($url, 'upload.wikimedia.org') === false) {
        return $url;
    }

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
function bnwp_img_url($url, $width = 480, $fallback = '') {
    $url = trim((string) $url);

    if ($url === '' || $url === '#') {
        return $fallback !== '' ? $fallback : bnwp_avatar_placeholder();
    }
    if (!preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
        return $fallback !== '' ? $fallback : bnwp_avatar_placeholder();
    }

    return bnwp_commons_thumb($url, $width);
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

    $src = bnwp_img_url($url, $a['w'], $a['fallback']);

    // 1x / 2x for Commons-hosted files, which can be re-thumbed at any width.
    $srcset = '';
    if (strpos($src, 'upload.wikimedia.org') !== false) {
        $x2 = bnwp_commons_thumb($url, $a['w'] * 2);
        if ($x2 !== $src) {
            $srcset = esc_url($src) . ' 1x, ' . esc_url($x2) . ' 2x';
        }
    }

    printf(
        '<img src="%1$s"%2$s width="%3$d"%4$s alt="%5$s" loading="%6$s" decoding="async"%7$s style="object-fit:%8$s">',
        esc_url($src),
        $srcset ? ' srcset="' . $srcset . '"' : '',
        (int) $a['w'],
        $a['h'] ? ' height="' . (int) $a['h'] . '"' : '',
        esc_attr($a['alt']),
        esc_attr($a['loading']),
        $a['class'] ? ' class="' . esc_attr($a['class']) . '"' : '',
        esc_attr($a['fit'])
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
    );
}

function bnwp_project_statuses() {
    return array(
        'ongoing'   => bnwp_text('চলমান', 'Ongoing'),
        'upcoming'  => bnwp_text('শীঘ্রই', 'Upcoming'),
        'completed' => bnwp_text('সমাপ্ত', 'Completed'),
    );
}

function bnwp_project_status_label($key) {
    $all = bnwp_project_statuses();
    return isset($all[$key]) ? $all[$key] : '';
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

/** Organization + Article structured data. */
function bnwp_schema() {
    $graph = array();

    $org = array(
        '@type'  => 'Organization',
        '@id'    => home_url('/#organization'),
        'name'   => bnwp_site_name(),
        'url'    => home_url('/'),
        'logo'   => get_template_directory_uri() . '/assets/uploads/Bangla_WikiConnect_LOGO.png',
        'sameAs' => array('https://meta.wikimedia.org/wiki/Bangla_WikiConnect'),
    );
    $graph[] = $org;

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
        '১৬ লক্ষ+ | 1.6M+ | শব্দ যোগ হয়েছে | words added',
        '2000+ | নিবন্ধ তৈরি | articles created',
        '100+ | চিত্র আপলোড | images uploaded',
        '20+ | স্বেচ্ছাসেবী আয়োজক | volunteer organisers',
        '2 | কর্মশালা | workshops',
        '2 | টিউটোরিয়াল | tutorials',
    ));
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

        if (count($parts) >= 4) {
            $value = $pick($parts[0], $parts[1]);
            $label = $pick($parts[2], $parts[3]);
        } else {
            $value = isset($parts[0]) ? $parts[0] : '';
            $label = $pick(
                isset($parts[1]) ? $parts[1] : '',
                isset($parts[2]) ? $parts[2] : ''
            );
        }

        if ($value === '') {
            continue;
        }

        // "2000+" -> count up to 2000 with a "+" suffix. Anything else is shown as typed.
        $count = null;
        $suffix = '';
        if (preg_match('/^(\d+)\s*(\+?)$/', $value, $m)) {
            $count  = (int) $m[1];
            $suffix = $m[2];
            $value  = bnwp_num(number_format_i18n($count)) . $suffix;
        }

        $rows[] = array('value' => $value, 'count' => $count, 'suffix' => $suffix, 'label' => $label);
    }

    return $rows;
}

function bnwp_customize($wp_customize) {
    $wp_customize->add_section('bnwp_impact', array(
        'title'       => __('Impact numbers', 'bnwp'),
        'priority'    => 30,
        'description' => __('One row per line.<br><br><strong>value | Bengali label | English label</strong><br>or, when the value itself differs by language:<br><strong>Bengali value | English value | Bengali label | English label</strong><br><br>A plain number such as 2000+ counts up when it scrolls into view and its digits are localised automatically. Anything else is shown exactly as typed.', 'bnwp'),
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
}
add_action('customize_register', 'bnwp_customize');


/* -------------------------------------------------------------------------
 * 10. Admin — custom fields for projects, members and posts
 * ---------------------------------------------------------------------- */

function bnwp_add_meta_boxes() {
    add_meta_box('bnwp_project', __('Project Details', 'bnwp'), 'bnwp_project_box', 'project', 'normal', 'high');
    add_meta_box('bnwp_persona', __('Team Member Details', 'bnwp'), 'bnwp_persona_box', 'persona', 'normal', 'high');
    add_meta_box('bnwp_post', __('BNWP Post Details', 'bnwp'), 'bnwp_post_box', 'post', 'side', 'default');
}
add_action('add_meta_boxes', 'bnwp_add_meta_boxes');

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
        <p class="description"><?php esc_html_e('Media Library image, or paste a Wikimedia Commons URL — it is resized to a thumbnail automatically.', 'bnwp'); ?></p>
        <div class="bnwp-media-preview<?php echo $has ? '' : ' is-hidden'; ?>">
            <img src="<?php echo esc_url($value); ?>" alt="">
        </div>
    </div>
    <?php
}

function bnwp_field_language($post_id) {
    bnwp_field_select(
        __('Language', 'bnwp'),
        '_bnwp_language',
        bnwp_get_meta('_bnwp_language', $post_id, 'bn'),
        array('bn' => 'বাংলা (bn)', 'en' => 'English (en)')
    );
}

function bnwp_project_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');
    bnwp_field_media(__('Logo', 'bnwp'), '_bnwp_logo', bnwp_get_meta('_bnwp_logo', $post->ID));
    bnwp_field_media(__('Cover image', 'bnwp'), '_bnwp_cover', bnwp_get_meta('_bnwp_cover', $post->ID));
    bnwp_field_text(__('Wiki URL', 'bnwp'), '_bnwp_wiki', bnwp_get_meta('_bnwp_wiki', $post->ID), 'url');
    bnwp_field_text(__('Lead / summary', 'bnwp'), '_bnwp_lead', bnwp_get_meta('_bnwp_lead', $post->ID));
    bnwp_field_select(__('Status', 'bnwp'), '_bnwp_status', bnwp_get_meta('_bnwp_status', $post->ID), bnwp_project_statuses());
    bnwp_field_language($post->ID);
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
    bnwp_field_language($post->ID);
}

function bnwp_post_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');
    bnwp_field_text(__('Author wiki username', 'bnwp'), '_bnwp_user', bnwp_get_meta('_bnwp_user', $post->ID));
    bnwp_field_language($post->ID);
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

        if ($key === '_bnwp_status') {
            $value = array_key_exists($raw, bnwp_project_statuses()) ? $raw : '';
        } elseif ($key === '_bnwp_language') {
            $value = in_array($raw, bnwp_langs(), true) ? $raw : 'bn';
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
