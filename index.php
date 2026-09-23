<?php
/**
 * Blog index / generic archive fallback.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

if (is_home() && !is_front_page()) {
    $title = single_post_title('', false);
    if ($title === '') {
        $title = bnwp_text('পোস্টসমূহ', 'Posts');
    }
} else {
    $title = get_the_archive_title();
}
?>

<div class="pagehead pagehead--sunken">
    <div class="wrap">
        <h1><?php echo esc_html(wp_strip_all_tags($title)); ?></h1>
        <?php $desc = get_the_archive_description(); if ($desc) : ?>
            <div style="color:var(--ink-soft);max-width:60ch;"><?php echo wp_kses_post($desc); ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="section">
    <div class="wrap">
        <?php if (have_posts()) : ?>
            <ul class="postlist">
                <?php while (have_posts()) : the_post(); ?>
                <li class="postlist__item reveal">
                    <a class="postlist__link" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>">
                        <time class="postlist__meta" datetime="<?php echo esc_attr(bnwp_iso_date()); ?>"><?php echo esc_html(bnwp_post_date()); ?></time>
                        <span>
                            <span class="postlist__title"><?php the_title(); ?></span>
                            <?php $ex = get_the_excerpt(); if ($ex) : ?>
                                <span class="postlist__excerpt"><?php echo esc_html(wp_trim_words($ex, 24, '…')); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="postlist__meta"><?php echo esc_html(bnwp_reading_time()); ?></span>
                    </a>
                </li>
                <?php endwhile; ?>
            </ul>

            <div class="pagination"><?php bnwp_pagination(); ?></div>

        <?php else : ?>
            <p class="notice"><?php echo esc_html(bnwp_text('কোনো লেখা পাওয়া যায়নি।', 'No posts found.')); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
