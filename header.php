<?php
if (!defined('ABSPATH')) { exit; }
?><!DOCTYPE html>
<html lang="<?php echo esc_attr(bnwp_current_language()); ?>" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo esc_attr(get_bloginfo('description')); ?>">
    <link rel="canonical" href="<?php echo esc_url(bnwp_translation_url(bnwp_current_language())); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class('d-flex flex-column min-vh-100'); ?>>
<?php wp_body_open(); ?>
<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v22.0"></script>
<header>
    <nav class="navbar navbar-expand-lg">
        <div class="container py-1 py-sm-2 border-bottom">
            <a class="navbar-brand bnwp-header-brand me-4" href="<?php echo esc_url(bnwp_lang_arg(home_url('/'))); ?>">
                <img
                    class="bnwp-header-logo"
                    src="<?php echo esc_url(get_template_directory_uri() . '/assets/uploads/Bangla_WikiConnect_Logo_small.png'); ?>"
                    alt="<?php echo esc_attr(bnwp_site_name()); ?>"
                    loading="lazy"
                >

                <span class="bnwp-header-title">
                    <?php echo esc_html(bnwp_site_name()); ?>
                </span>
            </a>
                        <div class="d-flex row-center" id="accessibilityMenu">
                <?php bnwp_language_switcher(); ?>
                    <div id="colorModeToogler">
                        <button
                            type="button"
                            class="btn nav-link px-2"
                            id="colorModeTooglerBtn"
                            aria-label="Toggle dark mode"
                        >
                            <i id="theme-icon-active" class="bi-sun"></i>
                        </button>
                    </div>
                <div id="LocalNavigationToogleIcon">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#LocalNavigationMenu" aria-controls="LocalNavigationMenu" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    <div class="navbar navbar-expand-lg">
        <div class="container">
            <div class="collapse navbar-collapse py-2" id="LocalNavigationMenu">
                <?php get_search_form(); ?>
                <?php
                if (has_nav_menu('primary') && bnwp_current_language() === 'bn') {
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'container' => false,
                        'menu_class' => 'navbar-nav me-auto mb-2 mb-lg-0',
                        'fallback_cb' => 'bnwp_primary_menu_fallback',
                        'depth' => 2,
                    ));
                } else {
                    bnwp_primary_menu_fallback();
                }
                ?>
            </div>
        </div>
    </div>
    <div class="border-bottom"></div>
</header>
<main class="full-height flex-grow-1">
