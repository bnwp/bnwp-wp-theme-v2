<?php
/**
 * 404.
 */
if (!defined('ABSPATH')) { exit; }

get_header();
?>

<div class="section">
    <div class="wrap wrap--narrow" style="text-align:center;">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/uploads/error_404.svg'); ?>"
             width="180" height="154" alt="" style="margin:0 auto 2rem;" decoding="async">

        <h1><?php echo esc_html(bnwp_text('পাতাটি পাওয়া যায়নি', 'Page not found')); ?></h1>

        <p style="color:var(--ink-soft);font-size:var(--step-1);">
            <?php echo esc_html(bnwp_text(
                'আপনি যে পাতাটি খুঁজছেন সেটি সরে গেছে বা কখনও ছিল না।',
                'The page you are looking for has moved, or never existed.'
            )); ?>
        </p>

        <div style="margin:2rem 0;"><?php get_search_form(); ?></div>

        <div class="hero__actions" style="justify-content:center;">
            <a class="btn btn--primary" href="<?php echo esc_url(bnwp_lang_arg(home_url('/'))); ?>">
                <?php echo esc_html(bnwp_text('প্রচ্ছদে ফিরুন', 'Back to home')); ?>
            </a>
            <?php $pl = get_post_type_archive_link('project'); ?>
            <a class="btn btn--ghost" href="<?php echo esc_url(bnwp_lang_arg($pl ? $pl : home_url('/projects/'))); ?>">
                <?php echo esc_html(bnwp_text('প্রকল্পসমূহ', 'Projects')); ?>
            </a>
        </div>
    </div>
</div>

<?php get_footer(); ?>
