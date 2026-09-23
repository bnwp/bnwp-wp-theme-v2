<?php
/**
 * Single post.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

while (have_posts()) : the_post();
    $author_wiki = bnwp_get_meta('_bnwp_user');
?>

<article>
    <div class="pagehead pagehead--sunken">
        <div class="wrap wrap--narrow">
            <nav class="breadcrumb" aria-label="<?php echo esc_attr(bnwp_text('ব্রেডক্রাম্ব', 'Breadcrumb')); ?>">
                <a href="<?php echo esc_url(bnwp_lang_arg(home_url('/'))); ?>"><?php echo esc_html(bnwp_text('প্রচ্ছদ', 'Home')); ?></a>
                <span>/</span>
                <a href="<?php echo esc_url(bnwp_page_url('posts')); ?>"><?php echo esc_html(bnwp_text('পোস্টসমূহ', 'Posts')); ?></a>
            </nav>

            <h1><?php the_title(); ?></h1>

            <div class="meta-row">
                <time datetime="<?php echo esc_attr(bnwp_iso_date()); ?>"><?php echo esc_html(bnwp_post_date()); ?></time>
                <span aria-hidden="true">·</span>
                <span><?php echo esc_html(bnwp_reading_time()); ?></span>
                <?php if ($author_wiki) : ?>
                    <span aria-hidden="true">·</span>
                    <span><?php echo esc_html(bnwp_text('লেখক', 'By')); ?>
                        <a href="<?php echo esc_url('https://meta.wikimedia.org/wiki/User:' . rawurlencode($author_wiki)); ?>">@<?php echo esc_html($author_wiki); ?></a>
                    </span>
                <?php endif; ?>
                <?php $cats = get_the_category_list(', '); if ($cats) : ?>
                    <span aria-hidden="true">·</span><span><?php echo wp_kses_post($cats); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="wrap wrap--narrow">
            <?php if (has_post_thumbnail()) : ?>
                <figure style="margin:0 0 2rem;">
                    <?php the_post_thumbnail('large', array('style' => 'border-radius:var(--radius);width:100%;height:auto;', 'loading' => 'eager')); ?>
                </figure>
            <?php endif; ?>

            <div class="prose">
                <?php the_content(); ?>
            </div>

            <?php
            wp_link_pages(array(
                'before' => '<nav class="pagination"><div class="nav-links">',
                'after'  => '</div></nav>',
            ));

            $tags = get_the_tag_list('<p style="margin-top:2rem;display:flex;gap:.5rem;flex-wrap:wrap;">', ' ', '</p>');
            if ($tags) {
                echo wp_kses_post($tags);
            }
            ?>

            <nav class="pagination" aria-label="<?php echo esc_attr(bnwp_text('লেখা নেভিগেশন', 'Post navigation')); ?>">
                <div class="nav-links">
                    <?php
                    previous_post_link('%link', '<span class="page-numbers">&larr; %title</span>');
                    next_post_link('%link', '<span class="page-numbers">%title &rarr;</span>');
                    ?>
                </div>
            </nav>

            <?php
            if (comments_open() || get_comments_number()) {
                comments_template();
            }
            ?>
        </div>
    </div>
</article>

<?php
endwhile;
get_footer();
