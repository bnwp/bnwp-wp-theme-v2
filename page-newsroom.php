<?php
/**
 * The "Newsroom" page.
 *
 * Editorial content from the page body (ongoing work, upcoming, archive),
 * followed by the most recent posts so the page is never bare.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

$args = array('post_type' => 'post', 'posts_per_page' => 6, 'no_found_rows' => true);
if (bnwp_lang_has_content('post')) {
    $args['meta_query'] = array(bnwp_lang_meta_query());
}
$recent = new WP_Query($args);
?>

<div class="pagehead pagehead--sunken">
    <div class="wrap">
        <h1><?php the_title(); ?></h1>
    </div>
</div>

<?php
$body = trim(wp_strip_all_tags(get_the_content()));
if ($body !== '') : ?>
<div class="section">
    <div class="wrap wrap--narrow">
        <div class="prose"><?php the_content(); ?></div>
    </div>
</div>
<?php endif; ?>

<?php if ($recent->have_posts()) : ?>
<div class="section<?php echo $body !== '' ? ' section--sunken' : ''; ?>">
    <div class="wrap">
        <div class="section__head">
            <h2><?php echo esc_html(bnwp_text('সাম্প্রতিক পোস্ট', 'Latest posts')); ?></h2>
            <a class="arrow-link" href="<?php echo esc_url(bnwp_page_url('posts')); ?>">
                <?php echo esc_html(bnwp_text('সব পোস্ট', 'All posts')); ?>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
            </a>
        </div>

        <ul class="postlist">
            <?php while ($recent->have_posts()) : $recent->the_post(); ?>
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
            <?php endwhile; wp_reset_postdata(); ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php get_footer(); ?>
