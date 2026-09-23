<?php
/**
 * The "Posts" page — a paginated listing of blog posts.
 *
 * This is a normal WP page, so without this template it renders its own
 * (empty) body and looks broken. v1 had the same template; it was lost in
 * the v2 rebuild.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));

$args = array(
    'post_type'      => 'post',
    'posts_per_page' => 10,
    'paged'          => $paged,
    'ignore_sticky_posts' => true,
);
if (bnwp_lang_has_content('post')) {
    $args['meta_query'] = array(bnwp_lang_meta_query());
}
$q = new WP_Query($args);
?>

<div class="pagehead pagehead--sunken">
    <div class="wrap">
        <h1><?php the_title(); ?></h1>
        <?php
        $intro = trim(wp_strip_all_tags(get_the_content()));
        if ($intro !== '') : ?>
            <div class="prose" style="max-width:62ch;"><?php the_content(); ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="section">
    <div class="wrap">
        <?php if ($q->have_posts()) : ?>
            <ul class="postlist">
                <?php while ($q->have_posts()) : $q->the_post(); ?>
                <li class="postlist__item reveal">
                    <a class="postlist__link" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>">
                        <time class="postlist__meta" datetime="<?php echo esc_attr(bnwp_iso_date()); ?>"><?php echo esc_html(bnwp_post_date()); ?></time>
                        <span>
                            <span class="postlist__title"><?php the_title(); ?></span>
                            <?php $ex = get_the_excerpt(); if ($ex) : ?>
                                <span class="postlist__excerpt"><?php echo esc_html(wp_trim_words($ex, 28, '…')); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="postlist__meta"><?php echo esc_html(bnwp_reading_time()); ?></span>
                    </a>
                </li>
                <?php endwhile; ?>
            </ul>

            <?php if ($q->max_num_pages > 1) : ?>
                <nav class="pagination" aria-label="<?php echo esc_attr(bnwp_text('পৃষ্ঠা তালিকা', 'Posts navigation')); ?>">
                    <div class="nav-links">
                        <?php
                        echo paginate_links(array(
                            'total'     => $q->max_num_pages,
                            'current'   => $paged,
                            'mid_size'  => 1,
                            'prev_text' => bnwp_text('পূর্ববর্তী', 'Previous'),
                            'next_text' => bnwp_text('পরবর্তী', 'Next'),
                            'add_args'  => bnwp_is_en() ? array('lang' => 'en') : false,
                        ));
                        ?>
                    </div>
                </nav>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p class="notice"><?php echo esc_html(bnwp_text('কোনো লেখা পাওয়া যায়নি।', 'No posts found.')); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
