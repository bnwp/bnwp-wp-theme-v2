<?php
/**
 * Site header.
 */
if (!defined('ABSPATH')) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FAF8F4" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#121310" media="(prefers-color-scheme: dark)">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>

<body <?php body_class('site'); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main"><?php echo esc_html(bnwp_text('মূল বিষয়বস্তুতে যান', 'Skip to content')); ?></a>

<header class="site-header">
    <div class="wrap site-header__bar">

        <a class="brand" href="<?php echo esc_url(bnwp_lang_arg(home_url('/'))); ?>" rel="home">
            <?php if (has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <?php bnwp_logo_img(40, '', 'eager'); ?>
            <?php endif; ?>
            <span class="brand__name"><?php echo esc_html(bnwp_site_name()); ?></span>
        </a>

        <nav id="site-nav" class="nav" aria-label="<?php echo esc_attr(bnwp_text('প্রধান মেনু', 'Primary menu')); ?>" hidden>
            <?php bnwp_primary_nav(); ?>
        </nav>

        <?php bnwp_language_switcher(); ?>

        <button type="button"
                class="icon-btn"
                data-theme-toggle
                aria-pressed="false"
                aria-label="<?php echo esc_attr(bnwp_text('আলো/অন্ধকার মোড বদলান', 'Toggle dark mode')); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a6.8 6.8 0 0 0 10.5 10.5Z"/>
            </svg>
        </button>

        <button type="button"
                class="icon-btn nav-toggle"
                data-nav-toggle
                aria-expanded="false"
                aria-controls="site-nav"
                aria-label="<?php echo esc_attr(bnwp_text('মেনু', 'Menu')); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>

    </div>
</header>

<main id="main" class="site__main">
