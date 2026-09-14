<?php
/**
 * BNWP WikiConnect theme functions.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BNWP_THEME_VERSION', '1.4.0');



function bnwp_current_language() {
    if (isset($_GET['lang'])) {
        $requested_language = sanitize_key(wp_unslash($_GET['lang']));
        if (in_array($requested_language, array('bn', 'en'), true)) {
            return $requested_language;
        }
    }

    if (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post) {
            $post_language = sanitize_key(get_post_meta($post->ID, '_bnwp_language', true));
            if (in_array($post_language, array('bn', 'en'), true)) {
                return $post_language;
            }
            if (substr($post->post_name, -3) === '-en') {
                return 'en';
            }
        }
    }

    return 'bn';
}

function bnwp_text($bengali, $english) {
    return bnwp_current_language() === 'en' ? $english : $bengali;
}

function bnwp_site_name() {
    return bnwp_current_language() === 'en' ? 'Bangla WikiConnect' : get_bloginfo('name');
}

function bnwp_document_title_parts($title) {
    if (bnwp_current_language() !== 'en') {
        return $title;
    }

    $site_name = get_bloginfo('name');
    foreach ($title as $part => $value) {
        if ($value === $site_name) {
            $title[$part] = bnwp_site_name();
        }
    }

    return $title;
}
add_filter('document_title_parts', 'bnwp_document_title_parts');

function bnwp_lang_arg($url, $lang = null) {
    $lang = $lang ? $lang : bnwp_current_language();
    if ($lang === 'en') {
        return add_query_arg('lang', 'en', $url);
    }
    return remove_query_arg('lang', $url);
}

function bnwp_get_avatar_placeholder() {
    return get_template_directory_uri() . '/assets/uploads/avatar-placeholder.png';
}

function bnwp_clean_image_url($url, $fallback = '') {
    $url = trim((string) $url);
    if ($url === '' || $url === '#') {
        return $fallback ? $fallback : bnwp_get_avatar_placeholder();
    }
    if (!preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
        return $fallback ? $fallback : bnwp_get_avatar_placeholder();
    }
    return $url;
}

function bnwp_page_url($base_slug, $lang = null) {
    $lang = $lang ? $lang : bnwp_current_language();
    $target_slug = $lang === 'en' ? $base_slug . '-en' : $base_slug;
    $page = get_page_by_path($target_slug, OBJECT, 'page');

    if (!$page && $lang === 'en') {
        $page = get_page_by_path($base_slug, OBJECT, 'page');
    }

    $url = $page ? get_permalink($page) : home_url('/' . trim($base_slug, '/') . '/');
    return bnwp_lang_arg($url, $lang);
}

function bnwp_translation_url($target_lang) {
    $target_lang = $target_lang === 'en' ? 'en' : 'bn';

    if (is_front_page()) {
        return bnwp_lang_arg(home_url('/'), $target_lang);
    }

    if (is_singular()) {
        $post = get_queried_object();
        if ($post && !empty($post->post_name)) {
            $type = get_post_type($post);
            $slug = $post->post_name;
            $target_slug = $target_lang === 'en'
                ? (substr($slug, -3) === '-en' ? $slug : $slug . '-en')
                : preg_replace('/-en$/', '', $slug);

            $match = get_page_by_path($target_slug, OBJECT, $type);
            if ($match) {
                return bnwp_lang_arg(get_permalink($match), $target_lang);
            }

            return bnwp_lang_arg(get_permalink($post), $target_lang);
        }
    }

    if (is_post_type_archive('project')) {
        return bnwp_lang_arg(get_post_type_archive_link('project'), $target_lang);
    }
    if (is_post_type_archive('persona') || is_tax('team')) {
        $base = is_tax('team') ? get_term_link(get_queried_object()) : get_post_type_archive_link('persona');
        return !is_wp_error($base) ? bnwp_lang_arg($base, $target_lang) : bnwp_lang_arg(home_url('/persona/'), $target_lang);
    }
    if (is_home()) {
        return bnwp_page_url('posts', $target_lang);
    }
    if (is_search()) {
        return add_query_arg('s', get_search_query(), bnwp_lang_arg(home_url('/'), $target_lang));
    }

    return bnwp_lang_arg(home_url('/'), $target_lang);
}

function bnwp_language_switcher() {
    $current = bnwp_current_language();
    $target  = $current === 'en' ? 'bn' : 'en';

    $target_label  = $target === 'en' ? 'English' : 'বাংলা';

    ?>
    <div class="border-start border-end" id="langSwitcher">
        <a
            class="btn nav-link px-2"
            href="<?php echo esc_url(bnwp_translation_url($target)); ?>"
            aria-label="<?php echo esc_attr('Switch to ' . $target_label); ?>"
            title="<?php echo esc_attr('Switch to ' . $target_label); ?>"
            hreflang="<?php echo esc_attr($target); ?>"
        >
            <i class="bi bi-translate me-1"></i>
            <span class="d-none d-md-inline">
                <?php echo esc_html($target_label); ?>
            </span>
        </a>
    </div>
    <?php
}

function bnwp_filter_main_query_by_language($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    if ($query->is_post_type_archive(array('project', 'persona')) || $query->is_tax('team') || $query->is_home() || $query->is_search()) {
        $meta_query = (array) $query->get('meta_query');
        $meta_query[] = array(
            'key' => '_bnwp_language',
            'value' => bnwp_current_language(),
            'compare' => '=',
        );
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'bnwp_filter_main_query_by_language');

function bnwp_setup() {
    load_theme_textdomain('bnwp', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('custom-logo', array('height' => 120, 'width' => 360, 'flex-height' => true, 'flex-width' => true));
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'bnwp'),
    ));
}
add_action('after_setup_theme', 'bnwp_setup');

function bnwp_enqueue_assets() {
    wp_enqueue_style('bnwp-bootstrap-mod', get_template_directory_uri() . '/assets/css/bootstrap5.3.mod.css', array(), BNWP_THEME_VERSION);
    wp_enqueue_style('bnwp-bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css', array(), '1.11.3');
    wp_enqueue_style('bnwp-theme-utils', get_template_directory_uri() . '/assets/css/theme.css', array('bnwp-bootstrap-mod'), BNWP_THEME_VERSION);
    wp_enqueue_style('bnwp-main-style', get_template_directory_uri() . '/assets/css/style.css', array('bnwp-theme-utils'), BNWP_THEME_VERSION);
    wp_enqueue_style('bnwp-style', get_stylesheet_uri(), array('bnwp-main-style'), BNWP_THEME_VERSION);

    wp_enqueue_script('bnwp-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array(), '5.3.3', true);
    wp_enqueue_script('bnwp-colormode', get_template_directory_uri() . '/assets/js/colormode.js', array(), BNWP_THEME_VERSION, true);
}
add_action('wp_enqueue_scripts', 'bnwp_enqueue_assets');

/**
 * Load the WordPress media picker only on BNWP content edit screens.
 */
function bnwp_enqueue_admin_media($hook_suffix) {
    if (!in_array($hook_suffix, array('post.php', 'post-new.php'), true)) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, array('project', 'persona'), true)) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style(
        'bnwp-admin',
        get_template_directory_uri() . '/assets/css/admin.css',
        array(),
        BNWP_THEME_VERSION
    );
    wp_enqueue_script(
        'bnwp-admin-media',
        get_template_directory_uri() . '/assets/js/admin-media.js',
        array('jquery'),
        BNWP_THEME_VERSION,
        true
    );
    wp_localize_script('bnwp-admin-media', 'bnwpMedia', array(
        'frameTitle' => __('Choose an image', 'bnwp'),
        'buttonText' => __('Use this image', 'bnwp'),
    ));
}
add_action('admin_enqueue_scripts', 'bnwp_enqueue_admin_media');

function bnwp_register_content_types() {
    register_post_type('project', array(
        'labels' => array(
            'name' => __('Projects', 'bnwp'),
            'singular_name' => __('Project', 'bnwp'),
            'add_new_item' => __('Add New Project', 'bnwp'),
            'edit_item' => __('Edit Project', 'bnwp'),
        ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'projects', 'with_front' => false),
        'menu_icon' => 'dashicons-portfolio',
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions'),
        'show_in_rest' => true,
    ));

    register_post_type('persona', array(
        'labels' => array(
            'name' => __('Team Members', 'bnwp'),
            'singular_name' => __('Team Member', 'bnwp'),
            'add_new_item' => __('Add New Team Member', 'bnwp'),
            'edit_item' => __('Edit Team Member', 'bnwp'),
        ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'persona', 'with_front' => false),
        'menu_icon' => 'dashicons-groups',
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions'),
        'show_in_rest' => true,
    ));

    register_taxonomy('team', array('persona'), array(
        'labels' => array(
            'name' => __('Teams', 'bnwp'),
            'singular_name' => __('Team', 'bnwp'),
        ),
        'public' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => 'teams', 'with_front' => false),
        'show_in_rest' => true,
    ));

    $meta_keys = array(
        '_bnwp_language', '_bnwp_source_file', '_bnwp_logo', '_bnwp_cover', '_bnwp_wiki', '_bnwp_lead',
        '_bnwp_name', '_bnwp_role', '_bnwp_username', '_bnwp_location', '_bnwp_email', '_bnwp_img', '_bnwp_bio', '_bnwp_user'
    );
    foreach ($meta_keys as $key) {
        register_post_meta('', $key, array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => function() { return current_user_can('edit_posts'); },
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

function bnwp_reading_time($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
    $words = preg_split('/\s+/u', trim($content));
    $count = $content ? count($words) : 0;
    return max(1, (int) ceil($count / 200));
}

function bnwp_bengali_numerals($value) {
    return strtr((string) $value, array(
        '0' => '০',
        '1' => '১',
        '2' => '২',
        '3' => '৩',
        '4' => '৪',
        '5' => '৫',
        '6' => '৬',
        '7' => '৭',
        '8' => '৮',
        '9' => '৯',
    ));
}

function bnwp_post_date($post_id = null) {
    $timestamp = get_post_timestamp($post_id ? $post_id : get_the_ID());
    if (!$timestamp) {
        return '';
    }

    if (bnwp_current_language() === 'en') {
        return wp_date('F j, Y', $timestamp);
    }

    $months = array(
        1 => 'জানুয়ারি',
        2 => 'ফেব্রুয়ারি',
        3 => 'মার্চ',
        4 => 'এপ্রিল',
        5 => 'মে',
        6 => 'জুন',
        7 => 'জুলাই',
        8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর',
        10 => 'অক্টোবর',
        11 => 'নভেম্বর',
        12 => 'ডিসেম্বর',
    );

    $day = bnwp_bengali_numerals(wp_date('j', $timestamp));
    $month = $months[(int) wp_date('n', $timestamp)];
    $year = bnwp_bengali_numerals(wp_date('Y', $timestamp));

    return sprintf('%s %s, %s', $day, $month, $year);
}

function bnwp_posts_pagination($args = array()) {
    the_posts_pagination(wp_parse_args($args, array(
        'mid_size' => 2,
        'prev_text' => bnwp_text('পূর্ববর্তী', 'Previous'),
        'next_text' => bnwp_text('পরবর্তী', 'Next'),
        'screen_reader_text' => bnwp_text('লেখার পৃষ্ঠা', 'Posts navigation'),
    )));
}

function bnwp_primary_menu_fallback() {
    $lang = bnwp_current_language();
    $labels = $lang === 'en'
        ? array('home' => 'Home', 'about' => 'About', 'news' => 'Newsroom', 'blog' => 'Blog', 'members' => 'Members', 'projects' => 'Projects')
        : array('home' => 'নীড়', 'about' => 'পরিচিতি', 'news' => 'বার্তাকক্ষ', 'blog' => 'ব্লগ', 'members' => 'সদস্য', 'projects' => 'প্রকল্প');
    $about = bnwp_page_url('about', $lang);
    $news = bnwp_page_url('newsroom', $lang);
    $posts = bnwp_page_url('posts', $lang);
    ?>
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?php echo esc_url(bnwp_lang_arg(home_url('/'), $lang)); ?>"><?php echo esc_html($labels['home']); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo esc_url($about); ?>"><?php echo esc_html($labels['about']); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo esc_url($news); ?>"><?php echo esc_html($labels['news']); ?></a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo esc_url($posts); ?>"><?php echo esc_html($labels['blog']); ?></a></li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?php echo esc_html($labels['members']); ?></a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo esc_url(bnwp_lang_arg(get_post_type_archive_link('persona'), $lang)); ?>"><?php echo $lang === 'en' ? 'All Members' : 'সকল সদস্য'; ?></a></li>
                <li><a class="dropdown-item" href="<?php echo esc_url(bnwp_lang_arg(home_url('/teams/cot/'), $lang)); ?>"><?php echo $lang === 'en' ? 'Core Team' : 'মূল দল'; ?></a></li>
                <li><a class="dropdown-item" href="<?php echo esc_url(bnwp_lang_arg(home_url('/teams/technical/'), $lang)); ?>"><?php echo $lang === 'en' ? 'Technical Team' : 'কারিগরি ও প্রযুক্তি দল'; ?></a></li>
                <li><a class="dropdown-item" href="<?php echo esc_url(bnwp_lang_arg(home_url('/teams/jury/'), $lang)); ?>"><?php echo $lang === 'en' ? 'Jury Team' : 'পর্যালোচক দল'; ?></a></li>
            </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="<?php echo esc_url(bnwp_lang_arg(get_post_type_archive_link('project'), $lang)); ?>"><?php echo esc_html($labels['projects']); ?></a></li>
    </ul>
    <?php
}

function bnwp_add_meta_boxes() {
    add_meta_box('bnwp_project_details', __('Project Details', 'bnwp'), 'bnwp_project_meta_box', 'project', 'normal', 'high');
    add_meta_box('bnwp_persona_details', __('Team Member Details', 'bnwp'), 'bnwp_persona_meta_box', 'persona', 'normal', 'high');
    add_meta_box('bnwp_post_details', __('BNWP Post Details', 'bnwp'), 'bnwp_post_meta_box', 'post', 'side', 'default');
}
add_action('add_meta_boxes', 'bnwp_add_meta_boxes');

function bnwp_input_row($label, $name, $value, $type = 'text') {
    printf(
        '<p><label style="font-weight:600;display:block;margin-bottom:4px;" for="%1$s">%2$s</label><input class="widefat" type="%4$s" id="%1$s" name="%1$s" value="%3$s"></p>',
        esc_attr($name),
        esc_html($label),
        esc_attr($value),
        esc_attr($type)
    );
}

function bnwp_media_input_row($label, $name, $value) {
    $has_image = $value !== '';
    ?>
    <div class="bnwp-media-field">
        <label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label>
        <div class="bnwp-media-controls">
            <input
                class="widefat"
                type="text"
                inputmode="url"
                data-bnwp-media-url
                id="<?php echo esc_attr($name); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
            >
            <button
                type="button"
                class="button bnwp-select-media"
                data-target="<?php echo esc_attr($name); ?>"
            ><?php esc_html_e('Choose image', 'bnwp'); ?></button>
            <button
                type="button"
                class="button-link-delete bnwp-remove-media<?php echo $has_image ? '' : ' is-hidden'; ?>"
                data-target="<?php echo esc_attr($name); ?>"
            ><?php esc_html_e('Clear', 'bnwp'); ?></button>
        </div>
        <p class="description"><?php esc_html_e('Choose from the Media Library or paste an external image URL.', 'bnwp'); ?></p>
        <div class="bnwp-media-preview<?php echo $has_image ? '' : ' is-hidden'; ?>">
            <img src="<?php echo esc_url($value); ?>" alt="">
        </div>
    </div>
    <?php
}

function bnwp_project_meta_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');
    bnwp_media_input_row('Logo', '_bnwp_logo', bnwp_get_meta('_bnwp_logo', $post->ID));
    bnwp_media_input_row('Cover', '_bnwp_cover', bnwp_get_meta('_bnwp_cover', $post->ID));
    bnwp_input_row('Wiki URL', '_bnwp_wiki', bnwp_get_meta('_bnwp_wiki', $post->ID));
    bnwp_input_row('Lead', '_bnwp_lead', bnwp_get_meta('_bnwp_lead', $post->ID));
    bnwp_input_row('Language', '_bnwp_language', bnwp_get_meta('_bnwp_language', $post->ID, 'bn'));
}

function bnwp_persona_meta_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');
    bnwp_input_row('Display Name', '_bnwp_name', bnwp_get_meta('_bnwp_name', $post->ID));
    bnwp_input_row('Role', '_bnwp_role', bnwp_get_meta('_bnwp_role', $post->ID));
    bnwp_input_row('Wiki Username', '_bnwp_username', bnwp_get_meta('_bnwp_username', $post->ID));
    bnwp_input_row('Location', '_bnwp_location', bnwp_get_meta('_bnwp_location', $post->ID));
    bnwp_input_row('Email', '_bnwp_email', bnwp_get_meta('_bnwp_email', $post->ID), 'email');
    bnwp_media_input_row('Profile Image', '_bnwp_img', bnwp_get_meta('_bnwp_img', $post->ID));
    bnwp_input_row('Short Bio', '_bnwp_bio', bnwp_get_meta('_bnwp_bio', $post->ID));
    bnwp_input_row('Language', '_bnwp_language', bnwp_get_meta('_bnwp_language', $post->ID, 'bn'));
}

function bnwp_post_meta_box($post) {
    wp_nonce_field('bnwp_save_meta', 'bnwp_meta_nonce');
    bnwp_input_row('Author Wiki Username', '_bnwp_user', bnwp_get_meta('_bnwp_user', $post->ID));
    bnwp_input_row('Language', '_bnwp_language', bnwp_get_meta('_bnwp_language', $post->ID, 'bn'));
}

function bnwp_save_post_meta($post_id) {
    if (!isset($_POST['bnwp_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bnwp_meta_nonce'])), 'bnwp_save_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    $keys = array('_bnwp_language', '_bnwp_logo', '_bnwp_cover', '_bnwp_wiki', '_bnwp_lead', '_bnwp_name', '_bnwp_role', '_bnwp_username', '_bnwp_location', '_bnwp_email', '_bnwp_img', '_bnwp_bio', '_bnwp_user');
    $url_keys = array('_bnwp_logo', '_bnwp_cover', '_bnwp_wiki', '_bnwp_img');
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $raw_value = wp_unslash($_POST[$key]);
            $value = in_array($key, $url_keys, true)
                ? esc_url_raw($raw_value)
                : sanitize_text_field($raw_value);
            update_post_meta($post_id, $key, $value);
        }
    }
}
add_action('save_post', 'bnwp_save_post_meta');

function bnwp_nav_menu_css_class($classes, $item, $args, $depth) {
    if (isset($args->theme_location) && $args->theme_location === 'primary') {
        $classes[] = 'nav-item';
        if (in_array('menu-item-has-children', $classes, true)) {
            $classes[] = 'dropdown';
        }
    }
    return array_unique($classes);
}
add_filter('nav_menu_css_class', 'bnwp_nav_menu_css_class', 10, 4);

function bnwp_nav_menu_link_attributes($atts, $item, $args, $depth) {
    if (isset($args->theme_location) && $args->theme_location === 'primary') {
        $atts['class'] = trim(($atts['class'] ?? '') . ($depth > 0 ? ' dropdown-item' : ' nav-link'));
        if (in_array('menu-item-has-children', $item->classes, true) && $depth === 0) {
            $atts['class'] .= ' dropdown-toggle';
            $atts['href'] = '#';
            $atts['role'] = 'button';
            $atts['data-bs-toggle'] = 'dropdown';
            $atts['aria-expanded'] = 'false';
        }
        if (bnwp_current_language() === 'en' && !empty($atts['href']) && strpos($atts['href'], home_url('/')) === 0) {
            $atts['href'] = bnwp_lang_arg($atts['href'], 'en');
        }
    }
    return $atts;
}
add_filter('nav_menu_link_attributes', 'bnwp_nav_menu_link_attributes', 10, 4);

function bnwp_nav_menu_submenu_css_class($classes, $args, $depth) {
    if (isset($args->theme_location) && $args->theme_location === 'primary') {
        $classes[] = 'dropdown-menu';
    }
    return array_unique($classes);
}
add_filter('nav_menu_submenu_css_class', 'bnwp_nav_menu_submenu_css_class', 10, 3);
